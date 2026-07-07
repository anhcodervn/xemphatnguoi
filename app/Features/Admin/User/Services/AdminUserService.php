<?php

namespace App\Features\Admin\User\Services;

use App\Features\Admin\PackageOrder\Resources\AdminPackageOrderResource;
use App\Features\Admin\User\Resources\AdminUserResource;
use App\Features\Admin\WalletTransaction\Resources\AdminWalletTransactionResource;
use App\Models\CaptchaTask;
use App\Models\PackageOrder;
use App\Models\User;
use App\Models\UserLog;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminUserService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginateUsers(array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $users = $this->userQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => AdminUserResource::collection($users->getCollection())->resolve(),
            'meta' => $this->paginationMeta($users),
            'stats' => [
                'total_users' => User::query()->count(),
                'new_today' => User::query()->whereDate('created_at', today())->count(),
                'active_users' => User::query()->where('status', 'active')->count(),
                'blocked_users' => User::query()->where('status', 'banned')->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userDetail(User $user): array
    {
        $user = User::query()
            ->with([
                'wallet',
                'userSubscriptions' => function (HasMany $query): void {
                    $query->where('status', SubscriptionStatus::Active->value)
                        ->with('package')
                        ->orderByDesc('expires_at');
                },
            ])
            ->withCount(['packageOrders', 'captchaTasks', 'apiKeys'])
            ->findOrFail($user->id);

        $wallet = $user->wallet;
        $currentSubscription = $user->userSubscriptions->first();
        $solvedTaskCount = (int) CaptchaTask::query()
            ->where('user_id', $user->id)
            ->where('status', CaptchaTask::STATUS_SOLVED)
            ->count();

        return [
            'user' => $user,
            'wallet' => $wallet,
            'current_subscription' => $currentSubscription,
            'stats' => [
                'total_spent' => $wallet instanceof Wallet ? (float) $wallet->total_spent : 0.0,
                'package_order_count' => $user->package_orders_count,
                'captcha_task_count' => $user->captcha_tasks_count,
                'api_key_count' => $user->api_keys_count,
                'solved_task_count' => $solvedTaskCount,
            ],
            'latest_login' => [
                'at' => $user->last_login_at?->toISOString(),
                'ip' => $user->last_login_ip,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginateUserWalletTransactions(User $user, array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $wallet = $user->wallet()->first();

        if (! $wallet instanceof Wallet) {
            return $this->emptyPagination($perPage);
        }

        $transactions = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->with(['wallet.user'])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('reference_type', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['type'] ?? null), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => AdminWalletTransactionResource::collection($transactions->getCollection())->resolve(),
            'meta' => $this->paginationMeta($transactions),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginateUserPackageOrders(User $user, array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $orders = PackageOrder::query()
            ->where('user_id', $user->id)
            ->with(['user', 'package', 'subscription'])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where('order_code', 'like', "%{$search}%");
            })
            ->when(filled($filters['package_id'] ?? null), fn (Builder $query) => $query->where('package_id', $filters['package_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => AdminPackageOrderResource::collection($orders->getCollection())->resolve(),
            'meta' => $this->paginationMeta($orders),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginateUserLogs(User $user, array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $logs = UserLog::query()
            ->where('user_id', $user->id)
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('action', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['action'] ?? null), fn (Builder $query) => $query->where('action', $filters['action']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => $logs->getCollection()->map(fn (UserLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'ip' => $log->ip,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at?->toISOString(),
            ])->all(),
            'meta' => $this->paginationMeta($logs),
        ];
    }

    private function userQuery(array $filters): Builder
    {
        return User::query()
            ->with([
                'wallet',
                'userSubscriptions' => function (HasMany $query): void {
                    $query->where('status', SubscriptionStatus::Active->value)
                        ->with('package')
                        ->orderByDesc('expires_at');
                },
            ])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    if (is_numeric($search)) {
                        $builder->orWhere('id', (int) $search);
                    }

                    $builder
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), function (Builder $query) use ($filters): void {
                $status = $filters['status'] === 'blocked' ? 'banned' : $filters['status'];
                $query->where('status', $status);
            })
            ->when(filled($filters['role'] ?? null), fn (Builder $query) => $query->where('role', $filters['role']))
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

    /**
     * @return array<string, mixed>
     */
    private function emptyPagination(int $perPage): array
    {
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
}
