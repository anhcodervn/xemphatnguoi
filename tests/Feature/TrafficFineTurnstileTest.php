<?php

use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use App\Features\TrafficFine\Enums\LookupStatus;
use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Services\LicensePlateNormalizer;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceInterface;
use App\Features\TrafficFine\Services\TrafficFineTurnstileSettingsService;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config([
        'traffic-fines.cache.store' => 'array',
        'services.turnstile.enabled' => false,
        'services.turnstile.site_key' => '',
        'services.turnstile.secret_key' => '',
        'services.turnstile.hostname' => 'localhost',
        'services.turnstile.action' => 'traffic_fine_lookup',
        'services.turnstile.connect_timeout' => 2,
        'services.turnstile.timeout' => 5,
        'services.turnstile.grant_ttl' => 300,
    ]);

    Cache::store('array')->flush();
    Http::preventStrayRequests();
});

function bindTurnstileTrafficFineSource(): object
{
    $source = new class implements TrafficFineSourceInterface
    {
        public int $calls = 0;

        public function name(): string
        {
            return 'turnstile_test_source';
        }

        public function lookup(string $normalizedPlate, VehicleType $vehicleType): TrafficFineLookupResultDataDto
        {
            $this->calls++;

            return new TrafficFineLookupResultDataDto(
                plate: $normalizedPlate,
                displayPlate: app(LicensePlateNormalizer::class)->display($normalizedPlate),
                vehicleType: $vehicleType->value,
                status: LookupStatus::NoViolation->value,
                violationCount: 0,
                violations: [],
                checkedAt: now()->toImmutable(),
            );
        }
    };

    app()->instance(TrafficFineSourceInterface::class, $source);

    return $source;
}

function enableTurnstileForLookup(): TrafficFineTurnstileSettingsService
{
    $settings = app(TrafficFineTurnstileSettingsService::class);
    $settings->update(true, 'public-site-key', 'private-secret-key');

    return $settings;
}

it('requires turnstile for guest and regular user public lookups before calling the source', function (string $actor): void {
    $source = bindTurnstileTrafficFineSource();
    enableTurnstileForLookup();

    if ($actor === 'user') {
        $this->actingAs(User::factory()->create(['role' => 'user']));
    }

    $this->postJson('/api/lookup', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
    ])->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('status', 'captcha_required');

    expect($source->calls)->toBe(0);
    Http::assertNothingSent();
})->with(['guest', 'user']);

it('accepts a valid turnstile token and grants access to the stored result page', function (): void {
    $source = bindTurnstileTrafficFineSource();
    enableTurnstileForLookup();
    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
            'hostname' => 'localhost',
            'action' => 'traffic_fine_lookup',
        ]),
    ]);

    $response = $this->postJson('/api/lookup', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'cf-turnstile-response' => 'valid-single-use-token',
    ])->assertOk()
        ->assertJsonPath('success', true);

    expect($source->calls)->toBe(1)
        ->and(json_encode($response->json(), JSON_THROW_ON_ERROR))->not->toContain('private-secret-key');

    Http::assertSent(function (ClientRequest $request): bool {
        return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'private-secret-key'
            && $request['response'] === 'valid-single-use-token'
            && $request['remoteip'] === '127.0.0.1'
            && is_string($request['idempotency_key'])
            && str_contains((string) $request->header('Content-Type')[0], 'application/x-www-form-urlencoded');
    });

    $this->get('/tra-cuu/30A12345?vehicle_type=car')
        ->assertOk()
        ->assertSee('30A-123.45');

    expect($source->calls)->toBe(1);
});

it('rejects failed or mismatched turnstile responses before calling the source', function (array $payload): void {
    $source = bindTurnstileTrafficFineSource();
    enableTurnstileForLookup();
    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response($payload),
    ]);

    $this->postJson('/api/lookup', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'cf-turnstile-response' => 'invalid-token',
    ])->assertUnprocessable()
        ->assertJsonPath('status', 'captcha_failed');

    expect($source->calls)->toBe(0);
})->with([
    'failed challenge' => [['success' => false, 'hostname' => 'localhost', 'action' => 'traffic_fine_lookup']],
    'wrong action' => [['success' => true, 'hostname' => 'localhost', 'action' => 'different_action']],
    'wrong hostname' => [['success' => true, 'hostname' => 'attacker.example', 'action' => 'traffic_fine_lookup']],
]);

it('fails closed when cloudflare verification is unavailable', function (): void {
    $source = bindTurnstileTrafficFineSource();
    enableTurnstileForLookup();
    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([], 503),
    ]);

    $this->postJson('/api/lookup', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'cf-turnstile-response' => 'token-during-outage',
    ])->assertStatus(503)
        ->assertJsonPath('status', 'captcha_unavailable');

    expect($source->calls)->toBe(0);
});

it('allows an authenticated admin to bypass turnstile', function (): void {
    $source = bindTurnstileTrafficFineSource();
    enableTurnstileForLookup();
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $this->postJson('/api/lookup', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
    ])->assertOk()
        ->assertJsonPath('success', true);

    expect($source->calls)->toBe(1);
    Http::assertNothingSent();
});

it('does not apply public captcha to authenticated client lookup routes', function (): void {
    $source = bindTurnstileTrafficFineSource();
    enableTurnstileForLookup();
    Sanctum::actingAs(User::factory()->create(['role' => 'user']));

    $this->postJson('/api/client/traffic-fines/lookup', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
    ])->assertOk();

    expect($source->calls)->toBe(1);
    Http::assertNothingSent();
});

it('never lets a direct result page trigger the external source when captcha is enabled', function (): void {
    $source = bindTurnstileTrafficFineSource();
    enableTurnstileForLookup();

    $this->get('/tra-cuu/30A12345?vehicle_type=car')
        ->assertOk()
        ->assertSee('Vui lòng hoàn tất xác minh bảo mật')
        ->assertSee('data-turnstile-widget', false);

    expect($source->calls)->toBe(0);
    Http::assertNothingSent();
});

it('stores turnstile secrets encrypted and never returns them from admin settings', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $response = $this->patchJson('/api/admin-api/settings/turnstile', [
        'enabled' => true,
        'site_key' => 'admin-public-key',
        'secret_key' => 'admin-private-secret',
    ])->assertOk()
        ->assertJsonPath('data.settings.enabled', true)
        ->assertJsonPath('data.settings.site_key', 'admin-public-key')
        ->assertJsonPath('data.settings.secret_configured', true)
        ->assertJsonMissingPath('data.settings.secret_key');

    $storedSecret = Setting::query()
        ->where('key', TrafficFineTurnstileSettingsService::SECRET_KEY)
        ->firstOrFail();

    expect($storedSecret->type)->toBe('encrypted')
        ->and($storedSecret->value)->not->toBe('admin-private-secret')
        ->and(json_encode($response->json(), JSON_THROW_ON_ERROR))->not->toContain('admin-private-secret');

    $this->getJson('/api/admin-api/settings/turnstile')
        ->assertOk()
        ->assertJsonPath('data.settings.secret_configured', true)
        ->assertJsonMissingPath('data.settings.secret_key');

    $publicSettings = $this->getJson('/api/system-settings')->assertOk();

    expect(json_encode($publicSettings->json(), JSON_THROW_ON_ERROR))->not->toContain('admin-private-secret');

    $this->patchJson('/api/admin-api/settings/turnstile', [
        'enabled' => true,
        'site_key' => 'rotated-public-key',
    ])->assertOk();

    expect(app(TrafficFineTurnstileSettingsService::class)->secretKey())->toBe('admin-private-secret');
});

it('validates a complete turnstile pair before enabling it', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->patchJson('/api/admin-api/settings/turnstile', [
        'enabled' => true,
        'site_key' => '',
        'secret_key' => '',
    ])->assertUnprocessable()
        ->assertJsonPath('status', false)
        ->assertJsonStructure(['data' => ['errors' => ['site_key', 'secret_key']]]);
});

it('renders the widget for guests and regular users but not authenticated admins', function (string $actor, bool $shouldSeeWidget): void {
    enableTurnstileForLookup();
    $this->withoutVite();

    if ($actor !== 'guest') {
        $this->actingAs(User::factory()->create(['role' => $actor]));
    }

    $response = $this->get('/')->assertOk();

    if ($shouldSeeWidget) {
        $response
            ->assertSee('data-turnstile-widget', false)
            ->assertSee('data-site-key="public-site-key"', false)
            ->assertDontSee('private-secret-key');
    } else {
        $response->assertDontSee('data-turnstile-widget', false);
    }
})->with([
    'guest' => ['guest', true],
    'regular user' => ['user', true],
    'admin' => ['admin', false],
]);
