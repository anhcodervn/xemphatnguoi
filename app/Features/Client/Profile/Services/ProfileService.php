<?php

namespace App\Features\Client\Profile\Services;

use App\Features\Client\Profile\Resources\UserLogResource;
use App\Features\Client\Profile\Resources\WalletTransactionResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProfileService
{
    public function profile(User $user): User
    {
        $profile = $user->fresh() ?? $user;
        $profile->loadMissing('userSubscriptions.package');

        return $profile;
    }

    /**
     * @param  array{search?:string,action?:string,per_page?:int}  $filters
     * @return array<string, mixed>
     */
    public function userLogs(User $user, array $filters = []): array
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));
        $search = trim((string) ($filters['search'] ?? ''));
        $action = (string) ($filters['action'] ?? 'all');

        $logs = $user->userLogs()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%")
                        ->orWhere('user_agent', 'like', "%{$search}%");
                });
            })
            ->when($action !== '' && $action !== 'all', fn (Builder $query) => $query->where('action', $action))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => UserLogResource::collection($logs->getCollection())->resolve(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ];
    }

    /**
     * @param  array{search?:string,type?:string,per_page?:int}  $filters
     * @return array<string, mixed>
     */
    public function walletTransactions(User $user, array $filters = []): array
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));
        $search = trim((string) ($filters['search'] ?? ''));
        $type = (string) ($filters['type'] ?? 'all');
        $wallet = $user->wallet;

        if ($wallet === null) {
            return [
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ],
            ];
        }

        $transactions = $wallet->transactions()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('reference_type', 'like', "%{$search}%");
                });
            })
            ->when($type !== '' && $type !== 'all', fn (Builder $query) => $this->applyWalletTypeFilter($query, $type))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => WalletTransactionResource::collection($transactions->getCollection())->resolve(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ];
    }

    private function applyWalletTypeFilter(Builder $query, string $type): void
    {
        match ($type) {
            'recharge' => $query->where('type', 'credit'),
            'deduct' => $query->whereIn('type', ['debit', 'hold']),
            'refund' => $query->where('type', 'refund'),
            'bonus' => $query->where(function (Builder $builder): void {
                $builder
                    ->where('type', 'adjustment')
                    ->orWhere(function (Builder $creditQuery): void {
                        $creditQuery
                            ->where('type', 'credit')
                            ->where('description', 'like', '%bonus%');
                    });
            }),
            default => null,
        };
    }
}
