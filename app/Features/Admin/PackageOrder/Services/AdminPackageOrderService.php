<?php

namespace App\Features\Admin\PackageOrder\Services;

use App\Features\Admin\PackageOrder\Resources\AdminPackageOrderResource;
use App\Models\PackageOrder;
use App\Models\PaymentTransaction;
use App\Models\UserSubscription;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\SubscriptionStatus;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminPackageOrderService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginateOrders(array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $orders = $this->orderQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => AdminPackageOrderResource::collection($orders->getCollection())->resolve(),
            'meta' => $this->paginationMeta($orders),
            'stats' => [
                'total_orders' => PackageOrder::query()->count(),
                'revenue' => (float) PackageOrder::query()->where('payment_status', PaymentStatus::Paid)->sum('final_amount'),
                'today_orders' => PackageOrder::query()->whereDate('created_at', today())->count(),
                'active_packages' => UserSubscription::query()
                    ->where('status', SubscriptionStatus::Active)
                    ->whereDate('expires_at', '>=', today())
                    ->count(),
                'expiring_soon' => UserSubscription::query()
                    ->where('status', SubscriptionStatus::Active)
                    ->whereDate('expires_at', '>=', today())
                    ->whereDate('expires_at', '<=', today()->copy()->addDays(7))
                    ->count(),
                'expired_packages' => UserSubscription::query()
                    ->where(function (Builder $query): void {
                        $query
                            ->where('status', SubscriptionStatus::Expired)
                            ->orWhereDate('expires_at', '<', today());
                    })
                    ->count(),
                'renewal_rate' => $this->renewalRate(),
                'monthly_revenue' => (float) PackageOrder::query()
                    ->where('payment_status', PaymentStatus::Paid)
                    ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('final_amount'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function orderDetail(PackageOrder $order): array
    {
        $order->load(['user.wallet', 'package', 'subscription', 'sourceSubscription']);

        $walletTransaction = WalletTransaction::query()
            ->where('reference_type', PackageOrder::class)
            ->where('reference_id', $order->id)
            ->latest('id')
            ->first();

        $paymentTransaction = PaymentTransaction::query()
            ->where('user_id', $order->user_id)
            ->where('amount', $order->final_amount)
            ->latest('id')
            ->first();

        return [
            'order' => $order,
            'wallet_transaction' => $walletTransaction,
            'payment_transaction' => $paymentTransaction,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function orderQuery(array $filters): Builder
    {
        return PackageOrder::query()
            ->with(['user', 'package', 'subscription'])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('package', fn (Builder $packageQuery) => $packageQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($filters['user_id'] ?? null), fn (Builder $query) => $query->where('user_id', $filters['user_id']))
            ->when(filled($filters['package_id'] ?? null), fn (Builder $query) => $query->where('package_id', $filters['package_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
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

    private function renewalRate(): float
    {
        $paidOrders = PackageOrder::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->count();

        if ($paidOrders === 0) {
            return 0.0;
        }

        $renewalOrders = PackageOrder::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereNotNull('source_subscription_id')
            ->count();

        return round(($renewalOrders / $paidOrders) * 100, 2);
    }
}
