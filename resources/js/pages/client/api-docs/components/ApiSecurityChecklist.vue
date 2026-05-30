<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';
import { CheckCircle2, ShieldCheck } from 'lucide-vue-next';

type SecurityItem = {
    key: string;
    title: string;
    description: string;
    icon: LucideIcon;
    iconClass: string;
};

defineProps<{
    items: SecurityItem[];
}>();
</script>

<template>
    <section class="rounded-[10px] border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-[10px] bg-emerald-100 text-emerald-600">
                <ShieldCheck class="h-5 w-5" />
            </div>
            <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Checklist bảo mật</h2>
        </div>

        <div class="mt-4 space-y-2.5">
            <article v-for="item in items" :key="item.key" class="rounded-[10px] border border-slate-200 bg-slate-50/80 p-3">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px]" :class="item.iconClass">
                        <component :is="item.icon" class="h-4 w-4" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-950">{{ item.title }}</p>
                            <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                        </div>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ item.description }}</p>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
