<?php

namespace App\Features\TrafficFine\Services;

use App\Exceptions\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class TrafficFineProviderBalanceService
{
    public function __construct(
        private readonly TrafficFineProviderSettingsService $providerSettings,
    ) {}

    /** @return array{balance: string, currency: string, fetched_at: string} */
    public function get(string $provider, bool $forceRefresh = false): array
    {
        $cacheKey = "traffic_fine_provider_balance:{$provider}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinute(), fn (): array => $this->fetch($provider));
    }

    /** @return array{balance: string, currency: string, fetched_at: string} */
    private function fetch(string $provider): array
    {
        $configuration = $this->providerSettings->configuration($provider);
        $url = trim((string) ($configuration['balance_url'] ?? ''));
        $token = trim((string) ($configuration['token'] ?? ''));
        $allowedUrls = (array) ($configuration['allowed_balance_urls'] ?? []);

        if ($url === '' || $token === '' || ! in_array($url, $allowedUrls, true)) {
            throw new ApiException('Provider chưa hỗ trợ hoặc chưa đủ cấu hình để lấy số dư.', 422);
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout(min(5, max(1, (int) ($configuration['connect_timeout'] ?? 3))))
                ->timeout(min(15, max(1, (int) ($configuration['timeout'] ?? 10))))
                ->retry(
                    times: 2,
                    sleepMilliseconds: 200,
                    when: static fn (Throwable $exception): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && ($exception->response->status() === 429 || $exception->response->serverError())),
                    throw: false,
                )
                ->get($url);
        } catch (Throwable $exception) {
            report($exception);

            throw new ApiException('Không thể kết nối API để lấy số dư provider.', 503);
        }

        if (! $response->successful()) {
            throw new ApiException('Provider từ chối yêu cầu lấy số dư.', 503);
        }

        $payload = $response->json();
        $balance = $this->extractBalance(is_array($payload) ? $payload : []);

        if ($balance === null) {
            throw new ApiException('Dữ liệu số dư provider trả về không hợp lệ.', 503);
        }

        $currency = strtoupper((string) data_get($payload, 'data.currency', data_get($payload, 'currency', 'VND')));

        return [
            'balance' => $balance,
            'currency' => preg_match('/^[A-Z]{3}$/', $currency) === 1 ? $currency : 'VND',
            'fetched_at' => now()->toISOString(),
        ];
    }

    /** @param array<string, mixed> $payload */
    private function extractBalance(array $payload): ?string
    {
        foreach (['api_balance', 'data.balance', 'balance', 'data.amount', 'amount', 'data.remaining_balance', 'data.credit', 'credit', 'data'] as $path) {
            $value = data_get($payload, $path);

            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                return (string) $value;
            }
        }

        return null;
    }
}
