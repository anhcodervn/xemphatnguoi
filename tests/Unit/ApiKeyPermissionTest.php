<?php

use App\Models\ApiKey;
use Tests\TestCase;

uses(TestCase::class);

test('api key permission matching supports exact and wildcard permissions', function () {
    $apiKey = new ApiKey([
        'permissions' => ['packages.read', 'recharge.*'],
    ]);

    expect($apiKey->allowsPermission('packages.read'))->toBeTrue();
    expect($apiKey->allowsPermission('recharge.read'))->toBeTrue();
    expect($apiKey->allowsPermission('recharge.create'))->toBeTrue();
    expect($apiKey->allowsPermission('profile.read'))->toBeFalse();
});

test('api key secret matching supports hashed and legacy plain text secrets', function () {
    $hashedApiKey = new ApiKey([
        'api_secret' => password_hash('secret-value', PASSWORD_BCRYPT),
    ]);

    $plainApiKey = new ApiKey([
        'api_secret' => 'plain-secret',
    ]);

    expect($hashedApiKey->matchesSecret('secret-value'))->toBeTrue();
    expect($hashedApiKey->matchesSecret('wrong-secret'))->toBeFalse();
    expect($plainApiKey->matchesSecret('plain-secret'))->toBeTrue();
    expect($plainApiKey->matchesSecret('another-secret'))->toBeFalse();
});

test('api key status helpers detect expired keys', function () {
    $expiredKey = new ApiKey([
        'status' => ApiKey::STATUS_ACTIVE,
        'expired_at' => now()->subMinute(),
    ]);

    $activeKey = new ApiKey([
        'status' => ApiKey::STATUS_ACTIVE,
        'expired_at' => now()->addMinute(),
    ]);

    expect($expiredKey->isExpired())->toBeTrue();
    expect($activeKey->isExpired())->toBeFalse();
});

test('api key ip whitelist supports wildcard and exact ip matching', function () {
    $wildcardKey = new ApiKey([
        'ip_whitelist' => ['*'],
    ]);

    $restrictedKey = new ApiKey([
        'ip_whitelist' => ['103.10.10.1', '103.10.10.2'],
    ]);

    expect($wildcardKey->allowsIp('8.8.8.8'))->toBeTrue();
    expect($restrictedKey->allowsIp('103.10.10.1'))->toBeTrue();
    expect($restrictedKey->allowsIp('8.8.8.8'))->toBeFalse();
});
