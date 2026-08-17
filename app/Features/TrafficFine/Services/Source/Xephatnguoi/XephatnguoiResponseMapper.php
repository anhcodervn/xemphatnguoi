<?php

namespace App\Features\TrafficFine\Services\Source\Xephatnguoi;

use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use App\Features\TrafficFine\Enums\LookupStatus;
use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Services\LicensePlateNormalizer;
use Throwable;

final class XephatnguoiResponseMapper
{
    public function __construct(private readonly LicensePlateNormalizer $normalizer) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function map(
        array $payload,
        string $expectedPlate,
        VehicleType $vehicleType,
    ): TrafficFineLookupResultDataDto {
        $rawViolations = $payload['data'] ?? null;
        $reportedCount = $payload['total'] ?? null;

        if (
            ($payload['status'] ?? null) !== 'success'
            || ! is_array($rawViolations)
            || ! array_is_list($rawViolations)
            || ! is_int($reportedCount)
            || $reportedCount < 0
            || $reportedCount > 10000
            || $reportedCount < count($rawViolations)
        ) {
            throw $this->invalidResponse();
        }

        $this->assertPlateMatches($payload['plate'] ?? null, $expectedPlate);
        $violations = [];

        foreach (array_slice($rawViolations, 0, 100) as $rawViolation) {
            if (! is_array($rawViolation)) {
                throw $this->invalidResponse();
            }

            $this->assertPlateMatches($rawViolation['bien_so'] ?? null, $expectedPlate);

            $violations[] = [
                'plate_color' => $this->nullableString($rawViolation['mau_bien'] ?? null),
                'time' => $this->nullableString($rawViolation['thoi_gian'] ?? null),
                'location' => $this->nullableString($rawViolation['dia_diem'] ?? null),
                'behavior' => $this->nullableString($rawViolation['hanh_vi'] ?? null),
                'status' => $this->nullableString($rawViolation['trang_thai'] ?? null),
                'agency' => $this->nullableString($rawViolation['don_vi_phat_hien'] ?? null),
                'resolution_agency' => $this->nullableString(
                    $rawViolation['nơi_giai_quyet'] ?? $rawViolation['noi_giai_quyet'] ?? null,
                ),
                'resolution_address' => $this->nullableString($rawViolation['dia_chi_giai_quyet'] ?? null),
                'resolution_phone' => $this->nullableString($rawViolation['so_dien_thoai'] ?? null),
            ];
        }

        return new TrafficFineLookupResultDataDto(
            plate: $expectedPlate,
            displayPlate: $this->normalizer->display($expectedPlate),
            vehicleType: $vehicleType->value,
            status: $reportedCount > 0 ? LookupStatus::Success->value : LookupStatus::NoViolation->value,
            violationCount: $reportedCount,
            violations: $violations,
            checkedAt: now()->toImmutable(),
        );
    }

    private function assertPlateMatches(mixed $sourcePlate, string $expectedPlate): void
    {
        try {
            $normalizedSourcePlate = $this->normalizer->normalize((string) $sourcePlate);
        } catch (Throwable) {
            throw $this->invalidResponse();
        }

        if ($normalizedSourcePlate !== $expectedPlate) {
            throw $this->invalidResponse();
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue === '' ? null : mb_strimwidth($normalizedValue, 0, 1000, '…');
    }

    private function invalidResponse(): TrafficFineProviderException
    {
        return new TrafficFineProviderException('Hệ thống tra cứu nhận được dữ liệu không hợp lệ.');
    }
}
