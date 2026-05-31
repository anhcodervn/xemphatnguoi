<?php

namespace App\Features\Cron\Controllers;

use App\Features\Cron\Actions\HandleApiBankVnCallbackAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CronController extends Controller
{
    public function __construct(
        private readonly HandleApiBankVnCallbackAction $handleApiBankVnCallbackAction,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'feature' => 'Cron',
            'message' => 'Cron feature index',
        ]);
    }

    public function callbackApiBankVn(Request $request): JsonResponse
    {
        $result = $this->handleApiBankVnCallbackAction->handle($request);

        return response()->json($result['body'], $result['status_code']);
    }
}
