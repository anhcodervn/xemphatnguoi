<?php

it('keeps the Blade and instant lookup result designs visually aligned', function (): void {
    $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/lookup-result.blade.php');
    $script = file_get_contents(dirname(__DIR__, 2).'/resources/js/public-lookup.ts');

    expect($blade)
        ->toContain('data-result-visual')
        ->toContain('data-result-tone="{{ $hasViolations ? \'violation\' : \'clear\' }}"')
        ->toContain('data-result-header')
        ->toContain('data-result-toolbar')
        ->toContain('data-result-ad-target')
        ->toContain('data-result-metrics')
        ->toContain('data-result-count-pill')
        ->toContain('data-result-empty')
        ->toContain('data-violation-list')
        ->toContain('data-violation-card')
        ->toContain('data-violation-header')
        ->toContain('data-violation-meta')
        ->toContain('data-violation-behavior')
        ->toContain('data-violation-details')
        ->toContain('Tra cứu vi phạm')
        ->toContain('Cập nhật:')
        ->toContain('Đã xử phạt')
        ->toContain('Chưa xử phạt')
        ->toContain('Nội dung vi phạm')
        ->toContain('Xem mức phạt')
        ->toContain('border border-slate-300 bg-white')
        ->toContain('border border-red-200 bg-red-50')
        ->toContain('count($lookup->data->violations)')
        ->not->toContain('data-notification-toggle')
        ->not->toContain('Tự động thông báo')
        ->and($script)
        ->toContain("header.dataset.resultVisual = ''")
        ->toContain("header.dataset.resultHeader = ''")
        ->toContain("header.dataset.resultToolbar = ''")
        ->toContain("header.dataset.resultTone = hasViolations ? 'violation' : 'clear'")
        ->toContain("advertisementTarget.dataset.resultAdTarget = ''")
        ->toContain("metrics.dataset.resultMetrics = ''")
        ->toContain("item.dataset.resultCountPill = ''")
        ->toContain("details.dataset.resultEmpty = ''")
        ->toContain("list.dataset.violationList = ''")
        ->toContain("card.dataset.violationCard = ''")
        ->toContain("header.dataset.violationHeader = ''")
        ->toContain("meta.dataset.violationMeta = ''")
        ->toContain("behavior.dataset.violationBehavior = ''")
        ->toContain("details.dataset.violationDetails = ''")
        ->toContain('Tra cứu vi phạm')
        ->toContain("metricItem('Đã xử phạt'")
        ->toContain("metricItem('Chưa xử phạt'")
        ->toContain('Nội dung vi phạm')
        ->toContain('Xem mức phạt')
        ->toContain('border border-slate-300 bg-white')
        ->toContain('border border-red-200 bg-red-50')
        ->toContain('data.violations.length')
        ->toContain('Mở trang đầy đủ')
        ->not->toContain('data-notification-toggle')
        ->not->toContain('Tự động thông báo');
});

it('keeps the public result page focused on the response instead of technical crawl details', function (): void {
    $page = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/public/result.blade.php');
    $home = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/public/home.blade.php');

    expect($page)
        ->toContain('Kết quả tra cứu')
        ->toContain('Tra cứu biển số mới')
        ->toContain('max-w-4xl px-4 py-4 sm:px-5 sm:py-6')
        ->toContain('mt-1 text-xl font-black tracking-tight text-slate-950 sm:text-2xl')
        ->toContain('data-lookup-result-ad')
        ->toContain('id="tra-cuu"')
        ->not->toContain('Trang này được đặt noindex')
        ->and($home)
        ->toContain('data-lookup-result-ad')
        ->toContain('<x-ad-slot name="lookup_result_bottom" />');
});

it('uses one compact density scale across public Blade surfaces', function (): void {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');
    $header = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/header.blade.php');
    $hero = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/home/hero.blade.php');
    $form = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/lookup-form.blade.php');
    $vehicleLinks = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/home/vehicle-links.blade.php');
    $violations = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/home/common-violations.blade.php');

    expect($css)
        ->toContain('.site-container')
        ->toContain('@apply mx-auto w-full max-w-5xl px-4 sm:px-5;')
        ->toContain('.site-section')
        ->toContain('@apply py-8 sm:py-10;')
        ->and($header)
        ->toContain('site-container flex h-[58px]')
        ->not->toContain('h-[66px]')
        ->and($hero)
        ->toContain('max-w-[720px]')
        ->toContain('text-[1.65rem]')
        ->not->toContain('max-w-[820px]')
        ->not->toContain('text-[2.8rem]')
        ->and($form)
        ->toContain('min-h-11')
        ->toContain('h-12 w-full')
        ->not->toContain('h-[52px]')
        ->and($vehicleLinks)
        ->not->toContain('min-h-[190px]')
        ->and($violations)
        ->not->toContain('min-h-[205px]');
});
