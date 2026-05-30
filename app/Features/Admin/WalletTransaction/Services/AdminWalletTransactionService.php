<?php

namespace App\Features\Admin\WalletTransaction\Services;

use App\Features\Admin\WalletTransaction\Resources\AdminWalletTransactionResource;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminWalletTransactionService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginateTransactions(array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $transactions = $this->transactionQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => AdminWalletTransactionResource::collection($transactions->getCollection())->resolve(),
            'meta' => $this->paginationMeta($transactions),
            'stats' => [
                'total_in' => (float) $this->baseStatsQuery($filters)
                    ->selectRaw(
                        "COALESCE(SUM(CASE WHEN type IN ('credit','refund') THEN amount WHEN type = 'adjustment' AND amount > 0 THEN amount ELSE 0 END), 0) as total_in"
                    )
                    ->value('total_in'),
                'total_out' => (float) $this->baseStatsQuery($filters)
                    ->selectRaw(
                        "COALESCE(SUM(CASE WHEN type IN ('debit','hold','release') THEN amount WHEN type = 'adjustment' AND amount < 0 THEN ABS(amount) ELSE 0 END), 0) as total_out"
                    )
                    ->value('total_out'),
                'today_count' => $this->baseStatsQuery($filters)->whereDate('created_at', today())->count(),
                'pending_or_failed_count' => $this->baseStatsQuery($filters)
                    ->whereIn('status', ['pending', 'failed', 'cancelled'])
                    ->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function transactionQuery(array $filters): Builder
    {
        return WalletTransaction::query()
            ->with(['wallet.user'])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    if (preg_match('/^WTX-(\d+)$/', $search, $matches) === 1) {
                        $builder->orWhere('id', (int) $matches[1]);
                    }

                    if (is_numeric($search)) {
                        $builder->orWhere('id', (int) $search);
                    }

                    $builder
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('wallet.user', function (Builder $userQuery) use ($search): void {
                            if (is_numeric($search)) {
                                $userQuery->orWhere('id', (int) $search);
                            }

                            $userQuery
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['user_id'] ?? null), fn (Builder $query) => $query->whereHas('wallet', fn (Builder $walletQuery) => $walletQuery->where('user_id', $filters['user_id'])))
            ->when(filled($filters['type'] ?? null), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseStatsQuery(array $filters): Builder
    {
        return (clone $this->transactionQuery($filters))->reorder();
    }

    /**
     * @return array<string, int>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
