<?php

namespace Database\Factories;

use App\Models\LookupHistory;
use App\Models\TrafficFineResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LookupHistory>
 */
class LookupHistoryFactory extends Factory
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
            'traffic_fine_result_id' => TrafficFineResult::factory(),
            'plate' => '30A'.fake()->numerify('#####'),
            'vehicle_type' => 'car',
            'violation_count' => fake()->numberBetween(0, 3),
            'created_at' => now(),
        ];
    }
}
