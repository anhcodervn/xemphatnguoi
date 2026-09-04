<?php

namespace Database\Factories;

use App\Models\MonitoringPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringPlan>
 */
class MonitoringPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'is_custom' => false,
            'vehicle_limit' => 5,
            'price' => 100000,
            'unit_price' => null,
            'min_vehicle_count' => 20,
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
