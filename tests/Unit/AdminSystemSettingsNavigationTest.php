<?php

test('admin sidebar has a dedicated system settings menu', function () {
    $navigationSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/admin/sidebar/navigation.ts');

    expect($navigationSource)
        ->toContain("key: 'system-settings'")
        ->toContain("label: 'Cấu hình hệ thống'")
        ->toContain("label: 'Cấu hình chung', href: '/admin/settings/general'")
        ->toContain("label: 'Cấu hình nội dung', href: '/admin/settings/content'");
});

test('admin system settings routes render the existing settings pages', function () {
    $routerSource = file_get_contents(dirname(__DIR__, 2).'/resources/js/router/modules/admin/index.ts');

    expect($routerSource)
        ->toContain("path: 'settings', redirect: { name: 'admin.settings.general' }")
        ->toContain("path: 'settings/general', name: 'admin.settings.general'")
        ->toContain('@/pages/admin/settings/index.vue')
        ->toContain("path: 'settings/content', name: 'admin.settings.content'")
        ->toContain('@/pages/admin/settings/content/index.vue');
});
