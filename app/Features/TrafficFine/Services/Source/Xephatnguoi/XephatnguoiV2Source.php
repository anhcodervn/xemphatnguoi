<?php

namespace App\Features\TrafficFine\Services\Source\Xephatnguoi;

use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Exceptions\TrafficFineConfigurationException;
use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceInterface;
use App\Features\TrafficFine\Services\TrafficFineProviderSettingsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class XephatnguoiV2Source implements TrafficFineSourceInterface
{
    private const ALLOWED_HOST = 'api.xephatnguoi.com';

    private const ALLOWED_PATH = '/v2/search';

    public function __construct(
        private readonly XephatnguoiResponseMapper $responseMapper,
        private readonly TrafficFineProviderSettingsService $providerSettings,
    ) {}

    public function name(): string
    {
        return 'xephatnguoi_v2';
    }

    public function lookup(string $normalizedPlate, VehicleType $vehicleType): TrafficFineLookupResultDataDto
    {
        $configuration = $this->providerSettings->configuration('xephatnguoi');
        $url = trim((string) config('traffic-fines.api_v2.url', ''));
        $token = trim((string) ($configuration['token'] ?? ''));
        $sourceVehicleType = $configuration['vehicle_types'][$vehicleType->value] ?? null;

        if (! $this->isAllowedUrl($url) || $token === '') {
            throw new TrafficFineConfigurationException('Nguồn tra cứu API v2 chưa được cấu hình hợp lệ.');
        }

        if (! is_int($sourceVehicleType)) {
            throw new TrafficFineConfigurationException('Loại phương tiện chưa được cấu hình cho nguồn tra cứu API v2.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout($this->configurationInteger($configuration, 'connect_timeout', 3, 1, 5))
                ->timeout($this->configurationInteger($configuration, 'timeout', 10, 1, 15))
                ->retry(
                    times: $this->configurationInteger($configuration, 'retry_times', 2, 1, 2),
                    sleepMilliseconds: $this->configurationInteger($configuration, 'retry_sleep_ms', 200, 0, 2000),
                    when: static function (Throwable $exception): bool {
                        return $exception instanceof ConnectionException
                            || ($exception instanceof RequestException
                                && ($exception->response->status() === 429 || $exception->response->serverError()));
                    },
                    throw: false,
                )
                ->get($url, [
                    'plate' => $normalizedPlate,
                    'type' => $sourceVehicleType,
                ]);

            if (! $response->successful()) {
                throw new TrafficFineProviderException('Hệ thống tra cứu API v2 đang tạm thời gián đoạn.');
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                throw new TrafficFineProviderException('Hệ thống tra cứu API v2 nhận được dữ liệu không hợp lệ.');
            }

            if (($payload['status'] ?? null) !== 'success') {
                throw new TrafficFineProviderException('Hệ thống tra cứu API v2 không thể hoàn tất yêu cầu.');
            }

            return $this->responseMapper->map($payload, $normalizedPlate, $vehicleType);
        } catch (TrafficFineProviderException $exception) {
            throw $exception;
        } catch (ConnectionException) {
            throw new TrafficFineProviderException('Kết nối tra cứu API v2 đã hết thời gian chờ.');
        } catch (Throwable) {
            throw new TrafficFineProviderException('Không thể tra cứu dữ liệu API v2 vào lúc này.');
        }
    }

    /** @param array<string, mixed> $configuration */
    private function configurationInteger(array $configuration, string $key, int $default, int $minimum, int $maximum): int
    {
        $value = (int) ($configuration[$key] ?? $default);

        return min($maximum, max($minimum, $value));
    }

    private function isAllowedUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && ($parts['host'] ?? null) === self::ALLOWED_HOST
            && ($parts['path'] ?? null) === self::ALLOWED_PATH
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment'])
            && (! isset($parts['port']) || $parts['port'] === 443);
    }
}
