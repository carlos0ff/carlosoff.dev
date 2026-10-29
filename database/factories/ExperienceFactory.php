<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => fake()->jobTitle(),
            'company' => fake()->company(),
            'description' => fake()->sentence(),
            'start_date' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-01'),
            'end_date' => null,
            'is_current' => false,
            'is_published' => true,
            'sort_order' => fake()->numberBetween(10, 99),
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }
}
