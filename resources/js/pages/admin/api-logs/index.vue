<script setup lang="ts">
import { adminApiLogService } from '@/services/admin-api-log.service';
import type { AdminApiLogItem } from '@/types/admin-api-log.type';
import { handleErrorResponse } from '@/utils/response';
import { FileClock, LoaderCircle, Search } from 'lucide-vue-next';
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

    return JSON.stringify(payload);
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
            <h1 class="text-xl font-bold text-slate-950">Quản lý API log</h1>
            <p class="mt-1 text-sm text-slate-500">Theo dõi request vào hệ thống API theo endpoint, API key, user và mã trạng thái trả về.</p>
        </section>

        <section class="grid gap-3 md:grid-cols-4">
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tổng log</p>
                <p class="mt-1 text-2xl font-bold text-slate-950">{{ summary.total }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">2xx</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ summary.success }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">4xx</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ summary.client_error }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">5xx</p>
                <p class="mt-1 text-2xl font-bold text-rose-600">{{ summary.server_error }}</p>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-3.5 shadow-sm">
            <div class="grid gap-2.5 lg:grid-cols-[1.6fr_160px_180px_auto]">
                <label class="flex items-center gap-2.5 rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2">
                    <Search class="h-4 w-4 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 outline-none placeholder:text-slate-400"
                        placeholder="Tìm endpoint, user, api_key, ip..."
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
                    <option value="">Tất cả mã trạng thái</option>
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
                <LoaderCircle class="h-5 w-5 animate-spin" />
                Đang tải API log...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[1280px]">
                    <thead class="bg-slate-50 text-left text-[13px] font-semibold text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Request</th>
                            <th class="px-4 py-3">User / API key</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Response time</th>
                            <th class="px-4 py-3">Payload</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">Chưa có API log nào.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="align-top hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="flex items-start gap-2">
                                    <FileClock class="mt-0.5 h-4 w-4 text-[#465fff]" />
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ formatDateTime(row.created_at) }}</p>
                                        <p>{{ row.ip || '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p class="font-semibold text-slate-900">{{ row.method }} {{ row.endpoint }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p class="font-semibold text-slate-900">{{ row.user?.full_name || row.user?.username || '-' }}</p>
                                <p>{{ row.api_key?.name || '-' }}</p>
                                <p class="font-mono text-xs text-slate-500">{{ row.api_key?.api_key || '' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span
                                    class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        (row.status_code ?? 0) >= 500
                                            ? 'border-rose-200 bg-rose-50 text-rose-700'
                                            : (row.status_code ?? 0) >= 400
                                              ? 'border-amber-200 bg-amber-50 text-amber-700'
                                              : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    "
                                >
                                    {{ row.status_code ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ row.response_time_ms ? `${row.response_time_ms} ms` : '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                <p class="line-clamp-2 max-w-[340px]">{{ formatJsonPreview(row.request_data) }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
