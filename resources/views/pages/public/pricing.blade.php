@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-5 sm:py-10">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-700">Trả theo lượt dùng</p>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Không cần mua gói, dùng bao nhiêu trả bấy nhiêu</h1>
            <p class="mt-2.5 text-sm leading-6 text-slate-600">Tra cứu trên website vẫn miễn phí. API dành cho hệ thống tích hợp được tính theo từng request thành công và trừ trực tiếp từ số dư ví.</p>
        </div>

        <div class="mx-auto mt-7 grid max-w-4xl gap-4 md:grid-cols-2">
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-sky-700">Tra cứu trên website</p>
                <p class="mt-2 text-2xl font-black text-slate-950">Miễn phí</p>
                <ul class="mt-5 grid gap-2 text-xs leading-5 text-slate-600">
                    <li>✓ Tra cứu trực tiếp theo biển số</li>
                    <li>✓ Không cần cài ứng dụng</li>
                    <li>✓ Kết quả được cache trong 24 giờ</li>
                </ul>
                <a href="{{ route('traffic-fines.lookup-page') }}" class="app-focus mt-5 inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-xs font-bold text-slate-800 hover:bg-slate-50">Tra cứu ngay</a>
            </article>
            <article class="rounded-xl border border-sky-700 bg-sky-700 p-5 text-white shadow-lg shadow-sky-900/10">
                <p class="text-sm font-bold text-sky-100">API cho hệ thống</p>
                <p class="mt-2 text-2xl font-black">{{ number_format($apiRequestPrice, 0, ',', '.') }}đ <span class="text-xs font-semibold text-sky-100">/ request thành công</span></p>
                <ul class="mt-5 grid gap-2 text-xs leading-5 text-sky-50">
                    <li>✓ Không phí khởi tạo, không cam kết gói</li>
                    <li>✓ Xác thực riêng bằng API key và secret</li>
                    <li>✓ Có log, chi phí và biểu đồ theo dõi rõ ràng</li>
                </ul>
                <a href="{{ route('dashboard', ['any' => 'api']) }}" class="app-focus mt-5 inline-flex min-h-11 items-center justify-center rounded-lg bg-white px-4 text-xs font-bold text-sky-800 hover:bg-sky-50">Bắt đầu tích hợp</a>
            </article>
        </div>

        <p class="mx-auto mt-5 max-w-3xl text-center text-xs leading-5 text-slate-500">Request lỗi xác thực, sai dữ liệu, không đủ số dư hoặc lỗi nguồn dữ liệu sẽ không bị tính phí.</p>
    </div>
@endsection
