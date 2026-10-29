<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\BillingPolicy;
use App\Models\ClientProject;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'CT-TEST-'.Str::upper(Str::random(6)),
            'client_project_id' => ClientProject::factory(),
            'client_id' => fn (array $attributes) => ClientProject::find($attributes['client_project_id'])->client_id,
            'object' => 'Desenvolvimento de software',
            'setup_fee_cents' => 300000,
            'monthly_fee_cents' => 50000,
            'billing_day' => 10,
            'starts_at' => now()->subMonths(2)->startOfMonth(),
        ];
    }

    public function active(): static
    {
        return $this->afterCreating(fn (Contract $contract) => $contract->forceFill(['status' => ContractStatus::Active, 'signed_at' => now()])->save());
    }

    /**
     * Política que permite suspensão (modo página de manutenção) com aprovação humana.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function withSuspensionPolicy(array $overrides = []): static
    {
        return $this->afterCreating(fn (Contract $contract) => $contract->policy()->create(array_merge(BillingPolicy::defaults(), [
            'grace_days' => 10,
            'pre_suspension_notice_days' => 5,
            'suspension_allowed' => true,
            'restriction_mode' => 'maintenance_page',
            'suspension_requires_approval' => true,
            'reactivation_mode' => 'automatic',
            'contract_clause_reference' => 'Cláusula 9',
        ], $overrides)));
    }
}
