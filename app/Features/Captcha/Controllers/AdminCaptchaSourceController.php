<?php

namespace App\Features\Captcha\Controllers;

use App\Features\Captcha\Requests\StoreCaptchaSourceRequest;
use App\Features\Captcha\Requests\UpdateCaptchaSourceRequest;
use App\Features\Captcha\Resources\CaptchaSourceResource;
use App\Features\Captcha\Services\CaptchaCatalogService;
use App\Http\Controllers\Controller;
use App\Models\CaptchaSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCaptchaSourceController extends Controller
{
    public function __construct(
        private readonly CaptchaCatalogService $catalogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->catalogService->adminSourceList($request),
        ]);
    }

    public function show(CaptchaSource $captchaSource): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'source' => CaptchaSourceResource::make($captchaSource)->resolve(),
            ],
        ]);
    }

    public function store(StoreCaptchaSourceRequest $request): JsonResponse
    {
        $source = $this->catalogService->storeSource($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Tạo nguồn captcha thành công.',
            'data' => [
                'source' => CaptchaSourceResource::make($source)->resolve(),
            ],
        ], 201);
    }

    public function update(UpdateCaptchaSourceRequest $request, CaptchaSource $captchaSource): JsonResponse
    {
        $source = $this->catalogService->updateSource($captchaSource, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật nguồn captcha thành công.',
            'data' => [
                'source' => CaptchaSourceResource::make($source)->resolve(),
            ],
        ]);
    }

    public function destroy(CaptchaSource $captchaSource): JsonResponse
    {
        $this->catalogService->deleteSource($captchaSource);

        return response()->json([
            'status' => true,
            'message' => 'Xóa nguồn captcha thành công.',
        ]);
    }
}
