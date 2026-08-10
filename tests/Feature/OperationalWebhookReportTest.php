<?php

use App\Features\Client\Contact\Services\ContactService;
use App\Models\ProxyCategory;
use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Service\Reporting\ProxySalesReporter;
use Illuminate\Support\Facades\Http;

afterEach(function () {
    app()->detectEnvironment(fn (): string => 'testing');
});

it('reports a fulfilled proxy sale with balances and estimated profit', function () {
    app()->detectEnvironment(fn (): string => 'local');
    $webhookUrl = 'https://discord.com/api/webhooks/sales-report';
    config()->set('services.discord.channels.sales', $webhookUrl);
    Http::preventStrayRequests();
    Http::fake([$webhookUrl => Http::response([], 204)]);

    $user = User::factory()->create();
    $category = ProxyCategory::query()->create([
        'code' => 'report-category',
        'name' => 'Report category',
        'is_active' => true,
    ]);
    $provider = ProxyProvider::query()->create([
        'name' => 'Proxy VN',
        'code' => 'proxy-vn',
        'driver' => ProxyProvider::DRIVER_PROXY_VN,
        'order_method' => ProxyProvider::ORDER_METHOD_AUTOMATIC,
        'is_active' => true,
    ]);
    $product = ProxyProduct::query()->create([
        'proxy_category_id' => $category->id,
        'default_provider_id' => $provider->id,
        'code' => 'report-product',
        'name' => 'Proxy báo cáo',
        'country_code' => 'VN',
        'protocol' => 'http',
        'supported_protocols' => ['http'],
        'selling_price' => 2500,
        'base_price' => 2000,
        'provider_product_code' => 'Viettel',
        'max_quantity' => 10,
        'is_active' => true,
    ]);
    $order = ProxyOrder::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $provider->id,
        'order_code' => 'PXY-REPORT-001',
        'idempotency_key' => fake()->uuid(),
        'type' => ProxyOrder::TYPE_PURCHASE,
        'status' => ProxyOrder::STATUS_FULFILLED,
        'product_code' => $product->code,
        'product_name' => $product->name,
        'quantity' => 2,
        'duration_days' => 3,
        'country_code' => 'VN',
        'protocol' => 'http',
        'unit_price' => 2500,
        'total_amount' => 15000,
        'ordered_at' => now(),
        'fulfilled_at' => now(),
    ]);
    WalletTransaction::query()->create([
        'wallet_id' => $user->wallet()->firstOrFail()->id,
        'type' => 'debit',
        'amount' => 15000,
        'balance_before' => 20000,
        'balance_after' => 5000,
        'reference_type' => ProxyOrder::class,
        'reference_id' => $order->id,
        'description' => 'Thanh toán đơn proxy',
        'status' => 'success',
    ]);

    app(ProxySalesReporter::class)->reportFulfilled($order);

    Http::assertSent(fn ($request): bool => $request->url() === $webhookUrl
        && str_contains((string) $request['content'], 'PXY-REPORT-001')
        && str_contains((string) $request['content'], '15.000 đ')
        && str_contains((string) $request['content'], '12.000 đ')
        && str_contains((string) $request['content'], '3.000 đ')
        && str_contains((string) $request['content'], '20.000 đ')
        && str_contains((string) $request['content'], '5.000 đ'));
});

it('reports new feedback with a direct admin review link', function () {
    app()->detectEnvironment(fn (): string => 'local');
    $webhookUrl = 'https://discord.com/api/webhooks/feedback-report';
    config()->set('services.discord.channels.feedback', $webhookUrl);
    Http::preventStrayRequests();
    Http::fake([$webhookUrl => Http::response([], 204)]);
    $user = User::factory()->create();

    app(ContactService::class)->createFeedback($user, [
        'subject' => 'Cần hỗ trợ proxy',
        'content' => 'Tôi cần quản trị viên kiểm tra giao dịch proxy này.',
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === $webhookUrl
        && str_contains((string) $request['content'], 'Cần hỗ trợ proxy')
        && str_contains((string) $request['content'], '/admin/feedbacks'));
});
