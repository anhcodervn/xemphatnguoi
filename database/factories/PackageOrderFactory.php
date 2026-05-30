<?php

namespace Database\Factories;

use App\Models\PackageOrder;
use App\Models\Package;
use App\Models\User;
use App\Support\Enums\PackageOrderStatus;
use App\Support\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PackageOrder>
 */
class PackageOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 99, 999);
        $discountAmount = fake()->randomFloat(2, 0, 50);

        return [
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'source_subscription_id' => null,
            'order_code' => 'PKG-'.Str::upper(Str::random(10)),
            'price' => $price,
            'discount_amount' => $discountAmount,
            'credit_amount' => 0,
            'final_amount' => max(0, $price - $discountAmount),
            'payment_method' => null,
            'payment_status' => PaymentStatus::Pending,
            'status' => PackageOrderStatus::Pending,
            'paid_at' => null,
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
