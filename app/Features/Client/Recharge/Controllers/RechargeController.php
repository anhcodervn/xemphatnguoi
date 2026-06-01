<?php

namespace App\Features\Client\Recharge\Controllers;

use App\Features\Client\Profile\Actions\RecordUserLogAction;
use App\Features\Client\Recharge\Actions\StoreRechargeOrderAction;
use App\Features\Client\Recharge\Requests\StoreRechargeOrderRequest;
use App\Features\Client\Recharge\Resources\RechargeOrderResource;
use App\Features\Client\Recharge\Services\RechargeService;
use App\Http\Controllers\Controller;
use App\Models\RechargeOrder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RechargeController extends Controller
{
    public function index(Request $request, RechargeService $rechargeService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_if(! $user instanceof User, 401);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:all,pending,processing,paid,failed,cancelled,expired'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $rechargeService->overview($user, $validated),
        ]);
    }

    public function store(
        StoreRechargeOrderRequest $request,
        StoreRechargeOrderAction $action,
        RecordUserLogAction $recordUserLogAction,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();

        abort_if(! $user instanceof User, 401);

        $order = $action->handle($user, $request->validated());

        $recordUserLogAction->handle(
            $user,
            'recharge_order_created',
            sprintf('Tạo yêu cầu nạp tiền %s', $order->order_code),
            $request,
        );

        return response()->json([
            'status' => true,
            'message' => 'Tạo yêu cầu nạp tiền thành công.',
            'data' => RechargeOrderResource::make($order)->resolve(),
        ]);
    }

    public function show(Request $request, RechargeOrder $rechargeOrder): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_if(! $user instanceof User, 401);
        abort_unless($rechargeOrder->user_id === $user->id, 404);

        return response()->json([
            'status' => true,
            'data' => RechargeOrderResource::make($rechargeOrder)->resolve(),
        ]);
    }
}
