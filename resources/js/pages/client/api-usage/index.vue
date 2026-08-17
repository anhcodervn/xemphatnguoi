<script setup lang="ts">
import { trafficFineService, type ApiUsageDashboard } from '@/services/traffic-fine.service';
import formatCash from '@/utils/helpers/formatCash';
import { Activity, CircleDollarSign, Clock3, ReceiptText } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const usage = ref<ApiUsageDashboard | null>(null);
const loading = ref(true);
const errorMessage = ref('');
const chartMaximum = computed(() => Math.max(1, ...(usage.value?.chart.map((item) => item.requests) ?? [1])));

const load = async (page = 1): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';
    try {
        usage.value = await trafficFineService.apiUsage(page);
    } catch {
        errorMessage.value = 'Không thể tải lịch sử sử dụng API.';
    } finally {
        loading.value = false;
    }
};

const billingLabel = (status: string): string =>
    ({
        charged: 'Đã tính phí',
        insufficient_balance: 'Không đủ số dư',
        not_charged: 'Không tính phí',
        not_billable: 'Không tính phí',
    })[status] ?? status;

onMounted(() => load());
</script>

<template>
    <div class="grid gap-6">
        <header>
            <p class="text-sm font-bold text-sky-700">API Usage</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Lượt tra cứu và chi phí</h1>
            <p class="mt-2 text-sm text-slate-500">Theo dõi từng request API; dữ liệu nhạy cảm và secret không xuất hiện trong log này.</p>
        </header>

        <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">{{ errorMessage }}</div>
        <div v-if="loading && !usage" class="h-72 animate-pulse rounded-xl bg-slate-200" />
        <template v-else-if="usage">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in [
                        { label: 'Giá hiện tại', value: `${formatCash(usage.api_request_price)}đ`, icon: CircleDollarSign },
                        { label: 'Request hôm nay', value: usage.summary.requests_today.toLocaleString('vi-VN'), icon: Activity },
                        { label: 'Request tháng này', value: usage.summary.requests_month.toLocaleString('vi-VN'), icon: ReceiptText },
                        { label: 'Chi phí tháng này', value: `${formatCash(Number(usage.summary.amount_month))}đ`, icon: Clock3 },
                    ]"
                    :key="card.label"
                    class="rounded-xl border border-slate-200 bg-white p-5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-500">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ card.value }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700"
                            ><component :is="card.icon" class="h-5 w-5"
                        /></span>
                    </div>
                </article>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="text-lg font-bold text-slate-950">14 ngày gần nhất</h2>
                <div class="mt-6 flex h-52 items-end gap-2 border-b border-slate-200">
                    <div v-for="item in usage.chart" :key="item.date" class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-[10px] font-bold text-slate-500 opacity-0 group-hover:opacity-100">{{ item.requests }}</span>
                        <div
                            class="w-full max-w-8 rounded-t bg-sky-600"
                            :style="{ height: `${Math.max(4, (item.requests / chartMaximum) * 160)}px` }"
                            :title="`${item.label}: ${item.requests} request`"
                        />
                        <span class="hidden text-[10px] text-slate-400 sm:block">{{ item.label }}</span>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-bold text-slate-950">Log request</h2></div>
                <div v-if="!usage.logs.data.length" class="p-10 text-center text-sm text-slate-500">Chưa có request API.</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Thời gian</th>
                                <th class="px-5 py-4">Biển số</th>
                                <th class="px-5 py-4">Trạng thái</th>
                                <th class="px-5 py-4">Phí</th>
                                <th class="px-5 py-4">Độ trễ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="row in usage.logs.data" :key="row.id">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ new Date(row.created_at).toLocaleString('vi-VN') }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-950">{{ row.plate ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ row.api_key_name ?? 'API key' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="row.billing_status === 'charged' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                        >{{ row.status_code }} · {{ billingLabel(row.billing_status) }}</span
                                    >
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-bold text-slate-900">{{ formatCash(Number(row.charged_amount)) }}đ</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ row.response_time_ms }} ms</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="usage.logs.last_page > 1" class="flex justify-center gap-2 border-t border-slate-200 p-4">
                    <button
                        v-for="page in usage.logs.last_page"
                        :key="page"
                        type="button"
                        class="min-h-10 min-w-10 rounded-lg border text-sm font-bold"
                        :class="page === usage.logs.current_page ? 'border-sky-700 bg-sky-700 text-white' : 'border-slate-300'"
                        @click="load(page)"
                    >
                        {{ page }}
                    </button>
                </div>
            </section>
        </template>
    </div>
</template>
