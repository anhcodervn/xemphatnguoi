<?php

namespace Database\Factories;

use App\Models\SeoPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoPost>
 */
class SeoPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(),
            'slug' => fake()->unique()->slug(),
            'content' => [[
                'type' => 'paragraph',
                'children' => [['text' => fake()->paragraph()]],
            ]],
            'status' => SeoPost::STATUS_DRAFT,
            'robots' => 'index,follow',
            'index_status' => 'index',
            'created_by_type' => SeoPost::CREATOR_ADMIN,
        ];
    }
}
