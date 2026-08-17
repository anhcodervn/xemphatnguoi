<?php

use App\Features\TrafficFine\Exceptions\InvalidLicensePlateException;
use App\Features\TrafficFine\Services\LicensePlateNormalizer;
use Tests\TestCase;

uses(TestCase::class);

it('normalizes supported license plate formats', function (string $input): void {
    $normalizer = app(LicensePlateNormalizer::class);

    expect($normalizer->normalize($input))->toBe('30A12345')
        ->and($normalizer->display($input))->toBe('30A-123.45');
})->with([
    'hyphen and dot' => '30A-123.45',
    'spaces' => '30A 12345',
    'lowercase' => '30a12345',
    'dot' => '30A.12345',
]);

it('rejects an invalid license plate', function (): void {
    app(LicensePlateNormalizer::class)->normalize('not-a-plate');
})->throws(InvalidLicensePlateException::class);
