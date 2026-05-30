<template>
    <Card custom-class="rounded-[10px] border border-slate-200/80 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
        <template #body>
            <div class="p-3.5">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-900">Thông báo hệ thống</h2>
                        <p class="mt-1 text-xs text-slate-500">Cập nhật mới nhất về gói, hệ thống và ưu đãi.</p>
                    </div>
                </div>

                <div v-if="loading" class="mt-3 text-xs text-slate-500">Đang tải thông báo...</div>

                <div v-else-if="notifications.length === 0" class="mt-3 text-xs text-slate-500">Chưa có thông báo.</div>

                <div v-else class="mt-3 space-y-2.5">
                    <button
                        v-for="item in notifications"
                        :key="item.id"
                        type="button"
                        class="grid w-full gap-2.5 rounded-[8px] border border-slate-100 bg-white px-3 py-2.5 text-left transition hover:border-slate-200 hover:bg-slate-50/70 md:grid-cols-[auto_1fr_auto]"
                        @click="openNotification(item)"
                    >
                        <div class="flex items-start">
                            <span :class="item.is_read ? 'rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700' : 'rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-700'">
                                {{ item.is_read ? 'Đã xem' : 'Mới' }}
                            </span>
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ item.content }}</p>
                        </div>

                        <div class="text-xs text-slate-400 md:text-right">
                            {{ formatTime(item.created_at) }}
                        </div>
                    </button>
                </div>
            </div>
        </template>
    </Card>
</template>

<script setup lang="ts">
import Card from '@/components/Card/index.vue';
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
