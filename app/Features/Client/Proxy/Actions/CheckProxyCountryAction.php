<?php

namespace App\Features\Client\Proxy\Actions;

use App\Features\Client\Proxy\Services\ProxyCheckerService;
use App\Models\ProxyCheckBatch;
use App\Models\User;

class CheckProxyCountryAction
{
    public function __construct(private readonly ProxyCheckerService $proxyCheckerService) {}

    /**
     * @param  array{proxies: list<string>}  $payload
     * @return array<string, mixed>
     */
    public function handle(User $user, array $payload): array
    {
        return $this->proxyCheckerService->start($user, $payload['proxies'], ProxyCheckBatch::TYPE_COUNTRY);
    }
}
