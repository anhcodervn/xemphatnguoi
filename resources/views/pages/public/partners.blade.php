@extends('layouts.public')

@section('content')
    <section class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-b from-sky-50 via-white to-white">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-sky-200/30 blur-3xl"></div>
            <div class="absolute -right-24 top-0 h-80 w-80 rounded-full bg-blue-200/30 blur-3xl"></div>
        </div>

        <div class="site-container relative grid gap-7 py-8 sm:py-10 lg:grid-cols-[minmax(0,1.15fr)_minmax(300px,0.85fr)] lg:items-center">
            <div>
                <x-breadcrumb :items="[['label' => 'Trang chủ', 'url' => route('traffic-fines.home')], ['label' => 'Đối tác API']]" />
                <div class="mt-4 inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-white px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[0.12em] text-sky-700 shadow-sm">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M8 9l3 3-3 3M13 15h3M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                    API dành cho đối tác
                </div>
                <h1 class="mt-3 max-w-2xl text-2xl font-black tracking-tight text-slate-950 sm:text-3xl sm:leading-tight">Đưa tính năng tra cứu phạt nguội vào hệ thống của bạn</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Kết nối một lần qua REST API để website, ứng dụng hoặc phần mềm nội bộ có thể tra cứu theo biển số. Không mua gói cố định, không phí khởi tạo và chỉ thanh toán theo request thành công.</p>

                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('dashboard', ['any' => 'api']) }}" class="site-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-sky-700 px-4 text-xs font-extrabold text-white shadow-lg shadow-sky-900/15 transition hover:bg-sky-800">
                        Xem tài liệu và thuê API
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </a>
                    <a href="{{ route('traffic-fines.pricing') }}" class="site-focus inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-xs font-extrabold text-slate-800 transition hover:border-sky-300 hover:text-sky-700">Xem bảng giá</a>
                </div>
                <p class="mt-3 flex items-center gap-2 text-xs leading-5 text-slate-500">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-emerald-600"><path d="M20 6L9 17l-5-5" /></svg>
                    Bạn sẽ được yêu cầu đăng nhập trước khi xem tài liệu kỹ thuật.
                </p>
            </div>

            <aside class="rounded-xl border border-sky-200 bg-white p-4 shadow-[0_18px_48px_-30px_rgba(3,105,161,0.4)] sm:p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold text-sky-700">Chi phí API hiện tại</p>
                        <p class="mt-1 text-2xl font-black tracking-tight text-slate-950">{{ number_format($apiRequestPrice, 0, ',', '.') }}đ</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">mỗi request thành công</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M16 14h2" /></svg>
                    </span>
                </div>
                <div class="my-4 h-px bg-slate-200"></div>
                <ul class="grid gap-3 text-xs leading-5 text-slate-600">
                    <li class="flex gap-3"><span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">✓</span><span>Không phí thuê bao tháng hoặc phí tích hợp ban đầu.</span></li>
                    <li class="flex gap-3"><span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">✓</span><span>Request lỗi xác thực, sai dữ liệu hoặc lỗi nguồn không bị tính phí.</span></li>
                    <li class="flex gap-3"><span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">✓</span><span>Có dashboard theo dõi lượt dùng, chi phí và lịch sử request.</span></li>
                </ul>
            </aside>
        </div>
    </section>

    <section class="site-container site-section">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-700">API là gì?</p>
            <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Một kết nối dành cho nhiều sản phẩm</h2>
            <p class="mt-2.5 text-sm leading-6 text-slate-600">API giúp hệ thống của đối tác gửi biển số và loại phương tiện tới máy chủ của chúng tôi, sau đó nhận về dữ liệu đã chuẩn hóa để tiếp tục hiển thị hoặc xử lý trong quy trình riêng.</p>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
            @foreach ([
                ['Website và ứng dụng', 'Thêm công cụ tra cứu vào sản phẩm đang vận hành mà không phải tự xây nguồn dữ liệu.', 'M4 6h16M4 10h16M9 20V10m6 10V10M6 4h12a2 2 0 012 2v14H4V6a2 2 0 012-2z'],
                ['Phần mềm quản lý xe', 'Tự động kiểm tra phương tiện trong đội xe, đại lý, garage hoặc doanh nghiệp vận tải.', 'M3 13l2-5h14l2 5M5 13v6m14-6v6M7 19h10M7 8l2-4h6l2 4'],
                ['Quy trình tự động', 'Dùng dữ liệu tra cứu trong CRM, cảnh báo nội bộ, báo cáo hoặc tác vụ định kỳ.', 'M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6l2.1 2.1m0-12.8l-2.1 2.1m-8.6 8.6l-2.1 2.1M12 9a3 3 0 100 6 3 3 0 000-6z'],
            ] as [$title, $description, $iconPath])
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="{{ $iconPath }}" /></svg>
                    </span>
                    <h3 class="mt-3 text-base font-bold text-slate-950">{{ $title }}</h3>
                    <p class="mt-1.5 text-xs leading-5 text-slate-600">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-50">
        <div class="site-container site-section">
            <div class="grid gap-7 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-700">Cách bắt đầu</p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Từ đăng ký đến request đầu tiên</h2>
                    <p class="mt-2.5 text-sm leading-6 text-slate-600">Tài liệu trong dashboard có sẵn công cụ tạo API key, ví dụ cURL, mô tả tham số, response và nguyên tắc bảo mật khi tích hợp.</p>
                </div>

                <ol class="grid gap-3">
                    @foreach ([
                        ['Đăng nhập tài khoản', 'Sử dụng tài khoản đối tác để truy cập dashboard và tài liệu kỹ thuật.'],
                        ['Tạo API key và secret', 'Lưu secret ở backend của bạn và giới hạn IP được phép gọi API.'],
                        ['Nạp số dư và gọi API', 'Gửi request GET; hệ thống chỉ trừ ví khi request tra cứu thành công.'],
                        ['Theo dõi trên dashboard', 'Xem biểu đồ, số lượt gọi, chi phí, trạng thái và lịch sử request.'],
                    ] as [$title, $description])
                        <li class="grid grid-cols-[36px_1fr] gap-3 rounded-lg border border-slate-200 bg-white p-4">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-950 text-xs font-black text-white">{{ $loop->iteration }}</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-950">{{ $title }}</h3>
                                <p class="mt-1 text-xs leading-5 text-slate-600">{{ $description }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    <section class="site-container site-section">
        <div class="overflow-hidden rounded-xl bg-slate-950 px-5 py-6 text-white sm:px-7 lg:flex lg:items-center lg:justify-between lg:gap-7 lg:px-8">
            <div>
                <p class="text-sm font-bold text-sky-300">Sẵn sàng tích hợp?</p>
                <h2 class="mt-1.5 text-2xl font-black tracking-tight">Xem tài liệu kỹ thuật và tạo API key</h2>
                <p class="mt-2 max-w-2xl text-xs leading-6 text-slate-300">Đăng nhập để xem endpoint, tạo credential, cấu hình whitelist và theo dõi toàn bộ lượt tra cứu.</p>
            </div>
            <a href="{{ route('dashboard', ['any' => 'api']) }}" class="site-focus mt-4 inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-white px-5 text-xs font-extrabold text-slate-950 transition hover:bg-sky-50 lg:mt-0">
                Xem chi tiết API
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
            </a>
        </div>
    </section>
@endsection
