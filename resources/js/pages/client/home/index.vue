<template>
    <div class="space-y-4 pb-3">
        <User />

        <section class="grid gap-4 xl:grid-cols-[0.98fr_1.42fr]">
            <Card custom-class="rounded-[10px] border border-slate-200/80 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
                <template #body>
                    <div class="p-4">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div>
                                <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-900">Hành động nhanh</h2>
                                <p class="mt-1 text-xs text-slate-500">Các thao tác thường dùng để quản lý dịch vụ.</p>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-2.5">
                            <button
                                v-for="action in quickActions"
                                :key="action.label"
                                type="button"
                                class="group flex items-center justify-between rounded-[8px] border border-slate-200 bg-white px-3 py-2.5 text-left transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-50"
                                @click="goToAction(action)"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <div :class="action.iconClass">
                                        <component :is="action.icon" class="h-4.5 w-4.5" />
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ action.label }}</p>
                                        <p class="line-clamp-1 text-xs text-slate-500">{{ action.description }}</p>
                                    </div>
                                </div>

                                <ChevronRight class="h-4 w-4 text-slate-300 transition group-hover:text-slate-500" />
                            </button>
                        </div>
                    </div>
                </template>
            </Card>

            <Notify />
        </section>

        <ListBank />
    </div>
</template>

<script setup lang="ts">
import Card from '@/components/Card/index.vue';
import { useRouter } from 'vue-router';
import ListBank from './ListBank.vue';
import Notify from './Notify.vue';
import User from './User.vue';
import { Banknote, ChevronRight, CreditCard, FileClock, Gift, Wallet } from 'lucide-vue-next';

const router = useRouter();

const quickActions = [
    {
        label: 'Nạp tiền',
        description: 'Thêm số dư để tiếp tục sử dụng dịch vụ',
        icon: Wallet,
        iconClass: 'flex h-8.5 w-8.5 items-center justify-center rounded-[8px] bg-emerald-100 text-emerald-600',
        to: { name: 'client.recharge' },
    },
    {
        label: 'Mua / Nâng cấp gói',
        description: 'Chọn gói phù hợp với nhu cầu của bạn',
        icon: Gift,
        iconClass: 'flex h-8.5 w-8.5 items-center justify-center rounded-[8px] bg-sky-100 text-sky-600',
        to: { name: 'client.package' },
    },
    {
        label: 'Quản lý thẻ',
        description: 'Thêm, sửa hoặc xóa thẻ thanh toán',
        icon: CreditCard,
        iconClass: 'flex h-8.5 w-8.5 items-center justify-center rounded-[8px] bg-violet-100 text-violet-600',
        to: { name: 'client.bank-manager' },
    },
    {
        label: 'Lịch sử giao dịch',
        description: 'Xem lại các giao dịch đã thực hiện',
        icon: FileClock,
        iconClass: 'flex h-8.5 w-8.5 items-center justify-center rounded-[8px] bg-amber-100 text-amber-600',
        to: { name: 'client.profile', query: { tab: 'wallet-log' } },
    },
    {
        label: 'Gửi góp ý',
        description: 'Chia sẻ ý kiến để chúng tôi cải thiện dịch vụ',
        icon: Banknote,
        iconClass: 'flex h-8.5 w-8.5 items-center justify-center rounded-[8px] bg-rose-100 text-rose-600',
        to: { name: 'client.contact' },
    },
];

const goToAction = async (action: { to: Parameters<typeof router.push>[0] }): Promise<void> => {
    await router.push(action.to);
};
</script>
