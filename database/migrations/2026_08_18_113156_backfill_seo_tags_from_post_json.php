<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('seo_posts')
            ->select(['id', 'tags', 'created_by_type'])
            ->whereNotNull('tags')
            ->orderBy('id')
            ->chunkById(100, function ($posts): void {
                foreach ($posts as $post) {
                    $tags = json_decode((string) $post->tags, true);

                    if (! is_array($tags)) {
                        continue;
                    }

                    foreach ($tags as $name) {
                        if (! is_string($name) || ($slug = Str::slug(Str::lower(trim($name)))) === '') {
                            continue;
                        }

                        DB::table('seo_tags')->insertOrIgnore([
                            'name' => trim($name),
                            'slug' => $slug,
                            'created_by_type' => $post->created_by_type ?: 'admin',
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $tagId = DB::table('seo_tags')->where('slug', $slug)->value('id');

                        if ($tagId !== null) {
                            DB::table('seo_post_tag')->insertOrIgnore([
                                'seo_post_id' => $post->id,
                                'seo_tag_id' => $tagId,
                            ]);
                        }
                    }
                }
            });
    }

    public function down(): void {}
};
