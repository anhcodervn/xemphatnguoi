<?php

namespace Database\Factories;

use App\Models\SeoTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoTag>
 */
class SeoTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'created_by_type' => 'admin',
            'is_active' => true,
        ];
    }
}
