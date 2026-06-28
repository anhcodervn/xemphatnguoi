<script setup lang="ts">
import { clientCronJobService, type CronJobLogItem } from '@/services/client-cron-job.service';
import { handleErrorResponse } from '@/utils/response';
import { onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const rows = ref<CronJobLogItem[]>([]);
const meta = reactive({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const selectedLog = ref<CronJobLogItem | null>(null);
const filters = reactive({
    status: '',
    status_code: '',
});

const formatDate = (value: string | null): string => {
    if (!value) {
        return '--';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const loadLogs = async (page = 1): Promise<void> => {
    loading.value = true;

    try {
        const response = await clientCronJobService.globalLogs({
            page,
            per_page: meta.per_page,
            status: filters.status || undefined,
            status_code: filters.status_code ? Number(filters.status_code) : undefined,
        });

        rows.value = response.data;
        Object.assign(meta, response.meta);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await loadLogs();
});
</script>

<template>
    <div class="space-y-5 pb-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <h1 class="text-3xl font-black tracking-[-0.04em] text-slate-950">Run Logs</h1>
            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">Xem các lần chạy gần đây, mã phản hồi, thời gian xử lý và nội dung phản hồi rút gọn.</p>

            <form class="mt-5 grid gap-3 md:grid-cols-3" @submit.prevent="loadLogs(1)">
                <select v-model="filters.status" class="h-11 rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500">
                    <option value="">Tất cả status</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                    <option value="timeout">Timeout</option>
                    <option value="error">Error</option>
                    <option value="blocked">Blocked</option>
                </select>
                <input
                    v-model="filters.status_code"
                    type="number"
                    min="100"
                    max="599"
                    placeholder="Status code"
                    class="h-11 rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500"
                />
                <button type="submit" class="h-11 rounded-[10px] border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Lọc logs
                </button>
            </form>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_360px]">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Danh sách logs</h2>
                        <p class="mt-1 text-sm text-slate-500">Chọn một log để xem chi tiết ở khung bên phải hoặc bên dưới.</p>
                    </div>
                </div>

                <div v-if="loading" class="mt-4 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                    Đang tải logs...
                </div>

                <div v-else-if="rows.length === 0" class="mt-4 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                    Chưa có log nào khớp bộ lọc.
                </div>

                <div v-else class="mt-4 space-y-3">
                    <button
                        v-for="log in rows"
                        :key="log.id"
                        type="button"
                        class="w-full rounded-[10px] border px-4 py-4 text-left transition"
                        :class="selectedLog?.id === log.id ? 'border-sky-300 bg-sky-50' : 'border-slate-200 bg-slate-50 hover:bg-white'"
                        @click="selectedLog = log"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="log.status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                        {{ log.status }}
                                    </span>
                                    <span class="text-xs font-medium text-slate-500">{{ log.method }}</span>
                                    <span class="text-xs text-slate-400">{{ formatDate(log.started_at) }}</span>
                                </div>
                                <p class="mt-3 break-all text-sm font-semibold text-slate-900">{{ log.url }}</p>
                            </div>
                            <div class="grid shrink-0 grid-cols-2 gap-3 sm:min-w-[180px]">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Code</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ log.status_code ?? '--' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Thời gian</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ log.duration_ms ?? '--' }} ms</p>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Log detail</h2>
                <div v-if="!selectedLog" class="mt-4 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-sm text-slate-500">
                    Chọn một log trong danh sách để xem request, response và lỗi chi tiết.
                </div>
                <div v-else class="mt-4 space-y-4">
                    <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Request</p>
                        <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ selectedLog.method }} {{ selectedLog.url }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Status</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ selectedLog.status }}</p>
                        </div>
                        <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Status code</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ selectedLog.status_code ?? '--' }}</p>
                        </div>
                    </div>
                    <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Error message</p>
                        <p class="mt-2 break-words whitespace-pre-wrap text-sm text-slate-700">{{ selectedLog.error_message || 'Không có lỗi.' }}</p>
                    </div>
                    <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Response preview</p>
                        <pre class="mt-2 overflow-auto whitespace-pre-wrap break-words text-xs text-slate-700">{{ selectedLog.response_body_preview || 'Không có dữ liệu response preview.' }}</pre>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
