<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

type ErrorAction = {
    href: string;
    label: string;
};

const props = defineProps<{
    code: string;
    eyebrow: string;
    title: string;
    description: string;
    primaryAction: ErrorAction;
    secondaryAction: ErrorAction;
    theme?: 'admin' | 'client';
}>();

const palette = computed(() => {
    if (props.theme === 'admin') {
        return {
            shell: 'border-slate-200 bg-white/90 shadow-[0_28px_90px_rgba(15,23,42,0.08)]',
            badge: 'border-violet-200 bg-violet-50 text-violet-700',
            title: 'text-slate-950',
            text: 'text-slate-600',
            subtle: 'text-slate-500',
            accent: 'bg-violet-600 text-white hover:bg-violet-500',
            secondary: 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
            icon: 'from-violet-500 via-indigo-500 to-sky-500',
            glow: 'bg-[radial-gradient(circle_at_top_right,_rgba(99,102,241,0.16),_transparent_36%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.12),_transparent_42%)]',
            grid: 'border-slate-200 bg-slate-50/80',
        };
    }

    return {
        shell: 'border-sky-100 bg-white/90 shadow-[0_28px_90px_rgba(14,116,144,0.1)]',
        badge: 'border-sky-200 bg-sky-50 text-sky-700',
        title: 'text-slate-950',
        text: 'text-slate-600',
        subtle: 'text-slate-500',
        accent: 'bg-sky-600 text-white hover:bg-sky-500',
        secondary: 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
        icon: 'from-cyan-500 via-sky-500 to-blue-600',
        glow: 'bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,0.14),_transparent_38%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.1),_transparent_42%)]',
        grid: 'border-slate-200 bg-slate-50/80',
    };
});
</script>

<template>
    <section class="relative overflow-hidden rounded-[24px] border p-6 backdrop-blur-xl sm:p-8" :class="palette.shell">
        <div class="absolute inset-0 opacity-80" :class="palette.glow" />
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(148,163,184,0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148,163,184,0.08)_1px,transparent_1px)] bg-[size:68px_68px] opacity-50" />

        <div class="relative">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em]" :class="palette.badge">
                    {{ eyebrow }}
                </span>
                <span class="text-sm font-semibold" :class="palette.subtle">{{ code }}</span>
            </div>

            <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <h1 class="font-['Space_Grotesk'] text-3xl font-bold tracking-tight sm:text-4xl" :class="palette.title">
                        {{ title }}
                    </h1>
                    <p class="mt-4 text-base leading-8" :class="palette.text">
                        {{ description }}
                    </p>
                </div>

                <div class="inline-flex h-20 w-20 shrink-0 items-center justify-center rounded-[22px] bg-gradient-to-br text-2xl font-bold text-white shadow-lg" :class="palette.icon">
                    {{ code }}
                </div>
            </div>

            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border p-5" :class="palette.grid">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em]" :class="palette.subtle">Gợi ý xử lý</p>
                    <ul class="mt-3 space-y-2 text-sm leading-7" :class="palette.text">
                        <li class="flex gap-3">
                            <span class="mt-2 h-2 w-2 rounded-full bg-current/70" />
                            <span>Kiểm tra lại đường dẫn hoặc chức năng bạn vừa truy cập.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-2 h-2 w-2 rounded-full bg-current/70" />
                            <span>Nếu trang vừa đổi cấu trúc, hãy quay về dashboard rồi điều hướng lại từ menu.</span>
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl border p-5" :class="palette.grid">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em]" :class="palette.subtle">Tiếp tục</p>
                    <div class="mt-4 flex flex-col gap-3">
                        <RouterLink
                            :to="primaryAction.href"
                            class="inline-flex items-center justify-center rounded-full px-4 py-3 text-sm font-semibold transition"
                            :class="palette.accent"
                        >
                            {{ primaryAction.label }}
                        </RouterLink>
                        <RouterLink
                            :to="secondaryAction.href"
                            class="inline-flex items-center justify-center rounded-full border px-4 py-3 text-sm font-semibold transition"
                            :class="palette.secondary"
                        >
                            {{ secondaryAction.label }}
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
