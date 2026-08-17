<?php

use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use Carbon\CarbonImmutable;

it('publishes canonical resolution counts without treating unknown statuses as pending', function (): void {
    $result = new TrafficFineLookupResultDataDto(
        plate: '29A33334',
        displayPlate: '29A-333.34',
        vehicleType: 'car',
        status: 'success',
        violationCount: 6,
        violations: [
            violationWithStatus('Đã xử lý'),
            violationWithStatus('  ĐÃ XỬ PHẠT  '),
            violationWithStatus('Chưa xử lý'),
            violationWithStatus('Chưa xử phạt'),
            violationWithStatus(null),
        ],
        checkedAt: CarbonImmutable::parse('2026-08-17 09:33:00'),
    );

    expect($result->processedCount())->toBe(2)
        ->and($result->unprocessedCount())->toBe(2)
        ->and($result->unknownStatusCount())->toBe(2)
        ->and($result->toArray())
        ->toMatchArray([
            'processed_count' => 2,
            'unprocessed_count' => 2,
            'unknown_status_count' => 2,
        ])
        ->and($result->toArray()['violations'][0]['resolution_status'])->toBe('processed')
        ->and($result->toArray()['violations'][2]['resolution_status'])->toBe('unprocessed')
        ->and($result->toArray()['violations'][4]['resolution_status'])->toBe('unknown');
});

it('derives resolution counts when hydrating a legacy cached payload', function (): void {
    $result = TrafficFineLookupResultDataDto::fromArray([
        'plate' => '29A33334',
        'display_plate' => '29A-333.34',
        'vehicle_type' => 'car',
        'status' => 'success',
        'violation_count' => 2,
        'violations' => [
            violationWithStatus('Da xu ly'),
            violationWithStatus('Chua xu phat'),
        ],
        'checked_at' => '2026-08-17T09:33:00+07:00',
    ]);

    expect($result->processedCount())->toBe(1)
        ->and($result->unprocessedCount())->toBe(1)
        ->and($result->unknownStatusCount())->toBe(0);
});

/**
 * @return array{plate_color: null, time: null, location: null, behavior: null, status: ?string, agency: null, resolution_agency: null, resolution_address: null, resolution_phone: null}
 */
function violationWithStatus(?string $status): array
{
    return [
        'plate_color' => null,
        'time' => null,
        'location' => null,
        'behavior' => null,
        'status' => $status,
        'agency' => null,
        'resolution_agency' => null,
        'resolution_address' => null,
        'resolution_phone' => null,
    ];
}
