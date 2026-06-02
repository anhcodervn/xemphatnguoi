<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $settings = $systemSettings ?? [];
            $siteName = $settings['site_name'] ?? config('app.name', 'Laravel');
            $metaTitle = $settings['meta_title'] ?: $siteName;
            $metaDescription = $settings['meta_description'] ?? '';
            $favicon = $settings['favicon'] ?? '';
        @endphp

        <title>{{ $metaTitle }}</title>
        @if ($metaDescription !== '')
            <meta name="description" content="{{ $metaDescription }}">
        @endif
        @if ($favicon !== '')
            <link rel="icon" href="{{ $favicon }}">
            <link rel="shortcut icon" href="{{ $favicon }}">
            <link rel="apple-touch-icon" href="{{ $favicon }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet" />

        @routes
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="font-sans antialiased">
        <div
            id="app"
            data-site-name="{{ $siteName }}"
        ></div>

        <script src="{{ asset('assets/libs/tinymce/tinymce.min.js') }}"></script>
    </body>
</html>
