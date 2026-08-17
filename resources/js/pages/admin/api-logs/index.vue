<script setup lang="ts">
import { adminApiLogService } from '@/services/admin-api-log.service';
import type { AdminApiLogItem } from '@/types/admin-api-log.type';
import formatCash from '@/utils/helpers/formatCash';
import { CheckCircle2, FileClock, Search, ShieldAlert, ShieldX } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

const rows = ref<AdminApiLogItem[]>([]);
const loading = ref(true);
const errorMessage = ref('');
const filters = reactive({ search: '', method: '', status_code: '' });
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
const summary = reactive({ total: 0, success: 0, client_error: 0, server_error: 0, charged: 0, revenue: '0.00' });

const summaryCards = computed(() => [
    { label: 'Tổng request', value: summary.total, icon: FileClock, tone: 'bg-slate-100 text-slate-700' },
    { label: 'Đã tính phí', value: summary.charged, icon: CheckCircle2, tone: 'bg-emerald-50 text-emerald-700' },
    { label: 'Doanh thu API', value: `${formatCash(Number(summary.revenue))}đ`, icon: ShieldAlert, tone: 'bg-sky-50 text-sky-700' },
    { label: 'Request lỗi', value: summary.client_error + summary.server_error, icon: ShieldX, tone: 'bg-red-50 text-red-700' },
]);

const load = async (page = 1): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';

    try {
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
    } catch {
        errorMessage.value = 'Không thể tải API usage.';
    } finally {
        loading.value = false;
    }
};

const statusClass = (statusCode: number | null): string => {
    if ((statusCode ?? 0) >= 500) return 'bg-red-50 text-red-700';
    if ((statusCode ?? 0) >= 400) return 'bg-amber-50 text-amber-700';
    return 'bg-emerald-50 text-emerald-700';
};

onMounted(() => load());
</script>

<template>
    <div class="grid gap-6">
        <header>
            <p class="text-sm font-bold text-sky-700">API</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">API Usage</h1>
            <p class="mt-2 text-sm text-slate-500">Request thực tế qua API key, gồm endpoint tra cứu phạt nguội phiên bản v1.</p>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="card in summaryCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">{{ card.label }}</p>
                        <p class="mt-2 text-2xl font-black">{{ typeof card.value === 'number' ? card.value.toLocaleString('vi-VN') : card.value }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg" :class="card.tone"
                        ><component :is="card.icon" class="h-5 w-5"
                    /></span>
                </div>
            </article>
        </section>

        <form class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_160px_160px_auto]" @submit.prevent="load(1)">
            <input
                v-model="filters.search"
                type="search"
                placeholder="Endpoint, IP, user hoặc API key"
                class="app-focus h-11 rounded-lg border border-slate-300 px-3 text-sm"
            />
            <select v-model="filters.method" class="app-focus h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                <option value="">Mọi method</option>
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PATCH">PATCH</option>
                <option value="DELETE">DELETE</option>
            </select>
            <input
                v-model="filters.status_code"
                inputmode="numeric"
                placeholder="Status code"
                class="app-focus h-11 rounded-lg border border-slate-300 px-3 text-sm"
            />
            <button
                type="submit"
                class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-slate-950 px-4 text-sm font-bold text-white"
            >
                <Search class="h-4 w-4" />Lọc
            </button>
        </form>

        <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">{{ errorMessage }}</div>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="loading" class="p-10 text-center text-sm text-slate-500">Đang tải API usage...</div>
            <div v-else-if="!rows.length" class="p-10 text-center text-sm text-slate-500">Chưa có request phù hợp.</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Thời gian</th>
                            <th class="px-5 py-4">Endpoint</th>
                            <th class="px-5 py-4">User / API key</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Tính phí</th>
                            <th class="px-5 py-4">Latency</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="row in rows" :key="row.id">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                {{ row.created_at ? new Date(row.created_at).toLocaleString('vi-VN') : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="mr-2 rounded bg-slate-100 px-2 py-1 font-mono text-xs font-bold">{{ row.method }}</span
                                ><span class="font-mono text-xs text-slate-700">{{ row.endpoint }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="font-bold text-slate-900">{{ formatCash(Number(row.charged_amount)) }}đ</p>
                                <p class="mt-1 text-xs text-slate-500">{{ row.billing_status }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ row.user?.username ?? 'Guest / service' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ row.api_key?.name ?? row.ip ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClass(row.status_code)">{{
                                    row.status_code ?? '—'
                                }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                {{ row.response_time_ms === null ? '—' : `${row.response_time_ms} ms` }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="meta.last_page > 1" class="flex justify-center gap-2">
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
