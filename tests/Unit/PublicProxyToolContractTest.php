<?php

test('public blade proxy tools are removed while authenticated client tools remain', function (): void {
    $root = dirname(__DIR__, 2);
    $routes = file_get_contents($root.'/routes/web.php');
    $landing = file_get_contents($root.'/resources/views/layouts/landing.blade.php');
    $clientRoutes = file_get_contents($root.'/resources/js/router/modules/client/index.ts');
    $sidebar = file_get_contents($root.'/resources/js/layouts/client/Sidebar.vue');

    expect($routes)->not->toContain('PublicProxyToolPageController')
        ->and($routes)->not->toContain("name('tools.proxy_live')")
        ->and($landing)->not->toContain("route('tools.proxy_live')")
        ->and($landing)->not->toContain("route('tools.proxy_country')")
        ->and(file_exists($root.'/app/Http/Controllers/PublicProxyToolPageController.php'))->toBeFalse()
        ->and(file_exists($root.'/resources/views/pages/tools/proxy-live-check.blade.php'))->toBeFalse()
        ->and(file_exists($root.'/resources/views/pages/tools/proxy-country-check.blade.php'))->toBeFalse()
        ->and($clientRoutes)->toContain("path: 'proxy-check'")
        ->and($clientRoutes)->toContain("path: 'proxy-country-check'")
        ->and($sidebar)->toContain("label: 'Công cụ'");
});
