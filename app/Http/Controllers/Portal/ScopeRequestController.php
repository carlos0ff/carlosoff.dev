<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AuditAction;
use App\Enums\ScopeRequestStatus;
use App\Models\AuditLog;
use App\Models\ClientProject;
use App\Models\ScopeRequest;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScopeRequestController extends PortalController
{
    public function index(Request $request): View
    {
        $projectIds = ClientProject::where('client_id', $this->clientId($request))->pluck('id');

        return view('portal.scope.index', [
            'requests' => ScopeRequest::whereIn('client_project_id', $projectIds)->with('project')->latest()->get(),
            'projects' => ClientProject::whereIn('id', $projectIds)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ClientProject $project, Notifier $notifier): RedirectResponse
    {
        $this->ensureProject($request, $project);

        if ($project->blocksNewRequests()) {
            return back()->withErrors(['scope' => 'Novas solicitações estão temporariamente indisponíveis para este projeto conforme o contrato. Fale com o responsável pelo contrato.']);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ], [], ['title' => 'título', 'description' => 'descrição']);

        $scope = ScopeRequest::create($data + ['client_project_id' => $project->id, 'requested_by' => $request->user()->id]);
        AuditLog::forProject($project, AuditAction::Create, 'Solicitação de escopo', $scope, $scope->title, 'Aberta pelo cliente', visibleToClient: true, actorName: $request->user()->name);
        $notifier->staff("Nova solicitação: {$scope->title}", "Projeto {$project->name}", route('admin.gestao.scope.show', $scope), 'escopo');

        return back()->with('status', 'Solicitação enviada. Você receberá a análise e o orçamento antes de qualquer execução.');
    }

    /**
     * Consentimento explícito: registra usuário, data, IP, navegador e o hash do texto exato apresentado.
     */
    public function decide(Request $request, ScopeRequest $scopeRequest, Notifier $notifier): RedirectResponse
    {
        $this->ensureProject($request, $scopeRequest->project);
        abort_unless($scopeRequest->status === ScopeRequestStatus::Quoted, 422, 'Esta solicitação não aguarda decisão.');

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'terms_hash' => ['required', 'string', 'size:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'consent' => ['required_if:decision,approve', 'accepted_if:decision,approve'],
        ], ['consent.accepted_if' => 'Para aprovar, confirme que concorda com o valor, o prazo e os critérios de aceite.']);

        // O orçamento mudou depois que a página foi aberta: o cliente precisa ver a versão atual.
        if (! hash_equals(hash('sha256', $scopeRequest->termsForConsent()), $data['terms_hash'])) {
            return back()->withErrors(['scope' => 'O orçamento foi atualizado. Revise os novos termos antes de decidir.']);
        }

        $scopeRequest->forceFill([
            'status' => $data['decision'] === 'approve' ? ScopeRequestStatus::Approved : ScopeRequestStatus::Rejected,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_ip' => $request->ip(),
            'decision_user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'decision_terms_hash' => $data['terms_hash'],
            'decision_notes' => $data['notes'] ?? null,
        ])->save();

        AuditLog::forProject($scopeRequest->project, $data['decision'] === 'approve' ? AuditAction::Approve : AuditAction::Reject, 'Solicitação de escopo', $scopeRequest, $scopeRequest->title,
            "Decisão do cliente (hash {$data['terms_hash']})", ['ip' => $request->ip()], visibleToClient: true, actorName: $request->user()->name);
        $notifier->staff("Orçamento {$scopeRequest->status->label()}: {$scopeRequest->title}", "Decisão registrada por {$request->user()->name}.", route('admin.gestao.scope.show', $scopeRequest), 'escopo');

        return back()->with('status', 'Decisão registrada.');
    }
}
