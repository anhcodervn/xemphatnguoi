<?php

namespace App\Features\Admin\Couponts\Actions;

use App\Features\Admin\Couponts\Services\CouponService;
use App\Models\Coupon;
use App\Models\User;

class UpdateCouponAction
{
    public function __construct(
        protected CouponService $couponService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(Coupon $coupon, array $payload, User $admin): Coupon
    {
        return $this->couponService->update($coupon, $payload, $admin);
    }
}
