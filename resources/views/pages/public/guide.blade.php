@extends('layouts.public')

@section('content')
    <article class="mx-auto max-w-2xl px-4 py-8 sm:px-5 sm:py-10">
        <x-breadcrumb :items="[['label' => 'Trang chủ', 'url' => route('traffic-fines.home')], ['label' => 'Hướng dẫn']]" />
        <h1 class="mt-4 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Hướng dẫn tra cứu phạt nguội</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Ba bước ngắn để kiểm tra biển số và đọc kết quả đúng cách.</p>

        <div class="mt-6 grid gap-5">
            @foreach ([['Chuẩn bị biển số', 'Đối chiếu biển số trên đăng ký xe. Bạn có thể nhập 30A-123.45, 30A 12345 hoặc 30A12345.'], ['Chọn đúng loại phương tiện', 'Chọn ô tô, xe máy hoặc xe máy điện. Nguồn dữ liệu thực tế có thể chỉ hỗ trợ một số loại xe tại từng thời điểm.'], ['Đọc và đối chiếu kết quả', 'Kiểm tra số lỗi, thời gian cập nhật, hành vi, địa điểm và đơn vị xử lý. Nếu cần làm thủ tục, hãy xác nhận lại với cơ quan có thẩm quyền.']] as $step)
                <section class="grid grid-cols-[36px_1fr] gap-3 border-t border-slate-200 pt-4 first:border-t-0 first:pt-0">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-950 text-sm font-black text-white">{{ $loop->iteration }}</span>
                    <div><h2 class="text-base font-bold text-slate-950">{{ $step[0] }}</h2><p class="mt-1.5 text-sm leading-6 text-slate-600">{{ $step[1] }}</p></div>
                </section>
            @endforeach
        </div>

        <div class="mt-7 rounded-lg bg-sky-50 p-4">
            <h2 class="text-base font-bold text-sky-950">Lưu ý quan trọng</h2>
            <p class="mt-1.5 text-xs leading-6 text-sky-900">Thông báo “không phát hiện vi phạm trong dữ liệu hiện có” không có nghĩa phương tiện chắc chắn không vi phạm. Dữ liệu có thể có độ trễ.</p>
        </div>

        <a href="{{ route('traffic-fines.lookup-page') }}" class="app-focus mt-5 inline-flex min-h-11 items-center justify-center rounded-lg bg-sky-700 px-5 text-xs font-extrabold text-white hover:bg-sky-800">Tra cứu ngay</a>
    </article>
@endsection
