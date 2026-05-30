<?php

namespace App\Features\Admin\Couponts\Actions;

use App\Features\Admin\Couponts\Services\CouponService;
use App\Models\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCouponLogsAction
{
    public function __construct(
        protected CouponService $couponService,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(array $filters = [], ?Coupon $coupon = null): LengthAwarePaginator
    {
        return $this->couponService->paginateLogs($filters, $coupon);
    }
}
