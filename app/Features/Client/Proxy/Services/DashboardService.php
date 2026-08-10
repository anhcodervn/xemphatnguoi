<?php

namespace App\Features\Client\Proxy\Services;

use App\Features\Client\Notification\Services\ClientNotificationService;
use App\Features\Client\Proxy\Resources\DashboardProxyResource;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\ProxyOrder;
use App\Models\User;
use App\Models\UserProxy;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly ClientNotificationService $notificationService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        $now = now();
        $expiringAt = $now->copy()->addDays(3);
        $wallet = $this->walletService->getWalletInfo($user);
        $notificationData = $this->notificationService->dashboard($user, 4);

        $proxySummary = UserProxy::query()
            ->whereBelongsTo($user)
            ->selectRaw(
                'SUM(CASE WHEN status = ? AND (expires_at IS NULL OR expires_at > ?) THEN 1 ELSE 0 END) as active_proxies',
                [UserProxy::STATUS_ACTIVE, $now],
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? AND expires_at > ? AND expires_at <= ? THEN 1 ELSE 0 END) as expiring_proxies',
                [UserProxy::STATUS_ACTIVE, $now, $expiringAt],
            )
            ->first();

        $expiringProxies = UserProxy::query()
            ->whereBelongsTo($user)
            ->select([
                'id',
                'user_id',
                'proxy_product_id',
                'label',
                'status',
                'country_code',
                'protocol',
                'host',
                'port',
                'expires_at',
            ])
            ->with('product:id,code,name')
            ->where('status', UserProxy::STATUS_ACTIVE)
            ->whereBetween('expires_at', [$now, $expiringAt])
            ->orderBy('expires_at')
            ->limit(5)
            ->get();

        return [
            'summary' => [
                'balance' => (string) $wallet['balance'],
                'active_proxies' => (int) ($proxySummary?->active_proxies ?? 0),
                'expiring_proxies' => (int) ($proxySummary?->expiring_proxies ?? 0),
                'unread_notifications' => $notificationData['unread'],
            ],
            'expiring_proxies' => DashboardProxyResource::collection($expiringProxies)->resolve(),
            'notifications' => $notificationData['notifications'],
            'recent_activities' => $this->recentActivities($user, (int) $wallet['id']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivities(User $user, int $walletId): array
    {
        $orderActivities = ProxyOrder::query()
            ->whereBelongsTo($user)
            ->select([
                'id',
                'order_code',
                'status',
                'product_name',
                'quantity',
                'total_amount',
                'created_at',
            ])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (ProxyOrder $order): array => [
                'id' => "order-{$order->id}",
                'type' => 'order',
                'title' => "Đơn hàng #{$order->order_code} {$this->orderStatusLabel($order->status)}",
                'description' => "{$order->product_name} · {$order->quantity} proxy",
                'amount' => (string) $order->total_amount,
                'status' => $order->status,
                'occurred_at' => $order->created_at?->toISOString(),
                'redirect_url' => '/proxy-orders',
            ]);

        $walletActivities = WalletTransaction::query()
            ->where('wallet_id', $walletId)
            ->where('status', 'success')
            ->where(function (Builder $query): void {
                $query->whereNull('reference_type')->orWhere('reference_type', '!=', ProxyOrder::class);
            })
            ->select(['id', 'type', 'amount', 'description', 'status', 'created_at'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (WalletTransaction $transaction): array => [
                'id' => "wallet-{$transaction->id}",
                'type' => $transaction->type === 'credit' ? 'wallet_credit' : 'wallet',
                'title' => $transaction->type === 'credit' ? 'Nạp tiền thành công' : 'Biến động số dư ví',
                'description' => $transaction->description ?: 'Giao dịch ví đã hoàn tất.',
                'amount' => $this->signedWalletAmount($transaction),
                'status' => $transaction->status,
                'occurred_at' => $transaction->created_at?->toISOString(),
                'redirect_url' => '/wallet',
            ]);

        return $orderActivities
            ->concat($walletActivities)
            ->sortByDesc('occurred_at')
            ->take(5)
            ->values()
            ->all();
    }

    private function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'fulfilled' => 'đã hoàn thành',
            'failed' => 'thất bại',
            'refunded' => 'đã hoàn tiền',
            'processing' => 'đang xử lý',
            default => 'đang chờ',
        };
    }

    private function signedWalletAmount(WalletTransaction $transaction): string
    {
        $amount = abs((float) $transaction->amount);

        return number_format(in_array($transaction->type, ['debit', 'hold'], true) ? -$amount : $amount, 2, '.', '');
    }
}
