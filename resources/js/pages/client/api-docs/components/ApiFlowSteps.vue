<script setup lang="ts">
import { Workflow } from 'lucide-vue-next';

type FlowStep = {
    order: string;
    title: string;
    description: string;
};

defineProps<{
    steps: FlowStep[];
}>();
</script>

<template>
    <section class="rounded-[10px] border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-[10px] bg-indigo-100 text-indigo-600">
                <Workflow class="h-5 w-5" />
            </div>
            <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Luồng gọi đề xuất</h2>
        </div>

        <div class="mt-5 space-y-4">
            <article v-for="step in steps" :key="step.order" class="flex items-start gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                    {{ step.order }}
                </span>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-950">{{ step.title }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ step.description }}</p>
                </div>
            </article>
        </div>
    </section>
</template>
