<script setup lang="ts">
import type { ProxyDashboard } from '@/services/client-proxy.service';
import { ArrowRight, CheckCircle2, CreditCard, ReceiptText } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';

defineProps<{
    activities: ProxyDashboard['recent_activities'];
}>();

const money = (value: string): string => `${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Math.abs(Number(value || 0)))}đ`;

const relativeTime = (value: string | null): string => {
    if (!value) return '--';
    const timestamp = new Date(value).getTime();
    if (Number.isNaN(timestamp)) return '--';
    const hours = Math.max(0, Math.floor((Date.now() - timestamp) / 3_600_000));
    if (hours < 1) return 'Vừa xong';
    if (hours < 24) return `${hours} giờ trước`;
    return `${Math.floor(hours / 24)} ngày trước`;
};
</script>

<template>
    <article class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_16px_40px_-32px_rgba(15,23,42,0.5)]">
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="font-extrabold text-slate-950">Hoạt động gần đây</h2>
                <p class="mt-1 text-xs text-slate-500">Đơn hàng và giao dịch ví mới nhất.</p>
            </div>
            <RouterLink to="/profile?tab=wallet-log" class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 sm:text-sm">
                Xem tất cả <ArrowRight class="h-4 w-4" />
            </RouterLink>
        </header>

        <div v-if="activities.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">Chưa có hoạt động nào gần đây.</div>

        <div v-else class="divide-y divide-slate-100">
            <RouterLink
                v-for="activity in activities"
                :key="activity.id"
                :to="activity.redirect_url"
                class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50"
            >
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                    :class="activity.type === 'order' ? 'bg-violet-50 text-violet-600' : 'bg-emerald-50 text-emerald-600'"
                >
                    <ReceiptText v-if="activity.type === 'order'" class="h-5 w-5" />
                    <CreditCard v-else-if="activity.type === 'wallet_credit'" class="h-5 w-5" />
                    <CheckCircle2 v-else class="h-5 w-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="line-clamp-1 text-sm font-bold text-slate-900">{{ activity.title }}</span>
                    <span class="mt-1 block truncate text-xs text-slate-500">{{ activity.description }}</span>
                </span>
                <span class="shrink-0 text-right">
                    <span class="block text-xs font-bold" :class="activity.type === 'wallet_credit' ? 'text-emerald-700' : 'text-slate-700'">
                        {{ activity.type === 'wallet_credit' ? '+' : '' }}{{ money(activity.amount) }}
                    </span>
                    <span class="mt-1 block text-[11px] text-slate-400">{{ relativeTime(activity.occurred_at) }}</span>
                </span>
            </RouterLink>
        </div>
    </article>
</template>
