<script setup lang="ts">
import { Copy, FileCode2, TerminalSquare, Key, LucideIcon } from 'lucide-vue-next';

type StatItem = {
    label: string;
    value: string;
    note: string;
    icon: LucideIcon;
};

defineProps<{
    baseUrl: string;
    stats: StatItem[];
    copiedKey: string | null;
}>();

defineEmits<{
    copyBaseUrl: [];
    viewEndpoints: [];
}>();
</script>

<template>
    <section
        class="overflow-hidden rounded-[10px] border border-white/10 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.12),transparent_30%),linear-gradient(135deg,#1533d3_0%,#1738cb_35%,#1b2d97_100%)] px-5 py-5 text-white shadow-[0_24px_60px_-42px_rgba(15,23,42,0.75)] md:px-6 md:py-6"
    >
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)] xl:items-center">
            <div>
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/85"
                >
                    <TerminalSquare class="h-3.5 w-3.5" />
                    API Workspace
                </div>

                <h1 class="mt-4 text-[2.15rem] font-black tracking-[-0.05em] text-white md:text-[2.8rem]">Tài liệu API</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/80 md:text-[15px]">
                    Tổng hợp endpoint, xác thực, webhook và cách dùng API cho hệ thống nạp tiền tự động.
                </p>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <RouterLink
                        to="/profile?tab=api-keys"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-[10px] bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
                    >
                        <Key class="h-4 w-4" />
                        Lấy API token
                    </RouterLink>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-[10px] border border-white/20 bg-transparent px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10"
                        @click="$emit('viewEndpoints')"
                    >
                        <FileCode2 class="h-4 w-4" />
                        Xem danh sách API
                    </button>
                </div>
            </div>

            <!-- <div class="grid gap-3 md:grid-cols-3">
                <article v-for="stat in stats" :key="stat.label" class="rounded-[10px] border border-white/15 bg-white/10 p-3.5 backdrop-blur">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] bg-white/12 text-white">
                            <component :is="stat.icon" class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/60">{{ stat.label }}</p>
                            <p class="mt-1 text-[1.6rem] font-black leading-none tracking-[-0.04em] text-white">{{ stat.value }}</p>
                            <p class="mt-2 text-xs text-white/70">{{ stat.note }}</p>
                        </div>
                    </div>
                </article>
            </div> -->
        </div>
    </section>
</template>
