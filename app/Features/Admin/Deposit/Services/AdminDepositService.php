<?php

namespace App\Features\Admin\Deposit\Services;

use App\Features\Admin\Deposit\Resources\AdminDepositResource;
use App\Models\PaymentTransaction;
use App\Models\RechargeOrder;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminDepositService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginateDeposits(array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $deposits = $this->depositQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => AdminDepositResource::collection($deposits->getCollection())->resolve(),
            'meta' => $this->paginationMeta($deposits),
            'stats' => [
                'total_deposits' => RechargeOrder::query()->count(),
                'pending_deposits' => RechargeOrder::query()
                    ->whereIn('status', [RechargeOrder::STATUS_PENDING, RechargeOrder::STATUS_PROCESSING])
                    ->count(),
                'success_today' => RechargeOrder::query()
                    ->where('status', RechargeOrder::STATUS_PAID)
                    ->whereDate('paid_at', today())
                    ->count(),
                'total_amount_today' => (float) RechargeOrder::query()
                    ->where('status', RechargeOrder::STATUS_PAID)
                    ->whereDate('paid_at', today())
                    ->sum('total_amount'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function depositDetail(RechargeOrder $deposit): array
    {
        $deposit->load(['user.wallet', 'rechargeMethod', 'bankAccount']);

        $walletTransaction = WalletTransaction::query()
            ->where('reference_type', RechargeOrder::class)
            ->where('reference_id', $deposit->id)
            ->latest('id')
            ->first();

        $paymentTransactions = PaymentTransaction::query()
            ->where('user_id', $deposit->user_id)
            ->when(filled($deposit->transfer_content), fn (Builder $query) => $query->where('content', 'like', '%'.$deposit->transfer_content.'%'))
            ->latest('id')
            ->limit(10)
            ->get();

        return [
            'deposit' => $deposit,
            'wallet_transaction' => $walletTransaction,
            'payment_transactions' => $paymentTransactions,
            'logs' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function depositQuery(array $filters): Builder
    {
        return RechargeOrder::query()
            ->with(['user.wallet', 'rechargeMethod', 'bankAccount'])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            if (is_numeric($search)) {
                                $userQuery->orWhere('id', (int) $search);
                            }

                            $userQuery
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['method'] ?? null), function (Builder $query) use ($filters): void {
                $query->where(function (Builder $builder) use ($filters): void {
                    $builder
                        ->where('method', $filters['method'])
                        ->orWhere('method_label', 'like', '%'.$filters['method'].'%');
                });
            })
            ->when(filled($filters['user_id'] ?? null), fn (Builder $query) => $query->where('user_id', $filters['user_id']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id');
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
