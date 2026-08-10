<?php

use App\Events\ProxyOrderUpdated;
use App\Features\Client\Proxy\Actions\ChangeProxyAction;
use App\Features\Client\Proxy\Actions\RenewProxyAction;
use App\Features\Client\Proxy\Services\ProxyOperationService;
use App\Features\Client\Wallet\Services\WalletService;
use App\Jobs\ProcessProxyOperationJob;
use App\Models\ProxyCategory;
use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\UserProxy;
use App\Service\Reporting\ProxySalesReporter;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Http\Client\Request as ProviderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;

function proxyOperationTarget(User $user): UserProxy
{
    $category = ProxyCategory::query()->create([
        'code' => 'operation-category-'.fake()->unique()->numerify('#####'),
        'name' => 'Operation category',
        'is_active' => true,
    ]);
    $provider = ProxyProvider::query()->create([
        'name' => 'Operation provider',
        'code' => 'operation-provider-'.fake()->unique()->numerify('#####'),
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
        'code' => 'operation-product-'.fake()->unique()->numerify('#####'),
        'name' => 'Operation product',
        'country_code' => 'VN',
        'protocol' => 'http',
        'supported_protocols' => ['http'],
        'base_price' => 1000,
        'selling_price' => 1200,
        'provider_product_code' => 'FPT',
        'max_quantity' => 10,
        'is_active' => true,
    ]);

    return UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $provider->id,
        'provider_proxy_id' => 'provider-proxy-'.fake()->uuid(),
        'status' => UserProxy::STATUS_ACTIVE,
        'country_code' => 'VN',
        'protocol' => 'http',
        'host' => '127.0.0.1',
        'port' => 8080,
        'expires_at' => now()->addDays(7),
    ]);
}

it('does not let a realtime broadcast failure interrupt proxy processing', function () {
    expect(is_subclass_of(ProxyOrderUpdated::class, ShouldRescue::class))->toBeTrue();
});

it('routes proxy change and renewal through their controller methods', function () {
    $statusRoute = Route::getRoutes()->match(
        Request::create('/api/client/proxy/orders/42', 'GET'),
    );
    $changeRoute = Route::getRoutes()->match(
        Request::create('/api/client/proxy/proxies/42/change-proxy', 'POST'),
    );
    $renewRoute = Route::getRoutes()->match(
        Request::create('/api/client/proxy/proxies/42/renew', 'POST'),
    );
    $fetchRotatingRoute = Route::getRoutes()->match(
        Request::create('/api/client/proxy/proxies/42/fetch-rotating', 'POST'),
    );

    expect($statusRoute->getActionName())
        ->toBe('App\Features\Client\Proxy\Controllers\ProxyController@showOrder')
        ->and($changeRoute->getActionName())
        ->toBe('App\Features\Client\Proxy\Controllers\ProxyController@changeProxy')
        ->and($renewRoute->getActionName())
        ->toBe('App\Features\Client\Proxy\Controllers\ProxyController@renewProxy')
        ->and($fetchRotatingRoute->getActionName())
        ->toBe('App\Features\Client\Proxy\Controllers\ProxyController@fetchRotatingProxy');
});

it('gets a rotating proxy for its owner without persisting provider data', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $proxy->product->forceFill([
        'settings' => [
            'proxy_type' => 'rotating',
            'rotating_carrier' => 'random',
            'rotating_province' => '0',
            'rotating_whitelist' => '',
        ],
    ])->save();
    $updatedAt = $proxy->updated_at->copy();

    Http::preventStrayRequests();
    Http::fake([
        'https://proxyxoay.shop/api/get.php*' => Http::response([
            'status' => 100,
            'message' => 'proxy nay se die sau 1777s',
            'proxyhttp' => '42.117.243.215:10836::',
            'proxysocks5' => '42.117.243.215:30836::',
            'Nha Mang' => 'fpt',
            'Vi Tri' => 'HaNoi1',
        ]),
    ]);

    $this->actingAs($user)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/fetch-rotating")
        ->assertSuccessful()
        ->assertJsonPath('data.proxy_id', $proxy->id)
        ->assertJsonPath('data.proxy', '42.117.243.215:10836::')
        ->assertJsonPath('data.protocol', 'http');

    Http::assertSent(function (ProviderRequest $request) use ($proxy): bool {
        $query = [];
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return str_starts_with($request->url(), 'https://proxyxoay.shop/api/get.php?')
            && ($query['key'] ?? null) === $proxy->provider_proxy_id
            && ($query['nhamang'] ?? null) === 'random'
            && ($query['tinhthanh'] ?? null) === '0'
            && ($query['whitelist'] ?? null) === '';
    });

    $freshProxy = $proxy->fresh();
    expect($freshProxy->updated_at->equalTo($updatedAt))->toBeTrue()
        ->and($freshProxy->host)->toBe($proxy->host)
        ->and($freshProxy->response)->toBe($proxy->response);
});

it('does not get rotating proxy data for another user or a static product', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $proxy = proxyOperationTarget($owner);

    Http::preventStrayRequests();
    Http::fake();

    $this->actingAs($otherUser)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/fetch-rotating")
        ->assertNotFound();

    $this->actingAs($owner)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/fetch-rotating")
        ->assertUnprocessable();

    Http::assertNothingSent();
});

it('returns an operation order status only to its owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $user->wallet()->update(['balance' => 5000]);
    Queue::fake();

    $this->actingAs($user)->postJson("/api/client/proxy/proxies/{$proxy->id}/change-proxy")->assertAccepted();

    $order = ProxyOrder::query()->sole();

    $this->actingAs($user)
        ->getJson("/api/client/proxy/orders/{$order->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.order.id', $order->id)
        ->assertJsonPath('data.order.status', ProxyOrder::STATUS_PENDING)
        ->assertJsonMissingPath('data.order.external_order_id');

    $this->actingAs($otherUser)
        ->getJson("/api/client/proxy/orders/{$order->id}")
        ->assertNotFound();
});

it('requires authentication for proxy mutations', function (string $endpoint) {
    $this->postJson($endpoint, [])->assertUnauthorized();
})->with([
    '/api/client/proxy/proxies/1/change-proxy',
    '/api/client/proxy/proxies/1/renew',
]);

it('validates proxy mutation payloads', function (string $endpoint, array $payload) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson($endpoint, $payload)
        ->assertUnprocessable();
})->with([
    'change rejects a client supplied idempotency key' => [
        '/api/client/proxy/proxies/1/change-proxy',
        ['idempotency_key' => 'client-controlled-value'],
    ],
    'renew requires a positive duration' => [
        '/api/client/proxy/proxies/1/renew',
        ['duration_days' => 0],
    ],
    'renew limits the duration' => [
        '/api/client/proxy/proxies/1/renew',
        ['duration_days' => 3651],
    ],
]);

it('does not expose another user proxy through mutation endpoints', function (string $endpoint) {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $proxy = proxyOperationTarget($owner);

    $payload = [];

    if (str_ends_with($endpoint, '/renew')) {
        $payload['duration_days'] = 30;
    }

    $this->actingAs($attacker)
        ->postJson(str_replace('{proxy}', (string) $proxy->id, $endpoint), $payload)
        ->assertNotFound();
})->with([
    '/api/client/proxy/proxies/{proxy}/change-proxy',
    '/api/client/proxy/proxies/{proxy}/renew',
]);

it('forwards an owned proxy and validated change payload to the service', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $payload = [];
    $service = Mockery::mock(ProxyOperationService::class);
    $service->shouldReceive('change')
        ->once()
        ->withArgs(fn (User $owner, UserProxy $target, array $data): bool => $owner->is($user)
            && $target->is($proxy)
            && is_string($data['idempotency_key'] ?? null)
            && Str::isUuid($data['idempotency_key']))
        ->andReturn(['proxy_id' => $proxy->id]);

    $result = (new ChangeProxyAction($service))->handle($user, $proxy->id, $payload);

    expect($result)->toBe(['proxy_id' => $proxy->id]);
});

it('forwards an owned proxy and validated renewal payload to the service', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $payload = ['duration_days' => 30];
    $service = Mockery::mock(ProxyOperationService::class);
    $service->shouldReceive('renew')
        ->once()
        ->withArgs(fn (User $owner, UserProxy $target, array $data): bool => $owner->is($user)
            && $target->is($proxy)
            && $data['duration_days'] === 30
            && is_string($data['idempotency_key'] ?? null)
            && Str::isUuid($data['idempotency_key']))
        ->andReturn(['proxy_id' => $proxy->id]);

    $result = (new RenewProxyAction($service))->handle($user, $proxy->id, $payload);

    expect($result)->toBe(['proxy_id' => $proxy->id]);
});

it('accepts a change request, charges the fee and dispatches a background job', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $user->wallet()->update(['balance' => 5000]);
    Queue::fake();

    $this->actingAs($user)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/change-proxy")
        ->assertAccepted()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.order.type', ProxyOrder::TYPE_CHANGE)
        ->assertJsonPath('data.order.total_amount', '1000.0000');

    $order = ProxyOrder::query()->sole();

    expect($user->wallet->fresh()->balance)->toBe('4000.00')
        ->and($proxy->fresh()->status)->toBe(UserProxy::STATUS_CHANGING)
        ->and($order->status)->toBe(ProxyOrder::STATUS_PENDING);

    Queue::assertPushed(ProcessProxyOperationJob::class, fn (ProcessProxyOperationJob $job): bool => $job->proxyOrderId === $order->id);
});

it('accepts a renewal request, charges by day and dispatches a background job', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $user->wallet()->update(['balance' => 50000]);
    Queue::fake();

    $this->actingAs($user)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/renew", [
            'duration_days' => 30,
        ])
        ->assertAccepted()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.order.type', ProxyOrder::TYPE_RENEW)
        ->assertJsonPath('data.order.total_amount', '36000.0000');

    $order = ProxyOrder::query()->sole();

    expect($user->wallet->fresh()->balance)->toBe('14000.00')
        ->and($order->status)->toBe(ProxyOrder::STATUS_PENDING);

    Queue::assertPushed(ProcessProxyOperationJob::class, fn (ProcessProxyOperationJob $job): bool => $job->proxyOrderId === $order->id);
});

it('accepts renewal for a rotating proxy by its access key', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $proxy->product()->update([
        'provider_product_code' => null,
        'settings' => ['proxy_type' => 'rotating'],
    ]);
    $user->wallet()->update(['balance' => 5000]);
    Queue::fake();

    $this->actingAs($user)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/renew", [
            'duration_days' => 1,
        ])
        ->assertAccepted()
        ->assertJsonPath('data.order.type', ProxyOrder::TYPE_RENEW);

    $order = ProxyOrder::query()->sole();

    expect($order->duration_days)->toBe(1);
    Queue::assertPushed(ProcessProxyOperationJob::class, fn (ProcessProxyOperationJob $job): bool => $job->proxyOrderId === $order->id);
});

it('processes a queued renewal by adding days to the current expiry', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $currentExpiry = now()->subDays(2)->startOfSecond();
    $proxy->forceFill([
        'status' => UserProxy::STATUS_EXPIRED,
        'expires_at' => $currentExpiry,
    ])->save();
    $user->wallet()->update(['balance' => 50000]);
    Queue::fake();

    $this->actingAs($user)->postJson("/api/client/proxy/proxies/{$proxy->id}/renew", [
        'duration_days' => 30,
    ])->assertAccepted();

    $order = ProxyOrder::query()->sole();
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/apiv2/giahanproxy.php*' => Http::response([
            'status' => 100,
            'time' => now()->addYear()->timestamp,
        ]),
    ]);
    Event::fake([ProxyOrderUpdated::class]);

    (new ProcessProxyOperationJob($order->id))->handle(app(WalletService::class), app(ProxySalesReporter::class));

    expect($order->fresh()->status)->toBe(ProxyOrder::STATUS_FULFILLED)
        ->and($proxy->fresh()->expires_at?->equalTo($currentExpiry->copy()->addDays(30)))->toBeTrue();

    Event::assertDispatched(ProxyOrderUpdated::class, fn (ProxyOrderUpdated $event): bool => $event->orderId === $order->id
        && $event->status === ProxyOrder::STATUS_FULFILLED);
});

it('rejects changing a proxy that is not active before provider dispatch', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $proxy->forceFill(['status' => 'expired'])->save();

    $this->actingAs($user)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/change-proxy")
        ->assertUnprocessable()
        ->assertJsonPath('errors.proxy.0', 'Proxy hiện không thể thực hiện thao tác này.');
});

it('rejects changing a proxy assigned to an inactive provider', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $proxy->provider()->update(['is_active' => false]);

    $this->actingAs($user)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/change-proxy")
        ->assertUnprocessable()
        ->assertJsonPath('errors.proxy.0', 'Nhà cung cấp của proxy hiện không hoạt động.');
});

it('rejects changing a rotating proxy before charging or dispatching a job', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $proxy->product()->update(['settings' => ['proxy_type' => 'rotating']]);
    $user->wallet()->update(['balance' => 5000]);
    Queue::fake();

    $this->actingAs($user)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/change-proxy")
        ->assertUnprocessable()
        ->assertJsonPath('errors.proxy.0', 'Không hỗ trợ đổi proxy cho sản phẩm xoay. Vui lòng mua lại proxy mới.');

    expect(ProxyOrder::query()->count())->toBe(0)
        ->and($user->wallet->fresh()->balance)->toBe('5000.00')
        ->and($proxy->fresh()->status)->toBe(UserProxy::STATUS_ACTIVE);

    Queue::assertNothingPushed();
});

it('rejects provider drivers without an automatic change handler', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $proxy->provider()->update(['driver' => ProxyProvider::DRIVER_GENERIC_REST]);

    $this->actingAs($user)
        ->postJson("/api/client/proxy/proxies/{$proxy->id}/change-proxy")
        ->assertServiceUnavailable()
        ->assertJsonPath('message', 'Nhà cung cấp chưa hỗ trợ thao tác proxy tự động.');
});

it('processes a queued change and broadcasts the completed order', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $user->wallet()->update(['balance' => 5000]);
    Queue::fake();

    $this->actingAs($user)->postJson("/api/client/proxy/proxies/{$proxy->id}/change-proxy")->assertAccepted();

    $order = ProxyOrder::query()->sole();
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/apiv2/doiproxy.php*' => Http::response([
            'status' => 100,
            'loaiproxy' => 'FPT',
            'idproxy' => 2772,
            'ip' => '27.73.88.211',
            'port' => 35270,
            'user' => 'mdtrong',
            'password' => 'pass',
            'type' => 'HTTPS',
        ]),
    ]);
    Event::fake([ProxyOrderUpdated::class]);

    (new ProcessProxyOperationJob($order->id))->handle(app(WalletService::class), app(ProxySalesReporter::class));

    $updatedProxy = $proxy->fresh();
    $updatedOrder = $order->fresh();

    expect($updatedOrder->status)->toBe(ProxyOrder::STATUS_FULFILLED)
        ->and($updatedProxy->status)->toBe(UserProxy::STATUS_ACTIVE)
        ->and($updatedProxy->provider_proxy_id)->toBe('2772')
        ->and($updatedProxy->provider_code)->toBe('2772')
        ->and($updatedProxy->host)->toBe('27.73.88.211')
        ->and($updatedProxy->port)->toBe(35270)
        ->and($updatedProxy->last_changed_at)->not->toBeNull();

    Event::assertDispatched(ProxyOrderUpdated::class, fn (ProxyOrderUpdated $event): bool => $event->orderId === $order->id
        && $event->status === ProxyOrder::STATUS_FULFILLED);
});

it('refunds a failed queued change only once', function () {
    $user = User::factory()->create();
    $proxy = proxyOperationTarget($user);
    $proxy->forceFill([
        'provider_code' => 'old-provider-code',
        'username' => 'old-user',
        'password' => 'old-password',
        'response' => ['status' => 100, 'source' => 'old-proxy'],
        'error_message' => 'old-proxy-note',
        'last_changed_at' => now()->subDay(),
    ])->save();
    $oldProxy = $proxy->fresh();
    $user->wallet()->update(['balance' => 5000]);
    Queue::fake();

    $this->actingAs($user)->postJson("/api/client/proxy/proxies/{$proxy->id}/change-proxy")->assertAccepted();

    $order = ProxyOrder::query()->sole();
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/apiv2/doiproxy.php*' => Http::response(['status' => 102]),
    ]);
    Event::fake([ProxyOrderUpdated::class]);
    $job = new ProcessProxyOperationJob($order->id);

    $job->handle(app(WalletService::class), app(ProxySalesReporter::class));
    $job->handle(app(WalletService::class), app(ProxySalesReporter::class));

    $restoredProxy = $proxy->fresh();

    expect($order->fresh()->status)->toBe(ProxyOrder::STATUS_REFUNDED)
        ->and($restoredProxy->status)->toBe(UserProxy::STATUS_ACTIVE)
        ->and($restoredProxy->provider_proxy_id)->toBe($oldProxy->provider_proxy_id)
        ->and($restoredProxy->provider_code)->toBe($oldProxy->provider_code)
        ->and($restoredProxy->host)->toBe($oldProxy->host)
        ->and($restoredProxy->port)->toBe($oldProxy->port)
        ->and($restoredProxy->username)->toBe($oldProxy->username)
        ->and($restoredProxy->password)->toBe($oldProxy->password)
        ->and($restoredProxy->protocol)->toBe($oldProxy->protocol)
        ->and($restoredProxy->response)->toBe($oldProxy->response)
        ->and($restoredProxy->error_message)->toBe($oldProxy->error_message)
        ->and($restoredProxy->expires_at?->equalTo($oldProxy->expires_at))->toBeTrue()
        ->and($restoredProxy->last_changed_at?->equalTo($oldProxy->last_changed_at))->toBeTrue()
        ->and($user->wallet->fresh()->balance)->toBe('5000.00');

    Event::assertDispatched(ProxyOrderUpdated::class, fn (ProxyOrderUpdated $event): bool => $event->orderId === $order->id
        && $event->status === ProxyOrder::STATUS_REFUNDED
        && $event->errorMessage === 'Nhà cung cấp không thể hoàn tất yêu cầu proxy lúc này.'
        && $event->broadcastWith()['error_message'] === 'Nhà cung cấp không thể hoàn tất yêu cầu proxy lúc này.');
});
