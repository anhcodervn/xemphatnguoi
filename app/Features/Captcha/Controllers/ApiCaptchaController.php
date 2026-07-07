<?php

namespace App\Features\Captcha\Controllers;

use App\Features\Captcha\Actions\ApiCaptchaAction;
use App\Features\Captcha\Requests\ShowApiCaptchaTaskRequest;
use App\Features\Captcha\Requests\StoreApiCaptchaTaskRequest;
use App\Features\Captcha\Services\CaptchaCatalogService;
use App\Features\Captcha\Services\CaptchaTaskService;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiCaptchaController extends Controller
{
    public function __construct(
        private readonly CaptchaCatalogService $catalogService,
        private readonly CaptchaTaskService $taskService,
        private readonly ApiCaptchaAction $apiCaptchaAction,
    ) {}

    public function services(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'services' => $this->catalogService->publicServices(),
            ],
        ]);
    }

    public function balance(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data' => $this->taskService->apiBalance($user),
        ]);
    }

    public function userInfo(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('apiKey');

        return response()->json([
            'status' => true,
            'data' => $this->taskService->apiUserInfo($user, $apiKey),
        ]);
    }

    public function create(StoreApiCaptchaTaskRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('apiKey');

        $task = $this->apiCaptchaAction->handle($request->validated(), $user, $apiKey);

        return response()->json([
            'status' => true,
            'message' => 'Tạo task thành công.',
            'data' => $task,
        ], 201);
    }

    public function result(ShowApiCaptchaTaskRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->taskService->showTask($user, $request->validated('task_code'));

        return response()->json([
            'status' => true,
            'data' => $result,
        ]);
    }
}
