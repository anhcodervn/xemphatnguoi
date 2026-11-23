<?php

use App\Events\WalletBalanceChanged;
use App\Features\TrafficFine\Actions\CheckVehicleMonitoringAction;
use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Services\Source\Xephatnguoi\XephatnguoiV2Source;
use App\Features\TrafficFine\Services\TrafficFineV2LookupService;
use App\Models\ApiKey;
use App\Models\MonitoringSubscription;
use App\Models\TrafficFineLookupLog;
use App\Models\TrafficFineResult;
use App\Models\User;
use App\Models\UserVehicle;
use App\Models\VehicleMonitoring;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'fallback-test-token',
        'traffic-fines.api_v2.url' => 'https://api.xephatnguoi.com/v2/search',
        'traffic-fines.cache.store' => 'array',
        'services.turnstile.enabled' => false,
    ]);
    Cache::store('array')->flush();
    Http::preventStrayRequests();
});

function fallbackSuccessPayload(): array
{
    return ['status' => 'success', 'plate' => '30K12345', 'type' => 1, 'data' => [], 'total' => 0];
}

it('uses v1 when v2 returns an http or payload error', function (int $status, mixed $body): void {
    Http::fake([
        'https://api.xephatnguoi.com/v2/search*' => Http::response($body, $status),
        'https://api.xephatnguoi.com/v1/search*' => Http::response(fallbackSuccessPayload()),
    ]);
    $result = app(XephatnguoiV2Source::class)->lookup('30K12345', VehicleType::Car);

    expect($result->violationCount)->toBe(0)->and($result->plate)->toBe('30K12345');
    Http::assertSentCount(2);
    expect(Http::recorded()[0][0]->url())->toContain('/v2/search')
        ->and(Http::recorded()[1][0]->url())->toContain('/v1/search');
})->with([
    'unauthorized' => [401, ['status' => 'error']],
    'rate limit' => [429, ['status' => 'error']],
    'server error' => [503, ['status' => 'error']],
    'unsuccessful payload' => [200, ['status' => 'error']],
    'invalid json' => [200, 'invalid json'],
    'invalid result' => [200, ['status' => 'success', 'data' => null]],
]);

it('falls back after a v2 connection failure and caches the successful result', function (): void {
    Http::fake([
        'https://api.xephatnguoi.com/v2/search*' => Http::failedConnection(),
        'https://api.xephatnguoi.com/v1/search*' => Http::response(fallbackSuccessPayload()),
    ]);
    $service = app(TrafficFineV2LookupService::class);
    $service->lookup('30K12345', 'car');
    $service->lookup('30K12345', 'car');

    Http::assertSentCount(2);
    expect(TrafficFineLookupLog::query()->where('status', 'provider_error')->count())->toBe(0);
});

it('keeps v2 success without calling v1', function (): void {
    Http::fake(['https://api.xephatnguoi.com/v2/search*' => Http::response(fallbackSuccessPayload())]);
    app(XephatnguoiV2Source::class)->lookup('30K12345', VehicleType::Car);
    Http::assertSentCount(1);
});

it('charges once on api and website fallback success and does not charge if both fail', function (bool $paidApi): void {
    $user = User::factory()->create();
    $user->wallet()->update(['balance' => 1000]);
    Event::fake([WalletBalanceChanged::class]);
    $headers = [];
    if ($paidApi) {
        $key = ApiKey::query()->create([
            'user_id' => $user->id, 'key_type' => ApiKey::TYPE_WALLET, 'name' => 'Fallback test',
            'api_key' => 'ak_fallback_test', 'api_secret_hash' => Hash::make('fallback-secret'),
            'api_secret_encrypted' => 'fallback-secret', 'permissions' => ['traffic-fines.lookup'],
            'ip_whitelist' => ['*'], 'status' => ApiKey::STATUS_ACTIVE,
        ]);
        $headers = ['X-API-KEY' => $key->api_key, 'X-API-SECRET' => 'fallback-secret'];
    } else {
        Sanctum::actingAs($user);
    }
    Http::fake([
        'https://api.xephatnguoi.com/v2/search*' => Http::response(['status' => 'error'], 503),
        'https://api.xephatnguoi.com/v1/search*' => Http::sequence()
            ->push(fallbackSuccessPayload())
            ->push(['status' => 'error'], 503)
            ->push(['status' => 'error'], 503),
    ]);
    $lookup = fn () => $paidApi
        ? $this->getJson('/api/v2/lookup?plate=30K12345&vehicle_type=car', $headers)
        : $this->postJson('/api/client/traffic-fines/lookup-v2', ['plate' => '30K12345', 'vehicle_type' => 'car']);
    $lookup()->assertOk()->assertJsonPath('data.is_latest', true);
    expect((float) $user->wallet()->firstOrFail()->balance)->toBe(850.0)
        ->and(WalletTransaction::query()->count())->toBe(1);
    Http::assertSentCount(2);

    Cache::store('array')->flush();
    TrafficFineResult::query()->delete();
    $lookup()->assertServiceUnavailable()->assertJsonPath('status', 'provider_error');
    expect((float) $user->wallet()->firstOrFail()->balance)->toBe(850.0)
        ->and(WalletTransaction::query()->count())->toBe(1);
})->with([true, false]);

it('updates scheduled monitoring through v1 when v2 fails', function (): void {
    $user = User::factory()->create();
    MonitoringSubscription::factory()->for($user)->create(['started_at' => now()->subDay(), 'expires_at' => now()->addDay()]);
    $vehicle = UserVehicle::factory()->for($user)->create(['plate' => '30K12345', 'vehicle_type' => 'car']);
    $monitoring = VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create(['enabled' => true]);
    Http::fake([
        'https://api.xephatnguoi.com/v2/search*' => Http::response(['status' => 'error'], 503),
        'https://api.xephatnguoi.com/v1/search*' => Http::response(fallbackSuccessPayload()),
    ]);
    app(CheckVehicleMonitoringAction::class)->handle($monitoring->id);
    expect($monitoring->refresh()->last_checked_at)->not->toBeNull()
        ->and($monitoring->last_violation_count)->toBe(0)
        ->and(WalletTransaction::query()->count())->toBe(0);
    Http::assertSentCount(2);
});
