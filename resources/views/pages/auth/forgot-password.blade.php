@extends('layouts.auth')

@section('title', 'Quên mật khẩu')
@section('eyebrow', 'Password Reset')

@section('main')
    <div class="space-y-6 px-5 py-5 sm:px-6">
        <div class="text-center">
            <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">
                Khôi phục truy cập
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-950 sm:text-[2rem]">Quên mật khẩu?</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu. Nếu email tồn tại trong hệ thống, chúng tôi sẽ gửi hướng dẫn cho bạn.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <p class="font-semibold text-slate-800">Lưu ý</p>
            <p class="mt-1 leading-6">
                Liên kết đặt lại mật khẩu sẽ được gửi đến email của bạn. Hãy kiểm tra cả hộp thư spam nếu chưa thấy thư đến.
            </p>
        </div>

        <form action="{{ route('auth.forgot-password.submit') }}" method="POST" class="grid gap-4" id="forgotPasswordForm">
            @csrf

            <div class="grid gap-2">
                <label for="email" class="text-sm font-medium text-slate-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                    placeholder="you@example.com"
                    autocomplete="email"
                >
            </div>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
            >
                Gửi liên kết đặt lại mật khẩu
            </button>
        </form>

        <div class="flex items-center justify-center gap-2 text-sm text-slate-500">
            <span>Đã nhớ mật khẩu?</span>
            <a href="{{ route('auth.login') }}" class="font-medium text-sky-700 transition hover:text-sky-800">
                Quay lại đăng nhập
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('forgotPasswordForm');

            if (!form) {
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            const defaultButtonText = submitButton ? submitButton.innerHTML : '';

            const setLoadingState = (isLoading) => {
                if (!submitButton) {
                    return;
                }

                submitButton.disabled = isLoading;
                submitButton.classList.toggle('opacity-60', isLoading);
                submitButton.classList.toggle('cursor-not-allowed', isLoading);
                submitButton.innerHTML = isLoading ? 'Đang gửi yêu cầu...' : defaultButtonText;
            };

            const clearFieldErrors = () => {
                form.querySelectorAll('.forgot-password-field-error').forEach((element) => element.remove());

                form.querySelectorAll('input').forEach((input) => {
                    input.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-100');
                    input.classList.add('border-slate-300', 'focus:border-sky-500', 'focus:ring-sky-100');
                });
            };

            const showFieldErrors = (errors = {}) => {
                Object.entries(errors).forEach(([field, messages]) => {
                    const input = form.querySelector(`[name="${field}"]`);

                    if (!input || !Array.isArray(messages) || messages.length === 0) {
                        return;
                    }

                    input.classList.remove('border-slate-300', 'focus:border-sky-500', 'focus:ring-sky-100');
                    input.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-100');

                    const errorElement = document.createElement('p');
                    errorElement.className = 'forgot-password-field-error text-sm text-red-600';
                    errorElement.textContent = messages[0];

                    input.parentElement.appendChild(errorElement);
                });
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                clearFieldErrors();
                setLoadingState(true);

                try {
                    const response = await axios.post(form.action, $(form).serialize(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    await Swal.fire({
                        icon: 'success',
                        title: 'Đã gửi yêu cầu',
                        text: response.data?.message ?? 'Nếu email tồn tại trong hệ thống, liên kết đặt lại mật khẩu đã được gửi.',
                        confirmButtonText: 'Đóng',
                    });

                    form.reset();
                } catch (error) {
                    const response = error.response ?? null;
                    const errors = response?.data?.errors ?? {};
                    const message = response?.data?.message ?? 'Không thể gửi yêu cầu đặt lại mật khẩu.';

                    showFieldErrors(errors);

                    Swal.fire({
                        icon: 'error',
                        title: 'Yêu cầu thất bại',
                        text: message,
                        confirmButtonText: 'Đóng',
                    });
                } finally {
                    setLoadingState(false);
                }
            });
        })();
    </script>
@endpush
