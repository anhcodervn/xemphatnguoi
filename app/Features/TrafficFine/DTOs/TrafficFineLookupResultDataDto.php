<?php

namespace App\Features\TrafficFine\DTOs;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class TrafficFineLookupResultDataDto
{
    /**
     * @param  list<array{plate_color: ?string, time: ?string, location: ?string, behavior: ?string, status: ?string, agency: ?string, resolution_agency: ?string, resolution_address: ?string, resolution_phone: ?string}>  $violations
     */
    public function __construct(
        public readonly string $plate,
        public readonly string $displayPlate,
        public readonly string $vehicleType,
        public readonly string $status,
        public readonly int $violationCount,
        public readonly array $violations,
        public readonly CarbonImmutable $checkedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $violations = collect(is_array($data['violations'] ?? null) ? $data['violations'] : [])
            ->filter(fn (mixed $violation): bool => is_array($violation))
            ->map(fn (array $violation): array => [
                'plate_color' => self::nullableString($violation['plate_color'] ?? null),
                'time' => self::nullableString($violation['time'] ?? null),
                'location' => self::nullableString($violation['location'] ?? null),
                'behavior' => self::nullableString($violation['behavior'] ?? null),
                'status' => self::nullableString($violation['status'] ?? null),
                'agency' => self::nullableString($violation['agency'] ?? null),
                'resolution_agency' => self::nullableString($violation['resolution_agency'] ?? null),
                'resolution_address' => self::nullableString($violation['resolution_address'] ?? null),
                'resolution_phone' => self::nullableString($violation['resolution_phone'] ?? null),
            ])
            ->values()
            ->all();

        return new self(
            plate: (string) ($data['plate'] ?? ''),
            displayPlate: (string) ($data['display_plate'] ?? ''),
            vehicleType: (string) ($data['vehicle_type'] ?? ''),
            status: (string) ($data['status'] ?? 'no_violation'),
            violationCount: (int) ($data['violation_count'] ?? count($violations)),
            violations: $violations,
            checkedAt: CarbonImmutable::parse((string) ($data['checked_at'] ?? 'now')),
        );
    }

    /**
     * @return array{plate: string, display_plate: string, vehicle_type: string, status: string, violation_count: int, processed_count: int, unprocessed_count: int, unknown_status_count: int, violations: list<array{plate_color: ?string, time: ?string, location: ?string, behavior: ?string, status: ?string, resolution_status: string, agency: ?string, resolution_agency: ?string, resolution_address: ?string, resolution_phone: ?string}>, checked_at: string}
     */
    public function toArray(): array
    {
        return [
            'plate' => $this->plate,
            'display_plate' => $this->displayPlate,
            'vehicle_type' => $this->vehicleType,
            'status' => $this->status,
            'violation_count' => $this->violationCount,
            'processed_count' => $this->processedCount(),
            'unprocessed_count' => $this->unprocessedCount(),
            'unknown_status_count' => $this->unknownStatusCount(),
            'violations' => array_map(
                fn (array $violation): array => [
                    ...$violation,
                    'resolution_status' => self::resolutionStatus($violation['status'] ?? null),
                ],
                $this->violations,
            ),
            'checked_at' => $this->checkedAt->toISOString(),
        ];
    }

    public function processedCount(): int
    {
        return $this->resolutionCounts()['processed'];
    }

    public function unprocessedCount(): int
    {
        return $this->resolutionCounts()['unprocessed'];
    }

    public function unknownStatusCount(): int
    {
        $knownStatusCount = $this->processedCount() + $this->unprocessedCount();

        return max(0, $this->violationCount - $knownStatusCount);
    }

    public static function resolutionStatus(?string $status): string
    {
        $normalizedStatus = Str::of($status ?? '')
            ->ascii()
            ->lower()
            ->squish()
            ->value();

        if (Str::startsWith($normalizedStatus, ['da xu ly', 'da xu phat'])) {
            return 'processed';
        }

        if (Str::startsWith($normalizedStatus, ['chua xu ly', 'chua xu phat'])) {
            return 'unprocessed';
        }

        return 'unknown';
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue === '' ? null : $normalizedValue;
    }

    /**
     * @return array{processed: int, unprocessed: int}
     */
    private function resolutionCounts(): array
    {
        $counts = ['processed' => 0, 'unprocessed' => 0];

        foreach (array_slice($this->violations, 0, max(0, $this->violationCount)) as $violation) {
            $resolutionStatus = self::resolutionStatus($violation['status'] ?? null);

            if ($resolutionStatus === 'processed') {
                $counts['processed']++;

                continue;
            }

            if ($resolutionStatus === 'unprocessed') {
                $counts['unprocessed']++;
            }
        }

        return $counts;
    }
}
