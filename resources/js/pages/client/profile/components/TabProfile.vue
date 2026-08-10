<script setup lang="ts">
import type { ClientProfileType } from '@/types/client-profile.type';
import { Camera, MailCheck, ShieldCheck, UserRound } from 'lucide-vue-next';

const props = defineProps<{
    form: {
        avatar: string;
        full_name: string;
        email: string;
        phone: string;
        username: string;
    };
    user: ClientProfileType | null;
    accountMeta: Array<{ label: string; value: string }>;
    errors: Partial<Record<'avatar' | 'full_name' | 'email' | 'phone' | 'username', string>>;
    saving: boolean;
}>();

const emit = defineEmits<{
    submit: [];
    'update:avatar': [value: string];
    'update:full-name': [value: string];
    'update:email': [value: string];
    'update:phone': [value: string];
    'update:username': [value: string];
}>();
</script>

<template>
    <div class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_340px]">
        <form class="space-y-4 rounded-[10px] border border-slate-200 bg-white p-4" @submit.prevent="emit('submit')">
            <div class="flex flex-col gap-3 rounded-[10px] border border-blue-100 bg-blue-50/60 p-3 md:flex-row md:items-center">
                <div class="relative">
                    <div
                        class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-[20px] border border-blue-100 bg-gradient-to-br from-blue-100 to-cyan-50"
                    >
                        <img v-if="props.form.avatar" :src="props.form.avatar" alt="Avatar" class="h-full w-full object-cover" />
                        <UserRound v-else class="h-10 w-10 text-slate-500" />
                    </div>

                    <span
                        class="absolute -bottom-1.5 -right-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg shadow-blue-200"
                    >
                        <Camera class="h-3.5 w-3.5" />
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Hồ sơ hiển thị</p>
                    <h2 class="mt-1 text-xl font-black tracking-[-0.04em] text-slate-900">
                        {{ props.user?.full_name || props.user?.username || 'Người dùng' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">{{ props.user?.email || 'Chưa có email' }}</p>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">Avatar URL</span>
                    <input
                        :value="props.form.avatar"
                        type="text"
                        class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                        placeholder="https://example.com/avatar.jpg"
                        @input="emit('update:avatar', ($event.target as HTMLInputElement).value)"
                    />
                    <p v-if="props.errors.avatar" class="text-xs text-rose-600">{{ props.errors.avatar }}</p>
                </label>

                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">Họ tên</span>
                    <input
                        :value="props.form.full_name"
                        type="text"
                        class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                        placeholder="Nhập họ tên"
                        @input="emit('update:full-name', ($event.target as HTMLInputElement).value)"
                    />
                    <p v-if="props.errors.full_name" class="text-xs text-rose-600">{{ props.errors.full_name }}</p>
                </label>

                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">Email</span>
                    <input
                        :value="props.form.email"
                        type="email"
                        class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                        placeholder="email@example.com"
                        @input="emit('update:email', ($event.target as HTMLInputElement).value)"
                    />
                    <p v-if="props.errors.email" class="text-xs text-rose-600">{{ props.errors.email }}</p>
                </label>

                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">Số điện thoại</span>
                    <input
                        :value="props.form.phone"
                        type="text"
                        class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                        placeholder="09xxxxxxxx"
                        @input="emit('update:phone', ($event.target as HTMLInputElement).value)"
                    />
                    <p v-if="props.errors.phone" class="text-xs text-rose-600">{{ props.errors.phone }}</p>
                </label>

                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">Username</span>
                    <input
                        :value="props.form.username"
                        type="text"
                        class="w-full rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none"
                        placeholder="username"
                        @input="emit('update:username', ($event.target as HTMLInputElement).value)"
                    />
                    <p v-if="props.errors.username" class="text-xs text-rose-600">{{ props.errors.username }}</p>
                </label>

                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">User ID</span>
                    <input
                        :value="props.user?.id ? `#${props.user.id}` : ''"
                        type="text"
                        disabled
                        class="w-full rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-500 outline-none"
                    />
                </label>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-[10px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="props.saving"
                >
                    {{ props.saving ? 'Đang lưu...' : 'Lưu thay đổi' }}
                </button>
            </div>
        </form>

        <section class="space-y-3">
            <div class="rounded-[10px] border border-slate-200 bg-white p-4">
                <div class="flex items-center gap-2">
                    <MailCheck class="h-4 w-4 text-blue-600" />
                    <h3 class="text-sm font-bold text-slate-900">Tổng quan xác thực</h3>
                </div>

                <div class="mt-3 space-y-2">
                    <div
                        v-for="meta in props.accountMeta"
                        :key="meta.label"
                        class="flex flex-col gap-1 rounded-[10px] border border-slate-100 bg-slate-50 px-3 py-2.5 sm:flex-row sm:items-start sm:justify-between sm:gap-3"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ meta.label }}</p>
                        <p class="text-right text-sm font-semibold text-slate-800">{{ meta.value }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-[10px] border border-blue-100 bg-gradient-to-br from-blue-50 to-cyan-50 p-4">
                <div class="flex items-center gap-2">
                    <ShieldCheck class="h-4 w-4 text-blue-600" />
                    <h3 class="text-sm font-bold text-slate-900">Khuyến nghị bảo mật</h3>
                </div>

                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li class="rounded-[8px] bg-white/80 px-3 py-2">Xác thực email giúp giảm rủi ro khóa tài khoản khi quên mật khẩu.</li>
                    <li class="rounded-[8px] bg-white/80 px-3 py-2">Bật 2FA khi backend hỗ trợ để bảo vệ đăng nhập từ thiết bị lạ.</li>
                    <li class="rounded-[8px] bg-white/80 px-3 py-2">Theo dõi IP và phiên gần nhất để phát hiện truy cập bất thường.</li>
                </ul>
            </div>
        </section>
    </div>
</template>
