<?php

namespace App\Features\N8nContent\Services;

use App\Models\SeoCategory;
use App\Models\SeoPost;
use App\Models\SeoTag;
use Illuminate\Database\Eloquent\Collection;

class N8nContentService
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly TagService $tagService,
        private readonly ArticleService $articleService,
    ) {}

    public function categories(): Collection
    {
        return $this->categoryService->list();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{category: SeoCategory, created: bool}
     */
    public function storeCategory(array $payload): array
    {
        return $this->categoryService->findOrCreate($payload);
    }

    public function tags(): Collection
    {
        return $this->tagService->list();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{tag: SeoTag, created: bool}
     */
    public function storeTag(array $payload): array
    {
        return $this->tagService->findOrCreate($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function findDuplicate(array $payload): ?SeoPost
    {
        return $this->articleService->findDuplicate($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{article: SeoPost, duplicate: bool}
     */
    public function createArticle(array $payload): array
    {
        return $this->articleService->create($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{article: SeoPost, duplicate: bool}
     */
    public function updateArticle(SeoPost $article, array $payload): array
    {
        return $this->articleService->update($article, $payload);
    }
}
