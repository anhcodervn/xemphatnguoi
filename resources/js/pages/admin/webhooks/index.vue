<script setup lang="ts">
import { adminWebhookService } from '@/services/admin-webhook.service';
import type { AdminWebhookDetailResponse, AdminWebhookItem, AdminWebhookLogItem, AdminWebhookLogsResponse } from '@/types/admin-webhook.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { computed, onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const loadingDetail = ref(false);
const testingId = ref<number | null>(null);
const togglingId = ref<number | null>(null);

const webhooks = ref<AdminWebhookItem[]>([]);
const stats = reactive({
    total_webhooks: 0,
    enabled_webhooks: 0,
    failed_today: 0,
    success_rate: 0,
});
const meta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

const filters = reactive({
    search: '',
    status: '',
    per_page: 10,
});

const selectedWebhookId = ref<number | null>(null);
const selectedWebhook = ref<AdminWebhookItem | null>(null);
const selectedWebhookLogs = ref<AdminWebhookLogItem[]>([]);
const logMeta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

const activeWebhook = computed(() => webhooks.value.find((item) => item.id === selectedWebhookId.value) ?? selectedWebhook.value);

const statusBadgeClass = (status: string): string => {
    if (status === 'active') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }
    return 'border-slate-200 bg-slate-100 text-slate-600';
};

const eventKeywordLabel = (eventKeyword: string | null): string => {
    if (!eventKeyword || eventKeyword.trim() === '') {
        return 'Tất cả giao dịch tiền vào';
    }

    return eventKeyword;
};

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }
    return new Date(value).toLocaleString('vi-VN');
};

const fetchWebhooks = async (page = 1): Promise<void> => {
    try {
        loading.value = true;
        const response = await adminWebhookService.list({
            search: filters.search || undefined,
            status: filters.status || undefined,
            per_page: filters.per_page,
            page,
        });
        webhooks.value = response.data;
        Object.assign(stats, response.stats);
        Object.assign(meta, response.meta);

        if (!selectedWebhookId.value && response.data.length > 0) {
            await openWebhookDetail(response.data[0].id);
        }
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const openWebhookDetail = async (id: number): Promise<void> => {
    try {
        loadingDetail.value = true;
        selectedWebhookId.value = id;

        const [detail, logs] = await Promise.all([
            adminWebhookService.detail(id),
            adminWebhookService.logs(id, { per_page: 10, page: 1 }),
        ]);

        const typedDetail = detail as AdminWebhookDetailResponse;
        selectedWebhook.value = typedDetail.webhook;
        selectedWebhookLogs.value = logs.data;
        Object.assign(logMeta, logs.meta);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingDetail.value = false;
    }
};

const fetchWebhookLogs = async (page = 1): Promise<void> => {
    if (!selectedWebhookId.value) {
        return;
    }

    try {
        loadingDetail.value = true;
        const logs = (await adminWebhookService.logs(selectedWebhookId.value, {
            per_page: logMeta.per_page,
            page,
        })) as AdminWebhookLogsResponse;
        selectedWebhookLogs.value = logs.data;
        Object.assign(logMeta, logs.meta);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingDetail.value = false;
    }
};

const toggleWebhook = async (webhook: AdminWebhookItem): Promise<void> => {
    try {
        togglingId.value = webhook.id;
        const result = await adminWebhookService.toggle(webhook.id);
        const nextStatus = result.status;

        webhooks.value = webhooks.value.map((item) => (item.id === webhook.id ? { ...item, status: nextStatus } : item));
        if (selectedWebhook.value?.id === webhook.id) {
            selectedWebhook.value = { ...selectedWebhook.value, status: nextStatus };
        }

        handleSuccessResponse({ data: { status: true, message: 'Đã cập nhật trạng thái webhook.' } });
        await fetchWebhooks(meta.current_page);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        togglingId.value = null;
    }
};

const testWebhook = async (webhook: AdminWebhookItem): Promise<void> => {
    try {
        testingId.value = webhook.id;
        await adminWebhookService.test(webhook.id);
        handleSuccessResponse({ data: { status: true, message: `Đã gửi test webhook #${webhook.id}.` } });
        await openWebhookDetail(webhook.id);
        await fetchWebhooks(meta.current_page);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        testingId.value = null;
    }
};

onMounted(async () => {
    await fetchWebhooks(1);
});
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-xl font-bold text-slate-950">Quản lý webhook</h1>
                    <p class="mt-1 text-sm text-slate-500">Theo dõi trạng thái webhook, tỷ lệ thành công và log lỗi gửi webhook.</p>
                </div>
                <button
                    type="button"
                    class="rounded-[10px] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    @click="fetchWebhooks(meta.current_page)"
                >
                    Làm mới
                </button>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total webhook</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ stats.total_webhooks }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Đang hoạt động</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.enabled_webhooks }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lỗi hôm nay</p>
                <p class="mt-1 text-2xl font-bold text-rose-600">{{ stats.failed_today }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tỷ lệ thành công</p>
                <p class="mt-1 text-2xl font-bold text-indigo-600">{{ stats.success_rate }}%</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-wrap gap-2">
                    <input
                        v-model="filters.search"
                        type="text"
                        class="min-w-[220px] rounded-[10px] border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Tìm URL, email user, id user..."
                    />
                    <select v-model="filters.status" class="rounded-[10px] border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active">active</option>
                        <option value="inactive">inactive</option>
                    </select>
                    <button
                        type="button"
                        class="rounded-[10px] border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        @click="fetchWebhooks(1)"
                    >
                        Lọc
                    </button>
                </div>

                <div class="overflow-x-auto rounded-[10px] border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Webhook</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Success/Fail</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-if="loading">
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">Đang tải webhook...</td>
                            </tr>
                            <tr v-else-if="webhooks.length === 0">
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">Không có webhook.</td>
                            </tr>
                            <tr v-for="item in webhooks" :key="item.id">
                                <td class="px-3 py-2 text-sm">
                                    <button class="max-w-[360px] truncate text-left font-semibold text-indigo-600 hover:underline" @click="openWebhookDetail(item.id)">
                                        {{ item.url }}
                                    </button>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ eventKeywordLabel(item.event_keyword) }}</p>
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-600">
                                    <p class="font-semibold text-slate-800">{{ item.user?.name || '-' }}</p>
                                    <p>{{ item.user?.email || '-' }}</p>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold" :class="statusBadgeClass(item.status)">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <p class="text-emerald-600">S: {{ item.success_count }}</p>
                                    <p class="text-rose-600">F: {{ item.failed_count }}</p>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            class="rounded-[8px] border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            :disabled="togglingId === item.id"
                                            @click="toggleWebhook(item)"
                                        >
                                            {{ togglingId === item.id ? 'Đang...' : item.status === 'active' ? 'Tắt' : 'Bật' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-[8px] bg-indigo-600 px-2 py-1 text-xs font-semibold text-white hover:bg-indigo-700"
                                            :disabled="testingId === item.id"
                                            @click="testWebhook(item)"
                                        >
                                            {{ testingId === item.id ? 'Testing...' : 'Test' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-base font-semibold text-slate-950">Chi tiết & logs</h2>
                <div v-if="!activeWebhook" class="mt-3 rounded-[10px] border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
                    Chọn một webhook để xem tình trạng và logs.
                </div>
                <template v-else>
                    <div class="mt-3 space-y-2 rounded-[10px] border border-slate-200 bg-slate-50 p-3 text-sm">
                        <p><span class="font-semibold text-slate-700">URL:</span> {{ activeWebhook.url }}</p>
                        <p><span class="font-semibold text-slate-700">Last called:</span> {{ formatDateTime(activeWebhook.last_called_at) }}</p>
                        <p><span class="font-semibold text-slate-700">Event:</span> {{ eventKeywordLabel(activeWebhook.event_keyword) }}</p>
                    </div>

                    <div class="mt-3 overflow-x-auto rounded-[10px] border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Time</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Event</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">HTTP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="loadingDetail">
                                    <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">Đang tải logs...</td>
                                </tr>
                                <tr v-else-if="selectedWebhookLogs.length === 0">
                                    <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">Không có log webhook.</td>
                                </tr>
                                <tr v-for="log in selectedWebhookLogs" :key="log.id">
                                    <td class="px-3 py-2 text-xs text-slate-600">{{ formatDateTime(log.created_at) }}</td>
                                    <td class="px-3 py-2 text-xs text-slate-700">{{ log.event || '-' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold" :class="statusBadgeClass(log.status)">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-slate-700">{{ log.http_status ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                        <span>Page {{ logMeta.current_page }}/{{ logMeta.last_page }}</span>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-[8px] border border-slate-200 px-2 py-1 hover:bg-slate-50 disabled:opacity-50"
                                :disabled="logMeta.current_page <= 1"
                                @click="fetchWebhookLogs(logMeta.current_page - 1)"
                            >
                                Trước
                            </button>
                            <button
                                type="button"
                                class="rounded-[8px] border border-slate-200 px-2 py-1 hover:bg-slate-50 disabled:opacity-50"
                                :disabled="logMeta.current_page >= logMeta.last_page"
                                @click="fetchWebhookLogs(logMeta.current_page + 1)"
                            >
                                Sau
                            </button>
                        </div>
                    </div>
                </template>
            </article>
        </section>
    </div>
</template>
