<script setup lang="ts">
import { adminTrafficFineService, type AdminTrafficFineReport, type AdminTrafficFineReportBreakdown } from '@/services/admin-traffic-fine.service';
import { Activity, AlertTriangle, CalendarDays, Car, CheckCircle2, Clock3, Database, Gauge, RefreshCw, ServerOff } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const periodOptions = [7, 30, 90] as const;
const selectedDays = ref<(typeof periodOptions)[number]>(30);
const report = ref<AdminTrafficFineReport | null>(null);
const loading = ref(true);
const errorMessage = ref('');
const updatedAt = ref<Date | null>(null);

const trendMaximum = computed(() => Math.max(1, ...(report.value?.daily.map((item) => item.total) ?? [1])));
const dailyRows = computed(() => [...(report.value?.daily ?? [])].reverse());
const periodLabel = computed(() => {
    if (!report.value) {
        return '';
    }

    return `${formatDate(report.value.period.from)} – ${formatDate(report.value.period.to)}`;
});

const summaryCards = computed(() => {
    if (!report.value) {
        return [];
    }

    const summary = report.value.summary;

    return [
        {
            label: 'Tổng lượt tra cứu',
            value: formatNumber(summary.total_lookups),
            note: `${formatNumber(summary.unique_plates)} biển số duy nhất`,
            icon: Activity,
            tone: 'bg-sky-50 text-sky-700',
        },
        {
            label: 'Hoàn thành',
            value: formatNumber(summary.completed_lookups),
            note: `${formatPercentage(summary.completion_rate)} yêu cầu`,
            icon: CheckCircle2,
            tone: 'bg-emerald-50 text-emerald-700',
        },
        {
            label: 'Tỷ lệ cache dương',
            value: formatPercentage(summary.cache_hit_rate),
            note: `${formatNumber(summary.cache_hits)} lượt từ Redis/Database`,
            icon: Database,
            tone: 'bg-indigo-50 text-indigo-700',
        },
        {
            label: 'Gọi provider',
            value: formatNumber(summary.provider_requests),
            note: `${formatNumber(summary.cache_misses)} cache miss`,
            icon: Gauge,
            tone: 'bg-amber-50 text-amber-700',
        },
        {
            label: 'Lỗi provider',
            value: formatNumber(summary.provider_errors),
            note: `${formatNumber(summary.negative_cache_hits)} lượt được chặn lặp`,
            icon: ServerOff,
            tone: 'bg-rose-50 text-rose-700',
        },
        {
            label: 'Độ trễ provider',
            value: summary.average_provider_latency_ms === null ? '—' : `${formatNumber(summary.average_provider_latency_ms)} ms`,
            note: 'Trung bình các request tới nguồn',
            icon: Clock3,
            tone: 'bg-slate-100 text-slate-700',
        },
    ];
});

const formatNumber = (value: number): string => value.toLocaleString('vi-VN', { maximumFractionDigits: 2 });
const formatPercentage = (value: number): string => `${formatNumber(value)}%`;
const formatDate = (value: string): string => new Date(`${value}T00:00:00`).toLocaleDateString('vi-VN');
const formatDateTime = (value: string): string => new Date(value).toLocaleString('vi-VN');
const chartHeight = (value: number): string => `${value === 0 ? 2 : Math.max(8, (value / trendMaximum.value) * 184)}px`;

const load = async (): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';

    try {
        report.value = await adminTrafficFineService.report(selectedDays.value);
        updatedAt.value = new Date();
    } catch {
        errorMessage.value = 'Không thể tải báo cáo. Vui lòng thử lại.';
    } finally {
        loading.value = false;
    }
};

const selectPeriod = (days: (typeof periodOptions)[number]): void => {
    if (selectedDays.value === days && report.value) {
        return;
    }

    selectedDays.value = days;
    void load();
};

const breakdownBarWidth = (item: AdminTrafficFineReportBreakdown): string => `${Math.min(100, item.percentage)}%`;

onMounted(load);
</script>

<template>
    <div class="grid gap-5">
        <header class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <div class="flex items-center gap-2 text-sm font-bold text-sky-700">
                        <CalendarDays class="h-4 w-4" aria-hidden="true" />
                        Báo cáo vận hành
                    </div>
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Báo cáo chi tiết tra cứu</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Theo dõi hiệu quả xử lý, cache và chất lượng nguồn dữ liệu từ nhật ký tra cứu thực tế.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div
                        role="group"
                        aria-label="Chọn khoảng thời gian báo cáo"
                        class="inline-flex min-h-11 rounded-lg border border-slate-200 bg-slate-50 p-1"
                    >
                        <button
                            v-for="days in periodOptions"
                            :key="days"
                            type="button"
                            :aria-pressed="selectedDays === days"
                            class="app-focus min-h-9 rounded-md px-3 text-sm font-bold transition-colors"
                            :class="selectedDays === days ? 'bg-white text-sky-700 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                            @click="selectPeriod(days)"
                        >
                            {{ days }} ngày
                        </button>
                    </div>
                    <button
                        type="button"
                        :disabled="loading"
                        class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-slate-950 px-4 text-sm font-bold text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="load"
                    >
                        <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin motion-reduce:animate-none' : ''" aria-hidden="true" />
                        Làm mới
                    </button>
                </div>
            </div>

            <div v-if="report && !loading" class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                <span>{{ periodLabel }}</span>
                <span v-if="updatedAt">Cập nhật lúc {{ updatedAt.toLocaleTimeString('vi-VN') }}</span>
            </div>
        </header>

        <div
            v-if="errorMessage"
            role="alert"
            class="flex flex-col gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-start gap-3 text-sm text-rose-800">
                <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                <span>{{ errorMessage }}</span>
            </div>
            <button type="button" class="app-focus min-h-11 rounded-lg px-4 text-sm font-bold text-rose-800 hover:bg-rose-100" @click="load">
                Thử lại
            </button>
        </div>

        <template v-if="loading">
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6" aria-label="Đang tải số liệu tổng quan">
                <div v-for="index in 6" :key="index" class="h-32 animate-pulse rounded-xl bg-slate-200 motion-reduce:animate-none" />
            </section>
            <div class="h-80 animate-pulse rounded-xl bg-slate-200 motion-reduce:animate-none" />
        </template>

        <template v-else-if="report">
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6" aria-label="Chỉ số báo cáo">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-500">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-black tabular-nums text-slate-950">{{ card.value }}</p>
                        </div>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg" :class="card.tone">
                            <component :is="card.icon" class="h-4.5 w-4.5" aria-hidden="true" />
                        </span>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-500">{{ card.note }}</p>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(280px,0.45fr)]">
                <article class="min-w-0 rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="font-bold text-slate-950">Xu hướng theo ngày</h2>
                            <p class="mt-1 text-sm text-slate-500">Tổng lượt tra cứu; dấu đỏ biểu thị ngày có lỗi provider.</p>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs text-slate-500" aria-label="Chú thích biểu đồ">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-sky-600" />Tra cứu</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500" />Có lỗi</span>
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto pb-2">
                        <div
                            class="flex h-56 min-w-max items-end gap-1 border-b border-slate-200"
                            role="list"
                            aria-label="Biểu đồ lượt tra cứu theo ngày"
                        >
                            <div
                                v-for="(item, index) in report.daily"
                                :key="item.date"
                                role="listitem"
                                class="flex h-full w-7 flex-col items-center justify-end gap-1"
                                :aria-label="`${item.label}: ${item.total} lượt, ${item.provider_errors} lỗi provider`"
                            >
                                <span v-if="item.provider_errors > 0" class="h-2 w-2 rounded-full bg-rose-500" aria-hidden="true" />
                                <div
                                    class="w-4 rounded-t transition-[height] duration-200 motion-reduce:transition-none"
                                    :class="item.total > 0 ? 'bg-sky-600' : 'bg-slate-200'"
                                    :style="{ height: chartHeight(item.total) }"
                                    :title="`${item.label}: ${item.total} lượt · ${item.cache_hits} cache dương · ${item.provider_errors} lỗi`"
                                />
                                <span
                                    class="h-4 text-[9px] tabular-nums text-slate-400"
                                    :class="selectedDays === 7 || index % (selectedDays === 30 ? 5 : 15) === 0 ? 'visible' : 'invisible'"
                                >
                                    {{ item.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                    <h2 class="font-bold text-slate-950">Chất lượng xử lý</h2>
                    <p class="mt-1 text-sm text-slate-500">Cache lỗi được tách riêng khỏi cache kết quả.</p>

                    <dl class="mt-6 grid gap-5">
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <dt class="font-semibold text-slate-700">Hoàn thành</dt>
                                <dd class="font-bold tabular-nums text-emerald-700">{{ formatPercentage(report.summary.completion_rate) }}</dd>
                            </div>
                            <div
                                class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"
                                role="progressbar"
                                aria-label="Tỷ lệ hoàn thành"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                :aria-valuenow="report.summary.completion_rate"
                            >
                                <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${report.summary.completion_rate}%` }" />
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <dt class="font-semibold text-slate-700">Cache dương</dt>
                                <dd class="font-bold tabular-nums text-indigo-700">{{ formatPercentage(report.summary.cache_hit_rate) }}</dd>
                            </div>
                            <div
                                class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"
                                role="progressbar"
                                aria-label="Tỷ lệ cache dương"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                :aria-valuenow="report.summary.cache_hit_rate"
                            >
                                <div class="h-full rounded-full bg-indigo-500" :style="{ width: `${report.summary.cache_hit_rate}%` }" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 border-t border-slate-200 pt-5">
                            <div>
                                <dt class="text-xs text-slate-500">Có vi phạm</dt>
                                <dd class="mt-1 text-lg font-black tabular-nums text-slate-950">
                                    {{ formatNumber(report.summary.violation_lookups) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Không vi phạm</dt>
                                <dd class="mt-1 text-lg font-black tabular-nums text-slate-950">
                                    {{ formatNumber(report.summary.no_violation_lookups) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Negative cache</dt>
                                <dd class="mt-1 text-lg font-black tabular-nums text-rose-700">
                                    {{ formatNumber(report.summary.negative_cache_hits) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Cache miss</dt>
                                <dd class="mt-1 text-lg font-black tabular-nums text-slate-950">{{ formatNumber(report.summary.cache_misses) }}</dd>
                            </div>
                        </div>
                    </dl>
                </article>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                    <div class="flex items-center gap-2">
                        <Car class="h-5 w-5 text-sky-700" aria-hidden="true" />
                        <h2 class="font-bold text-slate-950">Phân bổ loại xe</h2>
                    </div>
                    <div v-if="report.vehicle_types.length" class="mt-5 grid gap-4">
                        <div v-for="item in report.vehicle_types" :key="item.key">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold text-slate-700">{{ item.label }}</span>
                                <span class="tabular-nums text-slate-500"
                                    >{{ formatNumber(item.total) }} · {{ formatPercentage(item.percentage) }}</span
                                >
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-sky-600" :style="{ width: breakdownBarWidth(item) }" />
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-5 text-sm text-slate-500">Chưa có dữ liệu loại xe trong khoảng này.</p>
                </article>

                <article class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                    <div class="flex items-center gap-2">
                        <Database class="h-5 w-5 text-indigo-700" aria-hidden="true" />
                        <h2 class="font-bold text-slate-950">Phân bổ nguồn xử lý</h2>
                    </div>
                    <div v-if="report.sources.length" class="mt-5 grid gap-4">
                        <div v-for="item in report.sources" :key="item.key">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold text-slate-700">{{ item.label }}</span>
                                <span class="tabular-nums text-slate-500"
                                    >{{ formatNumber(item.total) }} · {{ formatPercentage(item.percentage) }}</span
                                >
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-indigo-500" :style="{ width: breakdownBarWidth(item) }" />
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-5 text-sm text-slate-500">Chưa có dữ liệu nguồn xử lý trong khoảng này.</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="flex items-start gap-3 border-b border-slate-200 p-5 sm:p-6">
                    <ServerOff class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" aria-hidden="true" />
                    <div>
                        <h2 class="font-bold text-slate-950">Lỗi gần nhất</h2>
                        <p class="mt-1 text-sm text-slate-500">Tối đa 8 lỗi provider trong khoảng báo cáo.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <caption class="sr-only">
                            Danh sách lỗi provider gần nhất
                        </caption>
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">Thời gian</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Biển số</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Loại xe</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Nguồn</th>
                                <th scope="col" class="px-5 py-3 text-right font-semibold">Độ trễ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="item in report.recent_errors" :key="item.id" class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ formatDateTime(item.created_at) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 font-bold text-slate-950">{{ item.plate }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ item.vehicle_type }}</td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <span class="inline-flex rounded-full bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700">{{
                                        item.source
                                    }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-600">
                                    {{ item.provider_latency_ms === null ? '—' : `${formatNumber(item.provider_latency_ms)} ms` }}
                                </td>
                            </tr>
                            <tr v-if="report.recent_errors.length === 0">
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Không có lỗi provider trong khoảng đã chọn.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <details class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <summary class="app-focus flex min-h-14 cursor-pointer items-center justify-between gap-3 px-5 py-4 font-bold text-slate-950 sm:px-6">
                    Dữ liệu theo ngày
                    <span class="text-xs font-medium text-slate-500">{{ formatNumber(report.daily.length) }} ngày</span>
                </summary>
                <div class="overflow-x-auto border-t border-slate-200">
                    <table class="min-w-full text-left text-sm">
                        <caption class="sr-only">
                            Chi tiết số liệu tra cứu từng ngày
                        </caption>
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">Ngày</th>
                                <th scope="col" class="px-5 py-3 text-right font-semibold">Tổng lượt</th>
                                <th scope="col" class="px-5 py-3 text-right font-semibold">Hoàn thành</th>
                                <th scope="col" class="px-5 py-3 text-right font-semibold">Cache dương</th>
                                <th scope="col" class="px-5 py-3 text-right font-semibold">Negative cache</th>
                                <th scope="col" class="px-5 py-3 text-right font-semibold">Lỗi provider</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="item in dailyRows" :key="item.date" class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-3 font-semibold text-slate-700">{{ formatDate(item.date) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ formatNumber(item.total) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-emerald-700">{{ formatNumber(item.completed) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-indigo-700">{{ formatNumber(item.cache_hits) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-rose-700">{{ formatNumber(item.negative_cache_hits) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-rose-700">{{ formatNumber(item.provider_errors) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>
        </template>
    </div>
</template>
