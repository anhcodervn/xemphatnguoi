<?php

namespace Database\Factories;

use App\Models\ProxyCheckBatch;
use App\Models\ProxyCheckItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProxyCheckItem>
 */
class ProxyCheckItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proxy_check_batch_id' => ProxyCheckBatch::factory(),
            'position' => 0,
            'endpoint' => '8.8.8.8:8080',
            'proxy' => '8.8.8.8:8080:user:password',
            'status' => ProxyCheckItem::STATUS_PENDING,
        ];
    }
}
