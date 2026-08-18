<?php

namespace App\Features\Admin\Seo\Services;

use App\Exceptions\ApiException;
use App\Models\SeoCategory;
use App\Models\SeoPost;
use App\Models\SeoPostActivityLog;
use App\Models\SeoTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            ->with('parent:id,name,slug')
            ->withCount(['posts', 'children'])
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
            'slug' => Str::slug(Str::lower($payload['slug'] ?: $payload['name'])),
            'description' => $payload['description'] ?? null,
            'parent_id' => $payload['parent_id'] ?? null,
            'seo_title' => $payload['seo_title'] ?? null,
            'seo_description' => $payload['seo_description'] ?? null,
            'robots' => $payload['robots'],
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
            'created_by_type' => $category->exists ? $category->created_by_type : SeoPost::CREATOR_ADMIN,
        ]);

        $category->save();

        return $category->fresh()->load('parent:id,name,slug')->loadCount(['posts', 'children']);
    }

    public function listPosts(array $filters = []): Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $source = trim((string) ($filters['source'] ?? ''));
        $createdByType = trim((string) ($filters['created_by_type'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $date = trim((string) ($filters['date'] ?? ''));

        return SeoPost::query()
            ->with([
                'category:id,name,slug',
                'seoTags:id,name,slug',
                'reviewer:id,full_name,username',
                'publisher:id,full_name,username',
            ])
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('seo_title', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($builder) => $builder->where('status', $status))
            ->when(filled($categoryId), fn (Builder $builder) => $builder->where('seo_category_id', $categoryId))
            ->when($source !== '', function (Builder $builder) use ($source): void {
                $builder->where(function (Builder $query) use ($source): void {
                    $query->where('source_type', $source)->orWhere('source_domain', 'like', "%{$source}%");
                });
            })
            ->when($createdByType !== '', fn (Builder $builder) => $builder->where('created_by_type', $createdByType))
            ->when($date !== '', fn (Builder $builder) => $builder->whereDate('created_at', $date))
            ->orderByRaw("CASE WHEN status = 'pending_review' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get();
    }

    public function upsertPost(array $payload, ?SeoPost $post = null, ?User $admin = null): SeoPost
    {
        $post ??= new SeoPost;
        $isNew = ! $post->exists;

        return DB::transaction(function () use ($payload, $post, $admin, $isNew): SeoPost {
            $post->fill([
                'seo_category_id' => $payload['seo_category_id'] ?? null,
                'title' => $payload['title'],
                'slug' => Str::slug(Str::lower($payload['slug'] ?: $payload['title'])),
                'excerpt' => $payload['excerpt'] ?? null,
                'thumbnail' => $payload['thumbnail'] ?? null,
                'content' => $payload['content'] ?? [],
                'faq' => $payload['faq'] ?? [],
                'seo_title' => $payload['seo_title'] ?? null,
                'seo_description' => $payload['seo_description'] ?? null,
                'canonical_url' => $payload['canonical_url'] ?? null,
                'og_image' => $payload['og_image'] ?? null,
                'robots' => $payload['robots'],
                'index_status' => $payload['index_status'] ?? 'index',
                'focus_keyword' => $payload['focus_keyword'] ?? null,
                'tags' => $payload['tags'] ?? [],
                'cover_alt' => $payload['cover_alt'] ?? null,
                'article_schema' => $payload['article_schema'] ?? true,
                'breadcrumb_schema' => $payload['breadcrumb_schema'] ?? true,
                'status' => $isNew ? SeoPost::STATUS_DRAFT : $post->status,
                'created_by_type' => $isNew ? SeoPost::CREATOR_ADMIN : $post->created_by_type,
                'created_by_id' => $isNew ? $admin?->id : $post->created_by_id,
            ]);
            $post->save();

            $tags = collect($payload['tags'] ?? [])
                ->map(fn (string $name): string => trim($name))
                ->filter()
                ->mapWithKeys(fn (string $name): array => [Str::slug(Str::lower($name)) => $name])
                ->map(fn (string $name, string $slug): SeoTag => SeoTag::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'created_by_type' => SeoPost::CREATOR_ADMIN, 'is_active' => true],
                ))
                ->values();
            $post->seoTags()->sync($tags->pluck('id')->all());

            SeoPostActivityLog::query()->create([
                'seo_post_id' => $post->id,
                'actor_type' => SeoPost::CREATOR_ADMIN,
                'actor_id' => $admin?->id,
                'action' => $isNew ? 'created_by_admin' : 'edited_by_admin',
                'old_status' => $isNew ? null : $post->status,
                'new_status' => $post->status,
            ]);

            return $post->fresh()->load([
                'category:id,name,slug',
                'seoTags:id,name,slug',
                'sources:id,seo_post_id,title,url,domain,type',
            ]);
        });
    }

    public function listTags(string $search = ''): Collection
    {
        return SeoTag::query()
            ->withCount('posts')
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->get();
    }

    public function upsertTag(array $payload, ?SeoTag $tag = null): SeoTag
    {
        $tag ??= new SeoTag;
        $tag->fill([
            'name' => trim($payload['name']),
            'slug' => Str::slug(Str::lower($payload['slug'] ?: $payload['name'])),
            'is_active' => $payload['is_active'] ?? true,
            'created_by_type' => $tag->exists ? $tag->created_by_type : SeoPost::CREATOR_ADMIN,
        ])->save();

        return $tag->fresh()->loadCount('posts');
    }

    public function destroyCategory(SeoCategory $category): void
    {
        if ($category->posts()->exists() || $category->children()->exists()) {
            throw new ApiException('Chỉ có thể xóa danh mục chưa được sử dụng và không có danh mục con.', 409);
        }

        $category->delete();
    }

    public function destroyTag(SeoTag $tag): void
    {
        if ($tag->posts()->exists()) {
            throw new ApiException('Chỉ có thể xóa tag chưa được sử dụng.', 409);
        }

        $tag->delete();
    }

    public function mergeCategory(SeoCategory $source, int $targetId): SeoCategory
    {
        $target = SeoCategory::query()->findOrFail($targetId);

        if ($source->is($target)) {
            throw new ApiException('Danh mục nguồn và đích phải khác nhau.', 422);
        }

        return DB::transaction(function () use ($source, $target): SeoCategory {
            $source->posts()->update(['seo_category_id' => $target->id]);
            $source->children()->update(['parent_id' => $target->id]);
            $source->delete();

            return $target->fresh()->loadCount(['posts', 'children']);
        });
    }

    public function mergeTag(SeoTag $source, int $targetId): SeoTag
    {
        $target = SeoTag::query()->findOrFail($targetId);

        if ($source->is($target)) {
            throw new ApiException('Tag nguồn và đích phải khác nhau.', 422);
        }

        return DB::transaction(function () use ($source, $target): SeoTag {
            $source->posts()->each(fn (SeoPost $post) => $post->seoTags()->syncWithoutDetaching([$target->id]));
            $source->delete();

            return $target->fresh()->loadCount('posts');
        });
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
