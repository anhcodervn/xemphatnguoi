<?php

namespace App\Features\Admin\Couponts\Actions;

use App\Features\Admin\Couponts\Services\CouponService;
use App\Models\Coupon;
use App\Models\User;

class DeleteCouponAction
{
    public function __construct(
        protected CouponService $couponService,
    ) {
    }

    public function handle(Coupon $coupon, User $admin): void
    {
        $this->couponService->delete($coupon, $admin);
    }
}
