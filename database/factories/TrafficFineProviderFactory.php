<?php

namespace Database\Factories;

use App\Models\TrafficFineProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrafficFineProvider>
 */
class TrafficFineProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'label' => fake()->company().' API',
            'driver' => 'xephatnguoi',
            'api_url' => 'https://api.xephatnguoi.com/v1/search',
            'api_token' => 'test-provider-token',
            'timeout' => 10,
            'connect_timeout' => 3,
            'retry_times' => 2,
            'retry_sleep_ms' => 200,
        ];
    }
}
