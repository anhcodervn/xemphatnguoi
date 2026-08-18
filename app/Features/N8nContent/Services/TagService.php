<?php

namespace App\Features\N8nContent\Services;

use App\Models\SeoPost;
use App\Models\SeoTag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TagService
{
    public function list(): Collection
    {
        return SeoTag::query()
            ->withCount('posts')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{tag: SeoTag, created: bool}
     */
    public function findOrCreate(array $payload): array
    {
        $name = trim((string) $payload['name']);
        $slug = $this->normalizeSlug((string) ($payload['slug'] ?? $name));
        $tag = SeoTag::query()->createOrFirst(
            ['slug' => $slug],
            [
                'name' => $name,
                'created_by_type' => SeoPost::CREATOR_N8N,
                'is_active' => true,
            ],
        );

        return ['tag' => $tag, 'created' => $tag->wasRecentlyCreated];
    }

    /**
     * @param  array<int, string>  $names
     */
    public function findOrCreateMany(array $names): Collection
    {
        $tags = collect($names)
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->mapWithKeys(fn (string $name): array => [$this->normalizeSlug($name) => $name])
            ->map(fn (string $name): SeoTag => $this->findOrCreate(['name' => $name])['tag'])
            ->values();

        return new Collection($tags->all());
    }

    public function normalizeSlug(string $value): string
    {
        $slug = Str::slug(Str::lower(trim($value)));

        return $slug !== '' ? $slug : 'tag-'.Str::lower(Str::random(8));
    }
}
