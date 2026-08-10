<?php

namespace App\Jobs;

use App\Events\ProxyOrderUpdated;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\UserProxy;
use App\Service\Proxy\ProxyVn;
use App\Service\Reporting\ProxySalesReporter;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ProcessProxyOperationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 45;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 300;

    public function __construct(public readonly int $proxyOrderId)
    {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return (string) $this->proxyOrderId;
    }

    public function handle(WalletService $walletService, ProxySalesReporter $proxySalesReporter): void
    {
        $order = $this->claimOrder();

        if (! $order instanceof ProxyOrder) {
            return;
        }

        $this->broadcast($order);

        try {
            $provider = $order->provider;
            $product = $order->product;
            $targetProxy = $order->targetProxy;

            if (
                ! $provider instanceof ProxyProvider
                || ! $provider->is_active
                || $provider->driver !== ProxyProvider::DRIVER_PROXY_VN
                || ! $product instanceof ProxyProduct
                || ! $targetProxy instanceof UserProxy
            ) {
                throw new RuntimeException('Proxy provider operation is unavailable.');
            }

            $api = new ProxyVn($provider);

            match ($order->type) {
                ProxyOrder::TYPE_CHANGE => $this->fulfillChange($order, $api->changeProxy($targetProxy, $product), $proxySalesReporter),
                ProxyOrder::TYPE_RENEW => $this->fulfillRenew($order, $api->renewProxy($targetProxy, $product, $order->duration_days), $proxySalesReporter),
                default => throw new RuntimeException('Unsupported proxy operation.'),
            };
        } catch (Throwable $exception) {
            report($exception);
            $this->refund($walletService, 'Nhà cung cấp không thể hoàn tất yêu cầu proxy lúc này.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception instanceof Throwable) {
            report($exception);
        }

        $this->refund(
            app(WalletService::class),
            'Tác vụ proxy không thể hoàn tất. Số tiền đã được hoàn lại vào ví.',
        );
    }

    private function claimOrder(): ?ProxyOrder
    {
        $claimed = ProxyOrder::query()
            ->whereKey($this->proxyOrderId)
            ->where('status', ProxyOrder::STATUS_PENDING)
            ->whereIn('type', [ProxyOrder::TYPE_CHANGE, ProxyOrder::TYPE_RENEW])
            ->update(['status' => ProxyOrder::STATUS_PROCESSING]);

        if ($claimed !== 1) {
            return null;
        }

        return ProxyOrder::query()
            ->with(['provider', 'product', 'targetProxy'])
            ->find($this->proxyOrderId);
    }

    /**
     * @param  array{status: true, message: string, proxy: list<array<string, mixed>>}  $providerResult
     */
    private function fulfillChange(ProxyOrder $currentOrder, array $providerResult, ProxySalesReporter $proxySalesReporter): void
    {
        $providerProxy = $providerResult['proxy'][0] ?? null;

        if (! is_array($providerProxy)) {
            throw new RuntimeException('Proxy provider returned invalid change data.');
        }

        $order = DB::transaction(function () use ($currentOrder, $providerProxy): ?ProxyOrder {
            $order = ProxyOrder::query()->whereKey($currentOrder->id)->lockForUpdate()->first();

            if (! $order instanceof ProxyOrder || $order->status !== ProxyOrder::STATUS_PROCESSING) {
                return null;
            }

            $targetProxy = $order->targetProxy()->lockForUpdate()->first();
            $providerProxyId = trim((string) ($providerProxy['idproxy'] ?? ''));
            $host = trim((string) ($providerProxy['ip'] ?? ''));
            $port = (int) ($providerProxy['port'] ?? 0);

            if (! $targetProxy instanceof UserProxy || $providerProxyId === '' || $host === '' || $port < 1 || $port > 65535) {
                throw new RuntimeException('Proxy provider returned invalid change data.');
            }

            $targetProxy->forceFill([
                'provider_proxy_id' => $providerProxyId,
                'provider_code' => $providerProxyId,
                'status' => UserProxy::STATUS_ACTIVE,
                'protocol' => mb_strtolower((string) ($providerProxy['type'] ?? $targetProxy->protocol)),
                'host' => $host,
                'port' => $port,
                'username' => filled($providerProxy['user'] ?? null) ? (string) $providerProxy['user'] : null,
                'password' => filled($providerProxy['password'] ?? null) ? (string) $providerProxy['password'] : null,
                'response' => $providerProxy,
                'error_message' => null,
                'expires_at' => is_numeric($providerProxy['time'] ?? null)
                    ? now()->createFromTimestamp((int) $providerProxy['time'])
                    : $targetProxy->expires_at,
                'last_changed_at' => now(),
            ])->save();

            $order->forceFill([
                'status' => ProxyOrder::STATUS_FULFILLED,
                'external_order_id' => $providerProxyId,
                'error_code' => null,
                'error_message' => null,
                'fulfilled_at' => now(),
            ])->save();

            return $order->refresh();
        });

        if ($order instanceof ProxyOrder) {
            $this->broadcast($order);
            $proxySalesReporter->reportFulfilled($order);
        }
    }

    /**
     * @param  array{status: true, message: string, expires_at: ?int}  $providerResult
     */
    private function fulfillRenew(ProxyOrder $currentOrder, array $providerResult, ProxySalesReporter $proxySalesReporter): void
    {
        $order = DB::transaction(function () use ($currentOrder, $providerResult): ?ProxyOrder {
            $order = ProxyOrder::query()->whereKey($currentOrder->id)->lockForUpdate()->first();

            if (! $order instanceof ProxyOrder || $order->status !== ProxyOrder::STATUS_PROCESSING) {
                return null;
            }

            $targetProxy = $order->targetProxy()->lockForUpdate()->first();

            if (! $targetProxy instanceof UserProxy) {
                throw new RuntimeException('Target proxy is unavailable.');
            }

            if ($providerResult['status'] !== true) {
                throw new RuntimeException('Proxy renewal result is invalid.');
            }

            $expiresAt = ($targetProxy->expires_at?->copy() ?? now())
                ->addDays($order->duration_days);

            $targetProxy->forceFill([
                'status' => $expiresAt->isFuture() ? UserProxy::STATUS_ACTIVE : UserProxy::STATUS_EXPIRED,
                'error_message' => null,
                'expires_at' => $expiresAt,
            ])->save();

            $order->forceFill([
                'status' => ProxyOrder::STATUS_FULFILLED,
                'error_code' => null,
                'error_message' => null,
                'fulfilled_at' => now(),
            ])->save();

            return $order->refresh();
        });

        if ($order instanceof ProxyOrder) {
            $this->broadcast($order);
            $proxySalesReporter->reportFulfilled($order);
        }
    }

    private function refund(WalletService $walletService, string $message): void
    {
        $order = DB::transaction(function () use ($walletService, $message): ?ProxyOrder {
            $order = ProxyOrder::query()
                ->with('user')
                ->whereKey($this->proxyOrderId)
                ->lockForUpdate()
                ->first();

            if (
                ! $order instanceof ProxyOrder
                || in_array($order->status, [ProxyOrder::STATUS_FULFILLED, ProxyOrder::STATUS_REFUNDED], true)
            ) {
                return null;
            }

            if ($order->user instanceof User && (float) $order->total_amount > 0) {
                $walletService->credit(
                    user: $order->user,
                    amount: (float) $order->total_amount,
                    referenceType: ProxyOrder::class,
                    referenceId: $order->id,
                    description: "Hoàn tiền đơn proxy {$order->order_code}.",
                );
            }

            if ($order->type === ProxyOrder::TYPE_CHANGE) {
                $order->targetProxy()->where('status', UserProxy::STATUS_CHANGING)->update([
                    'status' => UserProxy::STATUS_ACTIVE,
                ]);
            }

            $order->forceFill([
                'status' => ProxyOrder::STATUS_REFUNDED,
                'error_code' => 'provider_operation_failed',
                'error_message' => $message,
            ])->save();

            return $order->refresh();
        });

        if ($order instanceof ProxyOrder) {
            $this->broadcast($order);
        }
    }

    private function broadcast(ProxyOrder $order): void
    {
        ProxyOrderUpdated::dispatch(
            userId: $order->user_id,
            orderId: $order->id,
            targetProxyId: $order->target_user_proxy_id,
            type: $order->type,
            status: $order->status,
            errorMessage: $order->error_message,
        );
    }
}
