<?php

it('groups proxy checks under the tools dropdown', function () {
    $sidebar = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/client/Sidebar.vue');

    expect($sidebar)
        ->toContain("label: 'Công cụ'")
        ->toContain("label: 'Check Live Proxy', icon: ShieldCheck, href: '/proxy-check'")
        ->toContain("label: 'Check quốc gia', icon: Globe2, href: '/proxy-country-check'")
        ->toContain(':aria-expanded="openMenus[item.label] ?? false"')
        ->toContain('v-show="openMenus[item.label]"')
        ->toContain('fixed inset-y-0 left-0')
        ->not->toContain('lg:relative')
        ->toContain('min-h-0 flex-1 overflow-y-auto');

    $layout = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/ClientLayout.vue');

    expect($layout)->toContain('lg:ml-72');
});

it('connects the country checker page to its route and queued api', function () {
    $root = dirname(__DIR__, 2).'/resources/js';
    $router = file_get_contents($root.'/router/modules/client/index.ts');
    $service = file_get_contents($root.'/services/client-proxy.service.ts');
    $page = file_get_contents($root.'/pages/client/proxy-country-check/index.vue');

    expect($router)
        ->toContain("path: 'proxy-country-check'")
        ->toContain("name: 'client.proxy-country-check'")
        ->and($service)
        ->toContain("api.post('/api/client/proxy/country-check'")
        ->toContain('proxyCountryCheckStatus')
        ->and($page)
        ->toContain('Check quốc gia proxy')
        ->toContain("listen('.proxy.check.progressed'")
        ->toContain('Kết quả vị trí realtime')
        ->toContain('https://flagcdn.com/w80/')
        ->toContain('Quốc kỳ ${item.country_name || item.country_code}');
});
