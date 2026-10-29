<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(12),
            'image' => null,
            'technologies' => ['Laravel', 'PHP'],
            'details' => null,
            'github_url' => null,
            'demo_url' => null,
            'featured' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['featured' => true]);
    }

    /** @param  array<string, mixed>  $details */
    public function withDetails(array $details = []): static
    {
        return $this->state(fn () => ['details' => $details ?: [
            'overview' => ['Visão geral do projeto.'],
            'features' => ['Funcionalidade um', 'Funcionalidade dois'],
        ]]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
