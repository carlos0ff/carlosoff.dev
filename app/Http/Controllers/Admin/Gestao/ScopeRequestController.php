<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\AuditAction;
use App\Enums\ScopeRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClientProject;
use App\Models\ScopeRequest;
use App\Services\Notifier;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Solicitações fora do escopo. A equipe nunca marca "aprovada": isso só acontece pela decisão do cliente
 * no portal (com registro de consentimento) ou pela assinatura do termo de escopo.
 */
class ScopeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = ScopeRequestStatus::tryFrom((string) $request->query('status'));

        return view('admin.gestao.scope.index', [
            'requests' => ScopeRequest::with('project.client')
                ->when($status, fn (Builder $q) => $q->where('status', $status->value))
                ->latest()->paginate(20)->withQueryString(),
            'current' => $status?->value ?? 'all',
            'counts' => ['all' => ScopeRequest::count()] + ScopeRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
        ]);
    }

    public function store(Request $request, ClientProject $project): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ], [], ['title' => 'título', 'description' => 'descrição']);

        $scope = ScopeRequest::create($data + ['client_project_id' => $project->id, 'requested_by' => $request->user()->id]);
        $scope->forceFill(['status' => ScopeRequestStatus::UnderAnalysis])->save();
        AuditLog::forProject($project, AuditAction::Create, 'Solicitação de escopo', $scope, $scope->title, visibleToClient: true);

        return redirect()->route('admin.gestao.scope.show', $scope)->with('status', 'Solicitação registrada.');
    }

    public function show(ScopeRequest $scopeRequest): View
    {
        $scopeRequest->load(['project.client', 'requester', 'decider']);

        return view('admin.gestao.scope.show', [
            'scope' => $scopeRequest,
            'documents' => $scopeRequest->project->documents()->where('scope_request_id', $scopeRequest->id)->latest()->get(),
        ]);
    }

    /**
     * Orçamento: análise de impacto, horas, valor e prazo. Envia ao cliente para decisão.
     */
    public function quote(Request $request, ScopeRequest $scopeRequest, Notifier $notifier): RedirectResponse
    {
        if (in_array($scopeRequest->status, [ScopeRequestStatus::Approved, ScopeRequestStatus::Rejected, ScopeRequestStatus::Delivered], true)) {
            return back()->withErrors(['scope' => 'Solicitação já decidida: crie uma nova para alterar o orçamento.']);
        }

        $data = $request->validate([
            'affected_features' => ['nullable', 'string', 'max:5000'],
            'impact_analysis' => ['required', 'string', 'max:5000'],
            'estimated_hours' => ['required', 'numeric', 'min:0.5', 'max:5000'],
            'amount' => ['required', 'string', 'max:20'],
            'estimated_days' => ['required', 'integer', 'min:1', 'max:730'],
            'dependencies' => ['nullable', 'string', 'max:5000'],
            'acceptance_criteria' => ['required', 'string', 'max:5000'],
        ], [], ['impact_analysis' => 'análise de impacto', 'estimated_hours' => 'horas', 'amount' => 'valor', 'estimated_days' => 'prazo', 'acceptance_criteria' => 'critérios de aceite']);

        $data['amount_cents'] = Money::toCents($data['amount']);
        unset($data['amount']);

        $scopeRequest->fill($data);
        $scopeRequest->forceFill(['status' => ScopeRequestStatus::Quoted, 'quoted_at' => now()])->save();

        AuditLog::forProject($scopeRequest->project, AuditAction::Update, 'Solicitação de escopo', $scopeRequest, $scopeRequest->title, 'Orçada em '.Money::format($scopeRequest->amount_cents), visibleToClient: true);
        $notifier->client($scopeRequest->project->client, "Orçamento disponível: {$scopeRequest->title}", [
            'Valor adicional: '.Money::format($scopeRequest->amount_cents).", prazo estimado: {$scopeRequest->estimated_days} dia(s).",
            'O trabalho só começa após a sua aprovação registrada no portal ou a assinatura do termo de solicitação.',
        ], 'Avaliar orçamento', route('portal.scope.index'), 'escopo');

        return back()->with('status', 'Orçamento enviado ao cliente.');
    }

    public function status(Request $request, ScopeRequest $scopeRequest): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:cancelled,delivered']])['status'];

        if ($status === 'delivered' && $scopeRequest->status !== ScopeRequestStatus::Approved) {
            return back()->withErrors(['scope' => 'Só solicitações aprovadas pelo cliente podem ser entregues.']);
        }

        $scopeRequest->forceFill(['status' => ScopeRequestStatus::from($status)])->save();
        AuditLog::forProject($scopeRequest->project, AuditAction::Update, 'Solicitação de escopo', $scopeRequest, $scopeRequest->title, $scopeRequest->status->label(), visibleToClient: true);

        return back()->with('status', 'Situação atualizada.');
    }
}
