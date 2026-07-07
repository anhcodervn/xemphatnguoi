<?php

namespace App\Features\Captcha\Controllers;

use App\Features\Captcha\Services\CaptchaCatalogService;
use App\Features\Captcha\Services\CaptchaTaskService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientCaptchaController extends Controller
{
    public function __construct(
        private readonly CaptchaCatalogService $catalogService,
        private readonly CaptchaTaskService $taskService,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data' => $this->taskService->clientOverview($user),
        ]);
    }

    public function services(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'services' => $this->catalogService->publicServices(),
            ],
        ]);
    }

    public function tasks(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data' => $this->taskService->clientTaskList($user, $request),
        ]);
    }
}
