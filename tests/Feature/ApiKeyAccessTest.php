<?php

use App\Jobs\SaveUserLogJob;
use App\Models\ApiKey;
use App\Models\ApiLog;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

function grantApiAccessPackage(User $user): void
{
    $package = Package::factory()->create([
        'status' => PackageStatus::Active,
    ]);

    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(30),
    ]);
}

test('an authenticated client can create an api key with permissions', function () {
    Queue::fake();

    $user = User::factory()->create();
    grantApiAccessPackage($user);

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

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'api_key_create');
});

test('an authenticated client can rotate api credentials and queue a user log', function () {
    Queue::fake();

    $user = User::factory()->create();
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'name' => 'Rotate me',
        'api_key' => 'ntd_rotate_old_key',
        'api_secret' => Hash::make('old-secret'),
        'permissions' => ['profile.read'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/client/api-keys/{$apiKey->id}/rotate-secret");

    $response
        ->assertOk()
        ->assertJsonPath('status', true);

    $apiKey->refresh();

    expect($apiKey->api_key)->not->toBe('ntd_rotate_old_key')
        ->and(Hash::check((string) $response->json('data.api_secret'), (string) $apiKey->api_secret))->toBeTrue();

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'api_key_rotate');
});

test('an authenticated client can update api key whitelist and queue a user log', function () {
    Queue::fake();

    $user = User::factory()->create();
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'name' => 'Update me',
        'api_key' => 'ntd_update_key',
        'api_secret' => Hash::make('secret'),
        'permissions' => ['profile.read'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/client/api-keys/{$apiKey->id}", [
        'ip_whitelist' => ['127.0.0.1', '10.0.0.2'],
    ])->assertOk();

    $apiKey->refresh();

    expect($apiKey->ip_whitelist)->toBe(['127.0.0.1', '10.0.0.2']);

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'api_key_update');
});

test('an authenticated client can revoke api key and queue a user log', function () {
    Queue::fake();

    $user = User::factory()->create();
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'name' => 'Revoke me',
        'api_key' => 'ntd_revoke_key',
        'api_secret' => Hash::make('secret'),
        'permissions' => ['profile.read'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/client/api-keys/{$apiKey->id}")
        ->assertOk()
        ->assertJsonPath('status', true);

    expect($apiKey->fresh()?->status)->toBe(ApiKey::STATUS_REVOKED);

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'api_key_revoke');
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
