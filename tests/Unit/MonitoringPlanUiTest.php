<?php

it('exposes monitoring package pages in client and admin navigation', function (): void {
    $resourceRoot = dirname(__DIR__, 2).'/resources';
    $clientRoutes = file_get_contents($resourceRoot.'/js/router/modules/client/index.ts');
    $adminRoutes = file_get_contents($resourceRoot.'/js/router/modules/admin/index.ts');
    $clientSidebar = file_get_contents($resourceRoot.'/js/layouts/client/Sidebar.vue');
    $adminSidebar = file_get_contents($resourceRoot.'/js/layouts/admin/sidebar/navigation.ts');

    expect($clientRoutes)->toContain("name: 'client.packages'")
        ->and($clientRoutes)->toContain('@/pages/client/packages/index.vue')
        ->and($adminRoutes)->toContain('@/pages/admin/monitoring-plans/index.vue')
        ->and($adminRoutes)->toContain("path: 'packages', redirect: { name: 'admin.monitoring' }")
        ->and($adminRoutes)->toContain("name: 'admin.monitoring-subscriptions'")
        ->and($adminRoutes)->toContain('@/pages/admin/monitoring-subscriptions/index.vue')
        ->and($clientSidebar)->toContain('Gói dịch vụ')
        ->and($adminSidebar)->toContain("key: 'lookup-data'")
        ->and($adminSidebar)->toContain("key: 'monitoring-packages'")
        ->and($adminSidebar)->toContain('icon: PackageOpen')
        ->and($adminSidebar)->toContain('Quản lý gói')
        ->and($adminSidebar)->toContain('Gói đã cho thuê')
        ->and($adminSidebar)->toContain("{ label: 'Cấu hình giá API', href: '/admin/api-billing' }");
});

it('lists rented packages with server side filters and pagination', function (): void {
    $resourceRoot = dirname(__DIR__, 2).'/resources';
    $page = file_get_contents($resourceRoot.'/js/pages/admin/monitoring-subscriptions/index.vue');
    $service = file_get_contents($resourceRoot.'/js/services/admin-monitoring-plan.service.ts');

    expect($page)
        ->toContain('@/components/shared/DataTable/index.vue')
        ->toContain('Khách hàng hoặc tên gói')
        ->toContain('Tất cả trạng thái')
        ->toContain(':go-to-page="load"')
        ->and($service)
        ->toContain('/api/admin-api/monitoring/subscriptions')
        ->toContain('auto_renew?: boolean');
});

it('keeps custom quantity pricing server controlled and without a maximum field', function (): void {
    $resourceRoot = dirname(__DIR__, 2).'/resources';
    $page = file_get_contents($resourceRoot.'/js/pages/client/packages/index.vue');
    $service = file_get_contents($resourceRoot.'/js/services/client-monitoring-plan.service.ts');

    expect($page)->toContain(':min="plan.min_vehicle_count"')
        ->and($page)->not->toContain(' max=')
        ->and($service)->toContain('plan_id: planId')
        ->and($service)->not->toContain('total_price');
});

it('uses TinyMCE for package descriptions and sanitizes rich text before rendering', function (): void {
    $resourceRoot = dirname(__DIR__, 2).'/resources';
    $adminPage = file_get_contents($resourceRoot.'/js/pages/admin/monitoring-plans/index.vue');
    $clientPage = file_get_contents($resourceRoot.'/js/pages/client/packages/index.vue');

    expect($adminPage)
        ->toContain('@/components/shared/Editor/index.vue')
        ->toContain('v-model:value="form.description"')
        ->toContain('format="html"')
        ->and($clientPage)
        ->toContain('@/utils/rich-text')
        ->toContain('sanitizeRichText(plan.description')
        ->toContain('v-html="renderedDescription(plan)"');
});

it('uses SweetAlert2 instead of native alerts for package actions', function (): void {
    $resourceRoot = dirname(__DIR__, 2).'/resources';
    $adminPage = file_get_contents($resourceRoot.'/js/pages/admin/monitoring-plans/index.vue');
    $clientPage = file_get_contents($resourceRoot.'/js/pages/client/packages/index.vue');

    expect($adminPage)
        ->toContain("import Swal from 'sweetalert2'")
        ->toContain('showCancelButton: true')
        ->toContain('confirmation.isConfirmed')
        ->not->toContain('window.confirm')
        ->and($clientPage)
        ->toContain("import Swal from 'sweetalert2'")
        ->toContain('showCancelButton: true')
        ->toContain('confirmation.isConfirmed')
        ->not->toContain('window.confirm');
});

it('lets clients manage automatic package renewal', function (): void {
    $resourceRoot = dirname(__DIR__, 2).'/resources';
    $clientPage = file_get_contents($resourceRoot.'/js/pages/client/packages/index.vue');
    $service = file_get_contents($resourceRoot.'/js/services/client-monitoring-plan.service.ts');

    expect($clientPage)
        ->toContain('clientMonitoringPlanService.updateAutoRenew')
        ->toContain(':aria-checked="subscription.auto_renew"')
        ->toContain('Đang chờ tự gia hạn')
        ->and($service)
        ->toContain('/api/client/monitoring-plans/subscription/auto-renew');
});

it('offers only packages with a higher vehicle limit as upgrades', function (): void {
    $resourceRoot = dirname(__DIR__, 2).'/resources';
    $clientPage = file_get_contents($resourceRoot.'/js/pages/client/packages/index.vue');
    $service = file_get_contents($resourceRoot.'/js/services/client-monitoring-plan.service.ts');

    expect($clientPage)
        ->toContain('vehicleLimitFor(plan) > Number(subscription.value?.vehicle_limit ?? 0)')
        ->toContain('Nâng cấp lên ${vehicleLimitFor(plan)} xe')
        ->toContain('bắt đầu chu kỳ mới ngay sau khi nâng cấp')
        ->and($service)
        ->toContain("api.post('/api/client/monitoring-plans/upgrade'");
});
