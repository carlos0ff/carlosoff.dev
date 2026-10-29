<?php

namespace Database\Factories;

use App\Enums\TechnologyCategory;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technology>
 */
class TechnologyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' '.fake()->unique()->numberBetween(1, 9999),
            'category' => fake()->randomElement(TechnologyCategory::cases())->value,
            'is_published' => true,
            'featured' => false,
            'sort_order' => fake()->numberBetween(10, 99),
        ];
    }
}
