<?php

use App\Models\ProxyProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('admin can store arbitrary provider credential keys without exposing secrets', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $credentials = [
        'base_url' => 'https://provider.example/api',
        'api_key' => 'provider-api-key-secret',
        'secret_key' => 'provider-secret-key-value',
    ];

    $response = $this->actingAs($admin)->postJson('/api/admin-api/proxy-providers', [
        'name' => 'Nguồn API thử nghiệm',
        'code' => 'test_provider',
        'order_method' => 'automatic',
        'credentials' => $credentials,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.provider.code', 'test_provider')
        ->assertJsonPath('data.provider.order_method', 'automatic')
        ->assertJsonMissingPath('data.provider.driver')
        ->assertJsonPath('data.provider.has_credentials', true)
        ->assertJsonMissingPath('data.provider.credentials')
        ->assertJsonMissingPath('data.provider.api_base_url');

    $provider = ProxyProvider::query()->findOrFail($response->json('data.provider.id'));
    $rawCredentials = DB::table('proxy_providers')->where('id', $provider->id)->value('credentials');

    $this->actingAs($admin)->getJson('/api/admin-api/proxy-providers?per_page=100')
        ->assertSuccessful()
        ->assertJsonMissingPath('data.providers.data.0.credentials');

    $this->actingAs($admin)->getJson("/api/admin-api/proxy-providers/{$provider->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.provider.credentials.base_url', 'https://provider.example/api')
        ->assertJsonPath('data.provider.credentials.api_key', 'provider-api-key-secret')
        ->assertJsonPath('data.provider.credentials.secret_key', 'provider-secret-key-value');

    expect($provider->credentials)->toBe($credentials)
        ->and($provider->toArray())->not->toHaveKey('credentials')
        ->and($rawCredentials)->toContain('__encrypted')
        ->and($rawCredentials)->not->toContain('provider.example')
        ->and($rawCredentials)->not->toContain('provider-api-key-secret')
        ->and($rawCredentials)->not->toContain('provider-secret-key-value')
        ->and(json_encode($response->json()))->not->toContain('provider-api-key-secret')
        ->and(json_encode($response->json()))->not->toContain('provider-secret-key-value');
});

test('omitting credentials preserves them and an empty object removes them', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $provider = ProxyProvider::query()->create([
        'name' => 'Nguồn cần sửa',
        'driver' => 'manual',
        'credentials' => ['access_token' => 'keep-this-token'],
    ]);

    $this->actingAs($admin)->patchJson("/api/admin-api/proxy-providers/{$provider->id}", [
        'name' => 'Nguồn đã đổi tên',
    ])->assertOk()->assertJsonPath('data.provider.has_credentials', true);

    expect($provider->fresh()->credentials)->toBe(['access_token' => 'keep-this-token']);

    $this->actingAs($admin)->patchJson("/api/admin-api/proxy-providers/{$provider->id}", [
        'credentials' => [],
    ])->assertOk()->assertJsonPath('data.provider.has_credentials', false);

    expect($provider->fresh()->credentials)->toBe([]);
});

test('non admin users cannot read provider connection data', function () {
    $user = User::factory()->create();
    $provider = ProxyProvider::query()->create([
        'name' => 'Private provider',
        'credentials' => ['api_key' => 'private-key'],
    ]);

    $this->actingAs($user)
        ->getJson("/api/admin-api/proxy-providers/{$provider->id}")
        ->assertForbidden();
});

test('guests cannot read provider connection data', function () {
    $provider = ProxyProvider::query()->create([
        'name' => 'Private provider',
        'credentials' => ['api_key' => 'private-key'],
    ]);

    $this->getJson("/api/admin-api/proxy-providers/{$provider->id}")
        ->assertUnauthorized();
});

test('provider credentials must be a flat object of safe string keys and values', function (array $credentials) {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->postJson('/api/admin-api/proxy-providers', [
        'name' => 'Nguồn sai credential',
        'code' => 'invalid_provider',
        'order_method' => 'manual',
        'credentials' => $credentials,
    ])->assertUnprocessable()->assertJsonValidationErrors('credentials');
})->with([
    'list' => [['secret']],
    'nested object' => [['auth' => ['token' => 'secret']]],
    'non string value' => [['token' => 123]],
    'reserved storage key' => [['__encrypted' => 'secret']],
    'unsafe key' => [['api key' => 'secret']],
    'unsafe base url scheme' => [['base_url' => 'file:///etc/passwd']],
]);
