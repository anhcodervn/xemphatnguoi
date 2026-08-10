<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('CPN###??')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement([Coupon::TYPE_FIXED, Coupon::TYPE_PERCENT]),
            'value' => fake()->randomElement([50000, 10, 15]),
            'min_order_amount' => fake()->randomElement([0, 100000, 200000]),
            'max_discount_amount' => fake()->randomElement([null, 100000, 250000]),
            'max_usage' => fake()->randomElement([null, 50, 100]),
            'max_usage_per_user' => fake()->randomElement([1, 2, 5]),
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'expired_at' => now()->addDays(7),
            'first_order_only' => false,
            'is_active' => true,
            'requirements' => [
                'note' => fake()->sentence(),
            ],
        ];
    }
}
