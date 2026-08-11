<script setup lang="ts">
import { useSupportStore } from '@/stores/support.store';
import { MessageCircleMore } from 'lucide-vue-next';
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const supportStore = useSupportStore();
const badgeLabel = computed(() => (supportStore.userUnread > 99 ? '99+' : String(supportStore.userUnread)));
</script>

<template>
    <RouterLink
        to="/support"
        class="proxy-focus group fixed bottom-5 right-4 z-30 flex min-h-14 min-w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-[0_16px_40px_rgba(37,99,235,0.38)] transition duration-200 hover:-translate-y-1 hover:bg-blue-700 sm:bottom-6 sm:right-6"
        aria-label="Mở tin nhắn hỗ trợ"
    >
        <span class="absolute inset-0 -z-10 rounded-full bg-blue-500/35 motion-safe:animate-ping" aria-hidden="true" />
        <MessageCircleMore class="h-6 w-6" />
        <span
            v-if="supportStore.userUnread > 0"
            class="absolute -right-1 -top-1 inline-flex min-h-6 min-w-6 items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] font-black tabular-nums text-white ring-2 ring-white"
        >
            {{ badgeLabel }}
        </span>
        <span
            class="pointer-events-none absolute right-[calc(100%+0.75rem)] hidden whitespace-nowrap rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white opacity-0 shadow-lg transition group-hover:opacity-100 sm:block"
        >
            Chat với hỗ trợ
        </span>
    </RouterLink>
</template>
