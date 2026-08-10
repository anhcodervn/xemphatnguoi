<script setup lang="ts">
import { CircleUserRound, History, KeyRound, WalletCards } from 'lucide-vue-next';

type TabKey = 'profile' | 'password' | 'user-log' | 'wallet-log';

defineProps<{
    modelValue: TabKey;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: TabKey];
}>();

const tabs: Array<{ key: TabKey; label: string; description: string; icon: unknown }> = [
    { key: 'profile', label: 'Thông tin user', description: 'Hồ sơ và trạng thái tài khoản', icon: CircleUserRound },
    { key: 'password', label: 'Đổi mật khẩu', description: 'Bảo mật và phiên đăng nhập', icon: KeyRound },
    { key: 'user-log', label: 'Lịch sử người dùng', description: 'Nhật ký thao tác theo tài khoản', icon: History },
    { key: 'wallet-log', label: 'Lịch sử dòng tiền', description: 'Biến động số dư và giao dịch', icon: WalletCards },
];
</script>

<template>
    <div class="border-b border-slate-200/80 bg-slate-50/70 px-3 py-3">
        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="flex items-center gap-3 rounded-[10px] border px-3 py-2.5 text-left transition"
                :class="
                    modelValue === tab.key
                        ? 'border-blue-600 bg-gradient-to-r from-blue-700 to-blue-600 text-white shadow-[0_12px_28px_-18px_rgba(37,99,235,0.65)]'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:bg-blue-50/60 hover:text-blue-800'
                "
                @click="emit('update:modelValue', tab.key)"
            >
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px]"
                    :class="modelValue === tab.key ? 'bg-white/15 text-white' : 'bg-blue-100 text-blue-700'"
                >
                    <component :is="tab.icon" class="h-4.5 w-4.5" />
                </span>

                <span class="min-w-0">
                    <span class="block text-sm font-bold">{{ tab.label }}</span>
                    <span class="mt-0.5 block text-xs leading-5" :class="modelValue === tab.key ? 'text-white/70' : 'text-slate-400'">
                        {{ tab.description }}
                    </span>
                </span>
            </button>
        </div>
    </div>
</template>
