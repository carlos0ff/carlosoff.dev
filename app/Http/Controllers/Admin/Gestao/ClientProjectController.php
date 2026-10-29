<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\AuditAction;
use App\Enums\CommandStatus;
use App\Enums\DevelopmentStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\Domain;
use App\Models\ProjectValidation;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientProjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $filters = $request->only(['development', 'financial', 'operational', 'client', 'lead']);

        $projects = ClientProject::query()
            ->with(['client', 'technicalLead', 'latestHealthCheck'])
            ->when($search !== '', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            ->when($filters['development'] ?? null, fn (Builder $q, $v) => $q->where('development_status', $v))
            ->when($filters['financial'] ?? null, fn (Builder $q, $v) => $q->where('financial_status', $v))
            ->when($filters['operational'] ?? null, fn (Builder $q, $v) => $q->where('operational_status', $v))
            ->when($filters['client'] ?? null, fn (Builder $q, $v) => $q->where('client_id', $v))
            ->when($filters['lead'] ?? null, fn (Builder $q, $v) => $q->where('technical_lead_id', $v))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.gestao.projects.index', [
            'projects' => $projects,
            'search' => $search,
            'filters' => $filters,
            'clients' => Client::orderBy('legal_name')->pluck('legal_name', 'id'),
            'leads' => User::where('is_admin', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.gestao.projects.form', [
            'project' => new ClientProject(['client_id' => $request->integer('client') ?: null, 'development_status' => DevelopmentStatus::Planning]),
            'clients' => Client::orderBy('legal_name')->pluck('legal_name', 'id'),
            'leads' => User::where('is_admin', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = ClientProject::create($this->validated($request));
        AuditLog::forProject($project, AuditAction::Create, 'Projeto', $project, $project->name);

        return redirect()->route('admin.gestao.projects.show', $project)->with('status', 'Projeto cadastrado.');
    }

    public function show(ClientProject $project): View
    {
        $project->load(['client.contacts', 'technicalLead', 'contracts.policy', 'domains', 'hostingServices', 'validations', 'scopeRequests', 'attachments', 'latestHealthCheck']);

        return view('admin.gestao.projects.show', [
            'project' => $project,
            'charges' => $project->charges()->latest('due_date')->limit(12)->get(),
            'documents' => $project->documents()->latest()->get(),
            'commands' => $project->commands()->latest()->limit(15)->get(),
            'credentials' => $project->credentials()->latest()->get(),
            'incidents' => $project->incidents()->latest('opened_at')->limit(5)->get(),
            'checks' => $project->healthChecks()->latest('checked_at')->limit(10)->get(),
            'tickets' => $project->tickets()->latest()->limit(5)->get(),
            'history' => AuditLog::where('client_project_id', $project->id)->latest('id')->limit(30)->get(),
            'openOverdue' => $project->charges()->where('status', 'overdue')->get(),
            'documentTypes' => DocumentType::options(),
            'pendingApproval' => $project->commands()->where('status', CommandStatus::PendingApproval->value)->get(),
        ]);
    }

    public function edit(ClientProject $project): View
    {
        return view('admin.gestao.projects.form', [
            'project' => $project,
            'clients' => Client::orderBy('legal_name')->pluck('legal_name', 'id'),
            'leads' => User::where('is_admin', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, ClientProject $project): RedirectResponse
    {
        $data = $this->validated($request, $project);
        $before = $project->development_status;
        $project->fill($data);
        $changed = $project->getDirty();
        $project->save();

        AuditLog::forProject($project, AuditAction::Update, 'Projeto', $project, $project->name, 'Campos: '.implode(', ', array_keys($changed)), ['changes' => array_intersect_key($changed, array_flip(['development_status', 'published_at', 'production_url']))], visibleToClient: $before !== $project->development_status);

        return redirect()->route('admin.gestao.projects.show', $project)->with('status', 'Projeto atualizado.');
    }

    /**
     * Exceção autorizada: enquanto vigente, a régua não propõe suspensão para o projeto.
     */
    public function hold(Request $request, ClientProject $project): RedirectResponse
    {
        $data = $request->validate([
            'suspension_hold_until' => ['nullable', 'date', 'after_or_equal:today'],
            'suspension_hold_reason' => ['required_with:suspension_hold_until', 'nullable', 'string', 'max:255'],
        ], [], ['suspension_hold_until' => 'data limite', 'suspension_hold_reason' => 'motivo']);

        $project->forceFill($data)->save();
        AuditLog::forProject($project, AuditAction::Update, 'Projeto', $project, $project->name,
            $data['suspension_hold_until'] ? "Exceção de suspensão até {$data['suspension_hold_until']}: {$data['suspension_hold_reason']}" : 'Exceção de suspensão removida');

        return back()->with('status', 'Exceção atualizada.');
    }

    public function storeDomain(Request $request, ClientProject $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'registrar' => ['nullable', 'string', 'max:255'],
            'registrant_name' => ['nullable', 'string', 'max:255'],
            'registrant_document' => ['nullable', 'string', 'max:20'],
            'managed_by' => ['required', Rule::in(array_keys(Domain::PARTIES))],
            'payer' => ['required', Rule::in(array_keys(Domain::PARTIES))],
            'registered_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'auto_renew' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['name' => 'domínio']);

        $domain = $project->domains()->create($data + ['client_id' => $project->client_id]);
        AuditLog::forProject($project, AuditAction::Create, 'Domínio', $domain, $domain->name);

        return back()->with('status', 'Domínio registrado.');
    }

    public function storeHosting(Request $request, ClientProject $project): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:255'],
            'plan' => ['nullable', 'string', 'max:255'],
            'resources' => ['nullable', 'string', 'max:2000'],
            'managed_by' => ['required', Rule::in(array_keys(Domain::PARTIES))],
            'payer' => ['required', Rule::in(array_keys(Domain::PARTIES))],
            'monthly_cost' => ['nullable', 'string', 'max:20'],
            'renews_at' => ['nullable', 'date'],
            'limits_and_responsibilities' => ['nullable', 'string', 'max:4000'],
            'backup_policy' => ['nullable', 'string', 'max:4000'],
            'migration_procedure' => ['nullable', 'string', 'max:4000'],
        ], [], ['provider' => 'provedor']);

        $data['monthly_cost_cents'] = Money::toCents($data['monthly_cost'] ?? null);
        unset($data['monthly_cost']);

        $hosting = $project->hostingServices()->create($data + ['client_id' => $project->client_id]);
        AuditLog::forProject($project, AuditAction::Create, 'Hospedagem', $hosting, $hosting->provider);

        return back()->with('status', 'Hospedagem registrada.');
    }

    /**
     * Solicita ao cliente a validação de uma versão. Sem resposta, continua pendente (sem aceite tácito).
     */
    public function storeValidation(Request $request, ClientProject $project, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:60'],
            'environment' => ['required', 'in:staging,production'],
            'environment_url' => ['nullable', 'url', 'max:255'],
            'features_evaluated' => ['required', 'string', 'max:5000'],
            'tests_performed' => ['nullable', 'string', 'max:5000'],
            'known_issues' => ['nullable', 'string', 'max:5000'],
            'evidence' => ['nullable', 'string', 'max:5000'],
        ], [], ['version' => 'versão', 'features_evaluated' => 'funcionalidades avaliadas']);

        $validation = ProjectValidation::create($data + ['client_project_id' => $project->id, 'created_by' => $request->user()->id]);
        $project->forceFill(['development_status' => DevelopmentStatus::AwaitingApproval])->save();

        AuditLog::forProject($project, AuditAction::Create, 'Validação', $validation, "Versão {$validation->version}", 'Enviada ao cliente para validação', visibleToClient: true);
        $notifier->client($project->client, "Validação solicitada: {$project->name} v{$validation->version}", [
            'Uma nova versão está pronta para sua avaliação. Registre a aprovação, a aprovação com ressalvas ou a reprovação no portal.',
        ], 'Validar versão', route('portal.projects.show', $project), 'validacao');

        return back()->with('status', 'Validação solicitada ao cliente.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?ClientProject $project = null): array
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'technical_lead_id' => ['nullable', Rule::exists('users', 'id')->where('is_admin', true)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'technologies' => ['nullable', 'string', 'max:255'],
            'repository_url' => ['nullable', 'url', 'max:255'],
            'primary_domain' => ['nullable', 'string', 'max:255'],
            'staging_url' => ['nullable', 'url', 'max:255'],
            'production_url' => ['nullable', 'url', 'max:255'],
            'started_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'development_status' => ['required', Rule::enum(DevelopmentStatus::class)],
            'plan_name' => ['nullable', 'string', 'max:255'],
            'setup_fee' => ['nullable', 'string', 'max:20'],
            'monthly_fee' => ['nullable', 'string', 'max:20'],
            'billing_day' => ['nullable', 'integer', 'between:1,28'],
            'monitoring_enabled' => ['boolean'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['client_id' => 'cliente', 'name' => 'nome']);

        // Projeto não muda de cliente depois de criado (preserva contratos e cobranças vinculados).
        if ($project && (int) $data['client_id'] !== $project->client_id) {
            abort(back()->withErrors(['client_id' => 'O cliente do projeto não pode ser alterado.'])->withInput());
        }

        $data['setup_fee_cents'] = Money::toCents($data['setup_fee'] ?? null);
        $data['monthly_fee_cents'] = Money::toCents($data['monthly_fee'] ?? null);
        $data['monitoring_enabled'] = $request->boolean('monitoring_enabled');
        unset($data['setup_fee'], $data['monthly_fee']);

        return $data;
    }
}
