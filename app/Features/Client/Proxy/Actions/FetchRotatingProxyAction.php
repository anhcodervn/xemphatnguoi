<?php

namespace App\Features\Client\Proxy\Actions;

use App\Exceptions\ApiException;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\UserProxy;
use App\Service\Proxy\ProxyVn;
use Throwable;

class FetchRotatingProxyAction
{
    /** @return array{proxy_id: int, proxy: string, protocol: string, message: string} */
    public function handle(User $user, int $proxyId, ?string $protocol = null): array
    {
        $proxy = UserProxy::query()
            ->whereBelongsTo($user)
            ->with(['product', 'provider'])
            ->findOrFail($proxyId);
        $product = $proxy->product;
        $provider = $proxy->provider;

        if (
            ! $product instanceof ProxyProduct
            || ($product->settings['proxy_type'] ?? null) !== 'rotating'
            || ! $provider instanceof ProxyProvider
            || ! $provider->is_active
            || $provider->driver !== ProxyProvider::DRIVER_PROXY_VN
            || $proxy->status !== UserProxy::STATUS_ACTIVE
        ) {
            throw new ApiException('Proxy xoay không sẵn sàng để lấy dữ liệu.', 422);
        }

        try {
            $result = (new ProxyVn($provider))->getRotatingProxy($proxy, $product, $protocol);
        } catch (Throwable $exception) {
            report($exception);

            throw new ApiException('Không thể lấy proxy xoay lúc này. Vui lòng thử lại sau.', 502);
        }

        return [
            'proxy_id' => $proxy->id,
            'proxy' => $result['proxy'],
            'protocol' => $result['protocol'],
            'message' => $result['message'],
        ];
    }
}
