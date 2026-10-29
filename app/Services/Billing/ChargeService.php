<?php

namespace App\Services\Billing;

use App\Enums\AuditAction;
use App\Enums\ChargeStatus;
use App\Enums\PaymentSource;
use App\Models\AuditLog;
use App\Models\Charge;
use App\Models\ClientProject;
use App\Models\Payment;
use App\Services\Billing\Gateways\GatewayUnavailableException;
use App\Services\Operations\ReactivationService;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Emissão, baixa, estorno, cancelamento, contestação e negociação de cobranças.
 * Criar uma cobrança NUNCA conta como receita: só pagamentos confirmados alteram `paid_cents`.
 */
class ChargeService
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected FinancialStatusService $financialStatus,
        protected ReactivationService $reactivation,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(ClientProject $project, array $attributes): Charge
    {
        $charge = DB::transaction(function () use ($project, $attributes) {
            $charge = new Charge($attributes + ['gateway' => $this->gateways->driver()->name()]);
            $charge->client_id = $project->client_id;
            $charge->client_project_id = $project->id;
            $charge->status = ChargeStatus::Pending;
            $charge->save();

            // Cobrança lançada com vencimento já passado nasce vencida.
            return $this->recalculate($charge);
        });

        try {
            $external = $this->gateways->driver($charge->gateway)->createCharge($charge);
            $charge->forceFill(['gateway_charge_id' => $external['id'], 'payment_url' => $external['payment_url']])->save();
        } catch (GatewayUnavailableException $exception) {
            // A cobrança continua registrada internamente; o envio ao provedor pode ser refeito.
            Log::warning('Falha ao registrar cobrança no provedor', ['charge' => $charge->uuid, 'error' => $exception->getMessage()]);
        }

        AuditLog::forProject($project, AuditAction::Create, 'Cobrança', $charge, $charge->description, Money::format($charge->amount_cents).' venc. '.$charge->due_date->format('d/m/Y'), visibleToClient: true);
        $this->financialStatus->refresh($project);

        return $charge;
    }

    /**
     * Registra um recebimento. Idempotente pelo id do pagamento no provedor (webhooks repetidos não duplicam).
     */
    public function recordPayment(Charge $charge, int $amountCents, Carbon $paidAt, PaymentSource $source, ?string $method = null, ?string $gatewayPaymentId = null, ?string $evidencePath = null, ?string $evidenceNotes = null, ?int $recordedBy = null): Payment
    {
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('O valor do pagamento precisa ser positivo.');
        }

        if ($source === PaymentSource::Manual && blank($evidencePath) && blank($evidenceNotes)) {
            throw new InvalidArgumentException('Baixa manual exige evidência (comprovante ou descrição verificável).');
        }

        if (in_array($charge->status, [ChargeStatus::Cancelled, ChargeStatus::Refunded], true)) {
            throw new InvalidArgumentException('Não é possível registrar pagamento em cobrança cancelada ou estornada.');
        }

        if ($gatewayPaymentId !== null && ($existing = Payment::where('gateway_payment_id', $gatewayPaymentId)->first())) {
            return $existing;
        }

        $payment = DB::transaction(function () use ($charge, $amountCents, $paidAt, $source, $method, $gatewayPaymentId, $evidencePath, $evidenceNotes, $recordedBy) {
            $payment = Payment::create([
                'charge_id' => $charge->id,
                'amount_cents' => $amountCents,
                'paid_at' => $paidAt,
                'method' => $method,
                'source' => $source,
                'gateway_payment_id' => $gatewayPaymentId,
                'evidence_path' => $evidencePath,
                'evidence_notes' => $evidenceNotes,
                'recorded_by' => $recordedBy,
            ]);

            $this->recalculate($charge->refresh());

            return $payment;
        });

        AuditLog::forProject($charge->project, AuditAction::Payment, 'Cobrança', $charge, $charge->description, Money::format($amountCents).' via '.$source->label(), ['payment_id' => $payment->id, 'source' => $source->value], visibleToClient: true);

        $this->financialStatus->refresh($charge->project);

        if ($charge->status === ChargeStatus::Paid) {
            $this->reactivation->onChargeSettled($charge);
        }

        return $payment;
    }

    public function refund(Charge $charge, int $amountCents, string $reason, ?int $recordedBy = null, ?string $gatewayPaymentId = null): Payment
    {
        if ($gatewayPaymentId !== null && ($existing = Payment::where('gateway_payment_id', $gatewayPaymentId)->first())) {
            return $existing;
        }

        if ($amountCents <= 0 || $amountCents > $charge->paid_cents) {
            throw new InvalidArgumentException('Valor de estorno inválido.');
        }

        $payment = DB::transaction(function () use ($charge, $amountCents, $reason, $recordedBy, $gatewayPaymentId) {
            $payment = Payment::create([
                'charge_id' => $charge->id,
                'amount_cents' => -$amountCents,
                'paid_at' => now(),
                'source' => $gatewayPaymentId ? PaymentSource::Webhook : PaymentSource::Manual,
                'gateway_payment_id' => $gatewayPaymentId,
                'evidence_notes' => $reason,
                'recorded_by' => $recordedBy,
            ]);

            $charge->refresh();
            $this->recalculate($charge);

            if ($charge->paid_cents === 0) {
                $charge->forceFill(['status' => ChargeStatus::Refunded])->save();
            }

            return $payment;
        });

        AuditLog::forProject($charge->project, AuditAction::Refund, 'Cobrança', $charge, $charge->description, Money::format($amountCents).": {$reason}", visibleToClient: true);
        $this->financialStatus->refresh($charge->project);

        return $payment;
    }

    public function cancel(Charge $charge, string $reason): void
    {
        if ($charge->paid_cents > 0) {
            throw new InvalidArgumentException('Cobrança com pagamento registrado: faça o estorno antes de cancelar.');
        }

        $this->gateways->driver($charge->gateway)->cancelCharge($charge);
        $charge->forceFill(['status' => ChargeStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => $reason])->save();

        AuditLog::forProject($charge->project, AuditAction::Cancel, 'Cobrança', $charge, $charge->description, $reason, visibleToClient: true);
        $this->financialStatus->refresh($charge->project);
        $this->reactivation->onChargeSettled($charge);
    }

    /**
     * Contestação registrada: bloqueia régua de suspensão até ser resolvida.
     */
    public function dispute(Charge $charge, string $reason): void
    {
        $charge->forceFill(['disputed_at' => now(), 'dispute_reason' => $reason])->save();
        AuditLog::forProject($charge->project, AuditAction::Dispute, 'Cobrança', $charge, $charge->description, $reason, visibleToClient: true);
        $this->financialStatus->refresh($charge->project);
        $this->reactivation->cancelPendingSuspensions($charge, 'Cobrança contestada');
    }

    public function resolveDispute(Charge $charge, string $resolution): void
    {
        $charge->forceFill(['disputed_at' => null, 'dispute_reason' => trim(($charge->dispute_reason ?? '')."\nResolução: {$resolution}")])->save();
        AuditLog::forProject($charge->project, AuditAction::Update, 'Cobrança', $charge, $charge->description, "Contestação resolvida: {$resolution}", visibleToClient: true);
        $this->financialStatus->refresh($charge->project);
    }

    public function negotiate(Charge $charge, Carbon $until, string $notes): void
    {
        $charge->forceFill(['negotiation_until' => $until, 'negotiation_notes' => $notes])->save();
        AuditLog::forProject($charge->project, AuditAction::Update, 'Cobrança', $charge, $charge->description, 'Negociação até '.$until->format('d/m/Y'), visibleToClient: true);
        $this->financialStatus->refresh($charge->project);
        $this->reactivation->cancelPendingSuspensions($charge, 'Cobrança em negociação');
    }

    /**
     * Recalcula valores e status a partir dos pagamentos (fonte da verdade local).
     */
    public function recalculate(Charge $charge, ?Carbon $today = null): Charge
    {
        $today ??= Carbon::today();

        if (in_array($charge->status, [ChargeStatus::Cancelled], true)) {
            return $charge;
        }

        $paid = (int) $charge->payments()->sum('amount_cents');

        $status = match (true) {
            $paid >= $charge->amount_cents => ChargeStatus::Paid,
            $charge->due_date->lt($today) => ChargeStatus::Overdue,
            $paid > 0 => ChargeStatus::PartiallyPaid,
            default => ChargeStatus::Pending,
        };

        $charge->forceFill([
            'paid_cents' => max(0, $paid),
            'status' => $status,
            'paid_at' => $status === ChargeStatus::Paid ? ($charge->paid_at ?? now()) : null,
            'suspension_eligible_at' => $status === ChargeStatus::Paid ? null : $charge->suspension_eligible_at,
        ])->save();

        return $charge;
    }
}
