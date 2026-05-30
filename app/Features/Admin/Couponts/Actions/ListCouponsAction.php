<?php

namespace App\Features\Admin\Couponts\Actions;

use App\Features\Admin\Couponts\Services\CouponService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCouponsAction
{
    public function __construct(
        protected CouponService $couponService,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(array $filters = []): LengthAwarePaginator
    {
        return $this->couponService->paginate($filters);
    }
}
