<?php

namespace App\Features\Client\Proxy\Services;

use App\Exceptions\ApiException;
use App\Features\Client\Proxy\Resources\ProxyOrderResource;
use App\Features\Client\Proxy\Resources\UserProxyResource;
use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\User;
use App\Models\UserProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProxyService
{
    /**
     * Lấy danh sách đơn proxy thuộc riêng người dùng đang đăng nhập.
     *
     * @param  array<string, mixed>  $filters
     * @return array{orders: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function orders(User $user, array $filters = []): array
    {
        $orders = ProxyOrder::query()
            ->whereBelongsTo($user)
            ->with('product:id,code,name')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $searchQuery) use ($search): void {
                    if (is_numeric($search)) {
                        $searchQuery->orWhere('id', (int) $search);
                    }

                    $searchQuery
                        ->orWhere('order_code', 'like', "%{$search}%")
                        ->orWhere('product_code', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['type'] ?? null), fn (Builder $query) => $query->where('type', $filters['type']))
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();

        return [
            'orders' => ProxyOrderResource::collection($orders->getCollection())->resolve(),
            'meta' => $this->paginationMeta($orders),
        ];
    }

    /** @return array<string, mixed> */
    public function order(User $user, int $orderId): array
    {
        $order = ProxyOrder::query()
            ->whereBelongsTo($user)
            ->with('product:id,code,name')
            ->findOrFail($orderId);

        return (new ProxyOrderResource($order))->resolve();
    }

    /**
     * Lấy danh sách proxy đã cấp thuộc riêng người dùng đang đăng nhập.
     *
     * Các trường nhạy cảm của provider không được đưa vào resource trả về.
     *
     * @param  array<string, mixed>  $filters
     * @return array{proxies: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function proxies(User $user, array $filters = []): array
    {
        $query = UserProxy::query()
            ->whereBelongsTo($user)
            ->with(['product:id,code,name,settings', 'sourceOrder:id,order_code'])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $searchQuery) use ($search): void {
                    if (is_numeric($search)) {
                        $searchQuery->orWhere('id', (int) $search);
                    }

                    $searchQuery
                        ->orWhere('label', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $productQuery) => $productQuery
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('sourceOrder', fn (Builder $orderQuery) => $orderQuery
                            ->where('order_code', 'like', "%{$search}%"));
                });
            })
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['protocol'] ?? null), fn (Builder $query) => $query->where('protocol', Str::lower((string) $filters['protocol'])))
            ->when(filled($filters['proxy_type'] ?? null), function (Builder $query) use ($filters): void {
                $proxyType = (string) $filters['proxy_type'];

                $query->whereHas('product', function (Builder $productQuery) use ($proxyType): void {
                    if ($proxyType === 'rotating') {
                        $productQuery->where('settings->proxy_type', 'rotating');

                        return;
                    }

                    $productQuery->where(function (Builder $typeQuery): void {
                        $typeQuery
                            ->where('settings->proxy_type', 'static')
                            ->orWhereNull('settings->proxy_type');
                    });
                });
            })
            ->when(filled($filters['country_code'] ?? null), fn (Builder $query) => $query->where('country_code', Str::upper((string) $filters['country_code'])));

        match ($filters['sort'] ?? 'latest') {
            'oldest' => $query->oldest('id'),
            'expiry' => $query->orderBy('expires_at')->latest('id'),
            default => $query->latest('id'),
        };

        $proxies = $query
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();

        return [
            'proxies' => UserProxyResource::collection($proxies->getCollection())->resolve(),
            'meta' => $this->paginationMeta($proxies),
        ];
    }

    /** @return array<string, mixed> */
    public function proxy(User $user, int $proxyId): array
    {
        $proxy = UserProxy::query()
            ->whereBelongsTo($user)
            ->with(['product:id,code,name,settings', 'sourceOrder:id,order_code'])
            ->findOrFail($proxyId);

        return (new UserProxyResource($proxy))->resolve();
    }

    public function isProductAvailable(string $productCode): bool
    {
        $product = $this->getInfoProduct($productCode);

        return $product instanceof ProxyProduct && $product->is_active;
    }

    public function getInfoProduct(string $productCode): ProxyProduct
    {
        $product = ProxyProduct::query()
            ->with(['category', 'provider'])
            ->where('code', $productCode)
            ->first();

        if (! $product instanceof ProxyProduct) {
            throw new ApiException('Sản phẩm proxy đã ngưng bán.', 404);
        }

        return $product;
    }

    /** @return array{current_page: int, last_page: int, per_page: int, total: int} */
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
