<?php

namespace App\Features\Admin\Seo\Services;

use App\Models\SeoCategory;
use App\Models\SeoPost;
use Illuminate\Support\Collection;

class SeoService
{
    public function overview(): array
    {
        $categoryCount = SeoCategory::query()->count();
        $indexedCategoryCount = SeoCategory::query()
            ->where('is_active', true)
            ->where('robots', 'index,follow')
            ->count();
        $postCount = SeoPost::query()->count();
        $publishedPostCount = SeoPost::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->count();
        $canonicalCount = SeoPost::query()
            ->whereNotNull('canonical_url')
            ->where('canonical_url', '!=', '')
            ->count();
        $schemaReadyCount = SeoPost::query()
            ->where('article_schema', true)
            ->where('breadcrumb_schema', true)
            ->count();

        return [
            'summary' => [
                'total_categories' => $categoryCount,
                'indexed_categories' => $indexedCategoryCount,
                'total_posts' => $postCount,
                'published_posts' => $publishedPostCount,
                'sitemap_files' => 1,
                'technical_score' => $postCount > 0
                    ? (int) round((($canonicalCount + $schemaReadyCount) / max(1, $postCount * 2)) * 100)
                    : 0,
            ],
            'sitemaps' => $this->sitemapSummary(),
        ];
    }

    public function listCategories(string $search = ''): Collection
    {
        return SeoCategory::query()
            ->withCount('posts')
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('seo_title', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function upsertCategory(array $payload, ?SeoCategory $category = null): SeoCategory
    {
        $category ??= new SeoCategory;

        $category->fill([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'seo_title' => $payload['seo_title'] ?? null,
            'seo_description' => $payload['seo_description'] ?? null,
            'robots' => $payload['robots'],
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);

        $category->save();

        return $category->fresh()->loadCount('posts');
    }

    public function listPosts(array $filters = []): Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        return SeoPost::query()
            ->with('category:id,name')
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('seo_title', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($builder) => $builder->where('status', $status))
            ->orderByDesc('updated_at')
            ->get();
    }

    public function upsertPost(array $payload, ?SeoPost $post = null): SeoPost
    {
        $post ??= new SeoPost;

        $post->fill([
            'seo_category_id' => $payload['seo_category_id'] ?? null,
            'title' => $payload['title'],
            'slug' => $payload['slug'],
            'excerpt' => $payload['excerpt'] ?? null,
            'thumbnail' => $payload['thumbnail'] ?? null,
            'content' => $payload['content'] ?? [],
            'faq' => $payload['faq'] ?? [],
            'seo_title' => $payload['seo_title'] ?? null,
            'seo_description' => $payload['seo_description'] ?? null,
            'canonical_url' => $payload['canonical_url'] ?? null,
            'og_image' => $payload['og_image'] ?? null,
            'robots' => $payload['robots'],
            'focus_keyword' => $payload['focus_keyword'] ?? null,
            'tags' => $payload['tags'] ?? [],
            'cover_alt' => $payload['cover_alt'] ?? null,
            'article_schema' => $payload['article_schema'] ?? true,
            'breadcrumb_schema' => $payload['breadcrumb_schema'] ?? true,
            'status' => $payload['status'],
            'published_at' => $payload['status'] === 'published'
                ? ($payload['published_at'] ?? now())
                : ($payload['published_at'] ?? null),
            'scheduled_at' => $payload['status'] === 'scheduled'
                ? ($payload['scheduled_at'] ?? null)
                : null,
        ]);

        $post->save();

        return $post->fresh()->load('category:id,name');
    }

    public function sitemapSummary(): array
    {
        $publishedPostCount = SeoPost::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->count();
        $includedUrlCount = count(config('traffic-fine-content.sitemap_route_names', [])) + $publishedPostCount;

        return [
            [
                'title' => 'Public sitemap',
                'path' => '/sitemap.xml',
                'description' => 'Chứa các trang public chính và bài blog đã xuất bản; loại trừ dashboard, admin và kết quả biển số.',
                'included_count' => "{$includedUrlCount} URL",
            ],
        ];
    }
}
