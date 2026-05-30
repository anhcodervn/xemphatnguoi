<?php

use App\Models\ApiKey;
use App\Models\ApiLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

test('an authenticated client can create an api key with permissions', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/client/api-keys', [
        'name' => 'Primary integration',
        'permissions' => ['profile.read', 'bank-accounts.read'],
        'ip_whitelist' => ['127.0.0.1'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.api_key.name', 'Primary integration')
        ->assertJsonPath('data.api_key.permissions.0', 'profile.read')
        ->assertJsonPath('data.api_key.permissions.1', 'bank-accounts.read');

    $apiKey = ApiKey::query()->whereBelongsTo($user)->first();

    expect($apiKey)->not->toBeNull();
    expect(Hash::check((string) $response->json('data.api_secret'), (string) $apiKey?->api_secret))->toBeTrue();
});

test('an api key can access an allowed v1 endpoint and writes an api log', function () {
    $user = User::factory()->create();

    $plainSecret = 'secret-test-value';
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'name' => 'Bank accounts access',
        'api_key' => 'ntd_test_bank_accounts_key',
        'api_secret' => Hash::make($plainSecret),
        'permissions' => ['bank-accounts.read'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    $response = $this->withHeaders([
        'X-API-KEY' => $apiKey->api_key,
        'X-API-SECRET' => $plainSecret,
    ])->getJson('/api/v1/list-bank-accounts');

    $response->assertSuccessful()
        ->assertJsonPath('status', true);

    expect(ApiLog::query()->whereBelongsTo($apiKey)->count())->toBe(1);

    $log = ApiLog::query()->whereBelongsTo($apiKey)->latest('id')->first();

    expect($log?->endpoint)->toBe('api/v1/list-bank-accounts');
    expect($log?->status_code)->toBe(200);
});

test('an api key is forbidden from calling an endpoint outside its permissions', function () {
    $user = User::factory()->create();

    $plainSecret = 'secret-test-value';
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'name' => 'Recharge only',
        'api_key' => 'ntd_test_recharge_key',
        'api_secret' => Hash::make($plainSecret),
        'permissions' => ['recharge.read'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    $response = $this->withHeaders([
        'X-API-KEY' => $apiKey->api_key,
        'X-API-SECRET' => $plainSecret,
    ])->getJson('/api/v1/list-bank-accounts');

    $response->assertForbidden()
        ->assertJsonPath('status', false);

    $log = ApiLog::query()->whereBelongsTo($apiKey)->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log?->status_code)->toBe(403);
});
