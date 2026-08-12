<?php

namespace Database\Factories;

use App\Models\ProxyCheckBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProxyCheckBatch>
 */
class ProxyCheckBatchFactory extends Factory
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
            'check_type' => ProxyCheckBatch::TYPE_LIVE,
            'status' => ProxyCheckBatch::STATUS_PENDING,
            'total' => 1,
            'processed' => 0,
            'live' => 0,
            'die' => 0,
        ];
    }
}
