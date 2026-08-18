<?php

namespace Database\Factories;

use App\Models\SeoPost;
use App\Models\SeoPostActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoPostActivityLog>
 */
class SeoPostActivityLogFactory extends Factory
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
            'actor_type' => SeoPost::CREATOR_ADMIN,
            'action' => 'edited_by_admin',
            'old_status' => SeoPost::STATUS_DRAFT,
            'new_status' => SeoPost::STATUS_DRAFT,
        ];
    }
}
