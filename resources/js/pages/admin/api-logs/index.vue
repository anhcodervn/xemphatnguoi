<script setup lang="ts">
import { adminApiLogService } from '@/services/admin-api-log.service';
import type { AdminApiLogItem } from '@/types/admin-api-log.type';
import formatCash from '@/utils/helpers/formatCash';
import { Activity, ChevronDown, ChevronUp, Clock3, Search, ServerCrash, UsersRound } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

const localDate = (): string => {
    const date = new Date();
    const offset = date.getTimezoneOffset() * 60_000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 10);
};

const rows = ref<AdminApiLogItem[]>([]);
const loading = ref(true);
const errorMessage = ref('');
const expandedLogId = ref<number | null>(null);
const filters = reactive({ search: '', api_version: '', method: '', status_group: '', status_code: '', from: localDate(), to: localDate() });
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
const summary = reactive({
    total: 0,
    success: 0,
    client_error: 0,
    server_error: 0,
    service_unavailable: 0,
    affected_users: 0,
    affected_api_keys: 0,
    average_response_time_ms: null as number | null,
    first_failure_at: null as string | null,
    last_failure_at: null as string | null,
    charged: 0,
    revenue: '0.00',
});

const failureWindow = computed(() => {
    if (!summary.first_failure_at || !summary.last_failure_at) return 'Không có lỗi 5xx';

    return `${new Date(summary.first_failure_at).toLocaleTimeString('vi-VN')} – ${new Date(summary.last_failure_at).toLocaleTimeString('vi-VN')}`;
});

const summaryCards = computed(() => [
    {
        label: 'Tổng request',
        value: summary.total,
        hint: `${summary.success.toLocaleString('vi-VN')} thành công`,
        icon: Activity,
        tone: 'bg-sky-50 text-sky-700',
    },
    {
        label: 'Lỗi 5xx',
        value: summary.server_error,
        hint: `${summary.service_unavailable.toLocaleString('vi-VN')} lỗi 503`,
        icon: ServerCrash,
        tone: 'bg-red-50 text-red-700',
    },
    {
        label: 'Khách bị ảnh hưởng',
        value: summary.affected_users,
        hint: `${summary.affected_api_keys.toLocaleString('vi-VN')} API key`,
        icon: UsersRound,
        tone: 'bg-amber-50 text-amber-700',
    },
    {
        label: 'Latency trung bình',
        value: summary.average_response_time_ms === null ? '—' : `${summary.average_response_time_ms} ms`,
        hint: failureWindow.value,
        icon: Clock3,
        tone: 'bg-slate-100 text-slate-700',
    },
]);

const load = async (page = 1): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await adminApiLogService.list({
            page,
            search: filters.search || undefined,
            api_version: filters.api_version || undefined,
            method: filters.method || undefined,
            status_group: filters.status_group || undefined,
            status_code: filters.status_code || undefined,
            from: filters.from || undefined,
            to: filters.to || undefined,
        });

        rows.value = response.api_logs.data;
        meta.current_page = response.api_logs.current_page;
        meta.last_page = response.api_logs.last_page;
        meta.total = response.api_logs.total;
        Object.assign(summary, response.summary);
        expandedLogId.value = null;
    } catch {
        errorMessage.value = 'Không thể tải nhật ký API.';
    } finally {
        loading.value = false;
    }
};

const statusClass = (statusCode: number | null): string => {
    if ((statusCode ?? 0) >= 500) return 'bg-red-50 text-red-700';
    if ((statusCode ?? 0) >= 400) return 'bg-amber-50 text-amber-700';
    return 'bg-emerald-50 text-emerald-700';
};

const prettyJson = (value: unknown): string => {
    if (value === null || value === undefined) return 'Không có dữ liệu';
    if (typeof value === 'string') return value;

    return JSON.stringify(value, null, 2);
};

onMounted(() => load());
</script>

<template>
    <div class="grid gap-6">
        <header>
            <p class="text-sm font-bold text-sky-700">API observability</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Nhật ký API</h1>
            <p class="mt-2 text-sm text-slate-500">
                Theo dõi request dùng API key, lỗi HTTP, khách bị ảnh hưởng và dữ liệu đã được che thông tin nhạy cảm.
            </p>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="card in summaryCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">{{ card.label }}</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">
                            {{ typeof card.value === 'number' ? card.value.toLocaleString('vi-VN') : card.value }}
                        </p>
                        <p class="mt-1 truncate text-xs text-slate-500">{{ card.hint }}</p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" :class="card.tone"
                        ><component :is="card.icon" class="h-5 w-5"
                    /></span>
                </div>
            </article>
        </section>

        <form
            class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-2 xl:grid-cols-[minmax(200px,1fr)_120px_120px_150px_110px_145px_145px_auto]"
            @submit.prevent="load(1)"
        >
            <input
                v-model="filters.search"
                type="search"
                placeholder="Endpoint, IP, user hoặc API key"
                class="app-focus h-11 rounded-lg border border-slate-300 px-3 text-sm"
            />
            <select v-model="filters.api_version" class="app-focus h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                <option value="">Mọi phiên bản</option>
                <option value="v1">API v1</option>
                <option value="v2">API v2</option>
            </select>
            <select v-model="filters.method" class="app-focus h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                <option value="">Mọi method</option>
                <option v-for="method in ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']" :key="method" :value="method">{{ method }}</option>
            </select>
            <select v-model="filters.status_group" class="app-focus h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                <option value="">Mọi nhóm status</option>
                <option value="success">2xx thành công</option>
                <option value="client_error">4xx lỗi client</option>
                <option value="server_error">5xx lỗi server</option>
            </select>
            <input
                v-model="filters.status_code"
                inputmode="numeric"
                placeholder="Mã status"
                class="app-focus h-11 rounded-lg border border-slate-300 px-3 text-sm"
            />
            <label class="grid gap-1 text-xs font-semibold text-slate-500"
                ><span>Từ ngày</span
                ><input v-model="filters.from" type="date" class="app-focus h-11 rounded-lg border border-slate-300 px-3 text-sm text-slate-900"
            /></label>
            <label class="grid gap-1 text-xs font-semibold text-slate-500"
                ><span>Đến ngày</span
                ><input v-model="filters.to" type="date" class="app-focus h-11 rounded-lg border border-slate-300 px-3 text-sm text-slate-900"
            /></label>
            <button
                type="submit"
                class="app-focus inline-flex min-h-11 items-center justify-center gap-2 self-end rounded-lg bg-slate-950 px-4 text-sm font-bold text-white"
            >
                <Search class="h-4 w-4" />Lọc
            </button>
        </form>

        <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">{{ errorMessage }}</div>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="loading" class="p-10 text-center text-sm text-slate-500">Đang tải nhật ký API...</div>
            <div v-else-if="!rows.length" class="p-10 text-center text-sm text-slate-500">Không có request trong khoảng thời gian đã chọn.</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-4">Thời gian</th>
                            <th class="px-4 py-4">Endpoint</th>
                            <th class="px-4 py-4">Khách / API key</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-4 py-4">Tính phí</th>
                            <th class="px-4 py-4">Latency</th>
                            <th class="px-4 py-4"><span class="sr-only">Chi tiết</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <template v-for="row in rows" :key="row.id">
                            <tr>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                    {{ row.created_at ? new Date(row.created_at).toLocaleString('vi-VN') : '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-black uppercase"
                                            :class="row.api_version === 'v2' ? 'bg-violet-50 text-violet-700' : 'bg-sky-50 text-sky-700'"
                                            >API {{ row.api_version }}</span
                                        >
                                        <span class="rounded bg-slate-100 px-2 py-1 font-mono text-xs font-bold">{{ row.method }}</span>
                                    </div>
                                    <p class="mt-2 font-mono text-xs text-slate-700">{{ row.endpoint }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-slate-900">{{ row.user?.username ?? 'Không xác định' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ row.api_key?.name ?? row.ip ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClass(row.status_code)">{{
                                        row.status_code ?? '—'
                                    }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <p class="font-bold text-slate-900">{{ formatCash(Number(row.charged_amount)) }}đ</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ row.billing_status }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                    {{ row.response_time_ms === null ? '—' : `${row.response_time_ms} ms` }}
                                </td>
                                <td class="px-4 py-4">
                                    <button
                                        type="button"
                                        class="app-focus inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50"
                                        :aria-expanded="expandedLogId === row.id"
                                        :aria-label="expandedLogId === row.id ? 'Đóng chi tiết request' : 'Xem chi tiết request'"
                                        @click="expandedLogId = expandedLogId === row.id ? null : row.id"
                                    >
                                        <ChevronUp v-if="expandedLogId === row.id" class="h-4 w-4" /><ChevronDown v-else class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expandedLogId === row.id" class="bg-slate-50/70">
                                <td colspan="7" class="p-4">
                                    <div class="grid gap-4 xl:grid-cols-3">
                                        <article
                                            v-for="detail in [
                                                { label: 'Request', value: row.request_data },
                                                { label: 'Dữ liệu dịch vụ', value: row.service_response_data },
                                                { label: 'Response', value: row.response_data },
                                            ]"
                                            :key="detail.label"
                                            class="min-w-0 rounded-lg border border-slate-200 bg-white p-4"
                                        >
                                            <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ detail.label }}</h2>
                                            <pre
                                                class="mt-3 max-h-72 overflow-auto whitespace-pre-wrap break-words text-xs leading-5 text-slate-700"
                                                >{{ prettyJson(detail.value) }}</pre
                                            >
                                        </article>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="meta.last_page > 1" class="flex flex-wrap justify-center gap-2">
            <button
                v-for="page in meta.last_page"
                :key="page"
                type="button"
                class="app-focus min-h-10 min-w-10 rounded-lg border text-sm font-bold"
                :class="page === meta.current_page ? 'border-sky-700 bg-sky-700 text-white' : 'border-slate-300 bg-white'"
                @click="load(page)"
            >
                {{ page }}
            </button>
        </div>
    </div>
</template>
