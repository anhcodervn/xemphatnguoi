@extends('layouts.public')

@section('content')
    <article class="site-container py-8 sm:py-12">
        <x-breadcrumb :items="[['label' => 'Trang chủ', 'url' => route('traffic-fines.home')], ['label' => $pageTitle]]" />
        <header class="mt-6 max-w-3xl border-b border-slate-200 pb-6"><h1 class="text-3xl font-extrabold tracking-tight text-navy sm:text-4xl">{{ $pageTitle }}</h1><p class="mt-4 text-base leading-8 text-slate-600">{{ $pageDescription }}</p></header>
        <div class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_220px]">
        <div class="min-w-0">
        @if (in_array($pageSlug, ['gioi-thieu', 'lien-he'], true))
            <p class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-5 text-base leading-8 text-slate-700"><strong class="text-navy">Đơn vị vận hành:</strong> Xem Phạt Nguội.vn. Nền tảng hỗ trợ tra cứu độc lập, không phải website của cơ quan nhà nước hoặc Cảnh sát giao thông.</p>
        @endif
        @if ($contentHtml)
            <div class="article-content mt-6">{!! $contentHtml !!}</div>
        @elseif ($pageSlug !== 'lien-he')
            <div class="mt-6 rounded-lg bg-slate-50 p-4 text-xs leading-6 text-slate-600">Nội dung đang được cập nhật. Vui lòng liên hệ bộ phận hỗ trợ nếu bạn cần thông tin ngay.</div>
        @endif
        @if ($pageSlug === 'lien-he')
            <section class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5" aria-labelledby="contact-channels-title">
                <h2 id="contact-channels-title" class="text-xl font-bold text-navy">Kênh hỗ trợ được công bố</h2>
                <dl class="mt-4 grid gap-4 text-base leading-7 text-slate-700">
                    @if (filter_var($systemSettings['support_email'] ?? '', FILTER_VALIDATE_EMAIL))
                        <div><dt class="font-semibold text-navy">Email hỗ trợ</dt><dd><a href="mailto:{{ $systemSettings['support_email'] }}" class="site-focus inline-flex min-h-11 items-center break-all rounded text-brand underline underline-offset-4">{{ $systemSettings['support_email'] }}</a></dd></div>
                    @endif
                    @if (filled($systemSettings['hotline'] ?? null))
                        <div><dt class="font-semibold text-navy">Điện thoại hỗ trợ</dt><dd>{{ $systemSettings['hotline'] }}</dd></div>
                    @endif
                    @if (filled($systemSettings['address'] ?? null))
                        <div><dt class="font-semibold text-navy">Địa chỉ liên hệ</dt><dd>{{ $systemSettings['address'] }}</dd></div>
                    @endif
                    @if (filter_var($systemSettings['facebook'] ?? '', FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($systemSettings['facebook'], PHP_URL_SCHEME)), ['http', 'https'], true))
                        <div><dt class="font-semibold text-navy">Trang cộng đồng</dt><dd><a href="{{ $systemSettings['facebook'] }}" target="_blank" rel="noopener noreferrer" class="site-focus inline-flex min-h-11 items-center rounded text-brand underline underline-offset-4">Liên hệ qua Facebook →</a></dd></div>
                    @endif
                </dl>
                <p class="mt-4 text-sm leading-7 text-slate-600">Bạn có thể gửi yêu cầu qua kênh được công bố ở trang này. Website hỗ trợ giải thích cách sử dụng công cụ; việc xác minh vi phạm và xử lý hồ sơ thuộc cơ quan có thẩm quyền.</p>
            </section>
            <section class="mt-8" aria-labelledby="contact-correction-title">
                <h2 id="contact-correction-title" class="text-xl font-bold text-navy">Góp ý hoặc yêu cầu sửa thông tin</h2>
                <p class="mt-3 text-base leading-8 text-slate-700">Để mô tả vấn đề rõ ràng, hãy gửi đường dẫn trang, thời điểm gặp lỗi, nội dung chưa chính xác và nguồn đối chiếu nếu có. Với lỗi tra cứu, mô tả thông báo đang hiển thị và loại phương tiện bạn đã chọn.</p>
                <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-7 text-amber-950">Không gửi mật khẩu, API key, mã OTP hoặc thông tin thanh toán. Hãy che dữ liệu cá nhân không liên quan trước khi gửi ảnh chụp màn hình.</p>
            </section>
        @endif
        @if ($pageSlug === 'chinh-sach-bao-mat')
            <section class="article-content mt-10 border-t border-slate-200 pt-6" aria-labelledby="advertising-privacy-title">
                <h2 id="advertising-privacy-title">Google và cookie quảng cáo</h2>
                <p>Khi website hiển thị quảng cáo Google, các nhà cung cấp bên thứ ba, bao gồm Google, có thể sử dụng cookie để phân phối quảng cáo dựa trên những lần bạn truy cập website này hoặc các website khác.</p>
                <p>Cookie quảng cáo cho phép Google và các đối tác phân phối quảng cáo dựa trên hoạt động truy cập của bạn. Bạn có thể quản lý hoặc tắt cá nhân hóa quảng cáo trong <a href="https://myadcenter.google.com/" target="_blank" rel="noopener noreferrer">Trung tâm quảng cáo của Google</a>.</p>
                <p>Tìm hiểu thêm về <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener noreferrer">cách Google sử dụng thông tin trên các website đối tác</a> và <a href="https://optout.aboutads.info/" target="_blank" rel="noopener noreferrer">các lựa chọn quảng cáo của nhà cung cấp bên thứ ba</a>. Bạn cũng có thể quản lý cookie trong trình duyệt; việc chặn cookie cần thiết có thể ảnh hưởng chức năng đăng nhập.</p>
            </section>
        @endif
        </div>
        <nav aria-label="Thông tin và chính sách" class="grid gap-1 rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700 lg:sticky lg:top-24">
            <p class="mb-2 font-bold text-navy">Thông tin website</p>
            <a href="{{ route('content.about') }}" class="site-focus flex min-h-11 items-center rounded hover:text-brand">Giới thiệu</a>
            <a href="{{ route('content.contact') }}" class="site-focus flex min-h-11 items-center rounded hover:text-brand">Liên hệ &amp; góp ý</a>
            <a href="{{ route('content.privacy') }}" class="site-focus flex min-h-11 items-center rounded hover:text-brand">Chính sách bảo mật</a>
            <a href="{{ route('content.terms') }}" class="site-focus flex min-h-11 items-center rounded hover:text-brand">Điều khoản sử dụng</a>
            <a href="{{ route('content.disclaimer') }}" class="site-focus flex min-h-11 items-center rounded hover:text-brand">Miễn trừ trách nhiệm</a>
        </nav>
        </div>
    </article>
@endsection
