<?php

namespace App\Services\Operations;

use App\Enums\ChargeStatus;
use App\Enums\ContractStatus;
use App\Enums\OperationalStatus;
use App\Models\BillingPolicy;
use App\Models\Charge;
use App\Models\ChargeNotice;
use App\Services\Billing\Gateways\GatewayUnavailableException;
use App\Services\Billing\PaymentGatewayManager;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Verificações obrigatórias ANTES de qualquer suspensão. Todas precisam passar; o relatório fica gravado no comando.
 *
 * Erros de integração, contestação, negociação ou exceção autorizada sempre bloqueiam (fail-closed).
 * Incidentes técnicos de monitoramento não participam desta decisão.
 */
class SuspensionGuard
{
    public function __construct(protected PaymentGatewayManager $gateways) {}

    /**
     * @return array{allowed: bool, checked_at: string, charge_id: int, checks: list<array{key: string, label: string, passed: bool, detail: string}>}
     */
    public function evaluate(Charge $charge, ?Carbon $today = null): array
    {
        $today ??= Carbon::today();
        $charge->loadMissing(['project', 'contract.policy']);
        $project = $charge->project;
        $contract = $charge->contract;
        $policy = $contract?->policy;
        $checks = [];

        $add = function (string $key, string $label, bool $passed, string $detail) use (&$checks): void {
            $checks[] = compact('key', 'label', 'passed', 'detail');
        };

        // 1. Situação na fonte financeira.
        try {
            $source = $this->gateways->driver($charge->gateway)->fetchStatus($charge);
            $diverges = $source['paid_cents'] !== $charge->paid_cents;
            $add('source', 'Situação confirmada na fonte financeira', ! $source['settled'] && ! $diverges,
                $source['settled'] ? 'A fonte indica que a cobrança está paga.' : ($diverges ? 'Valores divergentes entre a fonte e o sistema: conciliar antes.' : 'Fonte confirma o débito em aberto.'));
        } catch (GatewayUnavailableException|Throwable $exception) {
            $add('source', 'Situação confirmada na fonte financeira', false, 'Falha ao consultar a fonte financeira: nenhuma suspensão sem confirmação.');
        }

        $add('overdue', 'Cobrança vencida e em aberto', $charge->status === ChargeStatus::Overdue && $charge->outstandingCents() > 0,
            "Status: {$charge->status->label()}; em aberto: {$charge->outstandingCents()} centavos.");

        $linked = $contract !== null
            && $contract->client_project_id === $project->id
            && $contract->client_id === $project->client_id
            && $contract->status === ContractStatus::Active;
        $add('contract', 'Projeto e contrato vinculados e vigentes', $linked,
            $contract ? "Contrato {$contract->number} ({$contract->status->label()})." : 'Cobrança sem contrato vinculado.');

        $add('policy', 'Política contratual prevê suspensão', $policy instanceof BillingPolicy && $policy->permitsSuspension(),
            $policy ? 'Suspensão permitida: '.($policy->suspension_allowed ? 'sim' : 'não')."; forma: {$policy->restriction_mode->label()}; cláusula: ".($policy->contract_clause_reference ?: 'não informada').'.' : 'Contrato sem política financeira.');

        $graceDays = $policy?->grace_days;
        $add('grace', 'Prazo de tolerância encerrado', $graceDays !== null && $charge->daysOverdue($today) > $graceDays,
            "Dias em atraso: {$charge->daysOverdue($today)}; tolerância: ".($graceDays ?? '—').'.');

        $add('dispute', 'Sem contestação em aberto', ! $charge->isDisputed(), $charge->isDisputed() ? 'Contestação registrada: '.($charge->dispute_reason ?? '') : 'Nenhuma contestação.');

        $add('negotiation', 'Sem negociação vigente', ! $charge->isUnderNegotiation($today),
            $charge->isUnderNegotiation($today) ? 'Negociação até '.$charge->negotiation_until->format('d/m/Y').'.' : 'Nenhuma negociação vigente.');

        $add('exception', 'Sem exceção autorizada para o projeto', ! $project->hasSuspensionHold(),
            $project->hasSuspensionHold() ? 'Exceção até '.$project->suspension_hold_until->format('d/m/Y').': '.$project->suspension_hold_reason : 'Nenhuma exceção.');

        $notice = ChargeNotice::where('charge_id', $charge->id)->where('stage', 'pre_suspension')->latest('sent_at')->first();
        $noticeDays = $policy?->pre_suspension_notice_days ?? 0;
        $add('notice', 'Cliente notificado com antecedência', $notice !== null && $notice->sent_at->copy()->startOfDay()->addDays($noticeDays)->lte($today),
            $notice ? "Aviso enviado em {$notice->sent_at->format('d/m/Y')} para {$notice->recipient}; antecedência exigida: {$noticeDays} dia(s)." : 'Aviso prévio ainda não enviado.');

        $add('state', 'Projeto ativo (não suspenso nem encerrado)', ! in_array($project->operational_status, [OperationalStatus::Suspended, OperationalStatus::Terminated], true),
            "Estado operacional: {$project->operational_status->label()}.");

        return [
            'allowed' => collect($checks)->every(fn (array $check) => $check['passed']),
            'checked_at' => now()->toIso8601String(),
            'charge_id' => $charge->id,
            'checks' => $checks,
        ];
    }
}
