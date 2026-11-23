<?php

namespace App\Features\Admin\MonitoringPlan\Controllers;

use App\Features\Admin\MonitoringPlan\Requests\MonitoringVehicleIndexRequest;
use App\Features\Admin\MonitoringPlan\Resources\MonitoringVehicleResource;
use App\Features\Admin\MonitoringPlan\Services\MonitoringVehicleService;
use App\Http\Controllers\Controller;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class MonitoringVehicleController extends Controller
{
    public function index(MonitoringVehicleIndexRequest $request, MonitoringVehicleService $service): JsonResponse
    {
        $paginated = MonitoringVehicleResource::collection($service->paginate($request->validated()))
            ->response()->getData(true);

        return response()->json(ApiResponse::success(data: [
            'vehicles' => $paginated['data'],
            'meta' => $paginated['meta'],
        ]));
    }
}
