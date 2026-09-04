<?php

test('client monitoring page exposes interval monitoring and change-only email controls', function () {
    $projectRoot = dirname(__DIR__, 2);
    $page = file_get_contents($projectRoot.'/resources/js/pages/client/monitoring/index.vue');
    $service = file_get_contents($projectRoot.'/resources/js/services/traffic-fine.service.ts');
    $schedule = file_get_contents($projectRoot.'/routes/console.php');

    expect($page)
        ->toContain('Theo dõi định kỳ')
        ->toContain('Gửi thông báo về email')
        ->toContain('Chỉ gửi khi số lỗi thay đổi')
        ->toContain('trafficFineService.monitoring')
        ->toContain('trafficFineService.updateVehicleMonitoring')
        ->toContain("import Swal from 'sweetalert2'")
        ->toContain('toast: true')
        ->and($service)
        ->toContain("api.get('/api/client/traffic-fines/monitoring')")
        ->toContain('/api/client/traffic-fines/vehicles/${id}/monitoring')
        ->and($schedule)
        ->toContain("Schedule::command('traffic-fines:dispatch-monitoring-checks')")
        ->toContain('everyMinute()');
});

test('client support route uses its actual dashboard path', function () {
    $layout = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/ClientLayout.vue');

    expect($layout)
        ->toContain("route.path === '/dashboard/support'")
        ->not->toContain("route.path === '/support'");
});
