<script setup lang="ts">
import { ArrowUpRight, type LucideIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps<{
    title: string;
    value: string | number;
    action: string;
    to: string;
    icon: LucideIcon;
    tone: 'blue' | 'green' | 'orange' | 'violet';
}>();

const toneClass = computed(() => {
    return {
        blue: 'bg-blue-50 text-blue-600 ring-blue-100',
        green: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        orange: 'bg-orange-50 text-orange-600 ring-orange-100',
        violet: 'bg-violet-50 text-violet-600 ring-violet-100',
    }[props.tone];
});
</script>

<template>
    <article class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_14px_35px_-28px_rgba(15,23,42,0.45)] sm:p-5">
        <div class="flex items-start gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1" :class="toneClass">
                <component :is="icon" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-slate-500 sm:text-sm">{{ title }}</p>
                <p class="mt-1 truncate text-2xl font-black tracking-tight text-slate-950">{{ value }}</p>
                <a
                    v-if="to.startsWith('#')"
                    :href="to"
                    class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-blue-700 hover:text-blue-800"
                >
                    {{ action }} <ArrowUpRight class="h-3.5 w-3.5" />
                </a>
                <RouterLink v-else :to="to" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-blue-700 hover:text-blue-800">
                    {{ action }} <ArrowUpRight class="h-3.5 w-3.5" />
                </RouterLink>
            </div>
        </div>
    </article>
</template>
