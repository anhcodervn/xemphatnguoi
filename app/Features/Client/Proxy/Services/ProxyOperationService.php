<?php

namespace App\Features\Client\Proxy\Services;

use App\Exceptions\ApiException;
use App\Features\Client\Proxy\Resources\ProxyOrderResource;
use App\Features\Client\Proxy\Resources\UserProxyResource;
use App\Features\Client\Wallet\Services\WalletService;
use App\Jobs\ProcessProxyOperationJob;
use App\Models\ProxyCategory;
use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\UserProxy;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProxyOperationService
{
    private const CHANGE_FEE = 1000.0;

    public function __construct(private readonly WalletService $walletService) {}

    /**
     * @param  array{idempotency_key: string}  $payload
     * @return array{order: array<string, mixed>, proxy?: array<string, mixed>}
     */
    public function change(User $user, UserProxy $proxy, array $payload, bool $synchronous = false): array
    {
        return $this->runIdempotent($user, $proxy, ProxyOrder::TYPE_CHANGE, 0, $payload['idempotency_key'], function () use ($user, $proxy, $payload): ProxyOrder {
            return DB::transaction(function () use ($user, $proxy, $payload): ProxyOrder {
                $existingOrder = $this->idempotentOrder($user, $payload['idempotency_key']);

                if ($existingOrder instanceof ProxyOrder) {
                    return $this->ensureSameOperation($existingOrder, ProxyOrder::TYPE_CHANGE, $proxy, 0);
                }

                $lockedProxy = $this->lockOwnedProxy($user, $proxy);
                [$product, $provider] = $this->validateProxy($lockedProxy, [UserProxy::STATUS_ACTIVE]);
                $this->ensureChangeIsSupported($product);
                $this->ensureNoPendingOperation($lockedProxy);
                $fee = $this->money(self::CHANGE_FEE);
                $order = $this->createOperationOrder(
                    user: $user,
                    proxy: $lockedProxy,
                    product: $product,
                    provider: $provider,
                    type: ProxyOrder::TYPE_CHANGE,
                    durationDays: 0,
                    unitPrice: $fee,
                    totalAmount: $fee,
                    idempotencyKey: $payload['idempotency_key'],
                );

                $this->walletService->debit(
                    user: $user,
                    amount: self::CHANGE_FEE,
                    referenceType: ProxyOrder::class,
                    referenceId: $order->id,
                    description: "Phí đổi proxy cho đơn {$order->order_code}.",
                );

                $lockedProxy->forceFill([
                    'status' => UserProxy::STATUS_CHANGING,
                ])->save();

                return $order;
            });
        }, $synchronous);
    }

    /**
     * @param  array{duration_days: int, idempotency_key: string}  $payload
     * @return array{order: array<string, mixed>, proxy?: array<string, mixed>}
     */
    public function renew(User $user, UserProxy $proxy, array $payload, bool $synchronous = false): array
    {
        return $this->runIdempotent($user, $proxy, ProxyOrder::TYPE_RENEW, $payload['duration_days'], $payload['idempotency_key'], function () use ($user, $proxy, $payload): ProxyOrder {
            return DB::transaction(function () use ($user, $proxy, $payload): ProxyOrder {
                $existingOrder = $this->idempotentOrder($user, $payload['idempotency_key']);

                if ($existingOrder instanceof ProxyOrder) {
                    return $this->ensureSameOperation($existingOrder, ProxyOrder::TYPE_RENEW, $proxy, $payload['duration_days']);
                }

                $lockedProxy = $this->lockOwnedProxy($user, $proxy);
                [$product, $provider] = $this->validateProxy($lockedProxy, [UserProxy::STATUS_ACTIVE, UserProxy::STATUS_EXPIRED]);
                $this->ensureNoPendingOperation($lockedProxy);
                $durationDays = $payload['duration_days'];
                $unitPrice = $this->money((float) $product->selling_price);
                $totalAmount = $this->money((float) $unitPrice * $durationDays);
                $order = $this->createOperationOrder(
                    user: $user,
                    proxy: $lockedProxy,
                    product: $product,
                    provider: $provider,
                    type: ProxyOrder::TYPE_RENEW,
                    durationDays: $durationDays,
                    unitPrice: $unitPrice,
                    totalAmount: $totalAmount,
                    idempotencyKey: $payload['idempotency_key'],
                );

                $this->walletService->debit(
                    user: $user,
                    amount: (float) $totalAmount,
                    referenceType: ProxyOrder::class,
                    referenceId: $order->id,
                    description: "Thanh toán gia hạn proxy cho đơn {$order->order_code}.",
                );

                return $order;
            });
        }, $synchronous);
    }

    private function lockOwnedProxy(User $user, UserProxy $proxy): UserProxy
    {
        return UserProxy::query()
            ->with(['product.category', 'provider'])
            ->whereBelongsTo($user)
            ->lockForUpdate()
            ->findOrFail($proxy->id);
    }

    /**
     * @param  list<string>  $allowedStatuses
     * @return array{ProxyProduct, ProxyProvider}
     */
    private function validateProxy(UserProxy $proxy, array $allowedStatuses): array
    {
        if (! in_array($proxy->status, $allowedStatuses, true)) {
            $this->fail('proxy', 'Proxy hiện không thể thực hiện thao tác này.');
        }

        $product = $proxy->product;
        $category = $product?->category;
        $provider = $proxy->provider;

        if (! $product instanceof ProxyProduct || ! $product->is_active) {
            $this->fail('proxy', 'Sản phẩm của proxy hiện không hoạt động.');
        }

        if (! $category instanceof ProxyCategory || ! $category->is_active) {
            $this->fail('proxy', 'Danh mục của proxy hiện không hoạt động.');
        }

        if (! $provider instanceof ProxyProvider || ! $provider->is_active) {
            $this->fail('proxy', 'Nhà cung cấp của proxy hiện không hoạt động.');
        }

        if ($provider->order_method !== ProxyProvider::ORDER_METHOD_AUTOMATIC) {
            $this->fail('proxy', 'Nhà cung cấp chưa hỗ trợ xử lý tự động.');
        }

        if ($provider->driver !== ProxyProvider::DRIVER_PROXY_VN) {
            throw new ApiException('Nhà cung cấp chưa hỗ trợ thao tác proxy tự động.', 503);
        }

        $isRotating = ($product->settings['proxy_type'] ?? null) === 'rotating';

        if (blank($proxy->provider_proxy_id) || (! $isRotating && blank($product->provider_product_code))) {
            $this->fail('proxy', 'Proxy chưa có đủ mã xử lý từ nhà cung cấp.');
        }

        return [$product, $provider];
    }

    private function ensureChangeIsSupported(ProxyProduct $product): void
    {
        if (($product->settings['proxy_type'] ?? null) === 'rotating') {
            $this->fail('proxy', 'Không hỗ trợ đổi proxy cho sản phẩm xoay. Vui lòng mua lại proxy mới.');
        }
    }

    private function ensureNoPendingOperation(UserProxy $proxy): void
    {
        $hasPendingOperation = ProxyOrder::query()
            ->where('target_user_proxy_id', $proxy->id)
            ->whereIn('status', [ProxyOrder::STATUS_PENDING, ProxyOrder::STATUS_PROCESSING])
            ->exists();

        if ($hasPendingOperation) {
            throw new ApiException('Proxy đang có một yêu cầu khác được xử lý.', 409);
        }
    }

    private function createOperationOrder(
        User $user,
        UserProxy $proxy,
        ProxyProduct $product,
        ProxyProvider $provider,
        string $type,
        int $durationDays,
        string $unitPrice,
        string $totalAmount,
        string $idempotencyKey,
    ): ProxyOrder {
        return ProxyOrder::query()->create([
            'user_id' => $user->id,
            'proxy_product_id' => $product->id,
            'proxy_provider_id' => $provider->id,
            'target_user_proxy_id' => $proxy->id,
            'order_code' => 'PXY-'.Str::upper((string) Str::ulid()),
            'idempotency_key' => $idempotencyKey,
            'type' => $type,
            'status' => ProxyOrder::STATUS_PENDING,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'quantity' => 1,
            'duration_days' => $durationDays,
            'country_code' => $proxy->country_code,
            'protocol' => $proxy->protocol,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'ordered_at' => now(),
        ]);
    }

    private function idempotentOrder(User $user, string $idempotencyKey): ?ProxyOrder
    {
        return ProxyOrder::query()
            ->whereBelongsTo($user)
            ->where('idempotency_key', $idempotencyKey)
            ->with('product:id,code,name')
            ->first();
    }

    private function ensureSameOperation(ProxyOrder $order, string $type, UserProxy $proxy, int $durationDays): ProxyOrder
    {
        if (
            $order->type !== $type
            || $order->target_user_proxy_id !== $proxy->id
            || $order->duration_days !== $durationDays
        ) {
            throw new ApiException('Mã chống trùng đã được sử dụng cho một thao tác khác.', 409);
        }

        return $order;
    }

    /**
     * @param  callable(): ProxyOrder  $operation
     * @return array{order: array<string, mixed>, proxy?: array<string, mixed>}
     */
    private function runIdempotent(
        User $user,
        UserProxy $proxy,
        string $type,
        int $durationDays,
        string $idempotencyKey,
        callable $operation,
        bool $synchronous,
    ): array {
        try {
            $order = $operation();
        } catch (UniqueConstraintViolationException $exception) {
            $order = $this->idempotentOrder($user, $idempotencyKey);

            if (! $order instanceof ProxyOrder) {
                throw $exception;
            }
        }

        $order = $this->ensureSameOperation($order, $type, $proxy, $durationDays);

        if ($synchronous) {
            return $this->processSynchronously($user, $proxy, $order);
        }

        if ($order->status === ProxyOrder::STATUS_PENDING) {
            ProcessProxyOperationJob::dispatch($order->id)->afterCommit();
        }

        return [
            'order' => (new ProxyOrderResource($order->loadMissing('product:id,code,name')))->resolve(),
        ];
    }

    /**
     * API v1 chờ provider xử lý xong và trả dữ liệu cuối cùng ngay trong request.
     * Luồng web không gọi hàm này mà tiếp tục xử lý bằng queue.
     *
     * @return array{order: array<string, mixed>, proxy: array<string, mixed>}
     */
    private function processSynchronously(User $user, UserProxy $proxy, ProxyOrder $order): array
    {
        if ($order->status === ProxyOrder::STATUS_PENDING) {
            ProcessProxyOperationJob::dispatchSync($order->id);
        }

        $processedOrder = ProxyOrder::query()
            ->whereBelongsTo($user)
            ->with('product:id,code,name')
            ->findOrFail($order->id);

        if ($processedOrder->status !== ProxyOrder::STATUS_FULFILLED) {
            throw new ApiException(
                $processedOrder->error_message ?: 'Không thể hoàn tất thao tác proxy lúc này.',
                502,
            );
        }

        $processedProxy = UserProxy::query()
            ->whereBelongsTo($user)
            ->with(['product:id,code,name,settings', 'sourceOrder:id,order_code'])
            ->findOrFail($proxy->id);

        return [
            'order' => (new ProxyOrderResource($processedOrder))->resolve(),
            'proxy' => (new UserProxyResource($processedProxy))->resolve(),
        ];
    }

    private function money(float $amount): string
    {
        return number_format(round($amount, 2), 4, '.', '');
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
