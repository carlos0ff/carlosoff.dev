<?php

namespace App\Services\Billing;

use App\Enums\ChargeStatus;
use App\Enums\FinancialStatus;
use App\Models\Charge;
use App\Models\ClientProject;
use Illuminate\Support\Carbon;

/**
 * O status financeiro do projeto é sempre DERIVADO das cobranças — nunca editado à mão nem pelo cliente.
 */
class FinancialStatusService
{
    public function refresh(ClientProject $project, ?Carbon $today = null): FinancialStatus
    {
        $today ??= Carbon::today();
        $open = $project->charges()->open()->get();
        // Compara com o valor persistido: a instância recebida pode estar desatualizada.
        $persisted = FinancialStatus::tryFrom((string) ClientProject::whereKey($project->id)->toBase()->value('financial_status')) ?? FinancialStatus::UpToDate;

        $overdue = $open->filter(fn (Charge $charge) => $charge->status === ChargeStatus::Overdue);

        $status = match (true) {
            $overdue->isNotEmpty() && $overdue->every(fn (Charge $charge) => $charge->isUnderNegotiation($today) || $charge->isDisputed()) => FinancialStatus::Negotiating,
            $overdue->isNotEmpty() => FinancialStatus::Overdue,
            $open->isNotEmpty() => FinancialStatus::Pending,
            in_array($persisted, [FinancialStatus::Overdue, FinancialStatus::Negotiating, FinancialStatus::Regularized], true) => FinancialStatus::Regularized,
            default => FinancialStatus::UpToDate,
        };

        if ($persisted !== $status) {
            ClientProject::whereKey($project->id)->update(['financial_status' => $status->value]);
        }
        $project->setAttribute('financial_status', $status)->syncOriginalAttribute('financial_status');

        return $status;
    }
}
