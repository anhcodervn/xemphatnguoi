<?php

namespace App\Features\Admin\PackageOrder\Actions;

use App\Features\Admin\PackageOrder\Services\AdminPackageOrderService;
use App\Models\PackageOrder;

class ShowAdminPackageOrderAction
{
    public function __construct(
        private readonly AdminPackageOrderService $adminPackageOrderService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(PackageOrder $order): array
    {
        return $this->adminPackageOrderService->orderDetail($order);
    }
}
