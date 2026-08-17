<?php

it('shows the required traffic fine dashboard navigation', function () {
    $sidebar = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/client/Sidebar.vue');

    expect($sidebar)
        ->toContain("label: 'Tổng quan'")
        ->toContain("label: 'Tra cứu trên website'")
        ->toContain("href: '/tra-cuu-phat-nguoi', external: true")
        ->toContain("label: 'Lịch sử tra cứu'")
        ->toContain("label: 'Xe của tôi'")
        ->toContain("label: 'Theo dõi biển số'")
        ->toContain("label: 'API'")
        ->toContain("label: 'Lượt dùng API'")
        ->toContain("label: 'Nạp tiền'")
        ->toContain("label: 'Giao dịch'")
        ->toContain("label: 'Tài khoản'")
        ->toContain('fixed inset-y-0 left-0')
        ->toContain('min-h-0 flex-1 overflow-y-auto');

    expect($sidebar)->not->toContain("label: 'Gói dịch vụ'");

    $layout = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/ClientLayout.vue');

    expect($layout)->toContain('lg:ml-72');
});

it('keeps lookup actions on Blade while dashboard retains management services', function () {
    $root = dirname(__DIR__, 2).'/resources/js';
    $router = file_get_contents($root.'/router/modules/client/index.ts');
    $service = file_get_contents($root.'/services/traffic-fine.service.ts');
    $vehiclesPage = file_get_contents($root.'/pages/client/vehicles/index.vue');

    expect($router)
        ->toContain("path: 'vehicles'")
        ->toContain("path: 'api-usage'")
        ->not->toContain("name: 'client.lookup'")
        ->and($service)
        ->toContain("api.get('/api/client/traffic-fines/vehicles')")
        ->toContain("api.get('/api/client/traffic-fines/api-usage'")
        ->not->toContain('/api/client/traffic-fines/lookup')
        ->not->toContain('lookupVehicle')
        ->and($vehiclesPage)
        ->toContain('publicLookupUrl')
        ->toContain('/tra-cuu/${encodeURIComponent(vehicle.plate)}')
        ->toContain('Tra cứu trên website')
        ->not->toContain('trafficFineService.lookupVehicle');
});
