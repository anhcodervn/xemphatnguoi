<?php

namespace App\Features\Admin\MonitoringPlan\Controllers;

use App\Features\Admin\MonitoringPlan\Requests\MonitoringPlanRequest;
use App\Features\Admin\MonitoringPlan\Resources\MonitoringPlanResource;
use App\Features\Admin\MonitoringPlan\Services\MonitoringPlanService;
use App\Http\Controllers\Controller;
use App\Models\MonitoringPlan;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class MonitoringPlanController extends Controller
{
    public function __construct(private readonly MonitoringPlanService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(ApiResponse::success(data: [
            'plans' => MonitoringPlanResource::collection($this->service->all())->resolve(),
        ]));
    }

    public function store(MonitoringPlanRequest $request): JsonResponse
    {
        $plan = $this->service->create($request->validated());

        return response()->json(ApiResponse::success('Đã tạo gói theo dõi.', [
            'plan' => (new MonitoringPlanResource($plan))->resolve(),
        ]), 201);
    }

    public function update(MonitoringPlanRequest $request, MonitoringPlan $monitoringPlan): JsonResponse
    {
        $plan = $this->service->update($monitoringPlan, $request->validated());

        return response()->json(ApiResponse::success('Đã cập nhật gói theo dõi.', [
            'plan' => (new MonitoringPlanResource($plan))->resolve(),
        ]));
    }

    public function destroy(MonitoringPlan $monitoringPlan): JsonResponse
    {
        $this->service->delete($monitoringPlan);

        return response()->json(ApiResponse::success('Đã xóa gói theo dõi.'));
    }
}
