<?php

namespace App\Services\Operations;

use App\Enums\AuditAction;
use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\OperationalStatus;
use App\Enums\RestrictionMode;
use App\Enums\StaffRole;
use App\Models\AuditLog;
use App\Models\Charge;
use App\Models\ClientProject;
use App\Models\RemoteCommand;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Emissão e ciclo de vida dos comandos remotos (manutenção, suspensão, reativação).
 *
 * Toda restrição é transparente (mensagem ao usuário), reversível e auditada. Nada aqui degrada desempenho,
 * apaga dados ou interfere em backups — o agente só alterna estados de acesso.
 */
class RemoteCommandService
{
    /** Modos aplicados pelo agente instalado no projeto. */
    public const AGENT_MODES = [RestrictionMode::MaintenancePage, RestrictionMode::RestrictNonEssential];

    /** Modos aplicados no próprio painel central (bloqueio de novas solicitações/serviços adicionais). */
    public const CENTRAL_MODES = [RestrictionMode::BlockNewRequests, RestrictionMode::SuspendAdditionalServices];

    public function __construct(protected SuspensionGuard $guard, protected Notifier $notifier) {}

    /**
     * @param  array{reason: string, message?: string|null, charge_id?: int|null, starts_at?: string|null, ends_at?: string|null, reactivates_id?: int|null}  $data
     *
     * @throws CommandNotAllowedException|IdempotencyConflictException
     */
    public function issue(ClientProject $project, CommandType $type, array $data, string $idempotencyKey, Actor $actor): RemoteCommand
    {
        $hash = hash('sha256', json_encode([$type->value, $data['reason'], $data['message'] ?? null, $data['charge_id'] ?? null, $data['starts_at'] ?? null, $data['ends_at'] ?? null]));

        if ($existing = $this->findByKey($project, $idempotencyKey)) {
            return $this->replay($existing, $hash);
        }

        [$mode, $status, $report] = match ($type) {
            CommandType::Suspend => $this->prepareSuspension($project, $data, $actor),
            CommandType::Maintenance => [RestrictionMode::MaintenancePage, CommandStatus::Queued, null],
            CommandType::Reactivate => [null, $actor->isHumanApproval ? CommandStatus::Queued : CommandStatus::PendingApproval, null],
        };

        if ($type === CommandType::Reactivate && ! $actor->isHumanApproval && ($data['auto_approved'] ?? false)) {
            $status = CommandStatus::Queued;
        }

        try {
            $command = RemoteCommand::create([
                'client_project_id' => $project->id,
                'charge_id' => $data['charge_id'] ?? null,
                'reactivates_id' => $data['reactivates_id'] ?? null,
                'type' => $type,
                'status' => $status,
                'channel' => $this->channelFor($project, $type, $mode),
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $hash,
                'restriction_mode' => $mode,
                'reason' => $data['reason'],
                'message_to_users' => $data['message'] ?? $this->defaultMessage($type),
                // Sem offset explícito (campo datetime-local do painel) = fuso configurado da operação.
                'starts_at' => isset($data['starts_at']) ? Carbon::parse($data['starts_at'], config('gestao.timezone'))->utc() : null,
                'ends_at' => isset($data['ends_at']) ? Carbon::parse($data['ends_at'], config('gestao.timezone'))->utc() : null,
                'guard_report' => $report,
                'triggered_by_type' => $actor->type,
                'triggered_by_id' => $actor->id,
                'triggered_by_label' => $actor->label,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Corrida entre duas requisições com a mesma chave: devolve a que venceu.
            return $this->replay($this->findByKey($project, $idempotencyKey), $hash);
        }

        if ($status === CommandStatus::Queued && $actor->isHumanApproval) {
            $command->forceFill(['approved_by' => $actor->type === 'user' ? $actor->id : null, 'approved_at' => now()])->save();
        }

        AuditLog::forProject($project, AuditAction::Command, 'Comando remoto', $command, $type->label(), "{$status->label()} — {$data['reason']}",
            ['command' => $command->uuid, 'mode' => $mode?->value, 'guard' => $report['allowed'] ?? null], actorName: $actor->label, actorType: $actor->type);

        if ($status === CommandStatus::PendingApproval) {
            $this->notifier->staff("Aprovação necessária: {$type->label()} — {$project->name}", $data['reason'], route('admin.gestao.projects.show', $project), 'operacional', [StaffRole::Admin]);
        }

        if ($status === CommandStatus::Queued) {
            $this->dispatch($command);
        }

        return $command->refresh();
    }

    /**
     * Aprovação humana. Para suspensão, as verificações são refeitas no momento da aprovação.
     */
    public function approve(RemoteCommand $command, User $user): RemoteCommand
    {
        if ($command->status !== CommandStatus::PendingApproval) {
            throw new CommandNotAllowedException('Este comando não está aguardando aprovação.');
        }

        if ($command->type === CommandType::Suspend) {
            $report = $this->guard->evaluate($command->charge);
            $command->forceFill(['guard_report' => $report])->save();

            if (! $report['allowed']) {
                throw new CommandNotAllowedException('As condições para suspensão não são mais atendidas.', $report);
            }
        }

        $command->forceFill(['status' => CommandStatus::Queued, 'approved_by' => $user->id, 'approved_at' => now()])->save();
        AuditLog::forProject($command->project, AuditAction::Approve, 'Comando remoto', $command, $command->type->label(), $command->reason);
        $this->dispatch($command);

        return $command->refresh();
    }

    public function cancel(RemoteCommand $command, string $reason, ?Actor $actor = null): void
    {
        if ($command->isFinal() || $command->status === CommandStatus::Delivered) {
            throw new CommandNotAllowedException('Comando já entregue ou finalizado não pode ser cancelado; emita uma reativação.');
        }

        $command->forceFill(['status' => CommandStatus::Cancelled, 'result' => ['cancelled_reason' => $reason]])->save();
        AuditLog::forProject($command->project, AuditAction::Cancel, 'Comando remoto', $command, $command->type->label(), $reason,
            actorName: $actor?->label, actorType: $actor?->type);
    }

    /**
     * Comandos pendentes para o agente. Marca como entregues (a execução só conta após o ack).
     *
     * @return list<RemoteCommand>
     */
    public function pullForAgent(ClientProject $project): array
    {
        return DB::transaction(function () use ($project) {
            $commands = $project->commands()
                ->where('channel', 'agent')
                ->whereIn('status', [CommandStatus::Queued->value, CommandStatus::Delivered->value])
                ->oldest('id')
                ->lockForUpdate()
                ->get();

            foreach ($commands as $command) {
                $command->forceFill([
                    'status' => CommandStatus::Delivered,
                    'delivered_at' => $command->delivered_at ?? now(),
                    'delivery_attempts' => $command->delivery_attempts + 1,
                ])->save();
            }

            return $commands->all();
        });
    }

    /**
     * Confirmação do agente (ou do operador). Idempotente: um segundo ack não altera nada.
     *
     * @param  array<string, mixed>  $result
     */
    public function acknowledge(RemoteCommand $command, CommandStatus $status, array $result, Actor $actor): RemoteCommand
    {
        if ($command->isFinal()) {
            return $command;
        }

        if (! in_array($status, [CommandStatus::Executed, CommandStatus::Rejected, CommandStatus::Failed], true)) {
            throw new CommandNotAllowedException('Status de confirmação inválido.');
        }

        $command->forceFill(['status' => $status, 'acknowledged_at' => now(), 'result' => $result])->save();

        if ($status === CommandStatus::Executed) {
            $this->applyEffects($command);
        } else {
            $this->notifier->staff("Comando {$status->label()}: {$command->type->label()} — {$command->project->name}",
                (string) ($result['message'] ?? 'Sem detalhes do agente.'), route('admin.gestao.projects.show', $command->project), 'operacional');
        }

        AuditLog::forProject($command->project, $status === CommandStatus::Executed ? match ($command->type) {
            CommandType::Suspend => AuditAction::Suspend,
            CommandType::Reactivate => AuditAction::Reactivate,
            CommandType::Maintenance => AuditAction::Update,
        } : AuditAction::Reject, 'Serviço', $command, $command->project->name, "{$command->type->label()}: {$status->label()} (canal {$command->channel})",
            ['command' => $command->uuid, 'result' => $result], visibleToClient: $status === CommandStatus::Executed, actorName: $actor->label, actorType: $actor->type);

        // Pagamento confirmado enquanto a suspensão estava em trânsito: reativa em seguida.
        if ($status === CommandStatus::Executed && $command->type === CommandType::Suspend && $command->charge && ! $command->charge->isOpen()) {
            app(ReactivationService::class)->onChargeSettled($command->charge);
        }

        return $command->refresh();
    }

    protected function dispatch(RemoteCommand $command): void
    {
        if ($command->channel === 'central') {
            $this->acknowledge($command, CommandStatus::Executed, ['message' => 'Aplicado no painel central.'], Actor::rule('execução central'));
        }
        // agent: aguarda o pull; operator: aguarda confirmação manual da equipe.
    }

    protected function applyEffects(RemoteCommand $command): void
    {
        $project = $command->project;

        match ($command->type) {
            CommandType::Suspend => $project->forceFill(['operational_status' => OperationalStatus::Suspended, 'active_restriction' => $command->restriction_mode]),
            CommandType::Maintenance => $project->forceFill(['operational_status' => OperationalStatus::ScheduledMaintenance]),
            CommandType::Reactivate => $project->forceFill(['operational_status' => OperationalStatus::Active, 'active_restriction' => null]),
        };
        $project->saveQuietly();

        $portal = route('portal.projects.show', $project);

        if ($command->type === CommandType::Suspend) {
            $policy = $command->charge?->contract?->policy;
            $this->notifier->client($project->client, "Serviço restrito conforme contrato — {$project->name}", array_filter([
                "Aplicamos a restrição prevista em contrato: {$command->restriction_mode?->label()}.",
                "Motivo: {$command->reason}",
                'Seus dados e backups estão preservados e nada foi apagado.',
                $policy?->regularization_instructions ? "Como regularizar: {$policy->regularization_instructions}" : 'Após a confirmação do pagamento o serviço é reativado conforme o contrato.',
                'Se houver contestação, responda a este e-mail ou abra um chamado no portal.',
            ]), 'Abrir portal', $portal, 'financeiro', billing: true);
        }

        if ($command->type === CommandType::Reactivate) {
            $this->notifier->client($project->client, "Serviço reativado — {$project->name}", ['O serviço foi reativado. Obrigado pela regularização.'], 'Abrir portal', $portal, 'operacional');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: RestrictionMode, 1: CommandStatus, 2: array<string, mixed>}
     */
    protected function prepareSuspension(ClientProject $project, array $data, Actor $actor): array
    {
        $charge = isset($data['charge_id']) ? Charge::find($data['charge_id']) : null;

        if (! $charge || $charge->client_project_id !== $project->id) {
            throw new CommandNotAllowedException('Suspensão exige uma cobrança vencida deste projeto como fundamento.');
        }

        $report = $this->guard->evaluate($charge);
        if (! $report['allowed']) {
            throw new CommandNotAllowedException('Suspensão não permitida pelas verificações contratuais.', $report);
        }

        $policy = $charge->contract->policy;
        $status = ($policy->suspension_requires_approval && ! $actor->isHumanApproval) ? CommandStatus::PendingApproval : CommandStatus::Queued;

        return [$policy->restriction_mode, $status, $report];
    }

    protected function channelFor(ClientProject $project, CommandType $type, ?RestrictionMode $mode): string
    {
        if ($type === CommandType::Reactivate) {
            return in_array($project->active_restriction, self::CENTRAL_MODES, true) ? 'central'
                : ($project->active_restriction === RestrictionMode::SuspendHosting ? 'operator' : 'agent');
        }

        return match (true) {
            in_array($mode, self::CENTRAL_MODES, true) => 'central',
            $mode === RestrictionMode::SuspendHosting => 'operator',
            default => 'agent',
        };
    }

    protected function defaultMessage(CommandType $type): string
    {
        return match ($type) {
            CommandType::Maintenance => 'Sistema em manutenção programada. Voltamos em breve.',
            CommandType::Suspend => 'Acesso temporariamente restrito por pendência contratual. Os dados estão preservados. Entre em contato com o responsável pelo contrato.',
            CommandType::Reactivate => 'Serviço reativado.',
        };
    }

    protected function findByKey(ClientProject $project, string $key): ?RemoteCommand
    {
        return RemoteCommand::where('client_project_id', $project->id)->where('idempotency_key', $key)->first();
    }

    protected function replay(RemoteCommand $existing, string $hash): RemoteCommand
    {
        if (! hash_equals($existing->request_hash, $hash)) {
            throw new IdempotencyConflictException('Idempotency-Key já usada com outro conteúdo.');
        }

        return $existing;
    }
}
