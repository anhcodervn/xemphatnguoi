<?php

namespace App\Features\Admin\Deposit\Actions;

use App\Features\Admin\Deposit\Services\AdminDepositService;

class ListAdminDepositsAction
{
    public function __construct(
        private readonly AdminDepositService $adminDepositService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters = []): array
    {
        return $this->adminDepositService->paginateDeposits($filters);
    }
}
