<?php

use App\Models\ProxyCategory;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

function taxonomyAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('keeps proxy routes in the standard feature route files', function () {
    $apiRoutes = File::get(base_path('routes/api.php'));

    expect(app_path('Features/Admin/Proxy/routes.php'))->toBeFile()
        ->and(app_path('Features/Client/Proxy/routes.php'))->toBeFile()
        ->and(app_path('Features/Api/Proxy/routes.php'))->toBeFile()
        ->and(app_path('Features/Admin/Proxy/api.php'))->not->toBeFile()
        ->and(app_path('Features/Client/Proxy/api.php'))->not->toBeFile()
        ->and($apiRoutes)->toContain("require base_path('app/Features/Admin/Proxy/routes.php');")
        ->and($apiRoutes)->toContain("require base_path('app/Features/Client/Proxy/routes.php');")
        ->and($apiRoutes)->toContain("require base_path('app/Features/Api/Proxy/routes.php');");
});

it('allows admin to create a category and attach products directly', function () {
    $admin = taxonomyAdmin();
    $provider = ProxyProvider::query()->create([
        'name' => 'Provider A',
        'driver' => 'manual',
        'is_active' => true,
    ]);

    $categoryResponse = $this->actingAs($admin)->postJson('/api/admin-api/proxy-categories', [
        'code' => 'residential',
        'name' => 'Proxy dân cư',
        'sort_order' => 1,
        'is_active' => true,
    ])->assertCreated();

    $this->actingAs($admin)->postJson('/api/admin-api/proxy-products', [
        'proxy_category_id' => $categoryResponse->json('data.category.id'),
        'code' => 'residential-vn',
        'name' => 'Proxy dân cư Việt Nam',
        'country_code' => 'VN',
        'supported_protocols' => ['http', 'socks5'],
        'default_provider_id' => $provider->id,
        'provider_product_code' => 'vendor-vn',
        'base_price' => 3000,
        'selling_price' => 5000,
        'max_quantity' => 100,
        'settings' => [
            'proxy_type' => 'rotating',
            'rotating_carrier' => 'random',
            'rotating_province' => '0',
            'rotating_whitelist' => '',
        ],
        'is_active' => true,
    ])->assertCreated()
        ->assertJsonPath('data.product.category.code', 'residential')
        ->assertJsonPath('data.product.protocol', 'http')
        ->assertJsonPath('data.product.supported_protocols', ['http', 'socks5'])
        ->assertJsonPath('data.product.settings.proxy_type', 'rotating')
        ->assertJsonPath('data.product.base_price', '3000.0000')
        ->assertJsonPath('data.product.selling_price', '5000.0000');

    expect(ProxyCategory::query()->firstOrFail()->products)->toHaveCount(1)
        ->and(ProxyProduct::query()->firstOrFail()->category->code)->toBe('residential')
        ->and(ProxyProduct::query()->firstOrFail()->settings)->toMatchArray([
            'proxy_type' => 'rotating',
            'rotating_carrier' => 'random',
            'rotating_province' => '0',
            'rotating_whitelist' => '',
        ]);
});

it('returns active categories with their products in the public catalog', function () {
    $category = ProxyCategory::query()->create(['code' => 'datacenter', 'name' => 'Proxy Datacenter']);
    $provider = ProxyProvider::query()->create(['name' => 'Provider', 'driver' => 'manual', 'is_active' => true]);
    ProxyProduct::query()->create([
        'proxy_category_id' => $category->id,
        'code' => 'dc-us-7d',
        'name' => 'Proxy US 7 ngày',
        'country_code' => 'US',
        'protocol' => 'socks5',
        'supported_protocols' => ['socks5', 'http'],
        'default_provider_id' => $provider->id,
        'base_price' => 1,
        'selling_price' => 2,
        'max_quantity' => 10,
        'is_active' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/client/proxy/products')
        ->assertOk()
        ->assertJsonPath('data.categories.0.code', 'datacenter')
        ->assertJsonPath('data.categories.0.products.0.code', 'dc-us-7d')
        ->assertJsonPath('data.categories.0.products.0.supported_protocols', ['socks5', 'http'])
        ->assertJsonPath('data.categories.0.products.0.selling_price', '2.0000');

    $category->update(['is_active' => false]);

    $this->actingAs($user)->getJson('/api/client/proxy/products')
        ->assertOk()
        ->assertJsonCount(0, 'data.categories')
        ->assertJsonCount(0, 'data.products');
});

it('does not delete a category that still has products', function () {
    $admin = taxonomyAdmin();
    $category = ProxyCategory::query()->create(['code' => 'mobile', 'name' => 'Proxy Mobile']);
    $provider = ProxyProvider::query()->create(['name' => 'Provider', 'driver' => 'manual']);
    $product = ProxyProduct::query()->create([
        'proxy_category_id' => $category->id,
        'code' => 'mobile-vn',
        'name' => 'Mobile Việt Nam',
        'default_provider_id' => $provider->id,
        'base_price' => 1,
        'selling_price' => 2,
    ]);

    $this->actingAs($admin)->deleteJson("/api/admin-api/proxy-categories/{$category->id}")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Không thể xóa chuyên mục đang có sản phẩm proxy.');

    $product->delete();
    $this->actingAs($admin)->deleteJson("/api/admin-api/proxy-categories/{$category->id}")->assertOk();
});

it('does not expose the removed proxy service admin endpoint', function () {
    $hasServiceRoute = collect(Route::getRoutes()->getRoutes())
        ->contains(fn ($route): bool => str_contains($route->uri(), 'proxy-services'));

    expect($hasServiceRoute)->toBeFalse();
});

it('rejects a daily selling price below the daily base price', function () {
    $admin = taxonomyAdmin();
    $category = ProxyCategory::query()->create(['code' => 'tier-validation', 'name' => 'Tier validation']);
    $provider = ProxyProvider::query()->create(['name' => 'Provider', 'driver' => 'manual', 'is_active' => true]);

    $this->actingAs($admin)->postJson('/api/admin-api/proxy-products', [
        'proxy_category_id' => $category->id,
        'code' => 'invalid-daily-price',
        'name' => 'Invalid daily price',
        'supported_protocols' => ['http'],
        'default_provider_id' => $provider->id,
        'base_price' => 5000,
        'selling_price' => 4000,
        'max_quantity' => 100,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('selling_price');
});

it('requires at least one supported protocol for an admin product', function () {
    $admin = taxonomyAdmin();
    $category = ProxyCategory::query()->create(['code' => 'protocol-validation', 'name' => 'Protocol validation']);
    $provider = ProxyProvider::query()->create(['name' => 'Provider', 'driver' => 'manual', 'is_active' => true]);

    $this->actingAs($admin)->postJson('/api/admin-api/proxy-products', [
        'proxy_category_id' => $category->id,
        'code' => 'missing-protocol',
        'name' => 'Missing protocol',
        'default_provider_id' => $provider->id,
        'base_price' => 1000,
        'selling_price' => 1200,
        'max_quantity' => 100,
        'supported_protocols' => [],
    ])->assertUnprocessable()->assertJsonValidationErrors('supported_protocols');
});
