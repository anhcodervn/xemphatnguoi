<?php

namespace Database\Factories;

use App\Models\Package;
use App\Support\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
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
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 99, 999),
            'duration_days' => fake()->randomElement([30, 60, 90, 365]),
            'account_limit' => fake()->numberBetween(1, 10),
            'can_buy_extra_account' => true,
            'extra_account_price' => fake()->randomFloat(2, 5, 50),
            'request_limit' => fake()->numberBetween(1000, 100000),
            'request_per_minute' => fake()->numberBetween(60, 600),
            'concurrent_limit' => fake()->numberBetween(1, 10),
            'features' => ['support', 'quota'],
            'status' => PackageStatus::Active,
        ];
    }
}
