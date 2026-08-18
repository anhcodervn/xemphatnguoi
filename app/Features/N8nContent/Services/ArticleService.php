<?php

namespace App\Features\N8nContent\Services;

use App\Features\N8nContent\Exceptions\N8nContentApiException;
use App\Models\SeoPost;
use App\Models\SeoPostActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleService
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly TagService $tagService,
        private readonly EditorContentNormalizerService $contentNormalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $identifiers
     */
    public function findDuplicate(array $identifiers, ?SeoPost $except = null): ?SeoPost
    {
        $checks = [
            'external_id' => filled($identifiers['external_id'] ?? null) ? trim((string) $identifiers['external_id']) : null,
            'source_url_hash' => filled($identifiers['source_url'] ?? null) ? $this->urlHash((string) $identifiers['source_url']) : null,
            'content_hash' => filled($identifiers['content_hash'] ?? null) ? mb_strtolower(trim((string) $identifiers['content_hash'])) : null,
            'slug' => filled($identifiers['slug'] ?? null) ? $this->normalizeSlug((string) $identifiers['slug']) : null,
        ];

        foreach ($checks as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $match = SeoPost::query()
                ->when($except instanceof SeoPost, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
                ->where($column, $value)
                ->first();

            if ($match instanceof SeoPost) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{article: SeoPost, duplicate: bool}
     */
    public function create(array $payload): array
    {
        $content = $this->contentNormalizer->normalize($payload['content']);
        $identifiers = $this->identifiers($payload, $content);
        $duplicate = $this->findDuplicate($identifiers);

        if ($duplicate instanceof SeoPost) {
            return ['article' => $duplicate, 'duplicate' => true];
        }

        try {
            return DB::transaction(function () use ($payload, $content, $identifiers): array {
                $category = filled($payload['category'] ?? null)
                    ? $this->categoryService->findOrCreate($payload['category'])['category']
                    : null;
                $tags = $this->tagService->findOrCreateMany($payload['tags'] ?? []);
                $source = is_array($payload['source'] ?? null) ? $payload['source'] : [];

                $article = SeoPost::query()->create([
                    ...$this->articleAttributes($payload, $content, $identifiers),
                    'seo_category_id' => $category?->id,
                    'status' => SeoPost::STATUS_PENDING_REVIEW,
                    'created_by_type' => SeoPost::CREATOR_N8N,
                    'robots' => 'index,follow',
                    'index_status' => 'index',
                    'source_type' => $this->nullableString($source['type'] ?? null),
                    'source_url' => $this->nullableString($source['url'] ?? null),
                    'source_url_hash' => $identifiers['source_url'] ? $this->urlHash($identifiers['source_url']) : null,
                    'source_title' => $this->nullableString($source['title'] ?? null),
                    'source_domain' => $this->sourceDomain($source),
                    'external_id' => $identifiers['external_id'],
                ]);

                $article->seoTags()->sync($tags->modelKeys());
                $this->syncLegacyTags($article, $tags->pluck('name')->all());
                $this->syncSources($article, $payload);
                $this->log($article, 'created_by_n8n', null, SeoPost::STATUS_PENDING_REVIEW);

                return ['article' => $this->loadForApi($article), 'duplicate' => false];
            }, 3);
        } catch (QueryException $exception) {
            $duplicate = $this->findDuplicate($identifiers);

            if ($duplicate instanceof SeoPost) {
                return ['article' => $duplicate, 'duplicate' => true];
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{article: SeoPost, duplicate: bool}
     */
    public function update(SeoPost $article, array $payload): array
    {
        if ($article->created_by_type !== SeoPost::CREATOR_N8N) {
            throw new N8nContentApiException('Only articles created by n8n may be updated through this API.', 403);
        }

        if (! in_array($article->status, [SeoPost::STATUS_DRAFT, SeoPost::STATUS_PENDING_REVIEW, SeoPost::STATUS_REJECTED], true)) {
            throw new N8nContentApiException('Approved or published articles cannot be updated through the n8n API.', 403);
        }

        $content = array_key_exists('content', $payload)
            ? $this->contentNormalizer->normalize($payload['content'])
            : ($article->content ?? []);
        $mergedPayload = $this->mergePayload($article, $payload);
        $identifiers = $this->identifiers($mergedPayload, $content);
        $duplicate = $this->findDuplicate($identifiers, $article);

        if ($duplicate instanceof SeoPost) {
            return ['article' => $duplicate, 'duplicate' => true];
        }

        return DB::transaction(function () use ($article, $payload, $mergedPayload, $content, $identifiers): array {
            $oldStatus = $article->status;
            $attributes = $this->articleAttributes($mergedPayload, $content, $identifiers);
            $source = is_array($mergedPayload['source'] ?? null) ? $mergedPayload['source'] : [];

            $article->fill([
                ...$attributes,
                'status' => SeoPost::STATUS_PENDING_REVIEW,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'source_type' => $this->nullableString($source['type'] ?? null),
                'source_url' => $this->nullableString($source['url'] ?? null),
                'source_url_hash' => filled($source['url'] ?? null) ? $this->urlHash((string) $source['url']) : null,
                'source_title' => $this->nullableString($source['title'] ?? null),
                'source_domain' => $this->sourceDomain($source),
                'external_id' => $identifiers['external_id'],
            ]);

            if (array_key_exists('category', $payload)) {
                $category = filled($payload['category'])
                    ? $this->categoryService->findOrCreate($payload['category'])['category']
                    : null;
                $article->seo_category_id = $category?->id;
            }

            $article->save();

            if (array_key_exists('tags', $payload)) {
                $tags = $this->tagService->findOrCreateMany($payload['tags'] ?? []);
                $article->seoTags()->sync($tags->modelKeys());
                $this->syncLegacyTags($article, $tags->pluck('name')->all());
            }

            if (array_key_exists('sources', $payload) || array_key_exists('source', $payload)) {
                $this->syncSources($article, $mergedPayload);
            }

            $this->log($article, 'updated_by_n8n', $oldStatus, SeoPost::STATUS_PENDING_REVIEW);

            return ['article' => $this->loadForApi($article), 'duplicate' => false];
        }, 3);
    }

    public function loadForApi(SeoPost $article): SeoPost
    {
        return $article->fresh()->load([
            'category:id,name,slug',
            'seoTags:id,name,slug',
            'sources:id,seo_post_id,title,url,domain,type',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array<string, mixed>>  $content
     * @return array<string, mixed>
     */
    private function articleAttributes(array $payload, array $content, array $identifiers): array
    {
        return [
            'title' => trim((string) $payload['title']),
            'slug' => $identifiers['slug'],
            'excerpt' => $this->nullableString($payload['excerpt'] ?? null),
            'content' => $content,
            'thumbnail' => $this->nullableString($payload['thumbnail'] ?? null),
            'focus_keyword' => $this->nullableString($payload['primary_keyword'] ?? null),
            'seo_title' => $this->nullableString($payload['meta_title'] ?? null),
            'seo_description' => $this->nullableString($payload['meta_description'] ?? null),
            'canonical_url' => $this->nullableString($payload['canonical_url'] ?? null),
            'content_hash' => $identifiers['content_hash'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $content
     * @return array{slug: string, source_url: ?string, external_id: ?string, content_hash: string}
     */
    private function identifiers(array $payload, array $content): array
    {
        $source = is_array($payload['source'] ?? null) ? $payload['source'] : [];

        return [
            'slug' => $this->normalizeSlug((string) ($payload['slug'] ?? $payload['title'] ?? '')),
            'source_url' => $this->nullableString($source['url'] ?? $payload['source_url'] ?? null),
            'external_id' => $this->nullableString($source['external_id'] ?? $payload['external_id'] ?? null),
            'content_hash' => $this->contentNormalizer->hash($content),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergePayload(SeoPost $article, array $payload): array
    {
        $source = [
            'type' => $article->source_type,
            'url' => $article->source_url,
            'title' => $article->source_title,
            'domain' => $article->source_domain,
            'external_id' => $article->external_id,
        ];

        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'content' => $article->content,
            'thumbnail' => $article->thumbnail,
            'primary_keyword' => $article->focus_keyword,
            'meta_title' => $article->seo_title,
            'meta_description' => $article->seo_description,
            'canonical_url' => $article->canonical_url,
            'source' => $source,
            ...$payload,
            'source' => [...$source, ...(is_array($payload['source'] ?? null) ? $payload['source'] : [])],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncSources(SeoPost $article, array $payload): void
    {
        $primary = is_array($payload['source'] ?? null) ? $payload['source'] : [];
        $sources = collect($payload['sources'] ?? []);

        if (filled($primary['url'] ?? null)) {
            $sources->prepend(Arr::only($primary, ['title', 'url', 'domain', 'type']));
        }

        $normalized = $sources
            ->filter(fn (mixed $source): bool => is_array($source) && filled($source['url'] ?? null))
            ->map(function (array $source): array {
                $url = trim((string) $source['url']);

                return [
                    'title' => $this->nullableString($source['title'] ?? null),
                    'url' => $url,
                    'url_hash' => $this->urlHash($url),
                    'domain' => $this->sourceDomain($source),
                    'type' => $this->nullableString($source['type'] ?? null),
                ];
            })
            ->unique('url_hash')
            ->values();

        $article->sources()->delete();
        $article->sources()->createMany($normalized->all());
    }

    /**
     * @param  array<int, string>  $tagNames
     */
    private function syncLegacyTags(SeoPost $article, array $tagNames): void
    {
        $article->forceFill(['tags' => array_values($tagNames)])->save();
    }

    private function log(SeoPost $article, string $action, ?string $oldStatus, ?string $newStatus): void
    {
        SeoPostActivityLog::query()->create([
            'seo_post_id' => $article->id,
            'actor_type' => SeoPost::CREATOR_N8N,
            'actor_id' => null,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'metadata' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function sourceDomain(array $source): ?string
    {
        if (filled($source['domain'] ?? null)) {
            return mb_strtolower(trim((string) $source['domain']));
        }

        $host = parse_url((string) ($source['url'] ?? ''), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? mb_strtolower($host) : null;
    }

    private function normalizeSlug(string $value): string
    {
        $slug = Str::slug(Str::lower(trim($value)));

        return $slug !== '' ? $slug : 'article-'.Str::lower(Str::random(12));
    }

    private function urlHash(string $url): string
    {
        return hash('sha256', mb_strtolower(rtrim(trim($url), '/')));
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
