<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\ChargeKind;
use App\Enums\ChargeStatus;
use App\Enums\CommandType;
use App\Enums\PaymentSource;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Charge;
use App\Models\ClientProject;
use App\Services\Billing\ChargeService;
use App\Services\Operations\Actor;
use App\Services\Operations\CommandNotAllowedException;
use App\Services\Operations\RemoteCommandService;
use App\Services\Operations\SuspensionGuard;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ChargeController extends Controller
{
    public function __construct(protected ChargeService $charges) {}

    public function index(Request $request): View
    {
        $status = ChargeStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('q'));

        return view('admin.gestao.charges.index', [
            'charges' => Charge::with(['project', 'client'])
                ->when($status, fn (Builder $q) => $q->where('status', $status->value))
                ->when($request->integer('project'), fn (Builder $q, $id) => $q->where('client_project_id', $id))
                ->when($search !== '', fn (Builder $q) => $q->where('description', 'like', "%{$search}%"))
                ->orderByRaw("case when status = 'overdue' then 0 when status in ('pending','partially_paid') then 1 else 2 end")
                ->orderBy('due_date')
                ->paginate(25)->withQueryString(),
            'search' => $search,
            'current' => $status?->value ?? 'all',
            'counts' => ['all' => Charge::count()] + Charge::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
        ]);
    }

    public function create(Request $request): View
    {
        $project = ClientProject::find($request->integer('project'));

        return view('admin.gestao.charges.create', [
            'project' => $project,
            'projects' => ClientProject::with('client')->orderBy('name')->get()->mapWithKeys(fn ($p) => [$p->id => "{$p->name} — {$p->client->displayName()}"]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_project_id' => ['required', 'exists:client_projects,id'],
            'kind' => ['required', Rule::enum(ChargeKind::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'string', 'max:20'],
            'due_date' => ['required', 'date'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
        ], [], ['client_project_id' => 'projeto', 'amount' => 'valor', 'due_date' => 'vencimento']);

        $project = ClientProject::findOrFail($data['client_project_id']);
        $amount = Money::toCents($data['amount']);
        if ($amount <= 0) {
            return back()->withErrors(['amount' => 'O valor precisa ser maior que zero.'])->withInput();
        }

        $charge = $this->charges->create($project, [
            'kind' => $data['kind'],
            'description' => $data['description'],
            'amount_cents' => $amount,
            'due_date' => $data['due_date'],
            'contract_id' => $data['contract_id'] ?? $project->activeContract?->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.gestao.charges.show', $charge)->with('status', 'Cobrança emitida. Ela só conta como recebida após a confirmação do pagamento.');
    }

    public function show(Charge $charge, SuspensionGuard $guard): View
    {
        $charge->load(['project.client', 'contract.policy', 'payments.recorder', 'notices']);

        return view('admin.gestao.charges.show', [
            'charge' => $charge,
            'guard' => $charge->status === ChargeStatus::Overdue ? $guard->evaluate($charge) : null,
            'commands' => $charge->project->commands()->where('charge_id', $charge->id)->latest()->get(),
            'attachments' => Attachment::where('attachable_type', Charge::class)->where('attachable_id', $charge->id)->get(),
        ]);
    }

    /**
     * Baixa manual controlada: responsável (usuário logado) + evidência obrigatória.
     */
    public function storePayment(Request $request, Charge $charge): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'string', 'max:20'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'method' => ['required', 'in:pix,boleto,transferencia,cartao,dinheiro,outro'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:5120'],
            'evidence_notes' => ['nullable', 'required_without:evidence', 'string', 'max:1000'],
        ], ['evidence_notes.required_without' => 'Anexe o comprovante ou descreva a evidência (ex.: ID da transação Pix e conta de destino).'], ['amount' => 'valor', 'paid_at' => 'data do pagamento']);

        $path = $request->file('evidence')?->store("payments/{$charge->uuid}", 'local');

        try {
            $this->charges->recordPayment($charge, Money::toCents($data['amount']), Carbon::parse($data['paid_at']), PaymentSource::Manual, $data['method'], null, $path, $data['evidence_notes'] ?? null, $request->user()->id);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['amount' => $exception->getMessage()]);
        }

        return back()->with('status', 'Pagamento registrado.');
    }

    public function refund(Request $request, Charge $charge): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'string'], 'reason' => ['required', 'string', 'max:255']]);

        return $this->attempt(fn () => $this->charges->refund($charge, Money::toCents($data['amount']), $data['reason'], $request->user()->id), 'Estorno registrado.');
    }

    public function cancel(Request $request, Charge $charge): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];

        return $this->attempt(fn () => $this->charges->cancel($charge, $reason), 'Cobrança cancelada.');
    }

    public function dispute(Request $request, Charge $charge): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason'];

        return $this->attempt(fn () => $this->charges->dispute($charge, $reason), 'Contestação registrada: a régua de suspensão está pausada.');
    }

    public function resolveDispute(Request $request, Charge $charge): RedirectResponse
    {
        $resolution = $request->validate(['resolution' => ['required', 'string', 'max:1000']])['resolution'];

        return $this->attempt(fn () => $this->charges->resolveDispute($charge, $resolution), 'Contestação resolvida.');
    }

    public function negotiate(Request $request, Charge $charge): RedirectResponse
    {
        $data = $request->validate(['until' => ['required', 'date', 'after_or_equal:today'], 'notes' => ['required', 'string', 'max:1000']]);

        return $this->attempt(fn () => $this->charges->negotiate($charge, Carbon::parse($data['until']), $data['notes']), 'Negociação registrada.');
    }

    /**
     * Proposta manual de suspensão pela equipe financeira: passa pelo guard e aguarda aprovação de um administrador.
     */
    public function proposeSuspension(Request $request, Charge $charge, RemoteCommandService $commands): RedirectResponse
    {
        try {
            $commands->issue($charge->project, CommandType::Suspend, [
                'reason' => "Inadimplência: {$charge->description} vencida em {$charge->due_date->format('d/m/Y')}",
                'charge_id' => $charge->id,
            ], 'manual-suspend:'.$charge->id.':'.Str::uuid(), Actor::rule('proposta manual de '.$request->user()->name));
        } catch (CommandNotAllowedException $exception) {
            return back()->withErrors(['charge' => $exception->getMessage()]);
        }

        return back()->with('status', 'Suspensão proposta. Ela só será aplicada após aprovação de um administrador.');
    }

    protected function attempt(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['charge' => $exception->getMessage()]);
        }

        return back()->with('status', $message);
    }
}
