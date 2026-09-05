<?php

it('shows api versions and filtering in admin and client logs', function (): void {
    $resourceRoot = dirname(__DIR__, 2).'/resources/js';
    $adminPage = file_get_contents($resourceRoot.'/pages/admin/api-logs/index.vue');
    $clientPage = file_get_contents($resourceRoot.'/pages/client/api-usage/index.vue');
    $adminType = file_get_contents($resourceRoot.'/types/admin-api-log.type.ts');
    $clientType = file_get_contents($resourceRoot.'/types/traffic-fine.type.ts');
    $adminNavigation = file_get_contents($resourceRoot.'/layouts/admin/sidebar/navigation.ts');

    expect($adminPage)
        ->toContain('v-model="filters.api_version"')
        ->toContain('Mọi phiên bản')
        ->toContain('API {{ row.api_version }}')
        ->and($clientPage)->toContain('API {{ row.api_version }}')
        ->and($adminType)->toContain("api_version: 'v1' | 'v2' | 'unknown'")
        ->and($clientType)->toContain("api_version: 'v1' | 'v2' | 'unknown'")
        ->and($adminNavigation)->toContain("key: 'partner-api'")
        ->toContain("label: 'API đối tác'")
        ->toContain("{ label: 'Cấu hình giá API', href: '/admin/api-billing' }")
        ->toContain("{ label: 'Nhật ký API', href: '/admin/api-usage' }");
});
