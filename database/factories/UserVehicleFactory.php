<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserVehicle>
 */
class UserVehicleFactory extends Factory
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
            'name' => fake()->randomElement(['Xe gia đình', 'Xe đi làm', 'Xe công ty']),
            'plate' => '30A'.fake()->unique()->numerify('#####'),
            'vehicle_type' => 'car',
        ];
    }
}
