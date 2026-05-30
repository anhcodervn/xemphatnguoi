<script setup lang="ts">
import { Check, Copy } from 'lucide-vue-next';

type QuickAccessItem = {
    key: string;
    label: string;
    value: string;
    note: string;
    copyValue?: string;
};

defineProps<{
    items: QuickAccessItem[];
    copiedKey: string | null;
}>();

defineEmits<{
    copy: [value: string, key: string];
}>();
</script>

<template>
    <section class="rounded-[10px] border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2">
            <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Thông tin truy cập</h2>
            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-500">i</span>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <article v-for="item in items" :key="item.key" class="rounded-[10px] border border-slate-200 bg-slate-50/70 p-3.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ item.label }}</p>
                        <p class="mt-1 break-all text-[1rem] font-semibold tracking-[-0.02em] text-slate-950">{{ item.value }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ item.note }}</p>
                    </div>

                    <button
                        v-if="item.copyValue"
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-[8px] border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                        @click="$emit('copy', item.copyValue, `quick-${item.key}`)"
                    >
                        <Check v-if="copiedKey === `quick-${item.key}`" class="h-3.5 w-3.5 text-emerald-600" />
                        <Copy v-else class="h-3.5 w-3.5" />
                    </button>
                </div>
            </article>
        </div>
    </section>
</template>
