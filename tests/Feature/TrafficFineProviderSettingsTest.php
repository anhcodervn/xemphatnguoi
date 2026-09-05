<?php

use App\Features\TrafficFine\Exceptions\TrafficFineConfigurationException;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceRegistry;
use App\Features\TrafficFine\Services\Source\Xephatnguoi\XephatnguoiSource;
use App\Features\TrafficFine\Services\TrafficFineProviderSettingsService;
use App\Models\Setting;
use App\Models\TrafficFineProvider;
use App\Models\TrafficFineResult;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config([
        'traffic-fines.default_source' => 'xephatnguoi',
        'traffic-fines.sources.backup' => [
            'label' => 'Backup API',
            'driver' => XephatnguoiSource::class,
            'priority' => 2,
            'url' => 'https://api.xephatnguoi.com/v1/search',
            'allowed_urls' => ['https://api.xephatnguoi.com/v1/search'],
            'token' => 'backup-env-token',
            'timeout' => 10,
            'connect_timeout' => 3,
            'retry_times' => 2,
            'retry_sleep_ms' => 200,
            'vehicle_types' => ['car' => 1, 'motorbike' => 2, 'electric_motorbike' => 3],
        ],
    ]);

    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
});

it('lists separately configured providers and never exposes their tokens', function (): void {
    config(['traffic-fines.sources.xephatnguoi.token' => 'private-env-token']);

    $response = $this->getJson('/api/admin-api/traffic-fines/provider')
        ->assertOk()
        ->assertJsonPath('data.active_provider', 'xephatnguoi')
        ->assertJsonPath('data.providers.0.name', 'xephatnguoi')
        ->assertJsonPath('data.providers.0.enabled', true)
        ->assertJsonPath('data.providers.1.name', 'backup')
        ->assertJsonPath('data.providers.1.enabled', false)
        ->assertJsonMissingPath('data.providers.0.token')
        ->assertJsonMissingPath('data.providers.1.token');

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('private-env-token')
        ->not->toContain('backup-env-token');
});

it('activates only one provider at a time and stores credentials encrypted', function (): void {
    $payload = [
        'enabled' => true,
        'url' => 'https://api.xephatnguoi.com/v1/search',
        'token' => 'new-private-token',
        'timeout' => 8,
        'connect_timeout' => 2,
        'retry_times' => 1,
        'retry_sleep_ms' => 100,
    ];

    $this->patchJson('/api/admin-api/traffic-fines/provider/backup', $payload)
        ->assertOk()
        ->assertJsonPath('data.active_provider', 'backup')
        ->assertJsonPath('data.provider.enabled', true)
        ->assertJsonMissingPath('data.provider.token');

    $overview = $this->getJson('/api/admin-api/traffic-fines/provider')
        ->assertOk()
        ->assertJsonPath('data.active_provider', 'backup')
        ->assertJsonPath('data.providers.0.enabled', false)
        ->assertJsonPath('data.providers.1.enabled', true);

    $storedToken = Setting::query()
        ->where('key', 'traffic_fine_provider_backup_token')
        ->firstOrFail();

    expect(Setting::query()->where('key', TrafficFineProviderSettingsService::ACTIVE_PROVIDER_KEY)->value('value'))
        ->toBe('backup')
        ->and($storedToken->type)->toBe('encrypted')
        ->and($storedToken->value)->not->toBe('new-private-token')
        ->and(json_encode($overview->json(), JSON_THROW_ON_ERROR))->not->toContain('new-private-token')
        ->and(app(TrafficFineProviderSettingsService::class)->configuration('backup')['token'])->toBe('new-private-token');
});

it('keeps the existing encrypted token when the admin saves an empty token', function (): void {
    $settings = app(TrafficFineProviderSettingsService::class);
    $settings->update('backup', [
        'enabled' => true,
        'token' => 'preserved-token',
    ]);

    $this->patchJson('/api/admin-api/traffic-fines/provider/backup', [
        'enabled' => true,
        'url' => 'https://api.xephatnguoi.com/v1/search',
        'timeout' => 10,
        'connect_timeout' => 3,
        'retry_times' => 2,
        'retry_sleep_ms' => 200,
    ])->assertOk();

    expect($settings->configuration('backup')['token'])->toBe('preserved-token');
});

it('can disable the active provider and prevents lookups without an active provider', function (): void {
    $this->patchJson('/api/admin-api/traffic-fines/provider/xephatnguoi', [
        'enabled' => false,
        'url' => 'https://api.xephatnguoi.com/v1/search',
        'timeout' => 10,
        'connect_timeout' => 3,
        'retry_times' => 2,
        'retry_sleep_ms' => 200,
    ])->assertOk()
        ->assertJsonPath('data.active_provider', null)
        ->assertJsonPath('data.provider.enabled', false);

    expect(fn () => app(TrafficFineSourceRegistry::class)->resolve())
        ->toThrow(TrafficFineConfigurationException::class, 'Chưa bật nguồn tra cứu nào.');
});

it('rejects unknown providers and incomplete configuration', function (): void {
    $payload = [
        'enabled' => true,
        'url' => '',
        'timeout' => 10,
        'connect_timeout' => 3,
        'retry_times' => 2,
        'retry_sleep_ms' => 200,
    ];

    $this->patchJson('/api/admin-api/traffic-fines/provider/missing', $payload)->assertUnprocessable();

    config(['traffic-fines.sources.backup.token' => '']);

    $this->patchJson('/api/admin-api/traffic-fines/provider/backup', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('status', false)
        ->assertJsonStructure(['errors']);

    $this->patchJson('/api/admin-api/traffic-fines/provider/xephatnguoi', [
        ...$payload,
        'url' => 'https://untrusted.example/v1/search',
        'token' => 'token',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.url.0', 'API URL không thuộc địa chỉ được phép của provider.');
});

it('forbids regular users from changing providers', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'user']));

    $this->patchJson('/api/admin-api/traffic-fines/provider/xephatnguoi', [
        'enabled' => false,
        'url' => 'https://api.xephatnguoi.com/v1/search',
        'timeout' => 10,
        'connect_timeout' => 3,
        'retry_times' => 2,
        'retry_sleep_ms' => 200,
    ])->assertForbidden();
});

it('keeps cached database results separated by provider', function (): void {
    TrafficFineResult::factory()->create([
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'provider' => 'xephatnguoi',
    ]);
    TrafficFineResult::factory()->create([
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'provider' => 'backup',
    ]);

    expect(TrafficFineResult::query()
        ->where('plate', '30A12345')
        ->where('vehicle_type', 'car')
        ->count())->toBe(2);
});

it('creates and deletes a managed provider through the admin API', function (): void {
    $payload = [
        'label' => 'XePhatNguoi dự phòng',
        'code' => 'xephatnguoi_phu',
        'driver' => 'xephatnguoi',
        'enabled' => true,
        'url' => 'https://api.xephatnguoi.com/v1/search',
        'token' => 'managed-private-token',
        'timeout' => 8,
        'connect_timeout' => 2,
        'retry_times' => 1,
        'retry_sleep_ms' => 100,
    ];

    $response = $this->postJson('/api/admin-api/traffic-fines/provider', $payload)
        ->assertCreated()
        ->assertJsonPath('data.name', 'xephatnguoi_phu')
        ->assertJsonPath('data.driver', 'xephatnguoi')
        ->assertJsonPath('data.deletable', true)
        ->assertJsonPath('data.enabled', true)
        ->assertJsonMissingPath('data.token')
        ->assertJsonMissingPath('data.api_token');

    $provider = TrafficFineProvider::query()->where('name', 'xephatnguoi_phu')->firstOrFail();

    expect($provider->api_token)->toBe('managed-private-token')
        ->and($provider->getRawOriginal('api_token'))->not->toBe('managed-private-token')
        ->and(json_encode($response->json(), JSON_THROW_ON_ERROR))->not->toContain('managed-private-token')
        ->and(app(TrafficFineProviderSettingsService::class)->activeName())->toBe('xephatnguoi_phu')
        ->and(app(TrafficFineSourceRegistry::class)->resolve()->name())->toBe('xephatnguoi_phu');

    $this->deleteJson('/api/admin-api/traffic-fines/provider/xephatnguoi_phu')->assertOk();

    expect(TrafficFineProvider::query()->where('name', 'xephatnguoi_phu')->exists())->toBeFalse()
        ->and(app(TrafficFineProviderSettingsService::class)->activeName())->toBe('');
});

it('does not allow deleting a provider declared by the system', function (): void {
    $this->deleteJson('/api/admin-api/traffic-fines/provider/xephatnguoi')
        ->assertUnprocessable()
        ->assertJsonPath('status', false);
});

it('gets provider balance through the backend without exposing its token', function (): void {
    TrafficFineProvider::factory()->create([
        'name' => 'balance_source',
        'driver' => 'xephatnguoi',
        'api_token' => 'balance-private-token',
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/balance' => Http::response([
            'status' => 'success',
            'api_balance' => 140040,
            'api_key' => 'upstream-private-api-key',
            'email' => 'provider@example.test',
            'timestamp' => '2026-09-05 17:26:21',
        ]),
    ]);

    $response = $this->getJson('/api/admin-api/traffic-fines/provider/balance_source/balance?refresh=1')
        ->assertOk()
        ->assertJsonPath('data.balance', '140040')
        ->assertJsonPath('data.currency', 'VND');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.xephatnguoi.com/v1/balance'
        && $request->hasHeader('Authorization', 'Bearer balance-private-token'));

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('balance-private-token')
        ->not->toContain('upstream-private-api-key')
        ->not->toContain('provider@example.test');
});

it('returns a safe error when provider balance payload is invalid', function (): void {
    TrafficFineProvider::factory()->create(['name' => 'invalid_balance_source']);

    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/balance' => Http::response(['status' => 'success', 'data' => []]),
    ]);

    $this->getJson('/api/admin-api/traffic-fines/provider/invalid_balance_source/balance?refresh=1')
        ->assertServiceUnavailable()
        ->assertJsonPath('message', 'Dữ liệu số dư provider trả về không hợp lệ.');
});
