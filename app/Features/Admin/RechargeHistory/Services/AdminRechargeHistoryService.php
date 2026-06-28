<?php

namespace App\Features\Admin\RechargeHistory\Services;

use App\Features\Admin\RechargeHistory\Resources\AdminRechargeHistoryResource;
use App\Features\Client\Wallet\Services\WalletDepositService;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Builder;

class AdminRechargeHistoryService
{
    public function __construct(
        private readonly WalletDepositService $walletDepositService,
    ) {}

    public function paginate(array $filters = []): array
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);
        $query = $this->buildQuery($filters);
        $transactions = (clone $query)
            ->with('user:id,username,full_name,email,phone')
            ->latest('id')
            ->paginate($perPage);

        $transactions->setCollection(
            $transactions->getCollection()->map(
                fn (PaymentTransaction $transaction): PaymentTransaction => $this->walletDepositService->syncTransactionStatus($transaction)
            )
        );

        return [
            'data' => AdminRechargeHistoryResource::collection($transactions->getCollection())->resolve(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
            'stats' => [
                'total_amount' => (float) (clone $query)->sum('amount'),
                'today_count' => (int) (clone $query)->whereDate('created_at', now()->toDateString())->count(),
                'pending_count' => (int) (clone $query)->where('status', 'pending')->count(),
                'matched_count' => (int) (clone $query)->where('status', 'matched')->count(),
                'success_count' => (int) (clone $query)->where('status', 'success')->count(),
                'failed_count' => (int) (clone $query)->where('status', 'failed')->count(),
            ],
        ];
    }

    private function buildQuery(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $userId = $filters['user_id'] ?? null;
        $status = (string) ($filters['status'] ?? '');
        $bankCode = trim((string) ($filters['bank_code'] ?? ''));
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        return PaymentTransaction::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('transaction_code', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%")
                        ->orWhere('bank_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('full_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($userId !== null && $userId !== '', fn (Builder $query) => $query->where('user_id', (int) $userId))
            ->when($bankCode !== '', fn (Builder $query) => $query->where('bank_code', 'like', "%{$bankCode}%"))
            ->when($status !== '', function (Builder $query) use ($status): void {
                match ($status) {
                    'processing' => $query->where('status', 'matched'),
                    'paid' => $query->where('status', 'success'),
                    'expired' => $query->where('status', 'cancelled')->where('raw_data->cancel_reason', 'expired'),
                    default => $query->where('status', $status),
                };
            })
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('created_at', '<=', $dateTo));
    }
}
