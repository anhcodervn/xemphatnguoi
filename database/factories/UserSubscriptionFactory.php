<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSubscription>
 */
class UserSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now();
        $expiresAt = now()->addDays(30);

        return [
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'order_id' => null,
            'package_name' => fake()->words(2, true),
            'package_price' => fake()->randomFloat(2, 99, 999),
            'base_account_limit' => fake()->numberBetween(1, 5),
            'extra_account_limit' => 0,
            'used_account' => 0,
            'auto_renew_enabled' => false,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'auto_renew_attempted_at' => null,
            'auto_renew_status' => null,
            'auto_renew_message' => null,
            'status' => SubscriptionStatus::Active,
        ];
    }

    public function forOrder(?PackageOrder $packageOrder = null): static
    {
        return $this->state(function () use ($packageOrder): array {
            $order = $packageOrder ?? PackageOrder::factory()->create();

            return [
                'user_id' => $order->user_id,
                'package_id' => $order->package_id,
                'order_id' => $order->id,
                'package_name' => $order->package->name,
                'package_price' => $order->price,
            ];
        });
    }
}
