<script setup lang="ts">
import { KeyRound, LogOut, ShieldAlert } from 'lucide-vue-next';

defineProps<{
    form: {
        current_password: string;
        new_password: string;
        new_password_confirmation: string;
    };
    errors: Partial<Record<'current_password' | 'password' | 'password_confirmation', string>>;
    saving: boolean;
    loggingOutDevices: boolean;
}>();

defineEmits<{
    submit: [];
    'logout-all-devices': [];
}>();
</script>

<template>
    <div class="grid gap-3 xl:grid-cols-[minmax(0,1.1fr)_320px]">
        <form class="space-y-4 rounded-[10px] border border-slate-200/80 bg-white p-4" @submit.prevent="$emit('submit')">
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
                    v-model="form.current_password"
                    type="password"
                    class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                    placeholder="Nhập mật khẩu hiện tại"
                />
                <p v-if="errors.current_password" class="text-xs text-rose-600">{{ errors.current_password }}</p>
            </label>

            <label class="block space-y-1.5">
                <span class="text-xs font-semibold text-slate-600">Mật khẩu mới</span>
                <input
                    v-model="form.new_password"
                    type="password"
                    class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                    placeholder="Nhập mật khẩu mới"
                />
                <p v-if="errors.password" class="text-xs text-rose-600">{{ errors.password }}</p>
            </label>

            <label class="block space-y-1.5">
                <span class="text-xs font-semibold text-slate-600">Xác nhận mật khẩu mới</span>
                <input
                    v-model="form.new_password_confirmation"
                    type="password"
                    class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                    placeholder="Nhập lại mật khẩu mới"
                />
                <p v-if="errors.password_confirmation" class="text-xs text-rose-600">{{ errors.password_confirmation }}</p>
            </label>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-[10px] bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="saving"
                >
                    <KeyRound class="h-4 w-4" />
                    {{ saving ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}
                </button>
            </div>
        </form>

        <section class="space-y-3">
            <div class="rounded-[10px] border border-slate-200/80 bg-white p-4">
                <h3 class="text-sm font-bold text-slate-900">Phiên & thiết bị</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">
                    Nếu nghi ngờ tài khoản đang mở ở thiết bị khác, dùng thao tác dưới đây để yêu cầu đăng xuất tất cả phiên còn lại.
                </p>

                <div class="mt-4 rounded-[10px] bg-slate-50/80 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Logout all devices</p>
                    <p class="mt-1 text-sm text-slate-600">Thao tác này phù hợp sau khi đổi mật khẩu hoặc phát hiện đăng nhập bất thường.</p>

                    <button
                        type="button"
                        class="mt-3 inline-flex items-center justify-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="loggingOutDevices"
                        @click="$emit('logout-all-devices')"
                    >
                        <LogOut class="h-4 w-4" />
                        {{ loggingOutDevices ? 'Đang xử lý...' : 'Logout all devices' }}
                    </button>
                </div>
            </div>

            <div class="rounded-[10px] border border-slate-200/80 bg-[linear-gradient(135deg,_#f8fafc_0%,_#eef2ff_100%)] p-4">
                <h3 class="text-sm font-bold text-slate-900">Checklist nhanh</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li class="rounded-[8px] bg-white/80 px-3 py-2">Không dùng lại mật khẩu cũ giữa nhiều dịch vụ.</li>
                    <li class="rounded-[8px] bg-white/80 px-3 py-2">Đổi mật khẩu ngay sau khi chia sẻ tài khoản tạm thời cho người khác.</li>
                    <li class="rounded-[8px] bg-white/80 px-3 py-2">Kết hợp theo dõi tab lịch sử người dùng để phát hiện hành vi lạ.</li>
                </ul>
            </div>
        </section>
    </div>
</template>
