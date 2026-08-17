<?php

use Tests\TestCase;

uses(TestCase::class);

test('public layout renders configured tag manager and meta pixel identifiers', function (): void {
    $this->withoutVite();

    $html = view('layouts.public', [
        'systemSettings' => [
            'site_name' => 'XemPhatNguoi.vn',
            'gtm_id' => 'GTM-ABC123',
            'meta_pixel_id' => '123456789012345',
        ],
    ])->render();

    expect($html)
        ->toContain('https://www.googletagmanager.com/gtm.js?id=')
        ->toContain('https://www.googletagmanager.com/ns.html?id=GTM-ABC123')
        ->toContain('https://connect.facebook.net/en_US/fbevents.js')
        ->toContain('https://www.facebook.com/tr?id=123456789012345&ev=PageView&noscript=1')
        ->toContain("fbq('init', '123456789012345')");
});

test('public layout ignores malformed tracking identifiers', function (): void {
    $this->withoutVite();

    $html = view('layouts.public', [
        'systemSettings' => [
            'site_name' => 'XemPhatNguoi.vn',
            'gtm_id' => '</script><script>alert(1)</script>',
            'meta_pixel_id' => 'not-a-pixel',
        ],
    ])->render();

    expect($html)
        ->not->toContain('googletagmanager.com/gtm.js')
        ->not->toContain('connect.facebook.net/en_US/fbevents.js')
        ->not->toContain('alert(1)');
});

test('public landing controllers request tracking settings from the setting store', function (): void {
    $root = dirname(__DIR__, 2);
    $contentController = file_get_contents($root.'/app/Http/Controllers/PublicContentPageController.php');
    $seoController = file_get_contents($root.'/app/Http/Controllers/PublicSeoPageController.php');

    expect($contentController)
        ->toContain("'gtm_id' => ''", "'meta_pixel_id' => ''")
        ->and($seoController)
        ->toContain("'gtm_id' => ''", "'meta_pixel_id' => ''");
});
