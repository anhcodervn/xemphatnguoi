<script setup lang="ts">
import { adminApiLogService } from '@/services/admin-api-log.service';
import type { AdminApiLogItem } from '@/types/admin-api-log.type';
import { handleErrorResponse } from '@/utils/response';
import { CheckCircle2, Clock3, FileClock, Search, ShieldAlert, ShieldX } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const rows = ref<AdminApiLogItem[]>([]);

const filters = reactive({
    search: '',
    method: '',
    status_code: '',
    page: 1,
});

const meta = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});

const summary = reactive({
    total: 0,
    success: 0,
    client_error: 0,
    server_error: 0,
});

const tableRange = computed(() => {
    if (meta.total === 0) {
        return '0-0';
    }

    const from = (meta.current_page - 1) * 15 + 1;
    const to = Math.min(meta.current_page * 15, meta.total);

    return `${from}-${to}`;
});

const summaryCards = computed(() => [
    {
        label: 'Tổng log',
        value: summary.total,
        icon: FileClock,
        tone: 'bg-slate-100 text-slate-700',
    },
    {
        label: '2xx',
        value: summary.success,
        icon: CheckCircle2,
        tone: 'bg-emerald-50 text-emerald-700',
    },
    {
        label: '4xx',
        value: summary.client_error,
        icon: ShieldAlert,
        tone: 'bg-amber-50 text-amber-700',
    },
    {
        label: '5xx',
        value: summary.server_error,
        icon: ShieldX,
        tone: 'bg-rose-50 text-rose-700',
    },
]);

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('vi-VN');
};

const formatJsonPreview = (payload: Record<string, unknown> | null): string => {
    if (!payload) {
        return '-';
    }

    return JSON.stringify(payload, null, 2);
};

const statusClass = (statusCode: number | null): string => {
    if ((statusCode ?? 0) >= 500) {
        return 'border-rose-200 bg-rose-50 text-rose-700';
    }

    if ((statusCode ?? 0) >= 400) {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
};

const fetchApiLogs = async (page = 1): Promise<void> => {
    try {
        loading.value = true;

        const response = await adminApiLogService.list({
            page,
            search: filters.search || undefined,
            method: filters.method || undefined,
            status_code: filters.status_code || undefined,
        });

        rows.value = response.api_logs.data;
        meta.current_page = response.api_logs.current_page;
        meta.last_page = response.api_logs.last_page;
        meta.total = response.api_logs.total;
        Object.assign(summary, response.summary);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const applyFilters = async (): Promise<void> => {
    filters.page = 1;
    await fetchApiLogs(1);
};

const goToPage = async (page: number): Promise<void> => {
    if (page < 1 || page > meta.last_page || page === meta.current_page) {
        return;
    }

    filters.page = page;
    await fetchApiLogs(page);
};

onMounted(async () => {
    await fetchApiLogs();
});
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <h1 class="text-xl font-bold text-slate-950">API log</h1>
            <p class="mt-1 text-sm text-slate-500">Theo dõi request user, response từ service và response trả về client theo từng lượt gọi API.</p>
        </section>

        <section class="grid gap-3 md:grid-cols-4">
            <article v-for="card in summaryCards" :key="card.label" class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                        <p class="mt-1 text-2xl font-bold text-slate-950">{{ card.value }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-[8px]" :class="card.tone">
                        <component :is="card.icon" class="h-4 w-4" />
                    </div>
                </div>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-3.5 shadow-sm">
            <div class="grid gap-2.5 lg:grid-cols-[1.6fr_150px_170px_auto]">
                <label class="flex items-center gap-2.5 rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2">
                    <Search class="h-4 w-4 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 outline-none placeholder:text-slate-400"
                        placeholder="Tìm endpoint, user, api key, ip..."
                        @keyup.enter="applyFilters"
                    />
                </label>

                <select v-model="filters.method" class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none">
                    <option value="">Tất cả method</option>
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PATCH">PATCH</option>
                    <option value="DELETE">DELETE</option>
                </select>

                <select v-model="filters.status_code" class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none">
                    <option value="">Tất cả status</option>
                    <option value="200">200</option>
                    <option value="201">201</option>
                    <option value="400">400</option>
                    <option value="401">401</option>
                    <option value="403">403</option>
                    <option value="404">404</option>
                    <option value="422">422</option>
                    <option value="500">500</option>
                </select>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-[8px] border border-[#465fff] px-3.5 py-2 text-sm font-semibold text-[#465fff] transition hover:bg-[#eef2ff]"
                    @click="applyFilters"
                >
                    Lọc
                </button>
            </div>
        </section>

        <section class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm">
            <div v-if="loading" class="flex items-center justify-center gap-2 px-6 py-16 text-sm text-slate-500">
                <Clock3 class="h-5 w-5 animate-spin" />
                Đang tải API log...
            </div>

            <div v-else-if="rows.length === 0" class="px-4 py-12 text-center text-sm text-slate-500">
                Chưa có API log nào.
            </div>

            <div v-else class="space-y-3 p-3">
                <article v-for="row in rows" :key="row.id" class="rounded-[10px] border border-slate-200 bg-slate-50/60 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-[8px] bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">{{ row.method }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold" :class="statusClass(row.status_code)">
                                    {{ row.status_code ?? '-' }}
                                </span>
                                <span class="text-xs text-slate-500">{{ row.response_time_ms ? `${row.response_time_ms} ms` : '-' }}</span>
                            </div>
                            <p class="text-sm font-semibold text-slate-950">{{ row.endpoint }}</p>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                <span>{{ formatDateTime(row.created_at) }}</span>
                                <span>{{ row.ip || '-' }}</span>
                                <span>{{ row.user?.full_name || row.user?.username || 'Ẩn danh' }}</span>
                                <span>{{ row.api_key?.name || 'Không có API key' }}</span>
                            </div>
                            <p v-if="row.api_key?.api_key" class="font-mono text-xs text-slate-400">{{ row.api_key.api_key }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 xl:grid-cols-3">
                        <div class="rounded-[8px] bg-slate-950 p-3">
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-300">Request user</p>
                            <pre class="max-h-[240px] overflow-auto whitespace-pre-wrap break-words text-xs text-slate-100">{{ formatJsonPreview(row.request_data) }}</pre>
                        </div>

                        <div class="rounded-[8px] bg-sky-950 p-3">
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-200">Response service</p>
                            <pre class="max-h-[240px] overflow-auto whitespace-pre-wrap break-words text-xs text-sky-100">{{ formatJsonPreview(row.service_response_data) }}</pre>
                        </div>

                        <div class="rounded-[8px] bg-emerald-950 p-3">
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-200">Response client</p>
                            <pre class="max-h-[240px] overflow-auto whitespace-pre-wrap break-words text-xs text-emerald-100">{{ formatJsonPreview(row.response_data) }}</pre>
                        </div>
                    </div>
                </article>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm text-slate-500">
                <p>Đang hiển thị {{ tableRange }} / {{ meta.total }}</p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-[8px] border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="meta.current_page <= 1"
                        @click="goToPage(meta.current_page - 1)"
                    >
                        Trước
                    </button>
                    <span>Trang {{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button
                        type="button"
                        class="rounded-[8px] border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                    >
                        Sau
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
