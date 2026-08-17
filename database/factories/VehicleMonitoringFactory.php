<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserVehicle;
use App\Models\VehicleMonitoring;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleMonitoring>
 */
class VehicleMonitoringFactory extends Factory
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
            'user_vehicle_id' => fn (array $attributes): int => UserVehicle::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->getKey(),
            'enabled' => false,
            'last_checked_at' => null,
            'last_violation_count' => null,
        ];
    }
}
