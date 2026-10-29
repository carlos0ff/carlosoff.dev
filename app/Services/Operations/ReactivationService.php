<?php

namespace App\Services\Operations;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\ReactivationMode;
use App\Models\Charge;
use App\Models\RemoteCommand;

/**
 * Reativação após regularização. Idempotente: no máximo uma reativação por suspensão (`reactivate:{uuid}`).
 */
class ReactivationService
{
    public function __construct(protected RemoteCommandService $commands) {}

    public function onChargeSettled(Charge $charge): void
    {
        $this->cancelPendingSuspensions($charge, 'Cobrança regularizada');

        $charge->forceFill(['suspension_eligible_at' => null])->saveQuietly();

        $suspensions = RemoteCommand::query()
            ->where('client_project_id', $charge->client_project_id)
            ->where('type', CommandType::Suspend->value)
            ->where('status', CommandStatus::Executed->value)
            ->whereNotExists(fn ($query) => $query->from('remote_commands as r')->whereColumn('r.reactivates_id', 'remote_commands.id')->whereNotIn('r.status', [CommandStatus::Cancelled->value, CommandStatus::Rejected->value, CommandStatus::Failed->value]))
            ->with('charge.contract.policy')
            ->get();

        foreach ($suspensions as $suspension) {
            // Outra cobrança ainda em aberto sustenta esta suspensão: não reativa.
            if ($suspension->charge && $suspension->charge->isOpen()) {
                continue;
            }

            $automatic = $suspension->charge?->contract?->policy?->reactivation_mode !== ReactivationMode::ManualApproval;

            $this->commands->issue($suspension->project, CommandType::Reactivate, [
                'reason' => 'Regularização confirmada da cobrança #'.($suspension->charge_id ?? '—'),
                'charge_id' => $suspension->charge_id,
                'reactivates_id' => $suspension->id,
                'auto_approved' => $automatic,
            ], "reactivate:{$suspension->uuid}", Actor::rule($automatic ? 'reativação automática após pagamento' : 'reativação mediante aprovação'));
        }
    }

    /**
     * Suspensões ainda não entregues ao agente são canceladas (contestação, negociação ou pagamento).
     */
    public function cancelPendingSuspensions(Charge $charge, string $reason): void
    {
        RemoteCommand::query()
            ->where('charge_id', $charge->id)
            ->where('type', CommandType::Suspend->value)
            ->whereIn('status', [CommandStatus::PendingApproval->value, CommandStatus::Queued->value])
            ->get()
            ->each(fn (RemoteCommand $command) => $this->commands->cancel($command, $reason, Actor::rule($reason)));

        if ($charge->suspension_eligible_at !== null) {
            $charge->forceFill(['suspension_eligible_at' => null])->saveQuietly();
        }
    }
}
