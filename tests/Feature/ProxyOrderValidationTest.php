<?php

use App\Models\ProxyCategory;
use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\UserProxy;
use App\Models\WalletTransaction;
use Illuminate\Http\Client\Request as ProviderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

function proxyOrderProduct(
    array $productOverrides = [],
    array $providerOverrides = [],
    array $categoryOverrides = [],
): ProxyProduct {
    $category = ProxyCategory::query()->create([
        'code' => 'order-category',
        'name' => 'Order category',
        'is_active' => true,
        ...$categoryOverrides,
    ]);
    $provider = ProxyProvider::query()->create([
        'name' => 'Order provider',
        'code' => 'order-provider',
        'driver' => ProxyProvider::DRIVER_PROXY_VN,
        'order_method' => ProxyProvider::ORDER_METHOD_AUTOMATIC,
        'credentials' => [
            'base_url' => 'https://proxy.vn/apiv2',
            'key' => 'provider-secret-key',
        ],
        'is_active' => true,
        ...$providerOverrides,
    ]);

    return ProxyProduct::query()->create([
        'proxy_category_id' => $category->id,
        'default_provider_id' => $provider->id,
        'code' => 'order-product',
        'name' => 'Order product',
        'country_code' => 'VN',
        'protocol' => 'http',
        'supported_protocols' => ['http', 'socks5'],
        'selling_price' => 2500,
        'base_price' => 2000,
        'provider_product_code' => 'Viettel',
        'max_quantity' => 10,
        'is_active' => true,
        ...$productOverrides,
    ]);
}

function validProxyOrderPayload(array $overrides = []): array
{
    return [
        'product_code' => 'order-product',
        'quantity' => 2,
        'duration_days' => 3,
        'protocol' => 'HTTP',
        ...$overrides,
    ];
}

it('routes proxy orders through the proxy controller action service flow', function () {
    $route = Route::getRoutes()->match(
        Request::create('/api/client/proxy/orders', 'POST'),
    );

    expect($route->getActionName())
        ->toBe('App\Features\Client\Proxy\Controllers\ProxyController@order')
        ->and(app_path('Features/Client/Proxy/Actions/OrderAction.php'))->toBeFile()
        ->and(app_path('Features/Client/Proxy/Services/OrderService.php'))->toBeFile()
        ->and(app_path('Features/Client/Proxy/Services/ProxyService.php'))->toBeFile()
        ->and(app_path('Features/Client/Proxy/Controllers/OrderController.php'))->not->toBeFile()
        ->and(app_path('Features/Client/Proxy/Services/ProxyOrderService.php'))->not->toBeFile();
});

it('debits the wallet and fulfills a proxy order synchronously', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/apiv2/muaproxy.php*' => Http::response([
            [
                'status' => 100,
                'idproxy' => 2772,
                'ip' => '27.71.1.10',
                'port' => 35270,
                'user' => 'proxy-user-1',
                'password' => 'proxy-password-1',
                'type' => 'HTTP',
                'time' => now()->addDays(3)->timestamp,
            ],
            [
                'status' => 100,
                'idproxy' => 2773,
                'ip' => '27.71.1.11',
                'port' => 35271,
                'user' => 'proxy-user-2',
                'password' => 'proxy-password-2',
                'type' => 'HTTP',
                'time' => now()->addDays(3)->timestamp,
            ],
            ['status' => 200],
        ]),
    ]);

    $product = proxyOrderProduct();
    $user = User::factory()->create();
    $user->wallet()->update(['balance' => 20000]);

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload())
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.order.status', ProxyOrder::STATUS_FULFILLED)
        ->assertJsonPath('data.order.product_code', 'order-product')
        ->assertJsonPath('data.order.quantity', 2)
        ->assertJsonPath('data.order.duration_days', 3)
        ->assertJsonPath('data.order.protocol', 'http')
        ->assertJsonPath('data.order.unit_price', '2500.0000')
        ->assertJsonPath('data.order.total_amount', '15000.0000')
        ->assertJsonCount(2, 'data.proxies');

    expect(ProxyProduct::query()->count())->toBe(1)
        ->and(ProxyProvider::query()->count())->toBe(1)
        ->and(ProxyCategory::query()->count())->toBe(1)
        ->and(ProxyOrder::query()->count())->toBe(1)
        ->and(UserProxy::query()->count())->toBe(2)
        ->and(WalletTransaction::query()->count())->toBe(1)
        ->and($user->wallet()->value('balance'))->toBe('5000.00')
        ->and($user->wallet()->value('total_spent'))->toBe('15000.00');

    Http::assertSent(function (ProviderRequest $request) use ($product): bool {
        return $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://proxy.vn/apiv2/muaproxy.php?')
            && $request['key'] === 'provider-secret-key'
            && $request['loaiproxy'] === $product->provider_product_code
            && $request['soluong'] === 2
            && $request['ngay'] === 3
            && $request['type'] === 'HTTP';
    });
});

it('rolls back the order and wallet debit when the provider rejects it', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/apiv2/muaproxy.php*' => Http::response([
            ['status' => 102, 'comen' => 'Khong du tien'],
        ]),
    ]);

    proxyOrderProduct();
    $user = User::factory()->create();
    $user->wallet()->update(['balance' => 20000]);

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload())
        ->assertStatus(502)
        ->assertJsonPath('status', false)
        ->assertJsonPath('message', 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.')
        ->assertJsonMissingPath('error');

    expect(ProxyOrder::query()->count())->toBe(0)
        ->and(UserProxy::query()->count())->toBe(0)
        ->and(WalletTransaction::query()->count())->toBe(0)
        ->and($user->wallet()->value('balance'))->toBe('20000.00')
        ->and($user->wallet()->value('total_spent'))->toBe('0.00');

    Http::assertSentCount(1);
});

it('rolls back the order and wallet debit when the provider connection fails', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/apiv2/muaproxy.php*' => Http::failedConnection(),
    ]);

    proxyOrderProduct();
    $user = User::factory()->create();
    $user->wallet()->update(['balance' => 20000]);

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload())
        ->assertStatus(502)
        ->assertJsonPath('status', false)
        ->assertJsonMissingPath('error');

    expect(ProxyOrder::query()->count())->toBe(0)
        ->and(UserProxy::query()->count())->toBe(0)
        ->and(WalletTransaction::query()->count())->toBe(0)
        ->and($user->wallet()->value('balance'))->toBe('20000.00')
        ->and($user->wallet()->value('total_spent'))->toBe('0.00');

    Http::assertSentCount(1);
});

it('does not call the provider when the wallet balance is insufficient', function () {
    Http::preventStrayRequests();
    Http::fake();

    proxyOrderProduct();
    $user = User::factory()->create();
    $user->wallet()->update(['balance' => 100]);

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload())
        ->assertUnprocessable()
        ->assertJsonPath('status', false);

    expect(ProxyOrder::query()->count())->toBe(0)
        ->and(UserProxy::query()->count())->toBe(0)
        ->and(WalletTransaction::query()->count())->toBe(0)
        ->and($user->wallet()->value('balance'))->toBe('100.00');

    Http::assertNothingSent();
});

it('generates the order idempotency key on the backend', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/apiv2/muaproxy.php*' => Http::response([
            [
                'status' => 100,
                'idproxy' => 2772,
                'ip' => '27.71.1.10',
                'port' => 35270,
                'user' => 'proxy-user-1',
                'password' => 'proxy-password-1',
                'time' => now()->addDays(3)->timestamp,
            ],
            [
                'status' => 100,
                'idproxy' => 2773,
                'ip' => '27.71.1.11',
                'port' => 35271,
                'user' => 'proxy-user-2',
                'password' => 'proxy-password-2',
                'time' => now()->addDays(3)->timestamp,
            ],
            ['status' => 200],
        ]),
    ]);

    proxyOrderProduct();
    $user = User::factory()->create();
    $user->wallet()->update(['balance' => 20000]);
    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload())
        ->assertOk();

    $order = ProxyOrder::query()->sole();

    expect(ProxyOrder::query()->count())->toBe(1)
        ->and(UserProxy::query()->count())->toBe(2)
        ->and(WalletTransaction::query()->count())->toBe(1)
        ->and($user->wallet()->value('balance'))->toBe('5000.00')
        ->and(Str::isUuid($order->idempotency_key))->toBeTrue();

    Http::assertSentCount(1);
});

it('requires authentication before validating a proxy order', function () {
    $this->postJson('/api/client/proxy/orders', validProxyOrderPayload())
        ->assertUnauthorized();
});

it('returns not found when the proxy product does not exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload())
        ->assertNotFound();
});

it('rejects malformed proxy order fields', function (array $overrides, string $field) {
    proxyOrderProduct();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'missing product code' => [['product_code' => ''], 'product_code'],
    'zero quantity' => [['quantity' => 0], 'quantity'],
    'duration too long' => [['duration_days' => 3651], 'duration_days'],
    'unsupported protocol value' => [['protocol' => 'ftp'], 'protocol'],
    'client supplied idempotency key' => [['idempotency_key' => fake()->uuid()], 'idempotency_key'],
]);

it('rejects a product that is not available through an active catalog and provider', function (
    array $productOverrides,
    array $providerOverrides,
    array $categoryOverrides,
) {
    proxyOrderProduct($productOverrides, $providerOverrides, $categoryOverrides);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_code');
})->with([
    'inactive product' => [['is_active' => false], [], []],
    'inactive provider' => [[], ['is_active' => false], []],
    'inactive category' => [[], [], ['is_active' => false]],
]);

it('enforces the selected product quantity and supported protocols', function (array $overrides, string $field) {
    proxyOrderProduct();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'product quantity limit' => [['quantity' => 11], 'quantity'],
    'product protocol list' => [['protocol' => 'https'], 'protocol'],
]);

it('accepts a rotating duration that must use the daily provider api', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/proxyxoay/apimuangay.php*' => Http::response(
            '{"status":100,"keyxoay":"rotating-key-1"}{"status":100,"keyxoay":"rotating-key-2"}{"status":100,"soluong":2,"comen":"successful transaction 2 key xoay"}',
        ),
    ]);
    proxyOrderProduct(['settings' => ['proxy_type' => 'rotating']]);
    $user = User::factory()->create();
    $user->wallet()->update(['balance' => 20000]);

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload(['duration_days' => 2]))
        ->assertSuccessful()
        ->assertJsonPath('data.order.status', ProxyOrder::STATUS_FULFILLED)
        ->assertJsonCount(2, 'data.proxies');

    expect(ProxyOrder::query()->count())->toBe(1)
        ->and(UserProxy::query()->count())->toBe(2)
        ->and(WalletTransaction::query()->count())->toBe(1)
        ->and($user->wallet()->value('balance'))->toBe('10000.00');

    Http::assertSent(fn (ProviderRequest $request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://proxy.vn/proxyxoay/apimuangay.php?')
        && $request['thoigian'] === 2
        && $request['soluong'] === 2);
});

it('requires an automatic provider product code before accepting the request', function () {
    proxyOrderProduct(
        ['provider_product_code' => null],
        ['driver' => ProxyProvider::DRIVER_GENERIC_REST, 'order_method' => ProxyProvider::ORDER_METHOD_AUTOMATIC],
    );
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/proxy/orders', validProxyOrderPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_code');
});
