<?php

use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\ProxyCategory;
use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\UserProxy;
use App\Models\WalletTransaction;

function proxyListingProduct(): ProxyProduct
{
    $category = ProxyCategory::query()->create([
        'code' => 'listing-category',
        'name' => 'Listing category',
        'is_active' => true,
    ]);
    $provider = ProxyProvider::query()->create([
        'name' => 'Listing provider',
        'code' => 'listing-provider',
        'driver' => ProxyProvider::DRIVER_PROXY_VN,
        'is_active' => true,
    ]);

    return ProxyProduct::query()->create([
        'proxy_category_id' => $category->id,
        'default_provider_id' => $provider->id,
        'code' => 'listing-product',
        'name' => 'Listing product',
        'country_code' => 'VN',
        'protocol' => 'http',
        'supported_protocols' => ['http'],
        'base_price' => 1000,
        'selling_price' => 1200,
        'max_quantity' => 10,
        'is_active' => true,
    ]);
}

function rotatingProxyListingProduct(): ProxyProduct
{
    $product = proxyListingProduct();
    $product->forceFill([
        'code' => 'rotating-listing-product',
        'name' => 'Rotating listing product',
        'settings' => ['proxy_type' => 'rotating'],
    ])->save();

    return $product;
}

function proxyListingOrder(User $user, ProxyProduct $product, string $code): ProxyOrder
{
    return ProxyOrder::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'order_code' => $code,
        'idempotency_key' => fake()->uuid(),
        'type' => 'purchase',
        'status' => 'fulfilled',
        'product_code' => $product->code,
        'product_name' => $product->name,
        'quantity' => 1,
        'duration_days' => 30,
        'country_code' => 'VN',
        'protocol' => 'http',
        'unit_price' => '1200.0000',
        'total_amount' => '36000.0000',
        'external_order_id' => 'provider-order-secret',
        'ordered_at' => now(),
        'fulfilled_at' => now(),
    ]);
}

it('requires authentication for proxy management lists', function (string $endpoint) {
    $this->getJson($endpoint)->assertUnauthorized();
})->with([
    '/api/client/proxy/dashboard',
    '/api/client/proxy/proxies',
    '/api/client/proxy/orders',
]);

it('returns dashboard statistics and recent records for only the authenticated user', function () {
    $product = proxyListingProduct();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $user->wallet()->update(['balance' => 987500]);
    $recentOrder = proxyListingOrder($user, $product, 'PXY-DASHBOARD-RECENT');
    proxyListingOrder($user, $product, 'PXY-DASHBOARD-OLDER');
    $otherOrder = proxyListingOrder($otherUser, $product, 'PXY-DASHBOARD-OTHER');

    $expiringProxy = UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'source_order_id' => $recentOrder->id,
        'provider_proxy_id' => 'dashboard-expiring-secret',
        'status' => UserProxy::STATUS_ACTIVE,
        'country_code' => 'VN',
        'protocol' => 'http',
        'host' => '127.0.0.1',
        'port' => 8080,
        'expires_at' => now()->addDays(3),
    ]);
    UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'source_order_id' => $recentOrder->id,
        'provider_proxy_id' => 'dashboard-expired-secret',
        'status' => 'expired',
        'country_code' => 'VN',
        'protocol' => 'http',
        'expires_at' => now()->subDay(),
    ]);
    UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'source_order_id' => $recentOrder->id,
        'provider_proxy_id' => 'dashboard-safe-secret',
        'status' => UserProxy::STATUS_ACTIVE,
        'country_code' => 'VN',
        'protocol' => 'http',
        'host' => '127.0.0.2',
        'port' => 8081,
        'expires_at' => now()->addDays(4),
    ]);
    UserProxy::query()->create([
        'user_id' => $otherUser->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'source_order_id' => $otherOrder->id,
        'provider_proxy_id' => 'dashboard-other-secret',
        'status' => UserProxy::STATUS_ACTIVE,
        'country_code' => 'VN',
        'protocol' => 'http',
        'expires_at' => now()->addDays(2),
    ]);

    $readNotification = Notification::query()->create([
        'scope' => Notification::SCOPE_SYSTEM,
        'user_id' => $user->id,
        'title' => 'Thông báo đã đọc',
        'content' => 'Nội dung thông báo đã đọc.',
        'type' => 'info',
    ]);
    NotificationRead::query()->create([
        'notification_id' => $readNotification->id,
        'user_id' => $user->id,
        'read_at' => now(),
    ]);
    Notification::query()->create([
        'scope' => Notification::SCOPE_SYSTEM,
        'user_id' => $user->id,
        'title' => 'Bảo trì hệ thống',
        'content' => 'Hệ thống sẽ bảo trì định kỳ.',
        'type' => 'important',
    ]);
    Notification::query()->create([
        'scope' => Notification::SCOPE_USER,
        'user_id' => $user->id,
        'title' => 'Proxy sắp hết hạn',
        'content' => 'Vui lòng kiểm tra proxy của bạn.',
        'type' => 'warning',
    ]);
    Notification::query()->create([
        'scope' => Notification::SCOPE_USER,
        'user_id' => $otherUser->id,
        'title' => 'Thông báo user khác',
        'content' => 'Không được xuất hiện.',
        'type' => 'info',
    ]);

    WalletTransaction::query()->create([
        'wallet_id' => $user->wallet()->value('id'),
        'type' => 'credit',
        'amount' => 500000,
        'balance_before' => 487500,
        'balance_after' => 987500,
        'description' => 'Nạp tiền qua ngân hàng',
        'status' => 'success',
    ]);

    $this->actingAs($user)
        ->getJson('/api/client/proxy/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.summary.balance', '987500.00')
        ->assertJsonPath('data.summary.active_proxies', 2)
        ->assertJsonPath('data.summary.expiring_proxies', 1)
        ->assertJsonPath('data.summary.unread_notifications', 1)
        ->assertJsonCount(1, 'data.expiring_proxies')
        ->assertJsonPath('data.expiring_proxies.0.id', $expiringProxy->id)
        ->assertJsonPath('data.expiring_proxies.0.endpoint', '127.0.0.1:8080')
        ->assertJsonCount(2, 'data.notifications')
        ->assertJsonPath('data.notifications.0.title', 'Bảo trì hệ thống')
        ->assertJsonCount(3, 'data.recent_activities')
        ->assertJsonFragment(['type' => 'wallet_credit', 'amount' => '500000.00'])
        ->assertJsonMissingPath('data.expiring_proxies.0.access_key')
        ->assertJsonMissingPath('data.expiring_proxies.0.connection')
        ->assertJsonMissingPath('data.expiring_proxies.0.provider_proxy_id')
        ->assertJsonMissingPath('data.expiring_proxies.0.host')
        ->assertJsonMissingPath('data.expiring_proxies.0.username')
        ->assertJsonMissingPath('data.expiring_proxies.0.password')
        ->assertJsonMissingPath('data.expiring_proxies.0.response')
        ->assertJsonMissing(['Proxy sắp hết hạn'])
        ->assertJsonMissing(['Thông báo user khác']);
});

it('returns only the authenticated users proxies and orders with the expected contract', function () {
    $product = proxyListingProduct();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = proxyListingOrder($user, $product, 'PXY-LIST-MINE');
    $otherOrder = proxyListingOrder($otherUser, $product, 'PXY-LIST-OTHER');

    UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'source_order_id' => $order->id,
        'provider_proxy_id' => 'provider-proxy-secret',
        'provider_code' => 'provider-action-secret',
        'label' => 'Proxy của tôi',
        'status' => UserProxy::STATUS_ACTIVE,
        'country_code' => 'VN',
        'protocol' => 'http',
        'host' => '127.0.0.1',
        'port' => 8080,
        'username' => 'proxy-user',
        'password' => 'proxy-password',
        'response' => ['secret' => 'provider-response'],
        'expires_at' => now()->addDays(30),
    ]);
    UserProxy::query()->create([
        'user_id' => $otherUser->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'source_order_id' => $otherOrder->id,
        'provider_proxy_id' => 'other-provider-proxy',
        'provider_code' => 'other-provider-code',
        'status' => UserProxy::STATUS_ACTIVE,
        'country_code' => 'VN',
        'protocol' => 'http',
        'host' => '10.0.0.1',
        'port' => 9000,
        'response' => ['secret' => 'other-response'],
    ]);

    $this->actingAs($user)
        ->getJson('/api/client/proxy/proxies?status=active&protocol=http&country_code=VN&search=PXY-LIST-MINE')
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.proxies.0.source_order_code', 'PXY-LIST-MINE')
        ->assertJsonPath('data.proxies.0.product.code', 'listing-product')
        ->assertJsonPath('data.proxies.0.proxy_type', 'static')
        ->assertJsonPath('data.proxies.0.connection.host', '127.0.0.1')
        ->assertJsonMissingPath('data.proxies.0.provider_code')
        ->assertJsonMissingPath('data.proxies.0.response')
        ->assertJsonMissingPath('data.proxies.0.provider_proxy_id');

    $this->actingAs($user)
        ->getJson('/api/client/proxy/orders?status=fulfilled&type=purchase&search=PXY-LIST-MINE')
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.orders.0.order_code', 'PXY-LIST-MINE')
        ->assertJsonPath('data.orders.0.product.name', 'Listing product')
        ->assertJsonPath('data.orders.0.total_amount', '36000.0000')
        ->assertJsonMissingPath('data.orders.0.external_order_id');
});

it('validates proxy management list filters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/client/proxy/proxies?status=fulfilled')
        ->assertUnprocessable();

    $this->actingAs($user)
        ->getJson('/api/client/proxy/proxies?proxy_type=invalid')
        ->assertUnprocessable();

    $this->actingAs($user)
        ->getJson('/api/client/proxy/orders?type=invalid')
        ->assertUnprocessable();
});

it('returns the rotating access key without fake host or authentication fields', function () {
    $product = rotatingProxyListingProduct();
    $user = User::factory()->create();
    $order = proxyListingOrder($user, $product, 'PXY-ROTATING');

    UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'source_order_id' => $order->id,
        'provider_proxy_id' => 'jfLFRqLnPgnDaRWzmdxnga',
        'provider_code' => 'jfLFRqLnPgnDaRWzmdxnga',
        'status' => UserProxy::STATUS_ACTIVE,
        'country_code' => 'VN',
        'protocol' => 'http',
        'host' => null,
        'port' => null,
        'username' => null,
        'password' => null,
        'response' => [
            'status' => 100,
            'keyxoay' => 'jfLFRqLnPgnDaRWzmdxnga',
            'proxyhttp' => '127.0.0.1:8080:proxy-user:proxy-password',
        ],
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->getJson('/api/client/proxy/proxies')
        ->assertSuccessful()
        ->assertJsonPath('data.proxies.0.proxy_type', 'rotating')
        ->assertJsonPath('data.proxies.0.access_key', 'jfLFRqLnPgnDaRWzmdxnga')
        ->assertJsonPath('data.proxies.0.connection', null)
        ->assertJsonMissingPath('data.proxies.0.rotating_proxy')
        ->assertJsonMissingPath('data.proxies.0.provider_proxy_id')
        ->assertJsonMissingPath('data.proxies.0.provider_code');
});

it('filters owned proxies by static or rotating product type', function () {
    $staticProduct = proxyListingProduct();
    $rotatingProduct = $staticProduct->replicate();
    $rotatingProduct->forceFill([
        'code' => 'rotating-filter-product',
        'name' => 'Rotating filter product',
        'settings' => ['proxy_type' => 'rotating'],
    ])->save();

    $user = User::factory()->create();
    $staticOrder = proxyListingOrder($user, $staticProduct, 'PXY-STATIC-FILTER');
    $rotatingOrder = proxyListingOrder($user, $rotatingProduct, 'PXY-ROTATING-FILTER');

    $staticProxy = UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $staticProduct->id,
        'proxy_provider_id' => $staticProduct->default_provider_id,
        'source_order_id' => $staticOrder->id,
        'provider_proxy_id' => 'static-filter-provider-id',
        'status' => UserProxy::STATUS_ACTIVE,
        'protocol' => 'http',
        'host' => '127.0.0.1',
        'port' => 8080,
    ]);
    $rotatingProxy = UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $rotatingProduct->id,
        'proxy_provider_id' => $rotatingProduct->default_provider_id,
        'source_order_id' => $rotatingOrder->id,
        'provider_proxy_id' => 'rotating-filter-key',
        'status' => UserProxy::STATUS_ACTIVE,
        'protocol' => 'http',
    ]);

    $this->actingAs($user)
        ->getJson('/api/client/proxy/proxies?proxy_type=static')
        ->assertSuccessful()
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.proxies.0.id', $staticProxy->id)
        ->assertJsonPath('data.proxies.0.proxy_type', 'static');

    $this->actingAs($user)
        ->getJson('/api/client/proxy/proxies?proxy_type=rotating')
        ->assertSuccessful()
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.proxies.0.id', $rotatingProxy->id)
        ->assertJsonPath('data.proxies.0.proxy_type', 'rotating');
});

it('hides old proxy credentials while a change task is processing', function () {
    $product = proxyListingProduct();
    $user = User::factory()->create();
    $order = proxyListingOrder($user, $product, 'PXY-CHANGING');

    $proxy = UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'source_order_id' => $order->id,
        'provider_proxy_id' => 'old-provider-proxy',
        'status' => UserProxy::STATUS_CHANGING,
        'country_code' => 'VN',
        'protocol' => 'http',
        'host' => '127.0.0.1',
        'port' => 8080,
        'username' => 'old-user',
        'password' => 'old-password',
        'expires_at' => now()->addDays(30),
    ]);

    $this->actingAs($user)
        ->getJson('/api/client/proxy/proxies?status=changing')
        ->assertSuccessful()
        ->assertJsonPath('data.proxies.0.id', $proxy->id)
        ->assertJsonPath('data.proxies.0.status', UserProxy::STATUS_CHANGING)
        ->assertJsonPath('data.proxies.0.access_key', null)
        ->assertJsonPath('data.proxies.0.connection', null);
});

it('returns one owned proxy for targeted realtime table updates', function () {
    $product = proxyListingProduct();
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = proxyListingOrder($owner, $product, 'PXY-TARGETED-UPDATE');
    $proxy = UserProxy::query()->create([
        'user_id' => $owner->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $product->default_provider_id,
        'source_order_id' => $order->id,
        'provider_proxy_id' => 'targeted-provider-proxy',
        'status' => UserProxy::STATUS_ACTIVE,
        'country_code' => 'VN',
        'protocol' => 'http',
        'host' => '127.0.0.1',
        'port' => 8080,
        'expires_at' => now()->addDays(30),
    ]);

    $this->actingAs($owner)
        ->getJson("/api/client/proxy/proxies/{$proxy->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.proxy.id', $proxy->id)
        ->assertJsonPath('data.proxy.connection.host', '127.0.0.1');

    $this->actingAs($otherUser)
        ->getJson("/api/client/proxy/proxies/{$proxy->id}")
        ->assertNotFound();
});
