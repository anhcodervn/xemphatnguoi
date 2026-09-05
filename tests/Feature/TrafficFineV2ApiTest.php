<?php

use App\Features\TrafficFine\Services\TrafficFineProviderSettingsService;
use App\Models\ApiKey;
use App\Models\ApiLog;
use App\Models\TrafficFineLookupLog;
use App\Models\TrafficFineResult;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\SettingStore;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config([
        'traffic-fines.api_v2.url' => 'https://api.xephatnguoi.com/v2/search',
        'traffic-fines.cache.store' => 'array',
        'traffic-fines.cache.ttl' => 86400,
        'traffic-fines.cache.error_ttl' => 60,
        'traffic-fines.billing.api_v2_request_price' => 150,
        'traffic-fines.sources.xephatnguoi.token' => 'v2-provider-token',
    ]);

    Cache::store('array')->flush();
    Http::preventStrayRequests();
});

/**
 * @return array{user: User, api_key: ApiKey, headers: array{X-API-KEY: string, X-API-SECRET: string}}
 */
function trafficFineV2Account(float $balance = 500): array
{
    $user = User::factory()->create();
    $user->wallet()->update([
        'balance' => $balance,
        'total_spent' => 0,
    ]);

    $secret = 'sk_'.Str::random(40);
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'key_type' => ApiKey::TYPE_WALLET,
        'name' => 'API v2 test key',
        'api_key' => 'ak_'.Str::lower(Str::random(28)),
        'api_secret_hash' => Hash::make($secret),
        'api_secret_encrypted' => $secret,
        'permissions' => ['traffic-fines.lookup'],
        'ip_whitelist' => ['*'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    return [
        'user' => $user,
        'api_key' => $apiKey,
        'headers' => [
            'X-API-KEY' => $apiKey->api_key,
            'X-API-SECRET' => $secret,
        ],
    ];
}

function fakeSuccessfulTrafficFineV2Response(): void
{
    Http::fake([
        'https://api.xephatnguoi.com/v2/search*' => Http::response([
            'status' => 'success',
            'plate' => '30K12345',
            'type' => 1,
            'data' => [],
            'total' => 0,
            'timestamp' => '2026-09-05 17:26:21',
        ]),
    ]);
}

it('uses only xephatnguoi v2 and charges 150 dong for each successful request', function (): void {
    app(SettingStore::class)->putString(TrafficFineProviderSettingsService::ACTIVE_PROVIDER_KEY, 'another_provider');
    fakeSuccessfulTrafficFineV2Response();
    $account = trafficFineV2Account();

    $this->withHeaders($account['headers'])
        ->getJson('/api/v2/lookup?plate=30K12345&vehicle_type=car')
        ->assertOk()
        ->assertJsonPath('cached', false)
        ->assertJsonPath('data.plate', '30K12345')
        ->assertJsonPath('data.vehicle_type', 'car');

    $this->withHeaders($account['headers'])
        ->getJson('/api/v2/lookup?plate=30K12345&vehicle_type=car')
        ->assertOk()
        ->assertJsonPath('cached', true);

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request): bool {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return parse_url($request->url(), PHP_URL_PATH) === '/v2/search'
            && $query === ['plate' => '30K12345', 'type' => '1']
            && $request->hasHeader('Authorization', 'Bearer v2-provider-token');
    });

    expect((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(200.0)
        ->and(WalletTransaction::query()->where('reference_type', 'traffic_fine_api_v2_request')->count())->toBe(2)
        ->and(ApiLog::query()->where('endpoint', 'api/v2/lookup')->where('unit_price', 150)->count())->toBe(2)
        ->and(TrafficFineResult::query()->where('provider', 'xephatnguoi_v2')->count())->toBe(1)
        ->and(TrafficFineLookupLog::query()->where('provider', 'xephatnguoi_v2')->count())->toBe(2);

    Sanctum::actingAs($account['user']);
    $this->getJson('/api/client/traffic-fines/api-usage')
        ->assertOk()
        ->assertJsonPath('data.logs.data.0.api_version', 'v2')
        ->assertJsonPath('data.logs.data.1.api_version', 'v2');
});

it('rejects insufficient v2 balance before calling or charging the provider', function (): void {
    fakeSuccessfulTrafficFineV2Response();
    $account = trafficFineV2Account(balance: 149);

    $this->withHeaders($account['headers'])
        ->getJson('/api/v2/lookup?plate=30K12345&vehicle_type=car')
        ->assertStatus(402)
        ->assertJsonPath('code', 'insufficient_balance')
        ->assertJsonPath('required_amount', 150);

    Http::assertNothingSent();
    expect((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(149.0)
        ->and(WalletTransaction::query()->where('reference_type', 'traffic_fine_api_v2_request')->exists())->toBeFalse();
});

it('requires the same public query parameters as api v1', function (): void {
    fakeSuccessfulTrafficFineV2Response();
    $account = trafficFineV2Account();

    $this->withHeaders($account['headers'])
        ->getJson('/api/v2/lookup?plate=30K12345&type=1')
        ->assertUnprocessable();

    Http::assertNothingSent();
    expect((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(500.0)
        ->and(WalletTransaction::query()->where('reference_type', 'traffic_fine_api_v2_request')->exists())->toBeFalse();
});

it('does not charge when xephatnguoi v2 fails', function (): void {
    Http::fake([
        'https://api.xephatnguoi.com/v2/search*' => Http::response(['status' => 'error'], 503),
    ]);
    $account = trafficFineV2Account();

    $this->withHeaders($account['headers'])
        ->getJson('/api/v2/lookup?plate=30K12345&vehicle_type=car')
        ->assertServiceUnavailable()
        ->assertJsonPath('status', 'provider_error');

    expect((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(500.0)
        ->and(WalletTransaction::query()->where('reference_type', 'traffic_fine_api_v2_request')->exists())->toBeFalse()
        ->and(ApiLog::query()->where('billing_status', 'charged')->exists())->toBeFalse();
});

it('lets admin configure the v2 price used by subsequent requests', function (): void {
    fakeSuccessfulTrafficFineV2Response();
    $account = trafficFineV2Account();
    $admin = User::factory()->create(['role' => 'admin']);

    Sanctum::actingAs($admin);
    $this->getJson('/api/admin-api/traffic-fines/billing')
        ->assertOk()
        ->assertJsonPath('data.api_v2_request_price', 150);

    $this->putJson('/api/admin-api/traffic-fines/billing', [
        'api_request_price' => 20,
        'api_v2_request_price' => 175,
        'api_v1_description' => 'API tiêu chuẩn cho hệ thống hiện tại.',
        'api_v2_description' => 'API nâng cao dành cho kết nối mới.',
    ])
        ->assertOk()
        ->assertJsonPath('data.api_request_price', 20)
        ->assertJsonPath('data.api_v2_request_price', 175)
        ->assertJsonPath('data.api_v1_description', 'API tiêu chuẩn cho hệ thống hiện tại.')
        ->assertJsonPath('data.api_v2_description', 'API nâng cao dành cho kết nối mới.');

    Sanctum::actingAs($account['user']);
    $dashboard = $this->getJson('/api/client/traffic-fines/dashboard')
        ->assertOk()
        ->assertJsonPath('data.api_v1_description', 'API tiêu chuẩn cho hệ thống hiện tại.')
        ->assertJsonPath('data.api_v2_description', 'API nâng cao dành cho kết nối mới.');

    expect(mb_strtolower(json_encode($dashboard->json(), JSON_THROW_ON_ERROR)))
        ->not->toContain('xephatnguoi')
        ->not->toContain('api.xephatnguoi.com')
        ->not->toContain('v2-provider-token');

    $this->withHeaders($account['headers'])
        ->getJson('/api/v2/lookup?plate=30K12345&vehicle_type=car')
        ->assertOk();

    expect((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(325.0)
        ->and((float) ApiLog::query()->where('endpoint', 'api/v2/lookup')->sole()->unit_price)->toBe(175.0)
        ->and((float) WalletTransaction::query()->where('reference_type', 'traffic_fine_api_v2_request')->sole()->amount)->toBe(175.0);
});
