<?php

use App\Events\WalletBalanceChanged;
use App\Features\TrafficFine\Services\ApiLookupAvailabilityService;
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

it('shows a login note for guests and a priced latest result checkbox for members', function (): void {
    app(SettingStore::class)->putString('traffic_fine_api_v2_request_price', '175');

    $response = $this->get('/')
        ->assertOk()
        ->assertSee('Để lấy kết quả mới nhất vui lòng')
        ->assertDontSee('name="latest_result"', false)
        ->assertSee('data-v2-endpoint', false)
        ->assertSee('data-v2-price="175"', false);

    expect(mb_strtolower($response->getContent()))
        ->not->toContain('xephatnguoi')
        ->not->toContain('web-v2-provider-token');

    $this->actingAs(webV2User())->get('/')
        ->assertOk()
        ->assertSee('name="latest_result"', false)
        ->assertSee('Lấy kết quả mới nhất 175đ/lượt');
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

it('uses v1 with the v2 price and a separate paid cache when v2 is disabled', function (): void {
    config(['traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search']);
    app(ApiLookupAvailabilityService::class)->update(v1Enabled: true, v2Enabled: false);
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
