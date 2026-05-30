<script setup lang="ts">
import { Activity, ArrowRight } from 'lucide-vue-next';

type WebhookStatusItem = {
    key: string;
    label: string;
    value: string;
    tone: 'success' | 'warning' | 'danger' | 'neutral';
    note: string;
};

defineProps<{
    items: WebhookStatusItem[];
}>();
</script>

<template>
    <section class="rounded-[10px] border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-[10px] bg-sky-100 text-sky-700">
                    <Activity class="h-5 w-5" />
                </div>
                <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Trạng thái webhook</h2>
            </div>

            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Đang hoạt động
            </span>
        </div>

        <div class="mt-4 space-y-3">
            <article v-for="item in items" :key="item.key">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ item.label }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-950">{{ item.value }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ item.note }}</p>
            </article>
        </div>

        <button
            type="button"
            class="mt-4 inline-flex items-center gap-2 rounded-[10px] border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-100"
        >
            Kiểm tra ngay
            <ArrowRight class="h-4 w-4" />
        </button>
    </section>
</template>
