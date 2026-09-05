<?php

it('connects the public partner navigation to protected api documentation', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $header = file_get_contents($projectRoot.'/resources/views/components/header.blade.php');
    $webRoutes = file_get_contents($projectRoot.'/routes/web.php');
    $partnerLanding = file_get_contents($projectRoot.'/resources/views/pages/public/partners.blade.php');
    $apiDocumentation = file_get_contents($projectRoot.'/resources/js/pages/client/api-docs/index.vue');
    $adminBilling = file_get_contents($projectRoot.'/resources/js/pages/admin/traffic-fines/BillingPage.vue');

    expect($header)
        ->toContain("'label' => 'API'")
        ->toContain("route('partners.api')")
        ->toContain('class="hidden items-center xl:flex"')
        ->toContain('class="group relative xl:hidden"')
        ->and($webRoutes)
        ->toContain("Route::get('/doi-tac', 'partners')")
        ->and($partnerLanding)
        ->toContain("route('dashboard', ['any' => 'api'])")
        ->toContain('API dành cho đối tác')
        ->toContain('Xem tài liệu và thuê API')
        ->toContain('mỗi request thành công')
        ->and($apiDocumentation)
        ->toContain('Chạy request đầu tiên trong 3 bước')
        ->toContain('<TabApiKeys')
        ->toContain('X-API-KEY')
        ->toContain('X-API-SECRET')
        ->toContain('electric_motorbike')
        ->toContain("selectedVersion = ref<'v1' | 'v2'>('v2')")
        ->toContain('copyRequestExample')
        ->toContain('<details')
        ->toContain('Nạp số dư để gọi API')
        ->toContain('dashboard.api_v1_description')
        ->toContain('dashboard.api_v2_description')
        ->not->toContain('<table')
        ->not->toContain('1.000 request')
        ->not->toContain('Checklist bảo mật')
        ->not->toContain('type=1')
        ->not->toContain('api.xephatnguoi.com')
        ->not->toContain('XePhatNguoi')
        ->not->toMatch('/xp_[A-Za-z0-9]{16,}/');

    expect($adminBilling)
        ->toContain('v-model.trim="v1Description"')
        ->toContain('v-model.trim="v2Description"')
        ->toContain('Không nhập tên nguồn, URL hoặc credential');
});
