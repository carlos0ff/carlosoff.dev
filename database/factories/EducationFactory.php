<?php

namespace Database\Factories;

use App\Models\Education;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course' => fake()->words(3, true),
            'institution' => fake()->company(),
            'description' => fake()->sentence(),
            'start_date' => fake()->dateTimeBetween('-8 years', '-4 years')->format('Y-m-01'),
            'end_date' => fake()->dateTimeBetween('-3 years', '-1 year')->format('Y-m-01'),
            'in_progress' => false,
            'is_published' => true,
            'sort_order' => fake()->numberBetween(10, 99),
        ];
    }
}
