<?php

it('connects the public partner navigation to protected api documentation', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $header = file_get_contents($projectRoot.'/resources/views/components/header.blade.php');
    $webRoutes = file_get_contents($projectRoot.'/routes/web.php');
    $partnerLanding = file_get_contents($projectRoot.'/resources/views/pages/public/partners.blade.php');
    $apiDocumentation = file_get_contents($projectRoot.'/resources/js/pages/client/api-docs/index.vue');

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
        ->toContain('Bắt đầu thuê API')
        ->toContain('<TabApiKeys')
        ->toContain('X-API-KEY')
        ->toContain('X-API-SECRET')
        ->toContain('electric_motorbike')
        ->toContain('1.000 request')
        ->toContain('Checklist bảo mật')
        ->not->toContain('api.xephatnguoi.com')
        ->not->toMatch('/xp_[A-Za-z0-9]{16,}/');
});
