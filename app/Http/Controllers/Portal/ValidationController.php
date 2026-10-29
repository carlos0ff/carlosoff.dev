<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AuditAction;
use App\Enums\DevelopmentStatus;
use App\Enums\ValidationOutcome;
use App\Models\AuditLog;
use App\Models\ProjectValidation;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ValidationController extends PortalController
{
    public function respond(Request $request, ProjectValidation $validation, Notifier $notifier): RedirectResponse
    {
        $this->ensureProject($request, $validation->project);
        abort_unless($validation->outcome === ValidationOutcome::Pending, 422, 'Esta validação já foi respondida.');

        $data = $request->validate([
            'outcome' => ['required', 'in:approved,approved_with_reservations,rejected'],
            'client_notes' => ['nullable', 'required_unless:outcome,approved', 'string', 'max:5000'],
            'confirm' => ['accepted'],
        ], ['client_notes.required_unless' => 'Descreva as ressalvas ou os motivos da reprovação.', 'confirm.accepted' => 'Confirme que avaliou a versão.']);

        $validation->forceFill([
            'outcome' => ValidationOutcome::from($data['outcome']),
            'client_notes' => $data['client_notes'] ?? null,
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
            'response_ip' => $request->ip(),
            'response_user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ])->save();

        $project = $validation->project;
        $project->forceFill(['development_status' => match ($validation->outcome) {
            ValidationOutcome::Rejected => DevelopmentStatus::Development,
            default => DevelopmentStatus::Completed,
        }])->save();

        AuditLog::forProject($project, AuditAction::Approve, 'Validação', $validation, "Versão {$validation->version}", $validation->outcome->label(), visibleToClient: true, actorName: $request->user()->name);
        $notifier->staff("Validação respondida: {$project->name} v{$validation->version}", $validation->outcome->label().($validation->client_notes ? " — {$validation->client_notes}" : ''), route('admin.gestao.projects.show', $project), 'validacao');

        return back()->with('status', 'Resposta registrada. Obrigado!');
    }
}
