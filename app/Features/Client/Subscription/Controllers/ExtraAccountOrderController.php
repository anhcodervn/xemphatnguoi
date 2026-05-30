<?php

namespace App\Features\Client\Subscription\Controllers;

use App\Features\Client\Subscription\Requests\MarkExtraAccountOrderPaidRequest;
use App\Features\Client\Subscription\Requests\StoreExtraAccountOrderRequest;
use App\Features\Client\Subscription\Services\ExtraAccountOrderService;
use App\Http\Controllers\Controller;
use App\Models\ExtraAccountOrder;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;

class ExtraAccountOrderController extends Controller
{
    public function __construct(
        private readonly ExtraAccountOrderService $extraAccountOrderService,
    ) {}

    public function store(StoreExtraAccountOrderRequest $request): JsonResponse
    {
        $subscription = UserSubscription::query()
            ->with('package')
            ->findOrFail($request->integer('user_subscription_id'));

        $this->authorize('manage', $subscription);

        $extraAccountOrder = $this->extraAccountOrderService->createOrder(
            $subscription,
            $request->integer('quantity'),
        );

        return response()->json([
            'status' => true,
            'message' => 'Tạo đơn mua thêm slot thành công.',
            'data' => $extraAccountOrder->fresh(['subscription']),
        ], 201);
    }

    public function pay(MarkExtraAccountOrderPaidRequest $request, ExtraAccountOrder $extraAccountOrder): JsonResponse
    {
        $this->authorize('manage', $extraAccountOrder);

        $paidOrder = $this->extraAccountOrderService->markAsPaid($request->user(), $extraAccountOrder);

        return response()->json([
            'status' => true,
            'message' => 'Đơn mua thêm slot đã được thanh toán.',
            'data' => $paidOrder->fresh(['subscription']),
        ]);
    }
}
