<script setup lang="ts">
import { KeyRound, LogOut, ShieldAlert } from 'lucide-vue-next';

const props = defineProps<{
    form: {
        current_password: string;
        new_password: string;
        new_password_confirmation: string;
    };
    errors: Partial<Record<'current_password' | 'password' | 'password_confirmation', string>>;
    saving: boolean;
    loggingOutDevices: boolean;
}>();

const emit = defineEmits<{
    submit: [];
    'logout-all-devices': [];
    'update:current-password': [value: string];
    'update:new-password': [value: string];
    'update:new-password-confirmation': [value: string];
}>();
</script>

<template>
    <div class="grid gap-3 xl:grid-cols-[minmax(0,1.1fr)_320px]">
        <form class="space-y-4 rounded-[10px] border border-slate-200 bg-white p-4" @submit.prevent="emit('submit')">
            <div class="flex items-start gap-3 rounded-[10px] border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                <ShieldAlert class="mt-0.5 h-4 w-4 shrink-0" />
                <div>
                    <p class="text-sm font-bold">Mật khẩu nên đủ mạnh</p>
                    <p class="mt-1 text-sm leading-6 text-amber-800">
                        Dùng tối thiểu 8 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt để giảm rủi ro bị dò quét.
                    </p>
                </div>
            </div>

            <label class="block space-y-1.5">
                <span class="text-xs font-semibold text-slate-600">Mật khẩu hiện tại</span>
                <input
                    :value="props.form.current_password"
                    type="password"
                    class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    placeholder="Nhập mật khẩu hiện tại"
                    @input="emit('update:current-password', ($event.target as HTMLInputElement).value)"
                />
                <p v-if="props.errors.current_password" class="text-xs text-rose-600">{{ props.errors.current_password }}</p>
            </label>

            <label class="block space-y-1.5">
                <span class="text-xs font-semibold text-slate-600">Mật khẩu mới</span>
                <input
                    :value="props.form.new_password"
                    type="password"
                    class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    placeholder="Nhập mật khẩu mới"
                    @input="emit('update:new-password', ($event.target as HTMLInputElement).value)"
                />
                <p v-if="props.errors.password" class="text-xs text-rose-600">{{ props.errors.password }}</p>
            </label>

            <label class="block space-y-1.5">
                <span class="text-xs font-semibold text-slate-600">Xác nhận mật khẩu mới</span>
                <input
                    :value="props.form.new_password_confirmation"
                    type="password"
                    class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    placeholder="Nhập lại mật khẩu mới"
                    @input="emit('update:new-password-confirmation', ($event.target as HTMLInputElement).value)"
                />
                <p v-if="props.errors.password_confirmation" class="text-xs text-rose-600">{{ props.errors.password_confirmation }}</p>
            </label>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-[10px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="props.saving"
                >
                    <KeyRound class="h-4 w-4" />
                    {{ props.saving ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}
                </button>
            </div>
        </form>

        <section class="space-y-3">
            <div class="rounded-[10px] border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-bold text-slate-900">Phiên và thiết bị</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">
                    Nếu nghi ngờ tài khoản đang mở ở thiết bị khác, dùng thao tác dưới đây để yêu cầu đăng xuất tất cả phiên còn lại.
                </p>

                <div class="mt-4 rounded-[10px] border border-blue-100 bg-blue-50/60 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Logout all devices</p>
                    <p class="mt-1 text-sm text-slate-600">Thao tác này phù hợp sau khi đổi mật khẩu hoặc phát hiện đăng nhập bất thường.</p>

                    <button
                        type="button"
                        class="mt-3 inline-flex items-center justify-center gap-2 rounded-[10px] border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="props.loggingOutDevices"
                        @click="emit('logout-all-devices')"
                    >
                        <LogOut class="h-4 w-4" />
                        {{ props.loggingOutDevices ? 'Đang xử lý...' : 'Đăng xuất mọi thiết bị' }}
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
