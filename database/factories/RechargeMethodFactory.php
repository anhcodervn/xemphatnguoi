<?php

namespace Database\Factories;

use App\Models\RechargeMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RechargeMethod>
 */
class RechargeMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('method-????'),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'badge_label' => fake()->randomElement(['Tự động', 'Thủ công']),
            'badge_type' => fake()->randomElement(['auto', 'manual']),
            'bank_name' => fake()->randomElement(['Vietcombank', 'ACB', 'MB Bank']),
            'account_number' => fake()->numerify('##########'),
            'account_name' => strtoupper(fake()->company()),
            'min_amount' => 50_000,
            'max_amount' => 100_000_000,
            'bonus_percentage' => fake()->numberBetween(0, 15),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
            'metadata' => [
                'source' => 'factory',
            ],
        ];
    }
}
