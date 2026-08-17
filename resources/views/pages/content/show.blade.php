@extends('layouts.public')

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-8 sm:px-5 sm:py-10">
        <x-breadcrumb :items="[['label' => 'Trang chủ', 'url' => route('traffic-fines.home')], ['label' => $pageTitle]]" />
        <header class="mt-4 max-w-2xl"><h1 class="text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $pageTitle }}</h1><p class="mt-2.5 text-sm leading-6 text-slate-600">{{ $pageDescription }}</p></header>
        @if ($contentHtml)
            <div class="article-content mt-6">{!! $contentHtml !!}</div>
        @else
            <div class="mt-6 rounded-lg bg-slate-50 p-4 text-xs leading-6 text-slate-600">Nội dung đang được cập nhật. Vui lòng liên hệ bộ phận hỗ trợ nếu bạn cần thông tin ngay.</div>
        @endif
    </article>
@endsection
