<?php

namespace Database\Factories;

use App\Models\TrafficFineLookupLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrafficFineLookupLog>
 */
class TrafficFineLookupLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->boolean(60) ? User::factory() : null,
            'plate' => '30A'.fake()->numerify('#####'),
            'vehicle_type' => 'car',
            'source' => 'provider',
            'cache_hit' => false,
            'provider' => 'test_provider',
            'provider_latency_ms' => fake()->numberBetween(100, 2000),
            'status' => 'no_violation',
            'ip' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }
}
