<?php

use Illuminate\Support\Facades\Schema;

it('contains the proxy marketplace schema', function () {
    expect(Schema::hasColumns('proxy_categories', [
        'code', 'name', 'description', 'icon', 'sort_order', 'is_active',
    ]))->toBeTrue();

    expect(Schema::hasColumns('proxy_providers', [
        'name', 'code', 'order_method', 'driver', 'api_base_url', 'credentials', 'settings', 'priority', 'is_active',
    ]))->toBeTrue();

    expect(Schema::hasColumns('proxy_products', [
        'proxy_category_id', 'code', 'name', 'country_code', 'protocol', 'supported_protocols', 'provider_product_code', 'default_provider_id',
        'base_price', 'selling_price', 'max_quantity', 'is_active',
    ]))->toBeTrue();

    expect(Schema::hasColumns('user_proxies', [
        'provider_code', 'response',
    ]))->toBeTrue();

});
