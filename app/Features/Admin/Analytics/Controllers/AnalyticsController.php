<?php

namespace App\Features\Admin\Analytics\Controllers;

use App\Features\Admin\Analytics\Requests\TestDiscordWebhookRequest;
use App\Features\Admin\Analytics\Services\AnalyticsService;
use App\Http\Controllers\Controller;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $this->analyticsService->dashboard(
            (string) $request->query('range', '7d'),
        )));
    }

    public function testDiscordWebhook(TestDiscordWebhookRequest $request): JsonResponse
    {
        $this->analyticsService->testDiscordWebhook($request->validated());

        return response()->json(ApiResponse::success(
            message: 'Đã gửi webhook kiểm tra thành công.',
        ));
    }
}
