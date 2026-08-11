<?php

test('admin dashboard uses the blue product palette', function () {
    $dashboardSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/pages/admin/home/index.vue');

    expect($dashboardSource)
        ->toContain('#071a3d', '#0b4bd9', 'bg-blue-600')
        ->not->toContain('emerald', 'teal');
});

test('admin dashboard includes complete operational sections', function () {
    $dashboardSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/pages/admin/home/index.vue');

    expect($dashboardSource)
        ->toContain('Xu hướng tài chính')
        ->toContain('Trạng thái task')
        ->toContain('Sức khỏe hệ thống')
        ->toContain('Dịch vụ doanh thu cao')
        ->toContain('Task lỗi gần đây')
        ->toContain('Các khu vực quản trị thường dùng')
        ->toContain("adminAnalyticsService.dashboard('7d')")
        ->toContain('adminProxyProviderService.list({ per_page: 100 })')
        ->toContain('adminProxyProductService.list({ per_page: 100 })');
});
