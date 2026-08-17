<?php

namespace App\Features\TrafficFine\Services;

use App\Features\TrafficFine\DTOs\TrafficFineLookupResponseDto;
use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Exceptions\UnsupportedVehicleTypeException;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceInterface;
use App\Models\LookupHistory;
use App\Models\TrafficFineLookupLog;
use App\Models\TrafficFineResult;
use App\Models\User;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Throwable;

class TrafficFineLookupService
{
    public function __construct(
        private readonly LicensePlateNormalizer $normalizer,
        private readonly TrafficFineSourceInterface $source,
        private readonly CacheFactory $cache,
    ) {}

    public function lookup(
        string $plate,
        string $vehicleType,
        ?User $user = null,
        ?string $ip = null,
    ): TrafficFineLookupResponseDto {
        $normalizedPlate = $this->normalizer->normalize($plate);
        $resolvedVehicleType = VehicleType::tryFrom($vehicleType);

        if (! $resolvedVehicleType instanceof VehicleType || ! $resolvedVehicleType->isEnabled()) {
            throw UnsupportedVehicleTypeException::forType($vehicleType);
        }

        $sourceName = $this->source->name();
        $cache = $this->cache->store((string) config('traffic-fines.cache.store', 'redis'));
        $cacheKey = $this->cacheKey($sourceName, $resolvedVehicleType, $normalizedPlate);
        $errorCacheKey = $this->errorCacheKey($sourceName, $resolvedVehicleType, $normalizedPlate);

        if ($cache->has($errorCacheKey)) {
            $this->recordLookup(
                user: $user,
                plate: $normalizedPlate,
                vehicleType: $resolvedVehicleType,
                resultId: null,
                violationCount: 0,
                source: 'negative_cache',
                cacheHit: true,
                status: 'provider_error',
                ip: $ip,
            );

            throw new TrafficFineProviderException('Hệ thống tra cứu đang tạm thời gián đoạn. Vui lòng thử lại sau ít phút.');
        }

        $cachedPayload = $cache->get($cacheKey);

        if (is_array($cachedPayload)) {
            $resolved = $this->resolvedFromCachePayload($cachedPayload);

            return $this->completeLookup($resolved, $user, $resolvedVehicleType, $ip);
        }

        $databaseResult = $this->freshDatabaseResult($sourceName, $normalizedPlate, $resolvedVehicleType);

        if ($databaseResult instanceof TrafficFineResult) {
            $resolved = $this->resolvedFromDatabase($databaseResult);
            $cache->put(
                $cacheKey,
                $this->cachePayload($resolved['data'], $databaseResult->id),
                $this->remainingCacheTtl($databaseResult),
            );

            return $this->completeLookup($resolved, $user, $resolvedVehicleType, $ip);
        }

        try {
            $resolved = $cache
                ->lock('lock:'.$cacheKey, (int) config('traffic-fines.cache.lock_seconds', 15))
                ->block((int) config('traffic-fines.cache.lock_wait_seconds', 3), function () use ($cache, $cacheKey, $normalizedPlate, $resolvedVehicleType, $sourceName): array {
                    $cachedPayload = $cache->get($cacheKey);

                    if (is_array($cachedPayload)) {
                        return $this->resolvedFromCachePayload($cachedPayload);
                    }

                    $databaseResult = $this->freshDatabaseResult($sourceName, $normalizedPlate, $resolvedVehicleType);

                    if ($databaseResult instanceof TrafficFineResult) {
                        $resolved = $this->resolvedFromDatabase($databaseResult);
                        $cache->put(
                            $cacheKey,
                            $this->cachePayload($resolved['data'], $databaseResult->id),
                            $this->remainingCacheTtl($databaseResult),
                        );

                        return $resolved;
                    }

                    $providerStartedAt = hrtime(true);
                    $data = $this->source->lookup($normalizedPlate, $resolvedVehicleType);
                    $providerLatency = (int) round((hrtime(true) - $providerStartedAt) / 1_000_000);
                    $expiresAt = now()->addSeconds((int) config('traffic-fines.cache.ttl', 86400));

                    $storedResult = TrafficFineResult::query()->updateOrCreate(
                        [
                            'plate' => $normalizedPlate,
                            'vehicle_type' => $resolvedVehicleType->value,
                        ],
                        [
                            'status' => $data->status,
                            'violation_count' => $data->violationCount,
                            'response_json' => $data->toArray(),
                            'provider' => $sourceName,
                            'checked_at' => $data->checkedAt,
                            'expires_at' => $expiresAt,
                        ],
                    );

                    $cache->put($cacheKey, $this->cachePayload($data, $storedResult->id), (int) config('traffic-fines.cache.ttl', 86400));

                    return [
                        'data' => $data,
                        'cached' => false,
                        'source' => 'provider',
                        'result_id' => $storedResult->id,
                        'provider_latency_ms' => $providerLatency,
                    ];
                });
        } catch (LockTimeoutException $exception) {
            report($exception);

            throw new TrafficFineProviderException('Hệ thống đang xử lý biển số này. Vui lòng thử lại sau ít giây.', previous: $exception);
        } catch (TrafficFineProviderException $exception) {
            $cache->put(
                $errorCacheKey,
                ['status' => 'provider_error'],
                (int) config('traffic-fines.cache.error_ttl', 60),
            );

            report($exception);
            $this->recordLookup(
                user: $user,
                plate: $normalizedPlate,
                vehicleType: $resolvedVehicleType,
                resultId: null,
                violationCount: 0,
                source: 'provider',
                cacheHit: false,
                status: 'provider_error',
                ip: $ip,
            );

            throw new TrafficFineProviderException('Hệ thống tra cứu đang tạm thời gián đoạn. Vui lòng thử lại sau ít phút.');
        } catch (Throwable $exception) {
            $cache->put(
                $errorCacheKey,
                ['status' => 'provider_error'],
                (int) config('traffic-fines.cache.error_ttl', 60),
            );

            report($exception);
            $this->recordLookup(
                user: $user,
                plate: $normalizedPlate,
                vehicleType: $resolvedVehicleType,
                resultId: null,
                violationCount: 0,
                source: 'provider',
                cacheHit: false,
                status: 'provider_error',
                ip: $ip,
            );

            throw new TrafficFineProviderException('Hệ thống tra cứu đang tạm thời gián đoạn. Vui lòng thử lại sau ít phút.');
        }

        return $this->completeLookup($resolved, $user, $resolvedVehicleType, $ip);
    }

    public function findCachedResult(string $plate, string $vehicleType): ?TrafficFineLookupResponseDto
    {
        $normalizedPlate = $this->normalizer->normalize($plate);
        $resolvedVehicleType = VehicleType::tryFrom($vehicleType);

        if (! $resolvedVehicleType instanceof VehicleType || ! $resolvedVehicleType->isEnabled()) {
            throw UnsupportedVehicleTypeException::forType($vehicleType);
        }

        $sourceName = $this->source->name();
        $cache = $this->cache->store((string) config('traffic-fines.cache.store', 'redis'));
        $cachedPayload = $cache->get($this->cacheKey($sourceName, $resolvedVehicleType, $normalizedPlate));

        if (is_array($cachedPayload)) {
            $resolved = $this->resolvedFromCachePayload($cachedPayload);

            return new TrafficFineLookupResponseDto(data: $resolved['data'], cached: true);
        }

        $databaseResult = $this->freshDatabaseResult($sourceName, $normalizedPlate, $resolvedVehicleType);

        if (! $databaseResult instanceof TrafficFineResult) {
            return null;
        }

        $resolved = $this->resolvedFromDatabase($databaseResult);
        $cache->put(
            $this->cacheKey($sourceName, $resolvedVehicleType, $normalizedPlate),
            $this->cachePayload($resolved['data'], $databaseResult->id),
            $this->remainingCacheTtl($databaseResult),
        );

        return new TrafficFineLookupResponseDto(data: $resolved['data'], cached: true);
    }

    private function cacheKey(string $sourceName, VehicleType $vehicleType, string $plate): string
    {
        return "traffic_fine:{$sourceName}:{$vehicleType->value}:{$plate}";
    }

    private function errorCacheKey(string $sourceName, VehicleType $vehicleType, string $plate): string
    {
        return "traffic_fine_error:{$sourceName}:{$vehicleType->value}:{$plate}";
    }

    private function freshDatabaseResult(
        string $sourceName,
        string $plate,
        VehicleType $vehicleType,
    ): ?TrafficFineResult {
        return TrafficFineResult::query()
            ->where('plate', $plate)
            ->where('vehicle_type', $vehicleType->value)
            ->where('provider', $sourceName)
            ->where('expires_at', '>', now())
            ->first();
    }

    private function remainingCacheTtl(TrafficFineResult $result): int
    {
        return max(1, $result->expires_at->getTimestamp() - now()->getTimestamp());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: TrafficFineLookupResultDataDto, cached: bool, source: string, result_id: ?int, provider_latency_ms: ?int}
     */
    private function resolvedFromCachePayload(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        return [
            'data' => TrafficFineLookupResultDataDto::fromArray($data),
            'cached' => true,
            'source' => 'redis',
            'result_id' => isset($payload['result_id']) ? (int) $payload['result_id'] : null,
            'provider_latency_ms' => null,
        ];
    }

    /**
     * @return array{data: TrafficFineLookupResultDataDto, cached: bool, source: string, result_id: int, provider_latency_ms: null}
     */
    private function resolvedFromDatabase(TrafficFineResult $result): array
    {
        $payload = $result->response_json;
        $payload['plate'] = $result->plate;
        $payload['display_plate'] = $this->normalizer->display($result->plate);
        $payload['vehicle_type'] = $result->vehicle_type;

        return [
            'data' => TrafficFineLookupResultDataDto::fromArray($payload),
            'cached' => true,
            'source' => 'database',
            'result_id' => $result->id,
            'provider_latency_ms' => null,
        ];
    }

    /**
     * @return array{data: array<string, mixed>, result_id: int}
     */
    private function cachePayload(TrafficFineLookupResultDataDto $data, int $resultId): array
    {
        return [
            'data' => $data->toArray(),
            'result_id' => $resultId,
        ];
    }

    /**
     * @param  array{data: TrafficFineLookupResultDataDto, cached: bool, source: string, result_id: ?int, provider_latency_ms: ?int}  $resolved
     */
    private function completeLookup(
        array $resolved,
        ?User $user,
        VehicleType $vehicleType,
        ?string $ip,
    ): TrafficFineLookupResponseDto {
        $this->recordLookup(
            user: $user,
            plate: $resolved['data']->plate,
            vehicleType: $vehicleType,
            resultId: $resolved['result_id'],
            violationCount: $resolved['data']->violationCount,
            source: $resolved['source'],
            cacheHit: $resolved['cached'],
            status: $resolved['data']->status,
            ip: $ip,
            providerLatencyMs: $resolved['provider_latency_ms'],
        );

        return new TrafficFineLookupResponseDto(
            data: $resolved['data'],
            cached: $resolved['cached'],
        );
    }

    private function recordLookup(
        ?User $user,
        string $plate,
        VehicleType $vehicleType,
        ?int $resultId,
        int $violationCount,
        string $source,
        bool $cacheHit,
        string $status,
        ?string $ip,
        ?int $providerLatencyMs = null,
    ): void {
        try {
            TrafficFineLookupLog::query()->create([
                'user_id' => $user?->id,
                'plate' => $plate,
                'vehicle_type' => $vehicleType->value,
                'source' => $source,
                'cache_hit' => $cacheHit,
                'provider' => $this->source->name(),
                'provider_latency_ms' => $providerLatencyMs,
                'status' => $status,
                'ip' => $ip,
                'created_at' => Carbon::now(),
            ]);

            if ($user instanceof User) {
                LookupHistory::query()->create([
                    'user_id' => $user->id,
                    'traffic_fine_result_id' => $resultId,
                    'plate' => $plate,
                    'vehicle_type' => $vehicleType->value,
                    'violation_count' => $violationCount,
                    'created_at' => Carbon::now(),
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
