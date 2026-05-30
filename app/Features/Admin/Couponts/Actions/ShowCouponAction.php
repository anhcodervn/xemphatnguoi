<?php

namespace App\Features\Admin\Couponts\Actions;

use App\Features\Admin\Couponts\Services\CouponService;
use App\Models\Coupon;

class ShowCouponAction
{
    public function __construct(
        protected CouponService $couponService,
    ) {
    }

    public function handle(Coupon $coupon): Coupon
    {
        return $this->couponService->show($coupon);
    }
}
