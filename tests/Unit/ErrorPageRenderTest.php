<?php

use Tests\TestCase;

uses(TestCase::class);

function errorPageSettings(): array
{
    return [
        'site_name' => 'Apibankvn.com',
        'support_email' => 'support@example.com',
        'hotline' => '1900 1234',
        'light_logo' => 'https://cdn.example.com/logo-light.png',
        'dark_logo' => '',
        'favicon' => 'https://cdn.example.com/favicon.png',
    ];
}

function errorPageActions(string $context): array
{
    return [
        'landing' => [
            'primary' => ['label' => 'Về trang chủ', 'href' => '/'],
            'secondary' => ['label' => 'Liên hệ hỗ trợ', 'href' => '/lien-he'],
        ],
        'client' => [
            'primary' => ['label' => 'Về tổng quan', 'href' => '/'],
            'secondary' => ['label' => 'Liên hệ & góp ý', 'href' => '/contact'],
        ],
        'admin' => [
            'primary' => ['label' => 'Về dashboard admin', 'href' => '/admin'],
            'secondary' => ['label' => 'Quản lý queue', 'href' => '/admin/queues'],
        ],
    ][$context];
}

test('landing 404 page renders expected call to actions', function () {
    $html = view('errors.404', [
        'errorContext' => 'landing',
        'errorActions' => errorPageActions('landing'),
        'systemSettings' => errorPageSettings(),
    ])->render();

    expect($html)
        ->toContain('Không tìm thấy trang bạn đang truy cập')
        ->toContain('Về trang chủ')
        ->toContain('Liên hệ hỗ trợ')
        ->toContain('https://cdn.example.com/logo-light.png');
});

test('admin 520 page renders admin quick actions', function () {
    $html = view('errors.520', [
        'errorContext' => 'admin',
        'errorActions' => errorPageActions('admin'),
        'systemSettings' => errorPageSettings(),
    ])->render();

    expect($html)
        ->toContain('Hệ thống gặp lỗi xử lý ngoài dự kiến')
        ->toContain('Về dashboard admin')
        ->toContain('Quản lý queue')
        ->toContain('support@example.com');
});

test('client 524 page renders client quick actions', function () {
    $html = view('errors.524', [
        'errorContext' => 'client',
        'errorActions' => errorPageActions('client'),
        'systemSettings' => errorPageSettings(),
    ])->render();

    expect($html)
        ->toContain('Yêu cầu xử lý quá lâu nên đã hết thời gian chờ')
        ->toContain('Về tổng quan')
        ->toContain('Liên hệ &amp; góp ý');
});
