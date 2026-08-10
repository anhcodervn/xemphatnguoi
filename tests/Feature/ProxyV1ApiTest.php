<?php

use App\Features\Client\Proxy\Actions\ChangeProxyAction;
use App\Features\Client\Proxy\Actions\FetchRotatingProxyAction;
use App\Features\Client\Proxy\Actions\OrderAction;
use App\Features\Client\Proxy\Actions\RenewProxyAction;
use App\Models\ApiKey;
use App\Models\ApiLog;
use App\Models\ProxyCategory;
use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\UserProxy;
use App\Support\ApiPermissionCatalog;
use Illuminate\Http\Client\Request as ProviderRequest;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** @return array{X-API-KEY: string, X-API-SECRET: string} */
function proxyV1Headers(User $user, array $permissions): array
{
    $secret = 'sk_'.Str::random(40);
    $apiKeyValue = 'ak_'.Str::lower(Str::random(28));

    ApiKey::query()->create([
        'user_id' => $user->id,
        'key_type' => ApiKey::TYPE_WALLET,
        'name' => 'Proxy v1 test',
        'api_key' => $apiKeyValue,
        'api_secret_hash' => Hash::make($secret),
        'api_secret_encrypted' => $secret,
        'permissions' => $permissions,
        'ip_whitelist' => ['*'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    return [
        'X-API-KEY' => $apiKeyValue,
        'X-API-SECRET' => $secret,
    ];
}

function proxyV1UserProxy(User $user): UserProxy
{
    $category = ProxyCategory::query()->create([
        'code' => 'api-v1-category-'.Str::lower(Str::random(8)),
        'name' => 'API v1 category',
        'is_active' => true,
    ]);
    $provider = ProxyProvider::query()->create([
        'name' => 'API v1 provider',
        'code' => 'api-v1-provider-'.Str::lower(Str::random(8)),
        'driver' => ProxyProvider::DRIVER_PROXY_VN,
        'order_method' => ProxyProvider::ORDER_METHOD_AUTOMATIC,
        'credentials' => [
            'base_url' => 'https://proxy.vn/apiv2',
            'key' => 'provider-secret-key',
            'user' => 'dailyproxy-user',
            'password' => 'dailyproxy-password',
        ],
        'is_active' => true,
    ]);
    $product = ProxyProduct::query()->create([
        'proxy_category_id' => $category->id,
        'default_provider_id' => $provider->id,
        'code' => 'api-v1-product-'.Str::lower(Str::random(8)),
        'name' => 'API v1 product',
        'protocol' => 'http',
        'supported_protocols' => ['http'],
        'provider_product_code' => 'FPT',
        'base_price' => 1000,
        'selling_price' => 1200,
        'is_active' => true,
    ]);

    return UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $provider->id,
        'provider_proxy_id' => 'provider-proxy-id',
        'status' => UserProxy::STATUS_ACTIVE,
        'protocol' => 'http',
    ]);
}

it('publishes all proxy v1 permissions and endpoints', function () {
    $permissions = ApiPermissionCatalog::keyed();

    expect($permissions)->toHaveKeys([
        'proxy-products.read',
        'proxy-orders.create',
        'proxy-operations.write',
        'proxy-rotating.read',
    ])->and($permissions['proxy-operations.write']['endpoints'])->toBe([
        'POST /api/v1/proxy/change',
        'POST /api/v1/proxy/renew',
    ]);
});

it('requires api key authentication and the endpoint permission', function () {
    $this->postJson('/api/v1/proxy/orders')->assertUnauthorized();

    $headers = proxyV1Headers(User::factory()->create(), ['proxy-products.read']);

    $this->withHeaders($headers)
        ->postJson('/api/v1/proxy/orders', [
            'productCode' => 'static-vn',
            'quantity' => 1,
            'durationDays' => 1,
            'protocol' => 'http',
        ])
        ->assertForbidden();
});

it('returns a concise authenticated user profile from v1 me', function () {
    $user = User::factory()->create([
        'username' => 'dailyproxy-user',
        'email' => 'user@dailyproxy.test',
        'full_name' => 'Daily Proxy User',
        'phone' => '0900000000',
        'role' => 'user',
        'status' => 'active',
    ]);
    $user->wallet()->update(['balance' => 987500]);
    $headers = proxyV1Headers($user, []);

    $this->getJson('/api/v1/me')->assertUnauthorized();

    $this->withHeaders($headers)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertExactJson([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'username' => 'dailyproxy-user',
                'email' => 'user@dailyproxy.test',
                'full_name' => 'Daily Proxy User',
                'status' => 'active',
                'balance' => '987500.00',
            ],
        ]);

    expect(ApiLog::query()->where('endpoint', 'api/v1/me')->count())->toBe(1);
});

it('maps camel case api payloads to the existing proxy actions', function () {
    $user = User::factory()->create();
    $headers = proxyV1Headers($user, [
        'proxy-orders.create',
        'proxy-operations.write',
        'proxy-rotating.read',
    ]);
    $orderAction = Mockery::mock(OrderAction::class);
    $orderAction->shouldReceive('handle')->once()->withArgs(
        fn (User $owner, array $payload): bool => $owner->is($user) && $payload === [
            'product_code' => 'static-vn',
            'quantity' => 2,
            'duration_days' => 30,
            'protocol' => 'http',
        ],
    )->andReturn([
        'order' => ['id' => 81, 'order_code' => 'PXY-TEST-ORDER', 'status' => 'fulfilled'],
        'proxies' => [[
            'id' => 42,
            'country_code' => 'VN',
            'protocol' => 'http',
            'host' => '127.0.0.1',
            'port' => 8080,
            'username' => 'secret-user',
            'password' => 'secret-password',
            'access_key' => null,
            'expires_at' => '2026-09-09T05:42:17.000000Z',
        ]],
    ]);
    app()->instance(OrderAction::class, $orderAction);

    $changeAction = Mockery::mock(ChangeProxyAction::class);
    $changeAction->shouldReceive('handleSynchronously')->once()->withArgs(
        fn (User $owner, int $proxyId, array $payload): bool => $owner->is($user)
            && $proxyId === 42
            && $payload === [],
    )->andReturn([
        'order' => ['id' => 82, 'type' => 'change', 'status' => 'fulfilled'],
        'proxy' => [
            'id' => 42,
            'connection' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'username' => 'user',
                'password' => 'password',
            ],
        ],
    ]);
    app()->instance(ChangeProxyAction::class, $changeAction);

    $renewAction = Mockery::mock(RenewProxyAction::class);
    $renewAction->shouldReceive('handleSynchronously')->once()->withArgs(
        fn (User $owner, int $proxyId, array $payload): bool => $owner->is($user)
            && $proxyId === 42
            && $payload === ['duration_days' => 30],
    )->andReturn([
        'order' => ['id' => 83, 'type' => 'renew', 'status' => 'fulfilled'],
        'proxy' => ['id' => 42, 'expires_at' => '2026-09-09T05:42:17.000000Z'],
    ]);
    app()->instance(RenewProxyAction::class, $renewAction);

    $fetchAction = Mockery::mock(FetchRotatingProxyAction::class);
    $fetchAction->shouldReceive('handle')->once()->withArgs(
        fn (User $owner, int $proxyId, string $protocol): bool => $owner->is($user)
            && $proxyId === 57
            && $protocol === 'socks5',
    )->andReturn([
        'proxy_id' => 57,
        'proxy' => '127.0.0.1:1080:user:password',
        'protocol' => 'socks5',
        'message' => 'Lấy proxy xoay thành công.',
    ]);
    app()->instance(FetchRotatingProxyAction::class, $fetchAction);

    $this->withHeaders($headers)->postJson('/api/v1/proxy/orders', [
        'productCode' => ' static-vn ',
        'quantity' => 2,
        'durationDays' => 30,
        'protocol' => 'HTTP',
    ])->assertOk()->assertExactJson([
        'status' => true,
        'message' => 'Mua proxy thành công.',
        'orderCode' => 'PXY-TEST-ORDER',
        'proxy' => [[
            'id' => 42,
            'protocol' => 'http',
            'key' => null,
            'proxy' => '127.0.0.1:8080:secret-user:secret-password',
            'country_code' => 'VN',
            'expired_at' => '2026-09-09T05:42:17.000000Z',
        ]],
    ]);

    $this->withHeaders($headers)->postJson('/api/v1/proxy/change', [
        'orderCode' => 42,
    ])->assertOk()
        ->assertExactJson([
            'status' => true,
            'message' => 'Đổi proxy thành công.',
            'products' => [[
                'id' => 42,
                'proxy' => '127.0.0.1:8080:user:password',
            ]],
        ]);

    $this->withHeaders($headers)->postJson('/api/v1/proxy/renew', [
        'orderCode' => 42,
        'durationDays' => 30,
    ])->assertOk()
        ->assertExactJson([
            'status' => true,
            'message' => 'Gia hạn proxy thành công.',
            'products' => [[
                'id' => 42,
                'expired_at' => '2026-09-09T05:42:17.000000Z',
            ]],
        ]);

    $this->withHeaders($headers)->postJson('/api/v1/proxy/rotating', [
        'id' => 57,
        'protocol' => 'SOCKS5',
    ])->assertOk()
        ->assertJsonPath('data.proxy_id', 57)
        ->assertJsonPath('data.protocol', 'socks5');

    $orderLog = ApiLog::query()->where('endpoint', 'api/v1/proxy/orders')->sole();
    $changeLog = ApiLog::query()->where('endpoint', 'api/v1/proxy/change')->sole();
    $rotatingLog = ApiLog::query()->where('endpoint', 'api/v1/proxy/rotating')->sole();

    expect(data_get($orderLog->response_data, 'proxy'))->toBe('[REDACTED]')
        ->and(data_get($changeLog->response_data, 'products.0.proxy'))->toBe('[REDACTED]')
        ->and(data_get($rotatingLog->response_data, 'data.proxy'))->toBe('[REDACTED]');
});

it('does not expose another users proxy through orderCode', function () {
    $apiUser = User::factory()->create();
    $otherUserProxy = proxyV1UserProxy(User::factory()->create());
    $headers = proxyV1Headers($apiUser, ['proxy-operations.write']);

    $this->withHeaders($headers)->postJson('/api/v1/proxy/change', [
        'orderCode' => $otherUserProxy->id,
    ])->assertNotFound();
});

it('returns a key instead of a proxy string after buying a rotating proxy through api', function () {
    $user = User::factory()->create();
    $headers = proxyV1Headers($user, ['proxy-orders.create']);
    $action = Mockery::mock(OrderAction::class);
    $action->shouldReceive('handle')->once()->andReturn([
        'order' => ['order_code' => 'PXY-ROTATING-ORDER'],
        'proxies' => [[
            'id' => 57,
            'country_code' => 'VN',
            'protocol' => 'http',
            'host' => null,
            'port' => null,
            'username' => null,
            'password' => null,
            'access_key' => 'rotating-access-key',
            'expires_at' => '2026-09-09T05:42:17.000000Z',
        ]],
    ]);
    app()->instance(OrderAction::class, $action);

    $this->withHeaders($headers)
        ->postJson('/api/v1/proxy/orders', [
            'productCode' => 'rotating-vn',
            'quantity' => 1,
            'durationDays' => 30,
            'protocol' => 'http',
        ])
        ->assertOk()
        ->assertExactJson([
            'status' => true,
            'message' => 'Mua proxy thành công.',
            'orderCode' => 'PXY-ROTATING-ORDER',
            'proxy' => [[
                'id' => 57,
                'protocol' => 'http',
                'country_code' => 'VN',
                'expired_at' => '2026-09-09T05:42:17.000000Z',
                'key' => 'rotating-access-key',
                'proxy' => null,
            ]],
        ]);
});

it('processes an api proxy change synchronously without waiting for the queue', function () {
    $user = User::factory()->create();
    $proxy = proxyV1UserProxy($user);
    $user->wallet()->update(['balance' => 5000]);
    $headers = proxyV1Headers($user, ['proxy-operations.write']);

    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/apiv2/doiproxy.php*' => Http::response([
            'status' => 100,
            'loaiproxy' => 'FPT',
            'idproxy' => 2772,
            'ip' => '27.73.88.211',
            'port' => 35270,
            'user' => 'api-user',
            'password' => 'api-password',
            'type' => 'HTTPS',
        ]),
    ]);
    Event::fake();

    $this->withHeaders($headers)
        ->postJson('/api/v1/proxy/change', ['orderCode' => $proxy->id])
        ->assertOk()
        ->assertExactJson([
            'status' => true,
            'message' => 'Đổi proxy thành công.',
            'products' => [[
                'id' => $proxy->id,
                'proxy' => '27.73.88.211:35270:api-user:api-password',
            ]],
        ]);

    expect(ProxyOrder::query()->sole()->status)->toBe(ProxyOrder::STATUS_FULFILLED)
        ->and($proxy->fresh()->provider_proxy_id)->toBe('2772')
        ->and($user->wallet->fresh()->balance)->toBe('4000.00');

    Http::assertSent(fn (ProviderRequest $request): bool => str_starts_with($request->url(), 'https://proxy.vn/apiv2/doiproxy.php?'));
});

it('returns an api error and refunds the wallet when a synchronous change fails', function () {
    $user = User::factory()->create();
    $proxy = proxyV1UserProxy($user);
    $originalProviderProxyId = $proxy->provider_proxy_id;
    $user->wallet()->update(['balance' => 5000]);
    $headers = proxyV1Headers($user, ['proxy-operations.write']);

    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/apiv2/doiproxy.php*' => Http::response(['status' => 102]),
    ]);
    Event::fake();

    $this->withHeaders($headers)
        ->postJson('/api/v1/proxy/change', ['orderCode' => $proxy->id])
        ->assertStatus(502)
        ->assertJsonPath('status', false);

    expect(ProxyOrder::query()->sole()->status)->toBe(ProxyOrder::STATUS_REFUNDED)
        ->and($proxy->fresh()->status)->toBe(UserProxy::STATUS_ACTIVE)
        ->and($proxy->fresh()->provider_proxy_id)->toBe($originalProviderProxyId)
        ->and($user->wallet->fresh()->balance)->toBe('5000.00');
});

it('validates rotating protocol and operation fields before dispatching actions', function (string $endpoint, array $payload) {
    $user = User::factory()->create();
    $headers = proxyV1Headers($user, ['proxy-orders.create', 'proxy-operations.write', 'proxy-rotating.read']);

    $this->withHeaders($headers)->postJson($endpoint, $payload)->assertUnprocessable();
})->with([
    'order requires product code' => ['/api/v1/proxy/orders', [
        'quantity' => 1,
        'durationDays' => 1,
        'protocol' => 'http',
    ]],
    'change requires user proxy id' => ['/api/v1/proxy/change', [
        'orderCode' => 0,
    ]],
    'change rejects a client supplied idempotency key' => ['/api/v1/proxy/change', [
        'orderCode' => 1,
        'idempotencyKey' => fake()->uuid(),
    ]],
    'renew requires positive days' => ['/api/v1/proxy/renew', [
        'orderCode' => 1,
        'durationDays' => 0,
    ]],
    'rotating only accepts http or socks5' => ['/api/v1/proxy/rotating', [
        'id' => 1,
        'protocol' => 'https',
    ]],
]);
