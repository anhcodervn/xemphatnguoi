<?php

use App\Events\WalletBalanceChanged;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config([
        'traffic-fines.cache.store' => 'array',
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'rate-limit-test-token',
        'traffic-fines.api_v2.url' => 'https://api.xephatnguoi.com/v2/search',
        'services.turnstile.enabled' => false,
    ]);

    Cache::store('array')->flush();
    Http::preventStrayRequests();
    $this->freezeTime();
});

it('allows twenty website lookup requests per minute per ip', function (string $endpoint, bool $authenticated): void {
    expect(config('traffic-fines.rate_limit.per_minute'))->toBe(20);

    if ($authenticated) {
        Sanctum::actingAs(User::factory()->create());
    }

    $payload = ['plate' => 'invalid', 'vehicle_type' => 'car'];

    for ($requestNumber = 0; $requestNumber < 20; $requestNumber++) {
        $this->postJson($endpoint, $payload)->assertUnprocessable();
    }

    $this->postJson($endpoint, $payload)
        ->assertTooManyRequests()
        ->assertJsonPath('status', 'rate_limited')
        ->assertHeader('Retry-After');

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
        ->postJson($endpoint, $payload)->assertUnprocessable();

    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
    $this->travel(61)->seconds();
    $this->postJson($endpoint, $payload)->assertUnprocessable();

    Http::assertNothingSent();
})->with([
    'public website' => ['/api/lookup', false],
    'authenticated website v1' => ['/api/client/traffic-fines/lookup', true],
    'authenticated website v2' => ['/api/client/traffic-fines/lookup-v2', true],
]);

it('does not rate limit paid api lookups or consume the website allowance', function (string $version): void {
    $user = User::factory()->create();
    Event::fake([WalletBalanceChanged::class]);
    $user->wallet()->update(['balance' => 10000]);
    $secret = 'sk_'.Str::random(40);
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'key_type' => ApiKey::TYPE_WALLET,
        'name' => 'Rate limit test',
        'api_key' => 'ak_'.Str::lower(Str::random(28)),
        'api_secret_hash' => Hash::make($secret),
        'api_secret_encrypted' => $secret,
        'permissions' => ['traffic-fines.lookup'],
        'ip_whitelist' => ['*'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    Http::fake([
        'https://api.xephatnguoi.com/*' => Http::response([
            'status' => 'success', 'plate' => '30K12345', 'type' => 1, 'data' => [], 'total' => 0,
        ]),
    ]);

    $headers = ['X-API-KEY' => $apiKey->api_key, 'X-API-SECRET' => $secret];

    for ($requestNumber = 0; $requestNumber < 21; $requestNumber++) {
        $this->getJson('/api/'.$version.'/lookup?plate=30K12345&vehicle_type=car', $headers)
            ->assertOk()
            ->assertHeaderMissing('X-RateLimit-Limit');
    }

    $payload = ['plate' => 'invalid', 'vehicle_type' => 'car'];

    for ($requestNumber = 0; $requestNumber < 20; $requestNumber++) {
        $this->postJson('/api/lookup', $payload)->assertUnprocessable();
    }

    $this->postJson('/api/lookup', $payload)->assertTooManyRequests();
    $this->getJson('/api/'.$version.'/lookup?plate=30K12345&vehicle_type=car', $headers)->assertOk();

    $price = (int) config($version === 'v2'
        ? 'traffic-fines.billing.api_v2_request_price'
        : 'traffic-fines.billing.api_request_price');

    expect((float) $user->wallet()->firstOrFail()->balance)->toBe((float) (10000 - 22 * $price));
})->with(['v1', 'v2']);
