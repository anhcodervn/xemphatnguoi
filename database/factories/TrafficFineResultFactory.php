<?php

namespace Database\Factories;

use App\Models\TrafficFineResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrafficFineResult>
 */
class TrafficFineResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plate = '30A'.fake()->numerify('#####');
        $checkedAt = now();

        return [
            'plate' => $plate,
            'vehicle_type' => 'car',
            'status' => 'no_violation',
            'violation_count' => 0,
            'response_json' => [
                'plate' => $plate,
                'display_plate' => substr($plate, 0, 3).'-'.substr($plate, 3, 3).'.'.substr($plate, 6, 2),
                'vehicle_type' => 'car',
                'status' => 'no_violation',
                'violation_count' => 0,
                'violations' => [],
                'checked_at' => $checkedAt->toISOString(),
            ],
            'provider' => 'test_provider',
            'checked_at' => $checkedAt,
            'expires_at' => $checkedAt->copy()->addDay(),
        ];
    }
}
