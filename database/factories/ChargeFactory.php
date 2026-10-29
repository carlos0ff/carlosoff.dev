<?php

namespace Database\Factories;

use App\Models\Charge;
use App\Models\ClientProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Charge>
 */
class ChargeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_project_id' => ClientProject::factory(),
            'client_id' => fn (array $attributes) => ClientProject::find($attributes['client_project_id'])->client_id,
            'kind' => 'monthly',
            'description' => 'Mensalidade',
            'amount_cents' => 50000,
            'due_date' => now()->addDays(10),
            'gateway' => 'manual',
        ];
    }
}
