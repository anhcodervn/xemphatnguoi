<?php

namespace Database\Factories;

use App\Models\RechargeClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RechargeClient>
 */
class RechargeClientFactory extends Factory
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
            'api_key_id' => null,
            'recharge_method_id' => null,
            'bank_account_id' => null,
            'matched_bank_transaction_id' => null,
            'order_code' => 'RCL'.fake()->unique()->numerify('########'),
            'client_order_code' => fake()->optional()->bothify('CLIENT-####'),
            'method' => 'bank_transfer',
            'method_label' => 'Chuyển khoản ngân hàng',
            'amount' => fake()->randomFloat(2, 10000, 1000000),
            'bank_name' => 'vcb',
            'account_number' => fake()->numerify('##########'),
            'account_name' => fake()->name(),
            'transfer_content' => 'NAP'.fake()->unique()->bothify('??####'),
            'status' => RechargeClient::STATUS_PENDING,
            'requested_at' => now(),
            'paid_at' => null,
            'expires_at' => now()->addMinutes(60),
            'metadata' => [],
        ];
    }
}
