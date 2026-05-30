<?php

namespace Database\Factories;

use App\Models\RechargeOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RechargeOrder>
 */
class RechargeOrderFactory extends Factory
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
            'recharge_method_id' => null,
            'bank_account_id' => null,
            'order_code' => 'DEP'.fake()->unique()->numerify('######'),
            'method' => 'banking',
            'method_label' => 'Chuyển khoản ngân hàng',
            'amount' => 500000,
            'bonus_amount' => 50000,
            'total_amount' => 550000,
            'bank_name' => 'Vietcombank',
            'account_number' => '1029384756',
            'account_name' => 'CONG TY TNHH CLIENT PANEL',
            'transfer_content' => 'NAP-BANKING-1801A',
            'status' => RechargeOrder::STATUS_PENDING,
            'requested_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'metadata' => [
                'description' => 'Đối soát tự động.',
            ],
        ];
    }
}
