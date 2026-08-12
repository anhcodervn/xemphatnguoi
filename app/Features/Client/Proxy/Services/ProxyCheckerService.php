<?php

namespace App\Features\Client\Proxy\Services;

use App\Events\ProxyCheckProgressed;
use App\Jobs\ProcessProxyCheckJob;
use App\Models\ProxyCheckBatch;
use App\Models\ProxyCheckItem;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ProxyCheckerService
{
    /**
     * Tạo batch và đẩy mỗi proxy thành một job độc lập sau khi transaction hoàn tất.
     *
     * @param  list<string>  $proxies
     * @return array<string, mixed>
     */
    public function start(User $user, array $proxies, string $checkType = ProxyCheckBatch::TYPE_LIVE): array
    {
        if (! in_array($checkType, [ProxyCheckBatch::TYPE_LIVE, ProxyCheckBatch::TYPE_COUNTRY], true)) {
            throw new RuntimeException('Loại kiểm tra proxy không hợp lệ.');
        }

        $batch = DB::transaction(function () use ($user, $proxies, $checkType): ProxyCheckBatch {
            $batch = ProxyCheckBatch::query()->create([
                'user_id' => $user->id,
                'check_type' => $checkType,
                'total' => count($proxies),
            ]);

            foreach ($proxies as $position => $proxy) {
                $parsed = $this->parseProxy($proxy);
                $item = $batch->items()->create([
                    'position' => $position,
                    'endpoint' => "{$parsed['ip']}:{$parsed['port']}",
                    'proxy' => $proxy,
                ]);

                ProcessProxyCheckJob::dispatch($item->id)->afterCommit();
            }

            return $batch;
        });

        return $this->payload($batch->fresh('items'));
    }

    /** @return array<string, mixed> */
    public function status(User $user, string $batchId, ?string $checkType = null): array
    {
        $batch = ProxyCheckBatch::query()
            ->whereBelongsTo($user)
            ->when($checkType !== null, fn ($query) => $query->where('check_type', $checkType))
            ->with('items')
            ->findOrFail($batchId);

        return $this->payload($batch);
    }

    public function process(int $itemId): void
    {
        $item = $this->claim($itemId);

        if (! $item instanceof ProxyCheckItem) {
            return;
        }

        $this->broadcast($item);

        try {
            $result = $item->batch?->check_type === ProxyCheckBatch::TYPE_COUNTRY
                ? $this->checkProxyCountry((string) $item->proxy)
                : $this->checkProxy((string) $item->proxy);
            $completedItem = $this->complete($item->id, $result);

            if ($completedItem instanceof ProxyCheckItem) {
                $this->broadcast($completedItem);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->fail($item->id);
        }
    }

    public function fail(int $itemId): void
    {
        $item = $this->complete($itemId, [
            'status' => ProxyCheckItem::STATUS_DIE,
            'exit_ip' => null,
            'country_code' => null,
            'country_name' => null,
            'region_name' => null,
            'city_name' => null,
            'timezone' => null,
            'isp' => null,
            'latency_ms' => null,
            'message' => 'Không thể hoàn tất kiểm tra proxy.',
        ]);

        if ($item instanceof ProxyCheckItem) {
            $this->broadcast($item);
        }
    }

    private function claim(int $itemId): ?ProxyCheckItem
    {
        return DB::transaction(function () use ($itemId): ?ProxyCheckItem {
            $item = ProxyCheckItem::query()->whereKey($itemId)->lockForUpdate()->first();

            if (! $item instanceof ProxyCheckItem || $item->status !== ProxyCheckItem::STATUS_PENDING) {
                return null;
            }

            $item->forceFill([
                'status' => ProxyCheckItem::STATUS_PROCESSING,
                'started_at' => now(),
            ])->save();

            $item->batch()->where('status', ProxyCheckBatch::STATUS_PENDING)->update([
                'status' => ProxyCheckBatch::STATUS_PROCESSING,
            ]);

            return $item->fresh('batch');
        });
    }

    /**
     * @param  array{status: string, exit_ip: ?string, country_code?: ?string, country_name?: ?string, region_name?: ?string, city_name?: ?string, timezone?: ?string, isp?: ?string, latency_ms: ?int, message: string}  $result
     */
    private function complete(int $itemId, array $result): ?ProxyCheckItem
    {
        return DB::transaction(function () use ($itemId, $result): ?ProxyCheckItem {
            $item = ProxyCheckItem::query()->whereKey($itemId)->lockForUpdate()->first();

            if (
                ! $item instanceof ProxyCheckItem
                || in_array($item->status, [ProxyCheckItem::STATUS_LIVE, ProxyCheckItem::STATUS_DIE], true)
            ) {
                return null;
            }

            $batch = ProxyCheckBatch::query()->whereKey($item->proxy_check_batch_id)->lockForUpdate()->first();

            if (! $batch instanceof ProxyCheckBatch) {
                return null;
            }

            $status = $result['status'] === ProxyCheckItem::STATUS_LIVE
                ? ProxyCheckItem::STATUS_LIVE
                : ProxyCheckItem::STATUS_DIE;

            $item->forceFill([
                'proxy' => null,
                'status' => $status,
                'exit_ip' => $result['exit_ip'],
                'country_code' => $result['country_code'] ?? null,
                'country_name' => $result['country_name'] ?? null,
                'region_name' => $result['region_name'] ?? null,
                'city_name' => $result['city_name'] ?? null,
                'timezone' => $result['timezone'] ?? null,
                'isp' => $result['isp'] ?? null,
                'latency_ms' => $result['latency_ms'],
                'message' => $result['message'],
                'completed_at' => now(),
            ])->save();

            $processed = $batch->processed + 1;
            $batch->forceFill([
                'status' => $processed >= $batch->total
                    ? ProxyCheckBatch::STATUS_COMPLETED
                    : ProxyCheckBatch::STATUS_PROCESSING,
                'processed' => $processed,
                'live' => $batch->live + ($status === ProxyCheckItem::STATUS_LIVE ? 1 : 0),
                'die' => $batch->die + ($status === ProxyCheckItem::STATUS_DIE ? 1 : 0),
                'completed_at' => $processed >= $batch->total ? now() : null,
            ])->save();

            return $item->fresh('batch');
        });
    }

    /**
     * @return array{status: string, exit_ip: ?string, latency_ms: ?int, message: string}
     */
    private function checkProxy(string $proxy): array
    {
        $parsed = $this->parseProxy($proxy);

        try {
            $response = Http::acceptJson()
                ->withUserAgent('DailyProxy Proxy Checker/1.0')
                ->withOptions([
                    'allow_redirects' => false,
                    'proxy' => $this->proxyUrl($parsed),
                    'verify' => true,
                ])
                ->connectTimeout((int) config('services.proxy.check_connect_timeout', 4))
                ->timeout((int) config('services.proxy.check_timeout', 8))
                ->get($this->targetUrl());
        } catch (Throwable) {
            return [
                'status' => ProxyCheckItem::STATUS_DIE,
                'exit_ip' => null,
                'latency_ms' => null,
                'message' => 'Không thể kết nối qua proxy.',
            ];
        }

        return $this->responseResult($response);
    }

    /**
     * @return array{status: string, exit_ip: ?string, country_code: ?string, country_name: ?string, region_name: ?string, city_name: ?string, timezone: ?string, isp: ?string, latency_ms: ?int, message: string}
     */
    private function checkProxyCountry(string $proxy): array
    {
        $parsed = $this->parseProxy($proxy);

        try {
            $response = Http::acceptJson()
                ->withUserAgent('DailyProxy Country Checker/1.0')
                ->withOptions([
                    'allow_redirects' => false,
                    'proxy' => $this->proxyUrl($parsed),
                    'verify' => true,
                ])
                ->connectTimeout((int) config('services.proxy.check_connect_timeout', 4))
                ->timeout((int) config('services.proxy.check_timeout', 8))
                ->get($this->countryTargetUrl());
        } catch (Throwable) {
            return $this->countryFailure('Không thể kết nối và xác định quốc gia của proxy.');
        }

        if (! $response->successful()) {
            return $this->countryFailure('Dịch vụ xác định quốc gia không phản hồi.', $this->latency($response));
        }

        $exitIp = $response->json('ip');
        $countryCode = $response->json('country_code');
        $countryName = $this->limitedString($response->json('country'), 100);

        if (
            $response->json('success') !== true
            || ! is_string($exitIp)
            || filter_var($exitIp, FILTER_VALIDATE_IP) === false
            || ! is_string($countryCode)
            || mb_strlen($countryCode) !== 2
            || $countryName === null
        ) {
            return $this->countryFailure('Không nhận được dữ liệu quốc gia hợp lệ.', $this->latency($response));
        }

        return [
            'status' => ProxyCheckItem::STATUS_LIVE,
            'exit_ip' => $exitIp,
            'country_code' => mb_strtoupper($countryCode),
            'country_name' => $countryName,
            'region_name' => $this->limitedString($response->json('region'), 150),
            'city_name' => $this->limitedString($response->json('city'), 150),
            'timezone' => $this->limitedString($response->json('timezone.id'), 100),
            'isp' => $this->limitedString($response->json('connection.isp'), 180),
            'latency_ms' => $this->latency($response),
            'message' => "Proxy được xác định tại {$countryName} ({$countryCode}).",
        ];
    }

    /**
     * @return array{status: string, exit_ip: null, country_code: null, country_name: null, region_name: null, city_name: null, timezone: null, isp: null, latency_ms: ?int, message: string}
     */
    private function countryFailure(string $message, ?int $latencyMs = null): array
    {
        return [
            'status' => ProxyCheckItem::STATUS_DIE,
            'exit_ip' => null,
            'country_code' => null,
            'country_name' => null,
            'region_name' => null,
            'city_name' => null,
            'timezone' => null,
            'isp' => null,
            'latency_ms' => $latencyMs,
            'message' => $message,
        ];
    }

    private function limitedString(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $maxLength);
    }

    /**
     * @return array{status: string, exit_ip: ?string, latency_ms: ?int, message: string}
     */
    private function responseResult(Response $response): array
    {
        if (! $response->successful()) {
            return [
                'status' => ProxyCheckItem::STATUS_DIE,
                'exit_ip' => null,
                'latency_ms' => $this->latency($response),
                'message' => 'Không thể kết nối qua proxy.',
            ];
        }

        $exitIp = $response->json('ip');

        if (! is_string($exitIp) || filter_var($exitIp, FILTER_VALIDATE_IP) === false) {
            return [
                'status' => ProxyCheckItem::STATUS_DIE,
                'exit_ip' => null,
                'latency_ms' => $this->latency($response),
                'message' => 'Proxy không trả về địa chỉ IP hợp lệ.',
            ];
        }

        return [
            'status' => ProxyCheckItem::STATUS_LIVE,
            'exit_ip' => $exitIp,
            'latency_ms' => $this->latency($response),
            'message' => 'Proxy đang hoạt động.',
        ];
    }

    private function targetUrl(): string
    {
        $url = (string) config('services.proxy.check_url', 'https://api.ipify.org?format=json');
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Cấu hình dịch vụ kiểm tra proxy không hợp lệ.');
        }

        return $url;
    }

    private function countryTargetUrl(): string
    {
        $url = (string) config('services.proxy.country_check_url', 'https://ipwho.is/');
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Cấu hình dịch vụ xác định quốc gia không hợp lệ.');
        }

        return $url;
    }

    /** @return array{ip: string, port: int, username: string, password: string} */
    private function parseProxy(string $proxy): array
    {
        [$ip, $port, $username, $password] = explode(':', $proxy, 4);

        return [
            'ip' => $ip,
            'port' => (int) $port,
            'username' => $username,
            'password' => $password,
        ];
    }

    /** @param array{ip: string, port: int, username: string, password: string} $proxy */
    private function proxyUrl(array $proxy): string
    {
        return sprintf(
            'http://%s:%s@%s:%d',
            rawurlencode($proxy['username']),
            rawurlencode($proxy['password']),
            $proxy['ip'],
            $proxy['port'],
        );
    }

    private function latency(Response $response): ?int
    {
        $totalTime = $response->handlerStats()['total_time'] ?? null;

        return is_numeric($totalTime) ? (int) round((float) $totalTime * 1000) : null;
    }

    /** @return array<string, mixed> */
    private function payload(ProxyCheckBatch $batch): array
    {
        $batch->loadMissing('items');

        return [
            'id' => $batch->id,
            'check_type' => $batch->check_type,
            'status' => $batch->status,
            'total' => $batch->total,
            'processed' => $batch->processed,
            'live' => $batch->live,
            'die' => $batch->die,
            'progress' => $batch->total > 0 ? (int) floor(($batch->processed / $batch->total) * 100) : 0,
            'created_at' => $batch->created_at?->toISOString(),
            'completed_at' => $batch->completed_at?->toISOString(),
            'results' => $batch->items->map(fn (ProxyCheckItem $item): array => $this->itemPayload($item))->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function itemPayload(ProxyCheckItem $item): array
    {
        return [
            'id' => $item->id,
            'position' => $item->position,
            'endpoint' => $item->endpoint,
            'status' => $item->status,
            'exit_ip' => $item->exit_ip,
            'country_code' => $item->country_code,
            'country_name' => $item->country_name,
            'region_name' => $item->region_name,
            'city_name' => $item->city_name,
            'timezone' => $item->timezone,
            'isp' => $item->isp,
            'latency_ms' => $item->latency_ms,
            'message' => $item->message,
            'started_at' => $item->started_at?->toISOString(),
            'completed_at' => $item->completed_at?->toISOString(),
        ];
    }

    private function broadcast(ProxyCheckItem $item): void
    {
        $batch = $item->batch;

        if (! $batch instanceof ProxyCheckBatch || $batch->user_id === null) {
            return;
        }

        ProxyCheckProgressed::dispatch(
            userId: $batch->user_id,
            batchId: $batch->id,
            batchStatus: $batch->status,
            total: $batch->total,
            processed: $batch->processed,
            live: $batch->live,
            die: $batch->die,
            item: $this->itemPayload($item),
        );
    }
}
