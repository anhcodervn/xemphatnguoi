<x-mail::message>
# Dữ liệu phạt nguội đã thay đổi

Xe **{{ $vehicleName }}** với biển số **{{ $plate }}** vừa được hệ thống kiểm tra định kỳ.

@if ($previousViolationCount === null)
Hệ thống lần đầu phát hiện **{{ $currentViolationCount }} lỗi** phạt nguội.
@else
Số lỗi đã thay đổi từ **{{ $previousViolationCount }}** thành **{{ $currentViolationCount }}**.
@endif

<x-mail::button :url="$lookupUrl">
Xem kết quả tra cứu
</x-mail::button>

Bạn chỉ nhận email khi số lỗi thay đổi. Nếu kết quả không thay đổi, hệ thống sẽ không gửi lại email.

Trân trọng,<br>
{{ config('app.name') }}
</x-mail::message>
