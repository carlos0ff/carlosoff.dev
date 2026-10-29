<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientProject>
 */
class ClientProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => 'Sistema '.fake()->word(),
            'description' => fake()->sentence(),
            'development_status' => 'development',
            'production_url' => 'https://app.example.test',
            'monthly_fee_cents' => 50000,
            'setup_fee_cents' => 300000,
            'billing_day' => 10,
            'monitoring_enabled' => true,
            'internal_notes' => 'Anotação interna do projeto',
        ];
    }
}
