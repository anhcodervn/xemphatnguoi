<template>
    <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Thông báo cập nhật</h2>
                <p class="mt-1 text-sm text-slate-500">Các thay đổi mới nhất về hệ thống, dịch vụ proxy và hướng dẫn sử dụng.</p>
            </div>
        </div>

        <div v-if="loading" class="mt-4 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
            Đang tải thông báo...
        </div>

        <div
            v-else-if="notifications.length === 0"
            class="mt-4 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500"
        >
            Chưa có thông báo nào.
        </div>

        <div v-else class="mt-4 space-y-3">
            <button
                v-for="item in notifications"
                :key="item.id"
                type="button"
                class="w-full rounded-[10px] border px-4 py-4 text-left transition"
                :class="item.is_read ? 'border-slate-200 bg-slate-50 hover:bg-white' : 'border-sky-200 bg-sky-50/70 hover:bg-sky-50'"
                @click="openNotification(item)"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                :class="
                                    item.is_read
                                        ? 'rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700'
                                        : 'rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-700'
                                "
                            >
                                {{ item.is_read ? 'Đã xem' : 'Mới' }}
                            </span>
                            <span class="text-xs text-slate-400">{{ formatTime(item.created_at) }}</span>
                        </div>

                        <p class="mt-3 text-base font-bold text-slate-950">{{ item.title }}</p>
                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ item.content }}</p>
                    </div>
                </div>
            </button>
        </div>
    </article>
</template>

<script setup lang="ts">
import { clientNotificationService } from '@/services/client-notification.service';
import type { ClientNotificationItem } from '@/types/client-notification.type';
import { handleErrorResponse } from '@/utils/response';
import { onMounted, ref } from 'vue';

const notifications = ref<ClientNotificationItem[]>([]);
const loading = ref(false);

const formatTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN');
};

const loadNotifications = async (): Promise<void> => {
    try {
        loading.value = true;
        const response = await clientNotificationService.list({ scope: 'system', per_page: 6 });
        notifications.value = response.data;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const openNotification = async (item: ClientNotificationItem): Promise<void> => {
    if (!item.is_read) {
        try {
            await clientNotificationService.markRead(item.id);
            item.is_read = true;
        } catch (error) {
            handleErrorResponse(error);
        }
    }

    if (item.redirect_url) {
        window.location.href = item.redirect_url;
    }
};

onMounted(loadNotifications);
</script>
