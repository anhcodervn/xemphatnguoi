<script setup lang="ts">
import type { ProxyDashboard } from '@/services/client-proxy.service';
import { ArrowRight, Clock3 } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';

defineProps<{
    proxies: ProxyDashboard['expiring_proxies'];
}>();

const dateOnly = (value: string | null): string => {
    if (!value) return '--';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '--' : date.toLocaleDateString('vi-VN');
};

const remainingDays = (value: string | null): number | null => {
    if (!value) return null;
    const timestamp = new Date(value).getTime();
    return Number.isNaN(timestamp) ? null : Math.ceil((timestamp - Date.now()) / 86_400_000);
};

const remainingLabel = (value: string | null): string => {
    const days = remainingDays(value);
    if (days === null) return '--';
    if (days <= 0) return 'Đã hết hạn';
    if (days === 1) return 'Còn 1 ngày';
    return `Còn ${days} ngày`;
};

const remainingClass = (value: string | null): string => {
    const days = remainingDays(value);
    if (days === null || days <= 0) return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    if (days <= 1) return 'bg-orange-50 text-orange-700 ring-orange-600/20';
    return 'bg-amber-50 text-amber-700 ring-amber-600/20';
};
</script>

<template>
    <article class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_16px_40px_-32px_rgba(15,23,42,0.5)]">
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="font-extrabold text-slate-950">Proxy sắp hết hạn</h2>
                <p class="mt-1 text-xs text-slate-500">Các proxy cần xử lý trong 3 ngày tới.</p>
            </div>
            <RouterLink to="/proxy-orders" class="inline-flex shrink-0 items-center gap-1 text-xs font-bold text-blue-700 sm:text-sm">
                Xem tất cả <ArrowRight class="h-4 w-4" />
            </RouterLink>
        </header>

        <div v-if="proxies.length === 0" class="flex flex-col items-center px-5 py-12 text-center">
            <span class="rounded-full bg-emerald-50 p-3 text-emerald-600"><Clock3 class="h-6 w-6" /></span>
            <p class="mt-3 font-bold text-slate-900">Không có proxy sắp hết hạn</p>
            <p class="mt-1 text-sm text-slate-500">Proxy của bạn vẫn còn thời hạn an toàn.</p>
        </div>

        <div v-else>
            <div class="divide-y divide-slate-100 md:hidden">
                <div v-for="proxy in proxies" :key="proxy.id" class="grid gap-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm font-bold text-slate-900">{{ proxy.endpoint }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ proxy.product?.name || `Proxy #${proxy.id}` }}</p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset"
                            :class="remainingClass(proxy.expires_at)"
                        >
                            {{ remainingLabel(proxy.expires_at) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-xs text-slate-500">
                        <span>{{ proxy.protocol.toUpperCase() }} · {{ dateOnly(proxy.expires_at) }}</span>
                        <RouterLink to="/proxy-orders" class="font-bold text-blue-700">Gia hạn</RouterLink>
                    </div>
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">IP:Port</th>
                            <th class="px-4 py-3">Loại proxy</th>
                            <th class="px-4 py-3">Ngày hết hạn</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="proxy in proxies" :key="proxy.id" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4 font-mono text-xs font-bold text-slate-900">{{ proxy.endpoint }}</td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-800">{{ proxy.product?.name || `Proxy #${proxy.id}` }}</p>
                                <p class="mt-1 text-xs uppercase text-slate-400">{{ proxy.protocol }}</p>
                            </td>
                            <td class="px-4 py-4 text-xs font-semibold text-slate-600">{{ dateOnly(proxy.expires_at) }}</td>
                            <td class="px-4 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset"
                                    :class="remainingClass(proxy.expires_at)"
                                >
                                    {{ remainingLabel(proxy.expires_at) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <RouterLink to="/proxy-orders" class="font-bold text-blue-700 hover:text-blue-800">Gia hạn</RouterLink>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</template>
