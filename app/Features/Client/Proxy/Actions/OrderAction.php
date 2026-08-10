<?php

namespace App\Features\Client\Proxy\Actions;

use App\Features\Client\Proxy\Services\OrderService;
use App\Features\Client\Proxy\Services\ProxyService;
use App\Models\User;
use Illuminate\Support\Str;

class OrderAction
{
    public function __construct(
        private readonly ProxyService $proxyService,
        private readonly OrderService $orderService,
    ) {}

    /**
     * @param  array{product_code: string, quantity: int, duration_days: int, protocol: string}  $payload
     * @return array{order: array<string, mixed>, proxies: list<array<string, mixed>>}
     */
    public function handle(User $user, array $payload): array
    {
        $payload['idempotency_key'] = Str::uuid()->toString();
        $product = $this->proxyService->getInfoProduct($payload['product_code']);

        return $this->orderService->order($user, $payload, $product);
    }
}
