@php
    $faqs = [
        ['question' => 'Tra cứu phạt nguội có mất phí không?', 'answer' => 'Biểu mẫu tra cứu công khai trên trang này không yêu cầu đăng nhập. Các dịch vụ mở rộng cho lịch sử, API hoặc quản lý nhiều xe có thể áp dụng chính sách riêng khi được công bố.'],
        ['question' => 'Bao lâu dữ liệu phạt nguội được cập nhật?', 'answer' => 'Không có một khoảng thời gian cố định cho mọi trường hợp. Thời điểm dữ liệu xuất hiện phụ thuộc thời gian ghi nhận, quy trình xác minh, khả năng đồng bộ và đơn vị xử lý.'],
        ['question' => 'Xe máy có tra cứu phạt nguội được không?', 'answer' => 'Có thể chọn xe máy hoặc xe máy điện trên biểu mẫu. Phạm vi kết quả phụ thuộc dữ liệu mà nguồn tra cứu cung cấp tại từng thời điểm.'],
        ['question' => 'Không tìm thấy vi phạm có nghĩa là không bị phạt không?', 'answer' => 'Không. Thông báo chưa ghi nhận chỉ phản ánh dữ liệu hiện có tại thời điểm tra cứu. Dữ liệu có thể được bổ sung sau quá trình xác minh và cập nhật.'],
        ['question' => 'Nhập biển số xe như thế nào cho đúng?', 'answer' => 'Bạn có thể nhập có hoặc không có dấu gạch nối, dấu chấm và khoảng trắng, ví dụ 30A-123.45 hoặc 30A12345.'],
        ['question' => 'Tôi cần làm gì khi phát hiện có vi phạm?', 'answer' => 'Hãy đọc đầy đủ biển số, thời gian, địa điểm, hành vi, đơn vị phát hiện và trạng thái xử lý; sau đó đối chiếu với nguồn hoặc cơ quan có thẩm quyền được nêu trong kết quả.'],
    ];
    $structuredData = [[
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqs)->map(fn ($item) => [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
        ])->all(),
    ]];
@endphp
@extends('layouts.public')

@section('content')
    <x-home.hero :vehicle-types="$vehicleTypes" :lookup-mode="$lookupMode" :turnstile="$turnstile" />

    <section id="ket-qua" class="scroll-mt-24 border-b border-slate-200 bg-white" aria-labelledby="lookup-result-title">
        <div class="site-container py-7 sm:py-8">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <p class="site-eyebrow">Thông tin theo biển số</p>
                    <h2 id="lookup-result-title" class="site-section-title">Kết quả tra cứu</h2>
                </div>
                <a href="{{ route('traffic-fines.knowledge.guide') }}" class="site-focus hidden min-h-11 items-center text-sm font-bold text-brand hover:text-sky-800 sm:inline-flex">Cách đọc kết quả <span aria-hidden="true">→</span></a>
            </div>
            <x-lookup-result />
            <div class="mt-3" data-lookup-result-ad><x-ad-slot name="lookup_result_bottom" /></div>
        </div>
    </section>

    <x-home.trust />
    <x-home.seo-content />
    <x-home.vehicle-links />
    <x-home.common-violations />
    <x-home.how-it-works />
    <x-home.faq :faqs="$faqs" />
    <x-home.latest-posts :posts="$latestPosts" />
    <x-home.cta />
@endsection

@push('scripts')
    @vite('resources/js/public-lookup.ts')
@endpush
