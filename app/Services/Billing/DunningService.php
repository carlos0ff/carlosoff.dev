<?php

namespace App\Services\Billing;

use App\Enums\ChargeStatus;
use App\Enums\CommandType;
use App\Enums\StaffRole;
use App\Models\BillingPolicy;
use App\Models\Charge;
use App\Models\ChargeNotice;
use App\Models\ClientProject;
use App\Services\Notifier;
use App\Services\Operations\Actor;
use App\Services\Operations\CommandNotAllowedException;
use App\Services\Operations\RemoteCommandService;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Régua de cobrança executada diariamente, com prazos vindos da política de CADA contrato:
 *
 * 1. lembrete antes do vencimento → 2. aviso no vencimento (segue pendente até confirmação) →
 * 3. avisos de atraso → 4. tolerância (serviço mantido) → 5. alerta interno ao fim da tolerância →
 * 6. aviso prévio de suspensão (com prazo para regularizar/contestar) → 7. proposta de suspensão
 * passando pelo SuspensionGuard (normalmente aguardando aprovação humana).
 */
class DunningService
{
    public function __construct(
        protected ChargeService $charges,
        protected FinancialStatusService $financialStatus,
        protected RemoteCommandService $commands,
        protected Notifier $notifier,
    ) {}

    /**
     * @return array{processed: int, notices: int, proposals: int}
     */
    public function process(?Carbon $today = null): array
    {
        $today ??= Carbon::today();
        $stats = ['processed' => 0, 'notices' => 0, 'proposals' => 0];

        Charge::query()->open()->with(['project.client', 'contract.policy'])->each(function (Charge $charge) use ($today, &$stats) {
            $stats['processed']++;
            $this->charges->recalculate($charge, $today);

            if (! $charge->isOpen()) {
                return;
            }

            $policy = $charge->contract?->policy ?? new BillingPolicy(BillingPolicy::defaults());
            $result = $this->processCharge($charge, $policy, $today);
            $stats['notices'] += $result['notices'];
            $stats['proposals'] += $result['proposal'] ? 1 : 0;
        });

        $projects = Charge::query()->select('client_project_id')->distinct()->pluck('client_project_id');
        ClientProject::whereIn('id', $projects)->each(fn ($project) => $this->financialStatus->refresh($project, $today));

        return $stats;
    }

    /**
     * @return array{notices: int, proposal: bool}
     */
    protected function processCharge(Charge $charge, BillingPolicy $policy, Carbon $today): array
    {
        $notices = 0;
        $daysUntilDue = (int) $today->diffInDays($charge->due_date, false);
        $amount = Money::format($charge->outstandingCents());

        if ($daysUntilDue > 0 && $daysUntilDue <= $policy->reminder_days_before) {
            $notices += (int) $this->notifyOnce($charge, 'reminder', 1, "Lembrete: cobrança vence em {$charge->due_date->format('d/m/Y')}", [
                "{$charge->description}: {$amount}, vencimento em {$charge->due_date->format('d/m/Y')}.",
            ]);
        }

        if ($daysUntilDue === 0) {
            $notices += (int) $this->notifyOnce($charge, 'due', 1, 'Cobrança vence hoje', [
                "{$charge->description}: {$amount}. A cobrança permanece pendente até a confirmação do pagamento.",
            ]);
        }

        if ($charge->status !== ChargeStatus::Overdue) {
            return ['notices' => $notices, 'proposal' => false];
        }

        // Contestação ou negociação: suspende a régua (nenhum aviso de atraso/suspensão).
        if ($charge->isDisputed() || $charge->isUnderNegotiation($today)) {
            return ['notices' => $notices, 'proposal' => false];
        }

        $daysOverdue = $charge->daysOverdue($today);
        $sequence = intdiv($daysOverdue - 1, max(1, $policy->overdue_notice_interval_days)) + 1;
        $notices += (int) $this->notifyOnce($charge, 'overdue', $sequence, "Cobrança em atraso — {$charge->project->name}", [
            "{$charge->description}: {$amount}, vencida em {$charge->due_date->format('d/m/Y')} ({$daysOverdue} dia(s)).",
            'Se o pagamento já foi feito, envie o comprovante pelo portal. Se discordar da cobrança, registre uma contestação.',
        ]);

        if ($daysOverdue <= $policy->grace_days) {
            return ['notices' => $notices, 'proposal' => false];
        }

        if (! ChargeNotice::where('charge_id', $charge->id)->where('stage', 'grace_expired')->exists()) {
            $this->record($charge, 'grace_expired', 1, 'equipe');
            $this->notifier->staff("Tolerância encerrada: {$charge->project->name}", "{$charge->description} ({$amount}) está {$daysOverdue} dia(s) em atraso. Avalie a situação conforme o contrato.",
                route('admin.gestao.charges.show', $charge), 'financeiro', [StaffRole::Admin, StaffRole::Finance]);
        }

        if (! $policy->exists || ! $policy->permitsSuspension() || $charge->project->hasSuspensionHold()) {
            return ['notices' => $notices, 'proposal' => false];
        }

        if ($charge->suspension_eligible_at === null) {
            $eligibleAt = $today->copy()->addDays($policy->pre_suspension_notice_days);
            $next = ChargeNotice::where('charge_id', $charge->id)->where('stage', 'pre_suspension')->max('sequence') + 1;

            $notices += (int) $this->notifyOnce($charge, 'pre_suspension', $next, "Aviso prévio de restrição do serviço — {$charge->project->name}", array_values(array_filter([
                "{$charge->description}: {$amount}, vencida em {$charge->due_date->format('d/m/Y')}.",
                'Conforme o contrato'.($policy->contract_clause_reference ? " ({$policy->contract_clause_reference})" : '').", a partir de {$eligibleAt->format('d/m/Y')} poderá ser aplicada: {$policy->restriction_mode->label()}.",
                'Nenhum dado será apagado e os backups continuam preservados.',
                $policy->regularization_instructions ? "Como regularizar: {$policy->regularization_instructions}" : null,
                'Se houver contestação ou proposta de negociação, responda antes dessa data: a restrição não será aplicada enquanto ela estiver em análise.',
            ])));

            $charge->forceFill(['suspension_eligible_at' => $eligibleAt])->save();

            return ['notices' => $notices, 'proposal' => false];
        }

        if ($today->lt($charge->suspension_eligible_at)) {
            return ['notices' => $notices, 'proposal' => false];
        }

        try {
            $this->commands->issue($charge->project, CommandType::Suspend, [
                'reason' => "Inadimplência: {$charge->description} ({$amount}) vencida em {$charge->due_date->format('d/m/Y')}",
                'charge_id' => $charge->id,
            ], "auto-suspend:charge:{$charge->id}:{$charge->suspension_eligible_at->format('Ymd')}", Actor::rule('régua de cobrança do contrato '.$charge->contract->number));

            return ['notices' => $notices, 'proposal' => true];
        } catch (CommandNotAllowedException $exception) {
            Log::info('Suspensão não proposta', ['charge' => $charge->uuid, 'report' => $exception->report]);

            return ['notices' => $notices, 'proposal' => false];
        }
    }

    /**
     * @param  list<string>  $lines
     */
    protected function notifyOnce(Charge $charge, string $stage, int $sequence, string $subject, array $lines): bool
    {
        if (ChargeNotice::where(['charge_id' => $charge->id, 'stage' => $stage, 'sequence' => $sequence])->exists()) {
            return false;
        }

        try {
            $notice = $this->record($charge, $stage, $sequence, null);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        $recipient = $this->notifier->client($charge->client, $subject, $lines, 'Ver cobrança', route('portal.charges.index'), 'financeiro', billing: true);
        $notice->forceFill(['recipient' => $recipient])->save();

        return true;
    }

    protected function record(Charge $charge, string $stage, int $sequence, ?string $recipient): ChargeNotice
    {
        return ChargeNotice::create([
            'charge_id' => $charge->id,
            'stage' => $stage,
            'sequence' => $sequence,
            'channel' => $stage === 'grace_expired' ? 'interno' : 'mail',
            'recipient' => $recipient,
            'sent_at' => now(),
        ]);
    }
}
