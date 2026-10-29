<?php

namespace Database\Factories;

use App\Enums\CourseType;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'institution' => fake()->company(),
            'type' => CourseType::Course->value,
            'description' => fake()->sentence(),
            'is_published' => true,
            'sort_order' => fake()->numberBetween(10, 99),
        ];
    }
}
