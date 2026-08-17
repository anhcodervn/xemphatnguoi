<?php

test('admin sidebar groups recharge configuration and history under users and finance', function () {
    $navigationSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/admin/sidebar/navigation.ts');

    expect($navigationSource)
        ->toContain("key: 'users-finance'")
        ->toContain("label: 'Người dùng & tài chính'")
        ->toContain("label: 'Cấu hình nạp tiền', href: '/admin/recharge/config'")
        ->toContain("label: 'Lịch sử nạp tiền', href: '/admin/recharge/history'");
});

test('admin sidebar uses compact functional navigation groups', function () {
    $projectRoot = dirname(__DIR__, 2);
    $navigationSource = file_get_contents($projectRoot.'/resources/js/layouts/admin/sidebar/navigation.ts');
    $sidebarSource = file_get_contents($projectRoot.'/resources/js/layouts/admin/sidebar/index.vue');

    expect($navigationSource)
        ->toContain("key: 'lookup-data'")
        ->toContain("label: 'Tra cứu & dữ liệu'")
        ->toContain("key: 'users-finance'")
        ->toContain("key: 'content-growth'")
        ->toContain("key: 'system-operations'")
        ->and($sidebarSource)
        ->toContain('filter((group) => group.children)')
        ->toContain(':aria-expanded="expandedGroups[group.key]"')
        ->toContain('class="grid gap-1.5"')
        ->not->toContain('rounded-[1.35rem]');
});

test('admin recharge routes still render their existing pages', function () {
    $routerSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/router/modules/admin/index.ts');

    expect($routerSource)
        ->toContain("path: 'recharge/config', name: 'admin.recharge.config'")
        ->toContain('@/pages/admin/settings/recharge/index.vue')
        ->toContain("path: 'recharge/history', name: 'admin.recharge.history'")
        ->toContain('@/pages/admin/recharge/history/index.vue');
});

test('admin page title uses the longest matching group or child route', function () {
    $navigationSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/admin/sidebar/navigation.ts');

    expect($navigationSource)
        ->toContain('adminMenuGroups.flatMap')
        ->toContain('...(group.children ?? [])')
        ->toContain('right.href.length - left.href.length')
        ->toContain("return matchedItem?.label ?? 'Admin';");
});
