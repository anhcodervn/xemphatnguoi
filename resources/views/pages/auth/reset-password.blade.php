@extends('layouts.auth')

@section('title', 'Đặt lại mật khẩu')
@section('eyebrow', 'Reset Password')

@section('main')
    <div class="space-y-6 px-5 py-5 sm:px-6">
        <div class="text-center">
            <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">
                Tạo mật khẩu mới
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-950 sm:text-[2rem]">Đặt lại mật khẩu</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Nhập email và mật khẩu mới để hoàn tất quá trình khôi phục tài khoản.
            </p>
        </div>

        <form action="{{ route('password.store') }}" method="POST" class="grid gap-4">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            @php
                $resolvedEmail = old('email', $email ?? request()->string('email')->toString());
                $hasResolvedEmail = filled($resolvedEmail);
            @endphp

            <div class="grid gap-2">
                <label for="email" class="text-sm font-medium text-slate-700">Email</label>

                @if ($hasResolvedEmail)
                    <input type="hidden" name="email" value="{{ $resolvedEmail }}">
                    <input
                        id="email"
                        type="email"
                        value="{{ $resolvedEmail }}"
                        class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-500 shadow-sm outline-none"
                        disabled
                    >
                    <p class="text-xs text-slate-500">Email được lấy trực tiếp từ liên kết khôi phục để tránh nhập nhầm tài khoản.</p>
                @else
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        @class([
                            'w-full rounded-2xl border px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:ring-2 focus:ring-sky-100',
                            'border-slate-300 focus:border-sky-500' => ! $errors->has('email'),
                            'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('email'),
                        ])
                        placeholder="you@example.com"
                        autocomplete="email"
                    >
                @endif

                @error('email')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="password" class="text-sm font-medium text-slate-700">Mật khẩu mới</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        @class([
                            'w-full rounded-2xl border px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:ring-2 focus:ring-sky-100',
                            'border-slate-300 focus:border-sky-500' => ! $errors->has('password'),
                            'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('password'),
                        ])
                        placeholder="Tối thiểu 8 ký tự"
                        autocomplete="new-password"
                    >
                    @error('password')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <label for="password_confirmation" class="text-sm font-medium text-slate-700">Nhập lại mật khẩu</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                        placeholder="Nhập lại mật khẩu"
                        autocomplete="new-password"
                    >
                </div>
            </div>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
            >
                Cập nhật mật khẩu
            </button>
        </form>
    </div>
@endsection
