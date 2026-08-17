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
        ->toContain('API hôm nay')
        ->toContain('API tháng này')
        ->toContain('Doanh thu hôm nay')
        ->toContain('Doanh thu tháng')
        ->toContain('Giá / request')
        ->toContain('Cache hit')
        ->toContain('Provider requests')
        ->toContain('Provider errors')
        ->toContain('Độ trễ TB')
        ->toContain('api_chart')
        ->toContain('adminTrafficFineService.overview()');
});
