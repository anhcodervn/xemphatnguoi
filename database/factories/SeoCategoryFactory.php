<?php

namespace Database\Factories;

use App\Models\SeoCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoCategory>
 */
class SeoCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'robots' => 'index,follow',
            'is_active' => true,
            'sort_order' => 0,
            'created_by_type' => 'admin',
        ];
    }
}
