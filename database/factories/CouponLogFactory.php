<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponLog>
 */
class CouponLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'user_id' => User::factory(),
            'admin_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'applied']),
            'status' => fake()->randomElement(['success', 'info']),
            'order_amount' => fake()->randomElement([null, 299000, 499000]),
            'discount_amount' => fake()->randomElement([null, 50000, 100000]),
            'note' => fake()->sentence(),
            'payload' => [
                'source' => 'factory',
            ],
        ];
    }
}
