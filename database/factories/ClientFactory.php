<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_type' => 'pj',
            'legal_name' => fake()->company().' Ltda',
            'trade_name' => fake()->company(),
            'document' => null,
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => 'active',
            'relationship_started_at' => now()->subYear(),
            'internal_notes' => 'Nota interna confidencial',
        ];
    }
}
