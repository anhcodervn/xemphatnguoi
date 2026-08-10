<script setup lang="ts">
import { BookOpenText, History, Layers3, Wallet } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';

const actions = [
    { label: 'Mua proxy', to: '/services', icon: Layers3, className: 'bg-blue-50 text-blue-700' },
    { label: 'Quản lý proxy', to: '/proxy-orders', icon: History, className: 'bg-emerald-50 text-emerald-700' },
    { label: 'Nạp tiền', to: '/wallet', icon: Wallet, className: 'bg-orange-50 text-orange-700' },
    { label: 'Tài liệu API', to: '/api-docs', icon: BookOpenText, className: 'bg-violet-50 text-violet-700' },
];
</script>

<template>
    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_16px_40px_-32px_rgba(15,23,42,0.5)]">
        <h2 class="font-extrabold text-slate-950">Truy cập nhanh</h2>
        <div class="mt-4 grid grid-cols-2 gap-3">
            <RouterLink
                v-for="action in actions"
                :key="action.to"
                :to="action.to"
                class="flex min-h-24 flex-col items-center justify-center gap-2 rounded-xl border border-slate-100 px-3 py-4 text-center text-sm font-bold transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-sm"
            >
                <span class="flex h-10 w-10 items-center justify-center rounded-xl" :class="action.className">
                    <component :is="action.icon" class="h-5 w-5" />
                </span>
                <span class="text-slate-800">{{ action.label }}</span>
            </RouterLink>
        </div>
    </article>
</template>
