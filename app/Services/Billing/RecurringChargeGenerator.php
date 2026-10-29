<?php

namespace App\Services\Billing;

use App\Enums\ChargeKind;
use App\Enums\ContractStatus;
use App\Models\Charge;
use App\Models\Contract;
use Illuminate\Support\Carbon;

/**
 * Gera as mensalidades de contratos vigentes. Idempotente: `recurrence_key` único por contrato + competência.
 */
class RecurringChargeGenerator
{
    public const DAYS_AHEAD = 20;

    public function __construct(protected ChargeService $charges) {}

    /**
     * @return list<Charge>
     */
    public function generate(?Carbon $today = null): array
    {
        $today ??= Carbon::today();
        $created = [];

        Contract::query()
            ->where('status', ContractStatus::Active->value)
            ->where('monthly_fee_cents', '>', 0)
            ->with('project')
            ->each(function (Contract $contract) use ($today, &$created) {
                foreach ([$today->copy()->startOfMonth(), $today->copy()->addMonthNoOverflow()->startOfMonth()] as $month) {
                    $charge = $this->generateFor($contract, $month, $today);
                    if ($charge) {
                        $created[] = $charge;
                    }
                }
            });

        return $created;
    }

    public function generateFor(Contract $contract, Carbon $month, Carbon $today): ?Charge
    {
        $day = min($contract->billing_day ?: 10, $month->daysInMonth);
        $dueDate = $month->copy()->day($day);
        $competence = $month->format('Y-m');
        $key = "contract:{$contract->id}:monthly:{$competence}";

        if (! $contract->isActiveOn($dueDate) || $dueDate->gt($today->copy()->addDays(self::DAYS_AHEAD))) {
            return null;
        }

        // Não cobrar retroativamente meses anteriores ao início do contrato.
        if ($contract->starts_at && $dueDate->lt($contract->starts_at)) {
            return null;
        }

        if (Charge::where('recurrence_key', $key)->exists()) {
            return null;
        }

        return $this->charges->create($contract->project, [
            'contract_id' => $contract->id,
            'kind' => ChargeKind::Monthly,
            'description' => "Mensalidade {$month->format('m/Y')} — contrato {$contract->number}",
            'competence' => $competence,
            'recurrence_key' => $key,
            'amount_cents' => $contract->monthly_fee_cents,
            'due_date' => $dueDate,
        ]);
    }
}
