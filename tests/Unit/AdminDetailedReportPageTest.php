<?php

test('admin detailed report is wired into navigation router and service', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $navigationSource = file_get_contents($projectRoot.'/resources/js/layouts/admin/sidebar/navigation.ts');
    $routerSource = file_get_contents($projectRoot.'/resources/js/router/modules/admin/index.ts');
    $serviceSource = file_get_contents($projectRoot.'/resources/js/services/admin-traffic-fine.service.ts');

    expect($navigationSource)
        ->toContain("label: 'Báo cáo chi tiết', href: '/admin/reports'")
        ->and($routerSource)
        ->toContain("path: 'reports', name: 'admin.reports'")
        ->toContain('@/pages/admin/traffic-fines/ReportPage.vue')
        ->and($serviceSource)
        ->toContain('async report(days = 30)')
        ->toContain("'/api/admin-api/traffic-fines/report'");
});

test('admin detailed report exposes period controls and operational sections', function (): void {
    $reportPage = file_get_contents(dirname(__DIR__, 2).'/resources/js/pages/admin/traffic-fines/ReportPage.vue');

    expect($reportPage)
        ->toContain('Báo cáo chi tiết tra cứu')
        ->toContain('Tỷ lệ cache dương')
        ->toContain('Negative cache')
        ->toContain('Xu hướng theo ngày')
        ->toContain('Phân bổ loại xe')
        ->toContain('Lỗi gần nhất')
        ->toContain('aria-label="Chọn khoảng thời gian báo cáo"');
});
