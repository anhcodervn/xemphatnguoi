@props(['settings' => []])

@php
    $siteName = ($settings['site_name'] ?? null) ?: config('app.name', 'XemPhatNguoi.vn');
@endphp

<footer class="bg-navy text-white">
    <div class="site-container grid gap-5 py-7 sm:py-8 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div class="max-w-sm">
            <a href="{{ route('traffic-fines.home') }}" class="site-focus inline-flex min-h-11 items-center gap-2.5 rounded-lg text-base font-extrabold text-white">
                <span class="flex h-9 w-9 items-center justify-center rounded-[10px] bg-brand" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-5 w-5"><path d="M4 7.5h16v10H4zM8 7.5l1.3-3h5.4l1.3 3M8 20h.01M16 20h.01"/><circle cx="12" cy="12.5" r="2.5"/></svg></span>
                {{ $siteName }}
            </a>
            <p class="mt-1.5 text-xs leading-5 text-slate-300">Công cụ hỗ trợ kiểm tra dữ liệu phạt nguội theo biển số. Kết quả cần được đối chiếu với cơ quan có thẩm quyền khi xác nhận chính thức.</p>
        </div>

        <nav aria-label="Liên kết tra cứu">
            <p class="text-sm font-bold text-white">Tra cứu</p>
            <div class="mt-1 grid text-xs text-slate-300 [&>a]:flex [&>a]:min-h-11 [&>a]:items-center">
                <a href="{{ route('traffic-fines.home') }}#tra-cuu" class="site-focus rounded hover:text-white">Tra cứu phạt nguội</a>
                <a href="{{ route('traffic-fines.lookup.car') }}" class="site-focus rounded hover:text-white">Tra cứu ô tô</a>
                <a href="{{ route('traffic-fines.lookup.motorbike') }}" class="site-focus rounded hover:text-white">Tra cứu xe máy</a>
                <a href="{{ route('traffic-fines.lookup.electric-motorbike') }}" class="site-focus rounded hover:text-white">Tra cứu xe máy điện</a>
                <a href="{{ route('traffic-fines.penalties.index') }}" class="site-focus rounded hover:text-white">Mức phạt giao thông</a>
            </div>
        </nav>

        <nav aria-label="Liên kết kiến thức">
            <p class="text-sm font-bold text-white">Kiến thức</p>
            <div class="mt-1 grid text-xs text-slate-300 [&>a]:flex [&>a]:min-h-11 [&>a]:items-center">
                <a href="{{ route('traffic-fines.knowledge.what-is') }}" class="site-focus rounded hover:text-white">Phạt nguội là gì?</a>
                <a href="{{ route('traffic-fines.knowledge.guide') }}" class="site-focus rounded hover:text-white">Hướng dẫn tra cứu</a>
                <a href="{{ route('traffic-fines.penalties.index') }}" class="site-focus rounded hover:text-white">Lỗi giao thông</a>
                <a href="{{ route('seo.index') }}" class="site-focus rounded hover:text-white">Blog giao thông</a>
            </div>
        </nav>

        <nav aria-label="Liên kết hỗ trợ">
            <p class="text-sm font-bold text-white">Hỗ trợ</p>
            <div class="mt-1 grid text-xs text-slate-300 [&>a]:flex [&>a]:min-h-11 [&>a]:items-center">
                <a href="{{ route('content.about') }}" class="site-focus rounded hover:text-white">Giới thiệu</a>
                <a href="{{ route('content.contact') }}" class="site-focus rounded hover:text-white">Liên hệ</a>
                <a href="{{ route('content.privacy') }}" class="site-focus rounded hover:text-white">Chính sách bảo mật</a>
                <a href="{{ route('content.terms') }}" class="site-focus rounded hover:text-white">Điều khoản sử dụng</a>
            </div>
        </nav>
    </div>

    <div class="border-t border-white/10">
        <div class="site-container flex flex-col gap-1 py-3 text-[11px] leading-5 text-slate-400 md:flex-row md:items-center md:justify-between">
            <p>© {{ now()->year }} {{ $siteName }}.</p>
            <p>Dữ liệu có thể có độ trễ so với quy trình cập nhật của đơn vị xử lý.</p>
        </div>
    </div>
</footer>
