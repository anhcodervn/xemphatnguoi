@php
    $settings = $systemSettings ?? [];
    $siteName = ($settings['site_name'] ?? null) ?: config('app.name', 'XemPhatNguoi.vn');
    $metaTitle = $pageMetaTitle ?? (($settings['meta_title'] ?? '') ?: $siteName);
    $metaDescription = $pageMetaDescription ?? (($settings['meta_description'] ?? '') ?: 'Tra cứu phạt nguội theo biển số xe.');
    $canonical = $pageMetaCanonical ?? $pageMetaUrl ?? request()->url();
    $metaImage = ($pageMetaImage ?? ($settings['og_image'] ?? '')) ?: asset('images/og-traffic-fine.svg');
    $robots = $pageMetaRobots ?? (($settings['robots'] ?? '') ?: 'index,follow');
    $configuredGtmId = trim((string) ($settings['gtm_id'] ?? ''));
    $gtmId = preg_match('/^GTM-[A-Z0-9]+$/i', $configuredGtmId) === 1 ? strtoupper($configuredGtmId) : '';
    $configuredMetaPixelId = trim((string) ($settings['meta_pixel_id'] ?? ''));
    $metaPixelId = preg_match('/^[0-9]+$/', $configuredMetaPixelId) === 1 ? $configuredMetaPixelId : '';
    $schemas = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => url('/'),
            'logo' => filled($settings['light_logo'] ?? null) ? $settings['light_logo'] : null,
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => url('/'),
        ],
    ];

    if (isset($structuredData) && is_array($structuredData)) {
        $schemas = [...$schemas, ...$structuredData];
    }
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $pageMetaUrl ?? request()->url() }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $siteName }} - Tra cứu phạt nguội">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">

    @if (! empty($settings['favicon']))<link rel="icon" href="{{ $settings['favicon'] }}">@endif

    @if ($gtmId !== '')
        <script>
            (function(w, d, s, l, i) {
                w[l] = w[l] || [];
                w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
                var firstScript = d.getElementsByTagName(s)[0];
                var tagManagerScript = d.createElement(s);
                var dataLayerQuery = l !== 'dataLayer' ? '&l=' + l : '';
                tagManagerScript.async = true;
                tagManagerScript.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dataLayerQuery;
                firstScript.parentNode.insertBefore(tagManagerScript, firstScript);
            })(window, document, 'script', 'dataLayer', {{ Illuminate\Support\Js::from($gtmId) }});
        </script>
    @endif

    @if ($metaPixelId !== '')
        <script>
            !function(f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function() { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = true;
                n.version = '2.0';
                n.queue = [];
                t = b.createElement(e);
                t.async = true;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s);
            }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', {{ Illuminate\Support\Js::from($metaPixelId) }});
            fbq('track', 'PageView');
        </script>
    @endif

    @vite('resources/css/app.css')
    @stack('head')

    @foreach ($schemas as $schema)
        <script type="application/ld+json">{!! json_encode(array_filter($schema, static fn ($value) => $value !== null && $value !== ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach
    <x-custom-meta-tags :html="$settings['custom_header'] ?? ''" />
</head>
<body class="public-site min-h-screen bg-white text-navy">
    @if ($gtmId !== '')
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" class="hidden" title="Google Tag Manager"></iframe></noscript>
    @endif
    @if ($metaPixelId !== '')
        <noscript><img src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1" height="1" width="1" class="hidden" alt=""></noscript>
    @endif
    <a href="#main-content" class="sr-only z-[100] rounded-md bg-slate-950 px-4 py-3 text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Bỏ qua điều hướng</a>
    <x-header :settings="$settings" />

    <main id="main-content">
        @yield('content')
    </main>

    <x-footer :settings="$settings" />
    @stack('scripts')
    @if (filled($settings['custom_script'] ?? null))
        {!! $settings['custom_script'] !!}
    @endif
</body>
</html>
