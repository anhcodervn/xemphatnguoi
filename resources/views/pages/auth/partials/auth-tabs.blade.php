@php
    $isRegisterTab = request()->routeIs('auth.register');
    $hasGoogleLoginRoute = Route::has('auth.google.redirect');
@endphp

<div class="space-y-6 px-5 py-4 sm:px-6">
    <div class="text-center">
        <h1 class="text-xl font-bold text-slate-950 sm:text-2xl">
            {{ $isRegisterTab ? 'Tạo tài khoản mới' : 'Đăng nhập hệ thống' }}
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            {{ $isRegisterTab
                ? 'Tạo tài khoản để bắt đầu dùng hệ thống nạp tiền tự động và đối soát tập trung.'
                : 'Đăng nhập để quản lý giao dịch, số dư và các cấu hình tích hợp của bạn.' }}
        </p>
    </div>

    <div class="grid grid-cols-2 rounded-2xl border border-slate-200 bg-slate-100 p-1">
        <a
            href="{{ route('auth.login') }}"
            @class([
                'rounded-xl px-4 py-3 text-center text-sm font-semibold transition',
                'bg-white text-slate-950 shadow-sm' => ! $isRegisterTab,
                'text-slate-500 hover:text-slate-700' => $isRegisterTab,
            ])
        >
            Đăng nhập
        </a>
        <a
            href="{{ route('auth.register') }}"
            @class([
                'rounded-xl px-4 py-3 text-center text-sm font-semibold transition',
                'bg-white text-slate-950 shadow-sm' => $isRegisterTab,
                'text-slate-500 hover:text-slate-700' => ! $isRegisterTab,
            ])
        >
            Đăng ký
        </a>
    </div>

    @if ($isRegisterTab)
        <form action="{{ route('auth.register.submit') }}" method="POST" class="grid gap-4" id="formRegister">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="register_name" class="text-sm font-medium text-slate-700">Họ tên</label>
                    <input
                        id="register_name"
                        name="name"
                        type="text"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                        placeholder="Nguyễn Văn A"
                    >
                </div>

                <div class="grid gap-2">
                    <label for="register_username" class="text-sm font-medium text-slate-700">Tên đăng nhập</label>
                    <input
                        id="register_username"
                        name="username"
                        type="text"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                        placeholder="naptientudong_user"
                    >
                </div>
            </div>

            <div class="grid gap-2">
                <label for="register_email" class="text-sm font-medium text-slate-700">Email</label>
                <input
                    id="register_email"
                    name="email"
                    type="email"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                    placeholder="you@example.com"
                >
            </div>

            <div class="grid gap-2">
                <label for="register_phone" class="text-sm font-medium text-slate-700">Số điện thoại</label>
                <input
                    id="register_phone"
                    name="phone"
                    type="text"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                    placeholder="0987654321"
                >
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="register_password" class="text-sm font-medium text-slate-700">Mật khẩu</label>
                    <input
                        id="register_password"
                        name="password"
                        type="password"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                        placeholder="Tối thiểu 8 ký tự"
                    >
                </div>

                <div class="grid gap-2">
                    <label for="register_password_confirmation" class="text-sm font-medium text-slate-700">Nhập lại mật khẩu</label>
                    <input
                        id="register_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                        placeholder="Nhập lại mật khẩu"
                    >
                </div>
            </div>

            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <input type="checkbox" name="accept_terms" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                <span>Tôi đồng ý với điều khoản sử dụng và chính sách bảo mật của hệ thống.</span>
            </label>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
            >
                Tạo tài khoản
            </button>
        </form>
    @else
        <form action="{{ route('auth.login.submit') }}" method="POST" class="grid gap-4" id="formLogin">
            @csrf

            <div class="grid gap-2">
                <label for="login_login" class="text-sm font-medium text-slate-700">Email hoặc tên đăng nhập</label>
                <input
                    id="login_login"
                    name="login"
                    type="text"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                    placeholder="you@example.com hoặc username"
                >
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between gap-3">
                    <label for="login_password" class="text-sm font-medium text-slate-700">Mật khẩu</label>
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-sky-700 transition hover:text-sky-800">Quên mật khẩu?</a>
                </div>
                <input
                    id="login_password"
                    name="password"
                    type="password"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                    placeholder="Nhập mật khẩu"
                >
            </div>

            <div>
                <label class="flex items-center gap-3 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>Ghi nhớ đăng nhập</span>
                </label>
            </div>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
            >
                Đăng nhập
            </button>

            <div class="relative py-1">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-white px-3 text-xs font-medium uppercase tracking-[0.18em] text-slate-400">Hoặc</span>
                </div>
            </div>

            @if ($hasGoogleLoginRoute)
                <a
                    href="{{ route('auth.google.redirect') }}"
                    class="inline-flex items-center justify-center gap-3 rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-950 hover:text-slate-950"
                >
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#4285F4] text-xs font-bold text-white">G</span>
                    <span>Đăng nhập bằng Google</span>
                </a>
            @else
                <button
                    type="button"
                    disabled
                    class="inline-flex items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-500"
                >
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#4285F4] text-xs font-bold text-white">G</span>
                    <span>Đăng nhập bằng Google</span>
                    <span class="rounded-full bg-white px-2 py-1 text-[10px] uppercase tracking-[0.18em] text-slate-400">Sắp có</span>
                </button>
            @endif
        </form>
    @endif
</div>
