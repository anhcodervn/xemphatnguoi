<?php

namespace App\Features\Admin\WalletTransaction\Actions;

use App\Features\Admin\WalletTransaction\Services\AdminWalletTransactionService;

class ListAdminWalletTransactionsAction
{
    public function __construct(
        private readonly AdminWalletTransactionService $adminWalletTransactionService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters = []): array
    {
        return $this->adminWalletTransactionService->paginateTransactions($filters);
    }
}
