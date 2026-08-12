<?php

use Illuminate\Support\Facades\Route;

test('public blade proxy tools are not registered', function (): void {
    expect(Route::has('tools.proxy_live'))->toBeFalse()
        ->and(Route::has('tools.proxy_country'))->toBeFalse();
});
