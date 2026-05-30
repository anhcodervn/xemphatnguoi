<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_id' => UserSubscription::factory(),
            'status' => AccountStatus::Active,
        ];
    }
}
