<?php

test('admin sidebar exposes recharge configuration and history', function () {
    $navigationSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/admin/sidebar/navigation.ts');

    expect($navigationSource)
        ->toContain("key: 'recharge'")
        ->toContain("label: 'Quản lý nạp tiền'")
        ->toContain("label: 'Cấu hình nạp tiền', href: '/admin/recharge/config'")
        ->toContain("label: 'Lịch sử nạp tiền', href: '/admin/recharge/history'");
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
