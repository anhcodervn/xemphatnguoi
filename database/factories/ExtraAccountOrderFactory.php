<?php

namespace Database\Factories;

use App\Models\ExtraAccountOrder;
use App\Models\UserSubscription;
use App\Support\Enums\ExtraAccountOrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtraAccountOrder>
 */
class ExtraAccountOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);

        return [
            'user_subscription_id' => UserSubscription::factory(),
            'quantity' => $quantity,
            'price' => $quantity * 10,
            'status' => ExtraAccountOrderStatus::Pending,
            'expired_at' => null,
        ];
    }
}
