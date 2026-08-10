<?php

use App\Models\ProxyCategory;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\UserProxy;
use Illuminate\Support\Facades\DB;

test('user proxy lưu nguyên provider code và mã hóa response nhạy cảm', function () {
    $user = User::factory()->create();
    $category = ProxyCategory::query()->create([
        'code' => 'storage-category',
        'name' => 'Storage category',
        'is_active' => true,
    ]);
    $provider = ProxyProvider::query()->create([
        'name' => 'Storage provider',
        'code' => 'storage-provider',
        'driver' => ProxyProvider::DRIVER_PROXY_VN,
        'is_active' => true,
    ]);
    $product = ProxyProduct::query()->create([
        'proxy_category_id' => $category->id,
        'default_provider_id' => $provider->id,
        'code' => 'storage-product',
        'name' => 'Storage product',
        'protocol' => 'http',
        'supported_protocols' => ['http'],
        'base_price' => 1000,
        'selling_price' => 1200,
        'max_quantity' => 10,
        'is_active' => true,
    ]);
    $providerResponse = [
        'status' => 100,
        'loaiproxy' => 'FPT',
        'idproxy' => 12420,
        'ip' => '113.22.201.111',
        'port' => 32582,
        'user' => 'provider-user',
        'password' => 'provider-password',
        'time' => 1786336881,
    ];

    $proxy = UserProxy::query()->create([
        'user_id' => $user->id,
        'proxy_product_id' => $product->id,
        'proxy_provider_id' => $provider->id,
        'provider_proxy_id' => '12420',
        'provider_code' => '12420',
        'status' => UserProxy::STATUS_ACTIVE,
        'country_code' => 'VN',
        'protocol' => 'http',
        'host' => '113.22.201.111',
        'port' => 32582,
        'username' => 'provider-user',
        'password' => 'provider-password',
        'response' => $providerResponse,
    ]);

    $rawProxy = DB::table('user_proxies')->where('id', $proxy->id)->first();
    $freshProxy = $proxy->fresh();

    expect($freshProxy?->provider_code)->toBe('12420')
        ->and($freshProxy?->response)->toBe($providerResponse)
        ->and($freshProxy?->toArray())->not->toHaveKeys(['provider_code', 'response'])
        ->and($rawProxy?->provider_code)->toBe('12420')
        ->and($rawProxy?->response)->not->toContain('113.22.201.111')
        ->and($rawProxy?->response)->not->toContain('provider-password');
});
