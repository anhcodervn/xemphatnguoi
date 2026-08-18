<?php

namespace App\Features\Admin\Seo\Controllers;

use App\Features\Admin\Seo\Requests\MergeSeoTaxonomyRequest;
use App\Features\Admin\Seo\Requests\RejectSeoPostRequest;
use App\Features\Admin\Seo\Requests\UpsertSeoCategoryRequest;
use App\Features\Admin\Seo\Requests\UpsertSeoPostRequest;
use App\Features\Admin\Seo\Requests\UpsertSeoTagRequest;
use App\Features\Admin\Seo\Services\ContentWorkflowService;
use App\Features\Admin\Seo\Services\SeoService;
use App\Http\Controllers\Controller;
use App\Models\SeoCategory;
use App\Models\SeoPost;
use App\Models\SeoTag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function __construct(
        protected SeoService $seoService,
        protected ContentWorkflowService $workflowService,
    ) {}

    public function overview(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->seoService->overview(),
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'categories' => $this->seoService->listCategories(
                    trim($request->string('search')->toString()),
                ),
            ],
        ]);
    }

    public function storeCategory(UpsertSeoCategoryRequest $request): JsonResponse
    {
        $category = $this->seoService->upsertCategory($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Tạo danh mục SEO thành công.',
            'data' => $category,
        ], 201);
    }

    public function showCategory(SeoCategory $seoCategory): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $seoCategory->loadCount('posts'),
        ]);
    }

    public function updateCategory(UpsertSeoCategoryRequest $request, SeoCategory $seoCategory): JsonResponse
    {
        $category = $this->seoService->upsertCategory($request->validated(), $seoCategory);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật danh mục SEO thành công.',
            'data' => $category,
        ]);
    }

    public function destroyCategory(SeoCategory $seoCategory): JsonResponse
    {
        $this->seoService->destroyCategory($seoCategory);

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa danh mục SEO.',
        ]);
    }

    public function posts(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'posts' => $this->seoService->listPosts([
                    'search' => $request->string('search')->toString(),
                    'status' => $request->string('status')->toString(),
                    'category_id' => $request->integer('category_id') ?: null,
                    'source' => $request->string('source')->toString(),
                    'created_by_type' => $request->string('created_by_type')->toString(),
                    'date' => $request->string('date')->toString(),
                ]),
                'categories' => SeoCategory::query()
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }

    public function storePost(UpsertSeoPostRequest $request): JsonResponse
    {
        $post = $this->seoService->upsertPost($request->validated(), admin: $this->admin($request));

        return response()->json([
            'status' => true,
            'message' => 'Tạo bài viết SEO thành công.',
            'data' => $post,
        ], 201);
    }

    public function showPost(SeoPost $seoPost): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $seoPost->load([
                'category:id,name,slug',
                'seoTags:id,name,slug',
                'sources:id,seo_post_id,title,url,domain,type',
                'activityLogs' => fn ($query) => $query->latest()->limit(30),
                'reviewer:id,full_name,username',
                'publisher:id,full_name,username',
            ]),
        ]);
    }

    public function updatePost(UpsertSeoPostRequest $request, SeoPost $seoPost): JsonResponse
    {
        $post = $this->seoService->upsertPost($request->validated(), $seoPost, $this->admin($request));

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật bài viết SEO thành công.',
            'data' => $post,
        ]);
    }

    public function destroyPost(SeoPost $seoPost): JsonResponse
    {
        $seoPost->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa bài viết SEO.',
        ]);
    }

    public function sitemaps(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'entries' => $this->seoService->sitemapSummary(),
            ],
        ]);
    }

    public function tags(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'tags' => $this->seoService->listTags(trim($request->string('search')->toString())),
            ],
        ]);
    }

    public function storeTag(UpsertSeoTagRequest $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Tạo tag thành công.',
            'data' => $this->seoService->upsertTag($request->validated()),
        ], 201);
    }

    public function updateTag(UpsertSeoTagRequest $request, SeoTag $seoTag): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Cập nhật tag thành công.',
            'data' => $this->seoService->upsertTag($request->validated(), $seoTag),
        ]);
    }

    public function destroyTag(SeoTag $seoTag): JsonResponse
    {
        $this->seoService->destroyTag($seoTag);

        return response()->json(['status' => true, 'message' => 'Đã xóa tag.']);
    }

    public function mergeCategory(MergeSeoTaxonomyRequest $request, SeoCategory $seoCategory): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Đã gộp danh mục.',
            'data' => $this->seoService->mergeCategory($seoCategory, (int) $request->validated('target_id')),
        ]);
    }

    public function mergeTag(MergeSeoTaxonomyRequest $request, SeoTag $seoTag): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Đã gộp tag.',
            'data' => $this->seoService->mergeTag($seoTag, (int) $request->validated('target_id')),
        ]);
    }

    public function saveDraft(Request $request, SeoPost $seoPost): JsonResponse
    {
        return $this->workflowResponse(
            $this->workflowService->saveDraft($seoPost, $this->admin($request)),
            'Đã lưu bài ở trạng thái nháp.',
        );
    }

    public function approve(Request $request, SeoPost $seoPost): JsonResponse
    {
        return $this->workflowResponse(
            $this->workflowService->approve($seoPost, $this->admin($request)),
            'Đã duyệt bài viết.',
        );
    }

    public function reject(RejectSeoPostRequest $request, SeoPost $seoPost): JsonResponse
    {
        return $this->workflowResponse(
            $this->workflowService->reject($seoPost, $this->admin($request), $request->string('rejection_reason')->toString()),
            'Đã từ chối bài viết.',
        );
    }

    public function publish(Request $request, SeoPost $seoPost): JsonResponse
    {
        return $this->workflowResponse(
            $this->workflowService->publish($seoPost, $this->admin($request)),
            'Đã xuất bản bài viết.',
        );
    }

    private function workflowResponse(SeoPost $post, string $message): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $post,
        ]);
    }

    private function admin(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
