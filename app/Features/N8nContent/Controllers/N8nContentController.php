<?php

namespace App\Features\N8nContent\Controllers;

use App\Features\N8nContent\Requests\CheckN8nArticleRequest;
use App\Features\N8nContent\Requests\StoreN8nArticleRequest;
use App\Features\N8nContent\Requests\StoreN8nCategoryRequest;
use App\Features\N8nContent\Requests\StoreN8nTagRequest;
use App\Features\N8nContent\Requests\UpdateN8nArticleRequest;
use App\Features\N8nContent\Services\N8nContentService;
use App\Http\Controllers\Controller;
use App\Models\SeoPost;
use Illuminate\Http\JsonResponse;

class N8nContentController extends Controller
{
    public function __construct(
        private readonly N8nContentService $contentService,
    ) {}

    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'service' => 'content-api',
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->contentService->categories(),
        ]);
    }

    public function storeCategory(StoreN8nCategoryRequest $request): JsonResponse
    {
        $result = $this->contentService->storeCategory($request->validated());

        return response()->json([
            'success' => true,
            'created' => $result['created'],
            'data' => $result['category'],
        ], $result['created'] ? 201 : 200);
    }

    public function tags(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->contentService->tags(),
        ]);
    }

    public function storeTag(StoreN8nTagRequest $request): JsonResponse
    {
        $result = $this->contentService->storeTag($request->validated());

        return response()->json([
            'success' => true,
            'created' => $result['created'],
            'data' => $result['tag'],
        ], $result['created'] ? 201 : 200);
    }

    public function checkArticle(CheckN8nArticleRequest $request): JsonResponse
    {
        $article = $this->contentService->findDuplicate($request->validated());

        return response()->json(array_filter([
            'success' => true,
            'exists' => $article instanceof SeoPost,
            'article_id' => $article?->id,
        ], fn (mixed $value): bool => $value !== null));
    }

    public function storeArticle(StoreN8nArticleRequest $request): JsonResponse
    {
        $result = $this->contentService->createArticle($request->validated());

        return $result['duplicate']
            ? $this->duplicateResponse($result['article'])
            : response()->json([
                'success' => true,
                'message' => 'Article created',
                'data' => $result['article'],
            ], 201);
    }

    public function updateArticle(UpdateN8nArticleRequest $request, SeoPost $seoPost): JsonResponse
    {
        $result = $this->contentService->updateArticle($seoPost, $request->validated());

        return $result['duplicate']
            ? $this->duplicateResponse($result['article'])
            : response()->json([
                'success' => true,
                'message' => 'Article updated and submitted for review',
                'data' => $result['article'],
            ]);
    }

    private function duplicateResponse(SeoPost $article): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'DUPLICATE_ARTICLE',
            'message' => 'Article already exists',
            'data' => [
                'article_id' => $article->id,
            ],
        ], 409);
    }
}
