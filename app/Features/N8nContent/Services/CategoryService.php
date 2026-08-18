<?php

namespace App\Features\N8nContent\Services;

use App\Models\SeoCategory;
use App\Models\SeoPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    public function list(): Collection
    {
        return SeoCategory::query()
            ->with('parent:id,name,slug')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{category: SeoCategory, created: bool}
     */
    public function findOrCreate(array $payload): array
    {
        if (filled($payload['id'] ?? null)) {
            return [
                'category' => SeoCategory::query()->findOrFail((int) $payload['id']),
                'created' => false,
            ];
        }

        $slug = $this->normalizeSlug((string) ($payload['slug'] ?? $payload['name'] ?? ''));
        $category = SeoCategory::query()->createOrFirst(
            ['slug' => $slug],
            [
                'name' => trim((string) $payload['name']),
                'description' => filled($payload['description'] ?? null) ? trim((string) $payload['description']) : null,
                'parent_id' => $payload['parent_id'] ?? null,
                'robots' => 'index,follow',
                'is_active' => true,
                'created_by_type' => SeoPost::CREATOR_N8N,
            ],
        );

        return ['category' => $category, 'created' => $category->wasRecentlyCreated];
    }

    public function normalizeSlug(string $value): string
    {
        $slug = Str::slug(Str::lower(trim($value)));

        return $slug !== '' ? $slug : 'category-'.Str::lower(Str::random(8));
    }
}
