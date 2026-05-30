<?php

namespace App\Features\Admin\Couponts\Controllers;

use App\Features\Admin\Couponts\Actions\DeleteCouponAction;
use App\Features\Admin\Couponts\Actions\ListCouponLogsAction;
use App\Features\Admin\Couponts\Actions\ListCouponsAction;
use App\Features\Admin\Couponts\Actions\ShowCouponAction;
use App\Features\Admin\Couponts\Actions\StoreCouponAction;
use App\Features\Admin\Couponts\Actions\UpdateCouponAction;
use App\Features\Admin\Couponts\Requests\ListCouponLogRequest;
use App\Features\Admin\Couponts\Requests\ListCouponRequest;
use App\Features\Admin\Couponts\Requests\StoreCouponRequest;
use App\Features\Admin\Couponts\Requests\UpdateCouponRequest;
use App\Features\Admin\Couponts\Resources\CouponDetailResource;
use App\Features\Admin\Couponts\Resources\CouponLogResource;
use App\Features\Admin\Couponts\Resources\CouponResource;
use App\Features\Admin\Couponts\Services\CouponService;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(ListCouponRequest $request, ListCouponsAction $action, CouponService $couponService): JsonResponse
    {
        $coupons = $action->handle($request->validated());

        return response()->json([
            'status' => true,
            'data' => [
                'coupons' => [
                    'data' => CouponResource::collection($coupons->items())->resolve(),
                    'meta' => [
                        'current_page' => $coupons->currentPage(),
                        'last_page' => $coupons->lastPage(),
                        'per_page' => $coupons->perPage(),
                        'total' => $coupons->total(),
                    ],
                ],
                'summary' => $couponService->summary(),
            ],
        ]);
    }

    public function show(Coupon $coupon, ShowCouponAction $action): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => (new CouponDetailResource($action->handle($coupon)))->resolve(),
        ]);
    }

    public function store(StoreCouponRequest $request, StoreCouponAction $action): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();
        abort_if(! $admin instanceof User, 401);

        $coupon = $action->handle($request->validated(), $admin);

        return response()->json([
            'status' => true,
            'message' => 'Tạo coupon thành công.',
            'data' => (new CouponDetailResource($coupon))->resolve(),
        ], 201);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon, UpdateCouponAction $action): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();
        abort_if(! $admin instanceof User, 401);

        $coupon = $action->handle($coupon, $request->validated(), $admin);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật coupon thành công.',
            'data' => (new CouponDetailResource($coupon))->resolve(),
        ]);
    }

    public function destroy(Request $request, Coupon $coupon, DeleteCouponAction $action): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();
        abort_if(! $admin instanceof User, 401);

        $action->handle($coupon, $admin);

        return response()->json([
            'status' => true,
            'message' => 'Xóa coupon thành công.',
        ]);
    }

    public function logs(ListCouponLogRequest $request, ListCouponLogsAction $action): JsonResponse
    {
        $logs = $action->handle($request->validated());

        return response()->json([
            'status' => true,
            'data' => [
                'logs' => [
                    'data' => CouponLogResource::collection($logs->items())->resolve(),
                    'meta' => [
                        'current_page' => $logs->currentPage(),
                        'last_page' => $logs->lastPage(),
                        'per_page' => $logs->perPage(),
                        'total' => $logs->total(),
                    ],
                ],
            ],
        ]);
    }
}
