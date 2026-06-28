<script setup lang="ts">
import { adminCronJobService } from '@/services/admin-cron-job.service';
import type { CronJobLogItem } from '@/services/client-cron-job.service';
import { handleErrorResponse } from '@/utils/response';
import { onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const rows = ref<CronJobLogItem[]>([]);
const selected = ref<CronJobLogItem | null>(null);
const filters = reactive({
    status: '',
});

const loadLogs = async (): Promise<void> => {
    loading.value = true;

    try {
        const response = await adminCronJobService.globalLogs({
            per_page: 50,
            status: filters.status || undefined,
        });

        rows.value = response.data;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

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

onMounted(async () => {
    await loadLogs();
});
</script>

<template>
    <div class="space-y-5">
        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <h1 class="text-3xl font-black tracking-[-0.04em] text-slate-950">System Logs / Failed Cron Logs</h1>
            <p class="mt-2 text-sm leading-7 text-slate-600">Tập trung review timeout, blocked URL, SSL error và response mismatch trên toàn hệ thống.</p>

            <form class="mt-5 grid gap-3 md:grid-cols-[220px_120px]" @submit.prevent="loadLogs">
                <select v-model="filters.status" class="h-11 rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500">
                    <option value="">Tất cả status</option>
                    <option value="failed">Failed</option>
                    <option value="timeout">Timeout</option>
                    <option value="error">Error</option>
                    <option value="blocked">Blocked</option>
                </select>
                <button type="submit" class="rounded-[10px] border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Lọc log</button>
            </form>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
            <article class="rounded-[10px] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[760px] w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Request</th>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Started</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading"><td colspan="4" class="px-4 py-10 text-center text-slate-500">Đang tải logs...</td></tr>
                            <tr v-else-if="rows.length === 0"><td colspan="4" class="px-4 py-10 text-center text-slate-500">Chưa có log nào.</td></tr>
                            <tr v-for="log in rows" :key="log.id" class="cursor-pointer border-t border-slate-100 hover:bg-slate-50" @click="selected = log">
                                <td class="px-4 py-3 text-slate-700">{{ log.status }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ log.method }}</p>
                                    <p class="mt-1 max-w-[320px] truncate text-xs text-slate-500">{{ log.url }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ log.status_code ?? '--' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ formatDate(log.started_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">Chi tiết log</h2>
                <div v-if="!selected" class="mt-4 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-sm text-slate-500">
                    Chọn một log để xem chi tiết response preview và lỗi.
                </div>
                <div v-else class="mt-4 space-y-4">
                    <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Request</p>
                        <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ selected.method }} {{ selected.url }}</p>
                    </div>
                    <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Error</p>
                        <p class="mt-2 break-words whitespace-pre-wrap text-sm text-slate-700">{{ selected.error_message || 'Không có lỗi.' }}</p>
                    </div>
                    <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Response preview</p>
                        <pre class="mt-2 overflow-auto whitespace-pre-wrap break-words text-xs text-slate-700">{{ selected.response_body_preview || 'Không có response preview.' }}</pre>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
