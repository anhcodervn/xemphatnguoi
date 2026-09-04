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
        ->and($clientSidebar)->toContain('Gói dịch vụ')
        ->and($adminSidebar)->toContain('Gói theo dõi xe');
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
