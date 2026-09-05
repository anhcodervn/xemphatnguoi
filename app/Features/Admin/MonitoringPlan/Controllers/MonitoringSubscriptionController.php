<?php

namespace App\Features\Admin\MonitoringPlan\Controllers;

use App\Features\Admin\MonitoringPlan\Requests\MonitoringSubscriptionIndexRequest;
use App\Features\Admin\MonitoringPlan\Resources\MonitoringSubscriptionResource;
use App\Features\Admin\MonitoringPlan\Services\MonitoringSubscriptionService;
use App\Http\Controllers\Controller;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class MonitoringSubscriptionController extends Controller
{
    public function __construct(private readonly MonitoringSubscriptionService $service) {}

    public function index(MonitoringSubscriptionIndexRequest $request): JsonResponse
    {
        $subscriptions = $this->service->paginate($request->validated());
        $paginated = MonitoringSubscriptionResource::collection($subscriptions)
            ->response()
            ->getData(true);

        return response()->json(ApiResponse::success(data: [
            'subscriptions' => $paginated['data'],
            'meta' => $paginated['meta'],
        ]));
    }
}
