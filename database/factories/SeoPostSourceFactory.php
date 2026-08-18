<?php

namespace Database\Factories;

use App\Models\SeoPost;
use App\Models\SeoPostSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoPostSource>
 */
class SeoPostSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seo_post_id' => SeoPost::factory(),
            'title' => fake()->sentence(),
            'url' => $url = fake()->unique()->url(),
            'url_hash' => hash('sha256', mb_strtolower(rtrim($url, '/'))),
            'domain' => parse_url($url, PHP_URL_HOST),
            'type' => 'official',
        ];
    }
}
