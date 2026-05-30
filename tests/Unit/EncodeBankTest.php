<?php

use App\Utils\EncodeBank;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

uses(TestCase::class);

test('encode bank utility can encode and decode plain text', function () {
    $plain = 'secret-bank-password';
    $encoded = EncodeBank::encode($plain);

    expect($encoded)
        ->not->toBe($plain)
        ->and(EncodeBank::isEncoded($encoded))->toBeTrue()
        ->and($encoded)->toContain('"v":"v2"')
        ->and(EncodeBank::decode($encoded))->toBe($plain);
});

test('encode bank utility can encode and decode nested login payloads', function () {
    $payload = [
        'sessionId' => 'session-123',
        'browserToken' => 'browser-token',
        'meta' => [
            'deviceId' => 'device-1',
            'locked' => false,
            'attempts' => 2,
        ],
    ];

    $encodedJson = EncodeBank::encodeJson($payload);
    $decodedPayload = EncodeBank::decodeJson($encodedJson);

    expect($encodedJson)
        ->toBeString()
        ->and($encodedJson)->not->toContain('session-123')
        ->and($encodedJson)->not->toContain('browser-token')
        ->and($decodedPayload)->toBe($payload);
});

test('encode bank utility can still decode legacy laravel crypt payloads', function () {
    $plain = 'legacy-bank-password';
    $legacyPayload = 'bank:enc:'.Crypt::encryptString($plain);

    expect(EncodeBank::isEncoded($legacyPayload))->toBeTrue()
        ->and(EncodeBank::decode($legacyPayload))->toBe($plain);
});
