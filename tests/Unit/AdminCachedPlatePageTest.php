<?php

test('admin cached plate page reuses the grouped results route and service', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $navigationSource = file_get_contents($projectRoot.'/resources/js/layouts/admin/sidebar/navigation.ts');
    $routerSource = file_get_contents($projectRoot.'/resources/js/router/modules/admin/index.ts');
    $serviceSource = file_get_contents($projectRoot.'/resources/js/services/admin-traffic-fine.service.ts');

    expect($navigationSource)
        ->toContain("label: 'Cache biển số', href: '/admin/traffic-fine-results'")
        ->and($routerSource)
        ->toContain("path: 'traffic-fine-results', name: 'admin.traffic-fine-results'")
        ->toContain('@/pages/admin/traffic-fines/ResultsPage.vue')
        ->and($serviceSource)
        ->toContain('type AdminCachedPlateResponse')
        ->toContain('async results(params: Partial<AdminCachedPlateFilters> = {})')
        ->toContain("'/api/admin-api/traffic-fines/results'");
});

test('admin cached plate page exposes ttl lookup intensity filters and accessible loading state', function (): void {
    $pageSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/pages/admin/traffic-fines/ResultsPage.vue');

    expect($pageSource)
        ->toContain('Quản lý cache biển số')
        ->toContain('TTL cache')
        ->toContain('Mức độ tra cứu')
        ->toContain('Negative cache lỗi cũng không được tính')
        ->toContain('aria-label="Chọn khoảng thống kê tra cứu"')
        ->toContain(':aria-busy="loading"')
        ->toContain('motion-reduce:animate-none');
});
