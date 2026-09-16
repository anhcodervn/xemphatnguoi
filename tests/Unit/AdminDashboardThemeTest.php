<?php

test('admin dashboard uses the traffic fine product palette', function () {
    $dashboardSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/pages/admin/home/index.vue');

    expect($dashboardSource)
        ->toContain('bg-slate-950', 'bg-sky-50', 'text-sky-700')
        ->not->toContain('gradient');
});

test('admin dashboard includes real api billing and operational metrics', function () {
    $dashboardSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/pages/admin/home/index.vue');

    expect($dashboardSource)
        ->toContain('Lượt trả phí hôm nay')
        ->toContain('Doanh thu hôm nay')
        ->toContain('Cost tháng')
        ->toContain('Lợi nhuận tháng')
        ->toContain('Tổng lợi nhuận API')
        ->toContain('Thu nhập từ lượt API trả phí')
        ->toContain('Doanh thu gói theo dõi')
        ->toContain('Tổng tiền khách nạp')
        ->toContain('metrics.package_revenue.month')
        ->toContain('metrics.wallet_deposits.today')
        ->toContain('metrics.wallet_deposits.week')
        ->toContain('metrics.wallet_deposits.month')
        ->toContain('Cache hit')
        ->toContain('Provider requests')
        ->toContain('Provider errors')
        ->toContain('Độ trễ TB')
        ->toContain('api_chart')
        ->toContain('adminTrafficFineService.overview()');
});
