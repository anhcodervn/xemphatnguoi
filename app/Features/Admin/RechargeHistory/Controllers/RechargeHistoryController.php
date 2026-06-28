<?php

namespace App\Features\Admin\RechargeHistory\Controllers;

use App\Features\Admin\RechargeHistory\Requests\AdminRechargeHistoryIndexRequest;
use App\Features\Admin\RechargeHistory\Services\AdminRechargeHistoryService;
use App\Http\Controllers\Controller;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class RechargeHistoryController extends Controller
{
    public function __construct(
        private readonly AdminRechargeHistoryService $adminRechargeHistoryService,
    ) {}

    public function index(AdminRechargeHistoryIndexRequest $request): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $this->adminRechargeHistoryService->paginate($request->validated())));
    }
}
