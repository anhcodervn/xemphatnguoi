<?php

namespace App\Features\Client\Proxy\Actions;

use App\Features\Client\Proxy\Services\ProxyOperationService;
use App\Models\User;
use App\Models\UserProxy;
use Illuminate\Support\Str;

class RenewProxyAction
{
    public function __construct(private readonly ProxyOperationService $proxyOperationService) {}

    /**
     * @param  array{duration_days: int}  $payload
     * @return array<string, mixed>
     */
    public function handle(User $user, int $proxyId, array $payload): array
    {
        return $this->execute($user, $proxyId, $payload, false);
    }

    /**
     * @param  array{duration_days: int}  $payload
     * @return array<string, mixed>
     */
    public function handleSynchronously(User $user, int $proxyId, array $payload): array
    {
        return $this->execute($user, $proxyId, $payload, true);
    }

    /**
     * @param  array{duration_days: int}  $payload
     * @return array<string, mixed>
     */
    private function execute(User $user, int $proxyId, array $payload, bool $synchronous): array
    {
        $payload['idempotency_key'] = Str::uuid()->toString();
        $proxy = UserProxy::query()
            ->whereBelongsTo($user)
            ->findOrFail($proxyId);

        if ($synchronous) {
            return $this->proxyOperationService->renew($user, $proxy, $payload, true);
        }

        return $this->proxyOperationService->renew($user, $proxy, $payload);
    }
}
