<?php

namespace Database\Factories;

use App\Models\AdSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdSlot>
 */
class AdSlotFactory extends Factory
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
            'code' => '<div data-ad-slot="test"></div>',
            'enabled' => true,
            'device' => 'all',
            'start_at' => null,
            'end_at' => null,
        ];
    }
}
