<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\ContractStatus;
use App\Enums\ReactivationMode;
use App\Enums\RestrictionMode;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use App\Models\Contract;
use App\Services\Contracts\ContractService;
use App\Support\DefaultContractClauses;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ContractController extends Controller
{
    public function __construct(protected ContractService $contracts) {}

    public function index(Request $request): View
    {
        $status = ContractStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('q'));

        return view('admin.gestao.contracts.index', [
            'contracts' => Contract::with(['client', 'project'])
                ->when($status, fn (Builder $q) => $q->where('status', $status->value))
                ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner->where('number', 'like', "%{$search}%")->orWhere('object', 'like', "%{$search}%")))
                ->latest()->paginate(20)->withQueryString(),
            'search' => $search,
            'current' => $status?->value ?? 'all',
            'counts' => ['all' => Contract::count()] + Contract::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
        ]);
    }

    public function create(ClientProject $project): View
    {
        return view('admin.gestao.contracts.form', ['project' => $project, 'contract' => new Contract([
            'object' => "Desenvolvimento do sistema {$project->name}",
            'setup_fee_cents' => $project->setup_fee_cents,
            'monthly_fee_cents' => $project->monthly_fee_cents,
            'billing_day' => $project->billing_day,
            'starts_at' => now(),
            'payment_method' => 'Pix ou boleto bancário',
            'clauses' => DefaultContractClauses::all(),
            'support_sla' => [
                'urgent' => ['response_hours' => 4, 'resolution_hours' => 24],
                'high' => ['response_hours' => 8, 'resolution_hours' => 48],
                'normal' => ['response_hours' => 24, 'resolution_hours' => 120],
                'low' => ['response_hours' => 48, 'resolution_hours' => 240],
            ],
        ])]);
    }

    public function store(Request $request, ClientProject $project): RedirectResponse
    {
        $contract = $this->contracts->create($project, $this->validated($request), $request->user());

        return redirect()->route('admin.gestao.contracts.show', $contract)->with('status', 'Contrato criado como minuta. Revise a política financeira.');
    }

    public function show(Contract $contract): View
    {
        $contract->load(['client', 'project', 'policy', 'versions.author', 'documents', 'charges']);

        return view('admin.gestao.contracts.show', ['contract' => $contract]);
    }

    public function edit(Contract $contract): View
    {
        return view('admin.gestao.contracts.form', ['project' => $contract->project, 'contract' => $contract]);
    }

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $summary = $request->validate(['change_summary' => ['required', 'string', 'max:255']], [], ['change_summary' => 'resumo da alteração'])['change_summary'];

        try {
            $this->contracts->update($contract, $this->validated($request), $request->user(), $summary);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['contract' => $exception->getMessage()]);
        }

        return redirect()->route('admin.gestao.contracts.show', $contract)->with('status', 'Nova versão do contrato registrada.');
    }

    public function updatePolicy(Request $request, Contract $contract): RedirectResponse
    {
        $data = $request->validate([
            'reminder_days_before' => ['required', 'integer', 'between:0,60'],
            'overdue_notice_interval_days' => ['required', 'integer', 'between:1,60'],
            'grace_days' => ['required', 'integer', 'between:0,365'],
            'pre_suspension_notice_days' => ['required', 'integer', 'between:1,90'],
            'suspension_allowed' => ['boolean'],
            'restriction_mode' => ['required', Rule::enum(RestrictionMode::class)],
            'suspension_requires_approval' => ['boolean'],
            'reactivation_mode' => ['required', Rule::enum(ReactivationMode::class)],
            'contract_clause_reference' => ['nullable', 'required_if:suspension_allowed,1', 'string', 'max:255'],
            'regularization_instructions' => ['nullable', 'string', 'max:2000'],
        ], ['contract_clause_reference.required_if' => 'Informe a cláusula contratual que autoriza a suspensão.']);

        $data['suspension_allowed'] = $request->boolean('suspension_allowed');
        $data['suspension_requires_approval'] = $request->boolean('suspension_requires_approval');

        $this->contracts->updatePolicy($contract, $data);

        return back()->with('status', 'Política financeira atualizada.');
    }

    public function activate(Request $request, Contract $contract): RedirectResponse
    {
        $justification = $request->validate(['justification' => ['required', 'string', 'max:255']], [], ['justification' => 'justificativa'])['justification'];
        $this->contracts->activate($contract, $justification);

        return back()->with('status', 'Contrato ativado.');
    }

    public function end(Request $request, Contract $contract): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:ended,cancelled'], 'reason' => ['required', 'string', 'max:255']]);
        $this->contracts->end($contract, ContractStatus::from($data['status']), $data['reason']);

        return back()->with('status', 'Contrato encerrado.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $rules = [
            'object' => ['required', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:20000'],
            'deliverables' => ['nullable', 'string', 'max:20000'],
            'setup_fee' => ['nullable', 'string', 'max:20'],
            'monthly_fee' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:5000'],
            'billing_day' => ['nullable', 'integer', 'between:1,28'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'renewal_rules' => ['nullable', 'string', 'max:5000'],
            'clauses' => ['nullable', 'array'],
            'sla' => ['nullable', 'array'],
        ];
        foreach (array_keys(Contract::CLAUSES) as $key) {
            $rules["clauses.{$key}"] = ['nullable', 'string', 'max:20000'];
        }
        foreach (TicketPriority::values() as $priority) {
            $rules["sla.{$priority}.response_hours"] = ['nullable', 'integer', 'between:1,720'];
            $rules["sla.{$priority}.resolution_hours"] = ['nullable', 'integer', 'between:1,2160'];
        }

        $data = $request->validate($rules, [], ['object' => 'objeto']);

        $data['setup_fee_cents'] = Money::toCents($data['setup_fee'] ?? null);
        $data['monthly_fee_cents'] = Money::toCents($data['monthly_fee'] ?? null);
        $data['clauses'] = array_filter($data['clauses'] ?? [], fn ($value) => filled($value));
        $data['support_sla'] = collect($data['sla'] ?? [])
            ->filter(fn ($times) => filled($times['response_hours'] ?? null) && filled($times['resolution_hours'] ?? null))
            ->map(fn ($times) => ['response_hours' => (int) $times['response_hours'], 'resolution_hours' => (int) $times['resolution_hours']])
            ->all();
        unset($data['setup_fee'], $data['monthly_fee'], $data['sla']);

        return $data;
    }
}
