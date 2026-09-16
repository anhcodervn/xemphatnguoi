<script setup lang="ts">
import { adminTrafficFineService, type AdminTrafficFineMetrics } from '@/services/admin-traffic-fine.service';
import formatCash from '@/utils/helpers/formatCash';
import { Activity, CircleDollarSign, Clock3, Landmark, PackageOpen, RefreshCw, TrendingDown, TrendingUp, Zap } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const metrics = ref<AdminTrafficFineMetrics | null>(null);
const loading = ref(true);
const errorMessage = ref('');
const updatedAt = ref<Date | null>(null);
const chartMaximum = computed(() => Math.max(1, ...(metrics.value?.api_chart.flatMap((item) => [Number(item.amount), Number(item.cost)]) ?? [1])));

const load = async (): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';
    try {
        metrics.value = await adminTrafficFineService.overview();
        updatedAt.value = new Date();
    } catch {
        errorMessage.value = 'Không thể tải thống kê hệ thống.';
    } finally {
        loading.value = false;
    }
};
onMounted(load);
</script>

<template>
    <div class="grid gap-6">
        <header class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-sky-700">Admin</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Tổng quan API</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Thu nhập chỉ tính từ lượt tra cứu API đã thu phí; tiền nạp ví và lượt tra cứu website miễn phí không được tính.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span v-if="updatedAt" class="text-xs text-slate-500"
                    ><Clock3 class="mr-1 inline h-4 w-4" />{{ updatedAt.toLocaleTimeString('vi-VN') }}</span
                ><button
                    type="button"
                    :disabled="loading"
                    class="app-focus inline-flex min-h-11 items-center gap-2 rounded-lg bg-slate-950 px-4 text-sm font-bold text-white disabled:opacity-60"
                    @click="load"
                >
                    <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />Làm mới
                </button>
            </div>
        </header>
        <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">{{ errorMessage }}</div>
        <div v-if="loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="i in 8" :key="i" class="h-28 animate-pulse rounded-xl bg-slate-200" />
        </div>
        <template v-else-if="metrics">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="item in [
                        { label: 'Lượt trả phí hôm nay', value: metrics.api_requests_today.toLocaleString('vi-VN'), icon: Activity },
                        { label: 'Lượt trả phí tháng', value: metrics.api_requests_month.toLocaleString('vi-VN'), icon: Zap },
                        { label: 'Doanh thu hôm nay', value: `${formatCash(Number(metrics.api_revenue_today))}đ`, icon: TrendingUp },
                        { label: 'Lợi nhuận hôm nay', value: `${formatCash(Number(metrics.api_profit_today))}đ`, icon: CircleDollarSign },
                        { label: 'Doanh thu tháng', value: `${formatCash(Number(metrics.api_revenue_month))}đ`, icon: TrendingUp },
                        { label: 'Cost tháng', value: `${formatCash(Number(metrics.api_cost_month))}đ`, icon: TrendingDown },
                        { label: 'Lợi nhuận tháng', value: `${formatCash(Number(metrics.api_profit_month))}đ`, icon: CircleDollarSign },
                        { label: 'Tổng lợi nhuận API', value: `${formatCash(Number(metrics.api_profit_total))}đ`, icon: CircleDollarSign },
                    ]"
                    :key="item.label"
                    class="rounded-xl border border-slate-200 bg-white p-5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-500">{{ item.label }}</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ item.value }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700"
                            ><component :is="item.icon" class="h-5 w-5"
                        /></span>
                    </div>
                </article>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-xl border border-violet-200 bg-violet-50/40 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-violet-700">Doanh thu gói theo dõi</p>
                            <p class="mt-2 text-3xl font-black text-slate-950">{{ formatCash(Number(metrics.package_revenue.month)) }}đ</p>
                            <p class="mt-1 text-xs text-slate-500">Tổng doanh thu gói trong tháng</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                            <PackageOpen class="h-6 w-6" />
                        </span>
                    </div>
                    <dl class="mt-6 grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg border border-violet-100 bg-white p-3">
                            <dt class="text-xs text-slate-500">Hôm nay</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ formatCash(Number(metrics.package_revenue.today)) }}đ</dd>
                        </div>
                        <div class="rounded-lg border border-violet-100 bg-white p-3">
                            <dt class="text-xs text-slate-500">Tuần này</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ formatCash(Number(metrics.package_revenue.week)) }}đ</dd>
                        </div>
                        <div class="rounded-lg border border-violet-100 bg-white p-3">
                            <dt class="text-xs text-slate-500">Toàn bộ</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ formatCash(Number(metrics.package_revenue.total)) }}đ</dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-emerald-700">Tổng tiền khách nạp</p>
                            <p class="mt-2 text-3xl font-black text-slate-950">{{ formatCash(Number(metrics.wallet_deposits.month)) }}đ</p>
                            <p class="mt-1 text-xs text-slate-500">Chỉ tính giao dịch đã cộng tiền vào ví</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <Landmark class="h-6 w-6" />
                        </span>
                    </div>
                    <dl class="mt-6 grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg border border-emerald-100 bg-white p-3">
                            <dt class="text-xs text-slate-500">Hôm nay</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ formatCash(Number(metrics.wallet_deposits.today)) }}đ</dd>
                        </div>
                        <div class="rounded-lg border border-emerald-100 bg-white p-3">
                            <dt class="text-xs text-slate-500">Tuần này</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ formatCash(Number(metrics.wallet_deposits.week)) }}đ</dd>
                        </div>
                        <div class="rounded-lg border border-emerald-100 bg-white p-3">
                            <dt class="text-xs text-slate-500">Tháng này</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ formatCash(Number(metrics.wallet_deposits.month)) }}đ</dd>
                        </div>
                    </dl>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.4fr_0.6fr]">
                <article class="rounded-xl border border-slate-200 bg-white p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-bold text-slate-950">Thu nhập từ lượt API trả phí</h2>
                            <p class="mt-1 text-sm text-slate-500">So sánh doanh thu và cost trong 14 ngày gần nhất</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="font-bold text-sky-700">Doanh thu {{ formatCash(Number(metrics.api_revenue_total)) }}đ</p>
                            <p class="mt-1 font-bold text-emerald-700">Lợi nhuận {{ formatCash(Number(metrics.api_profit_total)) }}đ</p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-4 text-xs font-semibold text-slate-600">
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sky-600" />Doanh thu</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-500" />Cost</span>
                    </div>
                    <div class="mt-6 flex h-56 items-end gap-2 border-b border-slate-200">
                        <div
                            v-for="item in metrics.api_chart"
                            :key="item.date"
                            class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-2"
                        >
                            <span class="text-[10px] font-bold text-slate-500 opacity-0 group-hover:opacity-100">{{ item.requests }} lượt</span>
                            <div class="flex h-[170px] w-full items-end justify-center gap-0.5">
                                <div
                                    class="w-full max-w-4 rounded-t bg-sky-600"
                                    :style="{ height: `${Math.max(3, (Number(item.amount) / chartMaximum) * 170)}px` }"
                                    :title="`${item.label}: doanh thu ${formatCash(Number(item.amount))}đ`"
                                />
                                <div
                                    class="w-full max-w-4 rounded-t bg-amber-500"
                                    :style="{ height: `${Math.max(3, (Number(item.cost) / chartMaximum) * 170)}px` }"
                                    :title="`${item.label}: cost ${formatCash(Number(item.cost))}đ · lợi nhuận ${formatCash(Number(item.profit))}đ`"
                                />
                            </div>
                            <span class="hidden text-[10px] text-slate-400 sm:block">{{ item.label }}</span>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-6">
                    <h2 class="font-bold text-slate-950">Hiệu năng nguồn dữ liệu</h2>
                    <dl class="mt-5 grid gap-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Tổng lượt API trả phí</dt>
                            <dd class="font-bold">{{ metrics.api_paid_requests_total.toLocaleString('vi-VN') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Lookup hôm nay</dt>
                            <dd class="font-bold">{{ metrics.lookup_today.toLocaleString('vi-VN') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Cache hit</dt>
                            <dd class="font-bold">{{ metrics.cache_hits.toLocaleString('vi-VN') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Provider requests</dt>
                            <dd class="font-bold">{{ metrics.provider_requests.toLocaleString('vi-VN') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Provider errors</dt>
                            <dd class="font-bold">{{ metrics.provider_errors.toLocaleString('vi-VN') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Users</dt>
                            <dd class="font-bold">{{ metrics.users.toLocaleString('vi-VN') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Cache miss</dt>
                            <dd class="font-bold">{{ metrics.cache_misses.toLocaleString('vi-VN') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Độ trễ TB</dt>
                            <dd class="font-bold">
                                {{
                                    metrics.average_provider_latency_ms === null
                                        ? '—'
                                        : `${metrics.average_provider_latency_ms.toLocaleString('vi-VN')} ms`
                                }}
                            </dd>
                        </div>
                    </dl>
                </article>
            </section>
        </template>
    </div>
</template>
