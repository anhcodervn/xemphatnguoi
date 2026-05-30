<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bank>
 */
class BankFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('bank-????'),
            'name' => fake()->unique()->company().' Bank',
            'short_name' => strtoupper(fake()->bothify('B??#')),
            'logo' => fake()->imageUrl(128, 128, 'business'),
            'bg_color' => fake()->randomElement([
                '#2563EB',
                '#16A34A',
                '#EA580C',
                '#7C3AED',
                '#0F766E',
            ]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 50),
            'limit_request_per_minute' => fake()->numberBetween(1, 20),
            'metadata' => [
                'country' => 'VN',
                'supports_api' => true,
            ],
        ];
    }
}
