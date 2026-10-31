<?php

use App\Features\TrafficFine\DTOs\TrafficFineLookupResponseDto;
use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use Carbon\CarbonImmutable;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('uses the serialization time for checked_at in every response', function (bool $cached): void {
    config(['app.timezone' => 'Asia/Ho_Chi_Minh']);
    $this->travelTo(CarbonImmutable::parse('2026-10-31T03:31:54.803743Z'));
    $result = TrafficFineLookupResultDataDto::fromArray([
        'checked_at' => '2026-10-30T03:31:54.803743Z',
    ]);
    $response = new TrafficFineLookupResponseDto(data: $result, cached: $cached);

    expect($response->toArray()['data']['checked_at'])->toBe('2026-10-31T10:31:54.803743+07:00');

    $this->travel(1)->minutes();

    expect($response->toArray()['data']['checked_at'])->toBe('2026-10-31T10:32:54.803743+07:00')
        ->and($result->checkedAt->toISOString())->toBe('2026-10-30T03:31:54.803743Z');
})->with([false, true]);

it('serializes checked_at in the application timezone without changing the instant', function (string $checkedAt): void {
    config(['app.timezone' => 'Asia/Ho_Chi_Minh']);

    $result = TrafficFineLookupResultDataDto::fromArray([
        'checked_at' => $checkedAt,
    ]);

    $serialized = $result->toArray()['checked_at'];

    expect($serialized)->toBe('2026-10-31T10:31:54.803743+07:00')
        ->and(CarbonImmutable::parse($serialized)->equalTo($result->checkedAt))->toBeTrue()
        ->and($result->checkedAt->toISOString())->toBe('2026-10-31T03:31:54.803743Z')
        ->and(TrafficFineLookupResultDataDto::fromArray($result->toArray())->toArray()['checked_at'])->toBe($serialized);
})->with([
    'legacy UTC cache' => '2026-10-31T03:31:54.803743Z',
    'local timezone' => '2026-10-31T10:31:54.803743+07:00',
    'other offset' => '2026-10-30T23:31:54.803743-04:00',
]);

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
