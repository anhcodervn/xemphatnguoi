<?php

namespace App\Features\Admin\PackageOrder\Actions;

use App\Features\Admin\PackageOrder\Services\AdminPackageOrderService;

class ListAdminPackageOrdersAction
{
    public function __construct(
        private readonly AdminPackageOrderService $adminPackageOrderService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters = []): array
    {
        return $this->adminPackageOrderService->paginateOrders($filters);
    }
}
