<?php

namespace App\Features\Client\MonitoringPlan\Controllers;

use App\Features\Client\MonitoringPlan\Actions\SubscribeMonitoringPlanAction;
use App\Features\Client\MonitoringPlan\Actions\UpdateMonitoringAutoRenewAction;
use App\Features\Client\MonitoringPlan\Actions\UpgradeMonitoringPlanAction;
use App\Features\Client\MonitoringPlan\Requests\SubscribeMonitoringPlanRequest;
use App\Features\Client\MonitoringPlan\Requests\UpdateMonitoringAutoRenewRequest;
use App\Features\Client\MonitoringPlan\Resources\MonitoringPlanResource;
use App\Features\TrafficFine\Services\MonitoringEntitlementService;
use App\Http\Controllers\Controller;
use App\Models\MonitoringPlan;
use App\Models\User;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringPlanController extends Controller
{
    public function index(Request $request, MonitoringEntitlementService $entitlements): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(ApiResponse::success(data: [
            'plans' => MonitoringPlanResource::collection(
                MonitoringPlan::query()->active()->orderBy('sort_order')->orderBy('id')->get(),
            )->resolve(),
            'subscription' => $entitlements->managementSummary($user),
        ]));
    }

    public function subscribe(
        SubscribeMonitoringPlanRequest $request,
        SubscribeMonitoringPlanAction $action,
        MonitoringEntitlementService $entitlements,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $subscription = $action->handle(
            $user,
            (int) $request->validated('plan_id'),
            $request->validated('vehicle_count') !== null ? (int) $request->validated('vehicle_count') : null,
        );

        return response()->json(ApiResponse::success('Đăng ký gói theo dõi thành công.', [
            'subscription' => $entitlements->summary($user->refresh()),
            'payment' => [
                'amount' => $subscription->total_price,
                'vehicle_limit' => $subscription->vehicle_limit,
            ],
        ]), 201);
    }

    public function updateAutoRenew(
        UpdateMonitoringAutoRenewRequest $request,
        UpdateMonitoringAutoRenewAction $action,
        MonitoringEntitlementService $entitlements,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $request->boolean('auto_renew'));

        return response()->json(ApiResponse::success('Đã cập nhật tự gia hạn gói dịch vụ.', [
            'subscription' => $entitlements->managementSummary($user->refresh()),
        ]));
    }

    public function upgrade(
        SubscribeMonitoringPlanRequest $request,
        UpgradeMonitoringPlanAction $action,
        MonitoringEntitlementService $entitlements,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $subscription = $action->handle(
            $user,
            (int) $request->validated('plan_id'),
            $request->validated('vehicle_count') !== null ? (int) $request->validated('vehicle_count') : null,
        );

        return response()->json(ApiResponse::success('Nâng cấp gói theo dõi thành công.', [
            'subscription' => $entitlements->managementSummary($user->refresh()),
            'payment' => [
                'amount' => $subscription->total_price,
                'vehicle_limit' => $subscription->vehicle_limit,
            ],
        ]), 201);
    }
}
