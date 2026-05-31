@extends('layouts.auth')

@section('title', 'Đăng nhập')
@section('eyebrow', 'Login')
@section('badge', 'Đăng nhập trong vài bước')

@section('main')
    @include('pages.auth.partials.auth-tabs')
@endsection

@push('scripts')
    <script>
        (() => {
            const googleErrorMessage = @json(session('auth_google_error'));

            if (googleErrorMessage) {
                Swal.fire({
                    icon: 'error',
                    title: 'Không thể đăng nhập bằng Google',
                    text: googleErrorMessage,
                    confirmButtonText: 'Đóng',
                });
            }

            const form = document.getElementById('formLogin');

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
                submitButton.innerHTML = isLoading ? 'Đang đăng nhập...' : defaultButtonText;
            };

            const clearFieldErrors = () => {
                form.querySelectorAll('.login-field-error').forEach((element) => element.remove());

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
                    errorElement.className = 'login-field-error text-sm text-red-600';
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
                        title: 'Đăng nhập thành công',
                        text: response.data?.message ?? 'Bạn đã đăng nhập thành công.',
                        confirmButtonText: 'Tiếp tục',
                    });

                    window.location.href = response.data?.redirect ?? '{{ url('/') }}';
                } catch (error) {
                    const response = error.response ?? null;
                    const errors = response?.data?.errors ?? {};
                    const message = response?.data?.message ?? 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin.';

                    showFieldErrors(errors);

                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể đăng nhập',
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
