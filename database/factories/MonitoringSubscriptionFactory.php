<?php

namespace Database\Factories;

use App\Models\MonitoringPlan;
use App\Models\MonitoringSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringSubscription>
 */
class MonitoringSubscriptionFactory extends Factory
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
            'monitoring_plan_id' => MonitoringPlan::factory(),
            'plan_name' => 'Gói 5 xe',
            'vehicle_limit' => 5,
            'unit_price' => 20000,
            'total_price' => 100000,
            'duration_days' => 30,
            'status' => 'active',
            'auto_renew' => false,
            'renewal_count' => 0,
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
        ];
    }
}
