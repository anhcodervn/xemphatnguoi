<?php

use App\Events\WalletBalanceChanged;
use App\Features\TrafficFine\Services\ApiLookupAvailabilityService;
use App\Features\TrafficFine\Services\TrafficFineProviderSettingsService;
use App\Features\TrafficFine\Services\TrafficFineTurnstileSettingsService;
use App\Models\TrafficFineResult;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\SettingStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config([
        'traffic-fines.api_v2.url' => 'https://api.xephatnguoi.com/v2/search',
        'traffic-fines.cache.store' => 'array',
        'traffic-fines.cache.ttl' => 86400,
        'traffic-fines.cache.error_ttl' => 60,
        'traffic-fines.billing.api_v2_request_price' => 150,
        'traffic-fines.sources.xephatnguoi.token' => 'web-v2-provider-token',
        'services.turnstile.enabled' => false,
    ]);

    Cache::store('array')->flush();
    Event::fake([WalletBalanceChanged::class]);
    Http::preventStrayRequests();
});

function fakeSuccessfulWebV2Lookup(): void
{
    Http::fake([
        'https://api.xephatnguoi.com/v2/search*' => Http::response([
            'status' => 'success',
            'plate' => '30K12345',
            'type' => 1,
            'data' => [],
            'total' => 0,
            'timestamp' => '2026-09-06 08:00:00',
        ]),
    ]);
}

function webV2User(float $balance = 500): User
{
    $user = User::factory()->create();
    $user->wallet()->update([
        'balance' => $balance,
        'total_spent' => 0,
    ]);

    return $user;
}

it('uses provider v1 for web v2 lookups when v2 is disabled', function (): void {
    config(['traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search']);
    app(ApiLookupAvailabilityService::class)->update(v1Enabled: true, v2Enabled: false);
    Http::fake(['https://api.xephatnguoi.com/v1/search*' => Http::response([
        'status' => 'success', 'plate' => '30K12345', 'type' => 1, 'data' => [], 'total' => 0,
    ])]);
    $user = webV2User();
    Sanctum::actingAs($user);

    $this->postJson('/api/client/traffic-fines/lookup-v2', ['plate' => '30K12345', 'vehicle_type' => 'car'])
        ->assertOk()->assertJsonPath('success', true)->assertJsonPath('billing.charged_amount', '150.00');

    Http::assertSentCount(1);
    expect(TrafficFineResult::query()->sole()->provider)->toBe('xephatnguoi')
        ->and((float) $user->wallet()->firstOrFail()->balance)->toBe(350.0);
});

it('requires login before using v2 lookup from the public website', function (): void {
    $this->postJson('/api/client/traffic-fines/lookup-v2', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ])->assertUnauthorized();

    Http::assertNothingSent();
    expect(WalletTransaction::query()->exists())->toBeFalse();
});

it('charges the configured v2 price after a successful authenticated web lookup', function (): void {
    fakeSuccessfulWebV2Lookup();
    $user = webV2User();
    Sanctum::actingAs($user);

    $this->postJson('/api/client/traffic-fines/lookup-v2', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ])
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('data.plate', '30K12345')
        ->assertJsonPath('data.vehicle_type', 'car')
        ->assertJsonPath('billing.charged_amount', '150.00')
        ->assertJsonPath('billing.balance', '350.00');

    expect((float) $user->wallet()->firstOrFail()->balance)->toBe(350.0)
        ->and((float) $user->wallet()->firstOrFail()->total_spent)->toBe(150.0)
        ->and(WalletTransaction::query()->where('reference_type', 'traffic_fine_web_v2_request')->count())->toBe(1)
        ->and(TrafficFineResult::query()->where('provider', 'xephatnguoi_v2')->count())->toBe(1);
});

it('charges every successful v2 website lookup including cache hits', function (): void {
    fakeSuccessfulWebV2Lookup();
    $user = webV2User();
    Sanctum::actingAs($user);
    $payload = ['plate' => '30K12345', 'vehicle_type' => 'car'];

    $this->postJson('/api/client/traffic-fines/lookup-v2', $payload)
        ->assertOk()
        ->assertJsonMissingPath('cached');
    $this->postJson('/api/client/traffic-fines/lookup-v2', $payload)
        ->assertOk()
        ->assertJsonMissingPath('cached')
        ->assertJsonPath('billing.balance', '200.00');

    Http::assertSentCount(1);
    expect((float) $user->wallet()->firstOrFail()->balance)->toBe(200.0)
        ->and(WalletTransaction::query()->where('reference_type', 'traffic_fine_web_v2_request')->count())->toBe(2);
});

it('does not call the provider or charge when the v2 website balance is insufficient', function (): void {
    fakeSuccessfulWebV2Lookup();
    $user = webV2User(balance: 149);
    Sanctum::actingAs($user);

    $this->postJson('/api/client/traffic-fines/lookup-v2', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ])
        ->assertStatus(402)
        ->assertJsonPath('code', 'insufficient_balance')
        ->assertJsonPath('required_amount', 150);

    Http::assertNothingSent();
    expect((float) $user->wallet()->firstOrFail()->balance)->toBe(149.0)
        ->and(WalletTransaction::query()->exists())->toBeFalse();
});

it('does not charge when the v2 website provider fails', function (): void {
    Http::fake([
        'https://api.xephatnguoi.com/v2/search*' => Http::response(['status' => 'error'], 503),
    ]);
    $user = webV2User();
    Sanctum::actingAs($user);

    $this->postJson('/api/client/traffic-fines/lookup-v2', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ])
        ->assertServiceUnavailable()
        ->assertJsonPath('status', 'provider_error');

    expect((float) $user->wallet()->firstOrFail()->balance)->toBe(500.0)
        ->and(WalletTransaction::query()->exists())->toBeFalse();
});

it('requires the configured turnstile challenge before a paid v2 website lookup', function (): void {
    app(TrafficFineTurnstileSettingsService::class)->update(
        enabled: true,
        siteKey: 'turnstile-site-key',
        secretKey: 'turnstile-secret-key',
    );
    $user = webV2User();
    Sanctum::actingAs($user);

    $this->postJson('/api/client/traffic-fines/lookup-v2', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('status', 'captcha_required');

    Http::assertNothingSent();
    expect((float) $user->wallet()->firstOrFail()->balance)->toBe(500.0)
        ->and(WalletTransaction::query()->exists())->toBeFalse();
});

it('keeps v1 and v2 tabs for guests and members alongside the latest result checkbox', function (): void {
    app(SettingStore::class)->putString('traffic_fine_api_request_price', '35');
    app(SettingStore::class)->putString('traffic_fine_api_v2_request_price', '175');

    $response = $this->get('/')
        ->assertOk()
        ->assertSee('Tra cứu V1')
        ->assertSee('Tra cứu V2')
        ->assertSee('name="lookup_version" value="v1"', false)
        ->assertSee('name="lookup_version" value="v2"', false)
        ->assertSee('175đ/lượt')
        ->assertSee('Cần đăng nhập')
        ->assertSee('Để lấy kết quả mới nhất vui lòng')
        ->assertSee('name="latest_result"', false)
        ->assertSee('data-v1-price="35"', false)
        ->assertSee('data-v2-endpoint', false)
        ->assertSee('data-v2-price="175"', false);

    expect(mb_strtolower($response->getContent()))
        ->not->toContain('xephatnguoi')
        ->not->toContain('web-v2-provider-token');

    $this->actingAs(webV2User())->get('/')
        ->assertOk()
        ->assertSee('Tra cứu V1')
        ->assertSee('Tra cứu V2')
        ->assertSee('name="lookup_version" value="v1"', false)
        ->assertSee('name="lookup_version" value="v2"', false)
        ->assertSee('Trừ từ ví')
        ->assertSee('name="latest_result"', false)
        ->assertSee('Lấy kết quả mới nhất')
        ->assertSee('data-latest-price>35đ/lượt', false);
});

function fakeSuccessfulWebV1Lookup(): void
{
    config(['traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search']);
    Http::fake(['https://api.xephatnguoi.com/v1/search*' => Http::response([
        'status' => 'success', 'plate' => '30K12345', 'type' => 1, 'data' => [], 'total' => 0,
    ])]);
}

it('requires login before requesting the latest v1 result and never calls the provider for guests', function (): void {
    $this->postJson('/api/lookup', [
        'plate' => '30K12345', 'vehicle_type' => 'car', 'latest_result' => true,
    ])->assertUnauthorized();
    Http::assertNothingSent();
    expect(WalletTransaction::query()->exists())->toBeFalse();
});

it('charges the configured v1 price for latest v1 lookups and refreshes its paid cache after sixty minutes', function (): void {
    $this->freezeTime();
    fakeSuccessfulWebV1Lookup();
    app(SettingStore::class)->putString('traffic_fine_api_request_price', '35');
    app(SettingStore::class)->putString('traffic_fine_api_v2_request_price', '175');
    $user = webV2User();
    $this->actingAs($user);
    $payload = ['plate' => '30K12345', 'vehicle_type' => 'car'];

    $this->postJson('/api/lookup', $payload)->assertOk()->assertJsonPath('data.is_latest', false);
    $paidPayload = [...$payload, 'latest_result' => true];
    $this->postJson('/api/lookup', $paidPayload)
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('billing.charged_amount', '35.00')
        ->assertJsonPath('billing.balance', '465.00')
        ->assertJsonPath('data.is_latest', true);
    Http::assertSentCount(2);

    $this->get('/tra-cuu/30K12345?vehicle_type=car&latest_result=1')
        ->assertOk()->assertSee('Thông tin mới nhất');
    $this->travel(59)->minutes();
    Cache::store('array')->flush();
    $this->postJson('/api/lookup', $paidPayload)
        ->assertOk()->assertJsonPath('billing.charged_amount', '35.00')->assertJsonPath('data.is_latest', true);
    Http::assertSentCount(2);
    $this->travel(1)->minutes();
    $this->postJson('/api/lookup', $paidPayload)->assertOk()->assertJsonPath('data.is_latest', true);
    Http::assertSentCount(3);
    expect(WalletTransaction::query()->where('reference_type', 'traffic_fine_web_v1_request')->count())->toBe(3)
        ->and((float) $user->wallet()->firstOrFail()->balance)->toBe(395.0);
});

it('keeps normal v1 lookups free for authenticated users with no balance and caches them for a day', function (): void {
    $this->freezeTime();
    fakeSuccessfulWebV1Lookup();
    $user = webV2User(balance: 0);
    $this->actingAs($user);
    $this->postJson('/api/lookup', [
        'plate' => '30K12345', 'vehicle_type' => 'car', 'latest_result' => false,
    ])->assertOk()->assertJsonPath('data.is_latest', false)->assertJsonMissingPath('billing');

    expect(TrafficFineResult::query()->sole()->expires_at->timestamp - now()->timestamp)->toBe(86400)
        ->and(WalletTransaction::query()->exists())->toBeFalse();
});

it('checks the v1 price before calling the provider for a paid latest lookup', function (): void {
    app(SettingStore::class)->putString('traffic_fine_api_request_price', '35');
    $this->actingAs(webV2User(balance: 34))->postJson('/api/lookup', [
        'plate' => '30K12345', 'vehicle_type' => 'car', 'latest_result' => true,
    ])->assertStatus(402)->assertJsonPath('required_amount', 35);
    Http::assertNothingSent();
    expect(WalletTransaction::query()->exists())->toBeFalse();
});

it('does not charge a latest v1 lookup when the provider fails', function (): void {
    config(['traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search']);
    Http::fake(['https://api.xephatnguoi.com/v1/search*' => Http::response(['status' => 'error'], 503)]);
    $user = webV2User();
    $this->actingAs($user)->postJson('/api/lookup', [
        'plate' => '30K12345', 'vehicle_type' => 'car', 'latest_result' => true,
    ])->assertServiceUnavailable()->assertJsonPath('status', 'provider_error');
    expect(WalletTransaction::query()->exists())->toBeFalse()
        ->and((float) $user->wallet()->firstOrFail()->balance)->toBe(500.0);
});

it('requires login to view the cached paid v1 result', function (): void {
    $this->get('/tra-cuu/30K12345?vehicle_type=car&latest_result=1')->assertRedirectToRoute('auth.login');
});

it('reads a paid v2 cached result only for an authenticated user', function (): void {
    fakeSuccessfulWebV2Lookup();
    $user = webV2User();
    Sanctum::actingAs($user);

    $this->postJson('/api/client/traffic-fines/lookup-v2', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ])->assertOk();

    $this->get('/tra-cuu/30K12345?vehicle_type=car&api_version=v2')
        ->assertOk()
        ->assertSee('30K-123.45');

    auth()->forgetGuards();

    $this->get('/tra-cuu/30K12345?vehicle_type=car&api_version=v2')
        ->assertRedirectToRoute('auth.login');
});

it('expires paid website results after sixty minutes in both cache and database', function (bool $flushCache): void {
    $this->freezeTime();
    fakeSuccessfulWebV2Lookup();
    Sanctum::actingAs(webV2User(balance: 1000));
    $payload = ['plate' => '30K12345', 'vehicle_type' => 'car'];
    $first = $this->postJson('/api/client/traffic-fines/lookup-v2', $payload)
        ->assertOk()->assertJsonPath('data.is_latest', true)->json('data.created_cache');

    expect(TrafficFineResult::query()->sole()->expires_at->timestamp - now()->timestamp)->toBe(3600);
    $this->travel(59)->minutes();
    if ($flushCache) {
        Cache::store('array')->flush();
    }
    $this->postJson('/api/client/traffic-fines/lookup-v2', $payload)
        ->assertOk()->assertJsonPath('data.is_latest', true)->assertJsonPath('data.created_cache', $first);
    Http::assertSentCount(1);

    $this->travel(1)->minutes();
    $this->postJson('/api/client/traffic-fines/lookup-v2', $payload)
        ->assertOk()->assertJsonPath('data.is_latest', true);
    Http::assertSentCount(2);
})->with([false, true]);

it('keeps both public gateways enabled and uses provider v1 with the v2 price and paid cache', function (): void {
    config(['traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search']);
    app(ApiLookupAvailabilityService::class)->update(v1Enabled: true, v2Enabled: true);
    app(SettingStore::class)->putString(TrafficFineProviderSettingsService::V2_API_VERSION_KEY, 'v1');
    app(SettingStore::class)->putString('traffic_fine_api_v2_request_price', '175');
    Http::fake(['https://api.xephatnguoi.com/v1/search*' => Http::response([
        'status' => 'success', 'plate' => '30K12345', 'type' => 1, 'data' => [], 'total' => 0,
    ])]);
    $payload = ['plate' => '30K12345', 'vehicle_type' => 'car'];
    $this->postJson('/api/lookup', $payload)->assertOk()->assertJsonPath('data.is_latest', false);

    Sanctum::actingAs(webV2User());
    $this->postJson('/api/client/traffic-fines/lookup-v2', $payload)
        ->assertOk()->assertJsonPath('data.is_latest', true)->assertJsonPath('billing.charged_amount', '175.00');
    $this->postJson('/api/client/traffic-fines/lookup-v2', $payload)
        ->assertOk()->assertJsonPath('data.is_latest', true)->assertJsonPath('billing.balance', '150.00');
    Http::assertSentCount(2);
    expect(TrafficFineResult::query()->where('api_version', 'v2')->sole()->provider)->toBe('xephatnguoi');
    $this->get('/tra-cuu/30K12345?vehicle_type=car&api_version=v2')
        ->assertOk()->assertSee('Thông tin mới nhất');
});
