<script setup lang="ts">
import type { ClientNotificationItem } from '@/types/client-notification.type';
import { BellRing, CircleAlert, Info, RefreshCcw, TriangleAlert } from 'lucide-vue-next';

defineProps<{
    notifications: ClientNotificationItem[];
    showAll: boolean;
}>();

defineEmits<{
    open: [notification: ClientNotificationItem];
    viewAll: [];
}>();

const typeLabel = (type: string | null): string => {
    return { important: 'Quan trọng', update: 'Cập nhật', warning: 'Nhắc nhở', info: 'Thông tin' }[type || 'info'] || 'Thông tin';
};

const typeClass = (type: string | null): string => {
    if (type === 'important') return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    if (type === 'warning') return 'bg-orange-50 text-orange-700 ring-orange-600/20';
    if (type === 'update') return 'bg-blue-50 text-blue-700 ring-blue-600/20';
    return 'bg-slate-100 text-slate-600 ring-slate-500/20';
};

const notificationIcon = (type: string | null) => {
    if (type === 'important') return CircleAlert;
    if (type === 'warning') return TriangleAlert;
    if (type === 'update') return RefreshCcw;
    return Info;
};

const relativeTime = (value: string | null): string => {
    if (!value) return '--';
    const timestamp = new Date(value).getTime();
    if (Number.isNaN(timestamp)) return '--';

    const minutes = Math.max(0, Math.floor((Date.now() - timestamp) / 60_000));
    if (minutes < 1) return 'Vừa xong';
    if (minutes < 60) return `${minutes} phút trước`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} giờ trước`;
    return `${Math.floor(hours / 24)} ngày trước`;
};
</script>

<template>
    <article
        id="dashboard-notifications"
        class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_16px_40px_-32px_rgba(15,23,42,0.5)]"
    >
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="font-extrabold text-slate-950">Thông báo từ quản trị viên</h2>
                <p class="mt-1 text-xs text-slate-500">4 thông báo hệ thống chung gần nhất.</p>
            </div>
            <button v-if="!showAll" type="button" class="text-xs font-bold text-blue-700 hover:text-blue-800" @click="$emit('viewAll')">
                Xem tất cả
            </button>
            <BellRing v-else class="h-5 w-5 text-blue-600" />
        </header>

        <div v-if="notifications.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">Chưa có thông báo mới.</div>

        <div v-else class="divide-y divide-slate-100">
            <button
                v-for="notification in notifications"
                :key="notification.id"
                type="button"
                class="flex w-full gap-3 px-5 py-4 text-left transition hover:bg-slate-50"
                @click="$emit('open', notification)"
            >
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="typeClass(notification.type)">
                    <component :is="notificationIcon(notification.type)" class="h-4 w-4" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-start justify-between gap-3">
                        <span class="line-clamp-1 text-sm font-bold text-slate-900">{{ notification.title }}</span>
                        <span v-if="!notification.is_read" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500" />
                    </span>
                    <span class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ notification.content }}</span>
                    <span class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset" :class="typeClass(notification.type)">
                            {{ typeLabel(notification.type) }}
                        </span>
                        <span class="text-[11px] text-slate-400">{{ relativeTime(notification.created_at) }}</span>
                    </span>
                </span>
            </button>
        </div>
    </article>
</template>
