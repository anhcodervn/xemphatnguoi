<?php

namespace App\Features\Captcha\Controllers;

use App\Features\Captcha\Requests\StoreCaptchaServiceRequest;
use App\Features\Captcha\Requests\UpdateCaptchaServiceRequest;
use App\Features\Captcha\Resources\CaptchaServiceResource;
use App\Features\Captcha\Services\CaptchaCatalogService;
use App\Http\Controllers\Controller;
use App\Models\CaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCaptchaServiceController extends Controller
{
    public function __construct(
        private readonly CaptchaCatalogService $catalogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->catalogService->adminServiceList($request),
        ]);
    }

    public function show(CaptchaService $captchaService): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'service' => CaptchaServiceResource::make($captchaService->load('source'))->resolve(),
            ],
        ]);
    }

    public function store(StoreCaptchaServiceRequest $request): JsonResponse
    {
        $service = $this->catalogService->storeService($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Tạo dịch vụ captcha thành công.',
            'data' => [
                'service' => CaptchaServiceResource::make($service)->resolve(),
            ],
        ], 201);
    }

    public function update(UpdateCaptchaServiceRequest $request, CaptchaService $captchaService): JsonResponse
    {
        $service = $this->catalogService->updateService($captchaService, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật dịch vụ captcha thành công.',
            'data' => [
                'service' => CaptchaServiceResource::make($service)->resolve(),
            ],
        ]);
    }
}
