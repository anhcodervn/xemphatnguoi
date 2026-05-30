<?php

namespace App\Features\Admin\Deposit\Actions;

use App\Features\Admin\Deposit\Services\AdminDepositService;
use App\Models\RechargeOrder;

class ShowAdminDepositAction
{
    public function __construct(
        private readonly AdminDepositService $adminDepositService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(RechargeOrder $deposit): array
    {
        return $this->adminDepositService->depositDetail($deposit);
    }
}
