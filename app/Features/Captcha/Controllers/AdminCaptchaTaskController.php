<?php

namespace App\Features\Captcha\Controllers;

use App\Features\Captcha\Services\CaptchaTaskService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCaptchaTaskController extends Controller
{
    public function __construct(
        private readonly CaptchaTaskService $taskService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->taskService->adminTaskList($request),
        ]);
    }
}
