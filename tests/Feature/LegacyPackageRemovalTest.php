<?php

use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

test('legacy package marketplace is removed from the application', function () {
    $legacyTables = [
        'packages',
        'user_packages',
        'package_orders',
        'user_subscriptions',
        'extra_account_orders',
        'accounts',
    ];

    foreach ($legacyTables as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }

    $routeUris = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route): string => $route->uri())
        ->filter(fn (string $uri): bool => str_contains($uri, 'package') || str_contains($uri, 'subscription'));

    expect($routeUris)->toBeEmpty()
        ->and(class_exists(Package::class))->toBeFalse()
        ->and(class_exists(PackageOrder::class))->toBeFalse()
        ->and(class_exists(UserSubscription::class))->toBeFalse();
});
