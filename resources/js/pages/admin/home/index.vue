<script setup lang="ts">
import { adminAnalyticsService } from '@/services/admin-analytics.service';
import { adminProxyProductService } from '@/services/admin-proxy-product.service';
import { adminProxyProviderService } from '@/services/admin-proxy-provider.service';
import type { AdminAnalyticsResponse } from '@/types/admin-analytics.type';
import { handleErrorResponse } from '@/utils/response';
import {
    Activity,
    ArrowRight,
    BarChart3,
    Boxes,
    CircleAlert,
    Clock3,
    Database,
    FileClock,
    KeyRound,
    PackageCheck,
    RefreshCw,
    ServerCog,
    Settings2,
    ShieldCheck,
    WalletCards,
    Webhook,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

type CatalogItem = {
    id: number;
    is_active: boolean;
};

const loading = ref(true);
const loadError = ref(false);
const analytics = ref<AdminAnalyticsResponse | null>(null);
const providers = ref<CatalogItem[]>([]);
const products = ref<CatalogItem[]>([]);
const providerTotal = ref(0);
const productTotal = ref(0);
const updatedAt = ref<Date | null>(null);

const numberValue = (value: number): string => value.toLocaleString('vi-VN');
const currency = (value: number): string => `${Math.round(value).toLocaleString('vi-VN')} đ`;
const percent = (value: number): string => `${Math.round(value * 10) / 10}%`;
const clampPercent = (value: number): number => Math.min(Math.max(value, 0), 100);

const activeProviderCount = computed(() => providers.value.filter((provider) => provider.is_active).length);
const activeProductCount = computed(() => products.value.filter((product) => product.is_active).length);

const metrics = computed(() => {
    const summary = analytics.value?.summary;

    return [
        {
            label: 'Doanh thu 7 ngày',
            value: summary ? currency(summary.proxy_revenue) : '--',
            note: summary ? `Chi phí nguồn ${currency(summary.provider_cost)}` : 'Đang đồng bộ dữ liệu',
            icon: WalletCards,
            iconClass: 'bg-blue-50 text-blue-700',
        },
        {
            label: 'Lợi nhuận gộp',
            value: summary ? currency(summary.gross_profit) : '--',
            note: summary ? `Biên lợi nhuận ${percent(summary.gross_margin)}` : 'Đang đồng bộ dữ liệu',
            icon: BarChart3,
            iconClass: 'bg-indigo-50 text-indigo-700',
        },
        {
            label: 'Tỷ lệ hoàn thành',
            value: summary ? percent(summary.task_success_rate) : '--',
            note: summary ? `${numberValue(summary.tasks_solved)}/${numberValue(summary.tasks_total)} task đã xử lý` : 'Đang đồng bộ dữ liệu',
            icon: ShieldCheck,
            iconClass: 'bg-cyan-50 text-cyan-700',
        },
        {
            label: 'API request',
            value: summary ? numberValue(summary.api_requests) : '--',
            note: summary ? `Phản hồi trung bình ${Math.round(summary.api_avg_response_ms)} ms` : 'Đang đồng bộ dữ liệu',
            icon: Activity,
            iconClass: 'bg-violet-50 text-violet-700',
        },
    ];
});

const dailyMax = computed(() => {
    const items = analytics.value?.daily_overview ?? [];
    return Math.max(...items.flatMap((item) => [item.revenue, item.profit]), 1);
});

const taskBreakdown = computed(() => {
    const summary = analytics.value?.summary;
    const total = Math.max(summary?.tasks_total ?? 0, 1);

    return [
        {
            label: 'Đã giải',
            value: summary?.tasks_solved ?? 0,
            width: clampPercent(((summary?.tasks_solved ?? 0) / total) * 100),
            barClass: 'bg-blue-600',
        },
        {
            label: 'Đang chờ',
            value: summary?.tasks_pending ?? 0,
            width: clampPercent(((summary?.tasks_pending ?? 0) / total) * 100),
            barClass: 'bg-amber-500',
        },
        {
            label: 'Thất bại',
            value: summary?.tasks_failed ?? 0,
            width: clampPercent(((summary?.tasks_failed ?? 0) / total) * 100),
            barClass: 'bg-rose-500',
        },
    ];
});

const catalogHealth = computed(() => [
    {
        label: 'Nguồn đang hoạt động',
        value: `${activeProviderCount.value}/${providerTotal.value}`,
        width: providerTotal.value > 0 ? clampPercent((activeProviderCount.value / providerTotal.value) * 100) : 0,
        icon: Database,
        iconClass: 'bg-blue-50 text-blue-700',
        barClass: 'bg-blue-600',
    },
    {
        label: 'Sản phẩm đang bán',
        value: `${activeProductCount.value}/${productTotal.value}`,
        width: productTotal.value > 0 ? clampPercent((activeProductCount.value / productTotal.value) * 100) : 0,
        icon: PackageCheck,
        iconClass: 'bg-cyan-50 text-cyan-700',
        barClass: 'bg-cyan-600',
    },
    {
        label: 'Webhook giám sát',
        value: `${analytics.value?.summary.active_webhooks ?? 0}/${analytics.value?.summary.configured_webhooks ?? 0}`,
        width:
            (analytics.value?.summary.configured_webhooks ?? 0) > 0
                ? clampPercent(((analytics.value?.summary.active_webhooks ?? 0) / (analytics.value?.summary.configured_webhooks ?? 1)) * 100)
                : 0,
        icon: Webhook,
        iconClass: 'bg-indigo-50 text-indigo-700',
        barClass: 'bg-indigo-600',
    },
]);

const quickActions = [
    { label: 'Quản lý nguồn', description: 'Kiểm tra kết nối và cấu hình nhà cung cấp.', to: '/admin/proxy-providers', icon: ServerCog },
    { label: 'Sản phẩm proxy', description: 'Cập nhật giá bán, giao thức và trạng thái.', to: '/admin/proxy-products', icon: Boxes },
    { label: 'Nhật ký API', description: 'Đối soát request và response theo từng lượt gọi.', to: '/admin/api-logs', icon: FileClock },
    { label: 'Cấu hình hệ thống', description: 'Thiết lập webhook và các thông số vận hành.', to: '/admin/settings/general', icon: Settings2 },
];

const loadDashboard = async (): Promise<void> => {
    try {
        loading.value = true;
        loadError.value = false;

        const [providerResponse, productResponse, analyticsResponse] = await Promise.all([
            adminProxyProviderService.list({ per_page: 100 }),
            adminProxyProductService.list({ per_page: 100 }),
            adminAnalyticsService.dashboard('7d'),
        ]);

        providers.value = providerResponse.providers?.data ?? [];
        products.value = productResponse.products?.data ?? [];
        providerTotal.value = Number(providerResponse.providers?.total ?? providers.value.length);
        productTotal.value = Number(productResponse.products?.total ?? products.value.length);
        analytics.value = analyticsResponse;
        updatedAt.value = new Date();
    } catch (error) {
        loadError.value = true;
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

onMounted(loadDashboard);
</script>

<template>
    <div class="space-y-5">
        <section
            class="overflow-hidden rounded-[10px] border border-blue-900/20 bg-[radial-gradient(circle_at_top_right,_rgba(56,189,248,0.28),_transparent_30%),linear-gradient(135deg,#071a3d_0%,#0b4bd9_55%,#075985_100%)] p-5 text-white shadow-[0_20px_55px_-34px_rgba(37,99,235,0.65)] sm:p-6"
        >
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-100">Admin workspace</p>
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold text-blue-50"
                        >
                            <span class="h-2 w-2 rounded-full bg-cyan-300" />
                            Dữ liệu vận hành 7 ngày
                        </span>
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-[-0.045em] sm:text-3xl">Trung tâm điều hành hệ thống proxy</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-50/90">
                        Theo dõi doanh thu, hiệu suất xử lý, sức khỏe catalog và các cảnh báo quan trọng tại một màn hình duy nhất.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <p v-if="updatedAt" class="inline-flex items-center gap-2 text-xs text-blue-100">
                        <Clock3 class="h-4 w-4" />
                        Cập nhật {{ updatedAt.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) }}
                    </p>
                    <button
                        type="button"
                        class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-[10px] bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white disabled:cursor-wait disabled:opacity-70"
                        :disabled="loading"
                        @click="loadDashboard"
                    >
                        <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
                        Làm mới dữ liệu
                    </button>
                </div>
            </div>
        </section>

        <section
            v-if="loadError"
            class="flex flex-col gap-4 rounded-[10px] border border-rose-200 bg-rose-50 p-4 sm:flex-row sm:items-center sm:justify-between"
            role="alert"
        >
            <div class="flex items-start gap-3">
                <CircleAlert class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
                <div>
                    <p class="font-semibold text-rose-900">Chưa tải được dữ liệu dashboard</p>
                    <p class="mt-1 text-sm text-rose-700">Kiểm tra kết nối dịch vụ rồi thử tải lại.</p>
                </div>
            </div>
            <button
                type="button"
                class="min-h-11 cursor-pointer rounded-[10px] bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700"
                @click="loadDashboard"
            >
                Thử lại
            </button>
        </section>

        <section v-if="loading && !analytics" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" aria-label="Đang tải chỉ số tổng quan">
            <div v-for="index in 4" :key="index" class="h-32 animate-pulse rounded-[10px] border border-slate-200 bg-slate-100" />
        </section>
        <section v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="metric in metrics"
                :key="metric.label"
                class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ metric.label }}</p>
                        <p class="mt-2 truncate text-2xl font-black tabular-nums tracking-[-0.04em] text-slate-950">{{ metric.value }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ metric.note }}</p>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px]" :class="metric.iconClass">
                        <component :is="metric.icon" class="h-5 w-5" aria-hidden="true" />
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.55fr)]">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-600">Xu hướng tài chính</p>
                        <h2 class="mt-1 text-base font-bold text-slate-950">Doanh thu và lợi nhuận theo ngày</h2>
                        <p class="mt-1 text-xs text-slate-500">So sánh trực tiếp dữ liệu phát sinh trong 7 ngày gần nhất.</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-600" aria-label="Chú thích biểu đồ">
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-sm bg-blue-600" />Doanh thu</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-400" />Lợi nhuận</span>
                    </div>
                </div>

                <div v-if="analytics?.daily_overview.length" class="mt-5 overflow-x-auto pb-1">
                    <div
                        class="flex h-64 min-w-[560px] items-end gap-3 border-b border-slate-200 px-2"
                        role="img"
                        aria-label="Biểu đồ cột doanh thu và lợi nhuận trong 7 ngày"
                    >
                        <div
                            v-for="item in analytics.daily_overview"
                            :key="item.label"
                            class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2 self-stretch"
                        >
                            <div class="flex w-full flex-1 items-end justify-center gap-1.5">
                                <div
                                    class="w-4 rounded-t bg-blue-600 sm:w-5"
                                    :style="{ height: `${Math.max((item.revenue / dailyMax) * 100, item.revenue > 0 ? 5 : 0)}%` }"
                                    :title="`Doanh thu ${currency(item.revenue)}`"
                                />
                                <div
                                    class="w-4 rounded-t bg-indigo-400 sm:w-5"
                                    :style="{ height: `${Math.max((Math.max(item.profit, 0) / dailyMax) * 100, item.profit > 0 ? 5 : 0)}%` }"
                                    :title="`Lợi nhuận ${currency(item.profit)}`"
                                />
                            </div>
                            <div class="pb-3 text-center">
                                <p class="text-[11px] font-semibold text-slate-700">{{ item.label }}</p>
                                <p class="mt-0.5 text-[10px] tabular-nums text-slate-500">{{ currency(item.revenue) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="mt-5 rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-4 py-16 text-center text-sm text-slate-500"
                >
                    Chưa có dữ liệu tài chính trong khoảng thời gian này.
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-indigo-600">Luồng xử lý</p>
                        <h2 class="mt-1 text-base font-bold text-slate-950">Trạng thái task</h2>
                    </div>
                    <div class="rounded-[10px] bg-indigo-50 p-2.5 text-indigo-700"><Activity class="h-5 w-5" /></div>
                </div>

                <div class="mt-5 space-y-5">
                    <div v-for="item in taskBreakdown" :key="item.label">
                        <div class="mb-2 flex items-center justify-between gap-3 text-xs">
                            <span class="font-semibold text-slate-700">{{ item.label }}</span>
                            <span class="font-bold tabular-nums text-slate-950">{{ numberValue(item.value) }}</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full" :class="item.barClass" :style="{ width: `${item.width}%` }" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                    <div>
                        <p class="text-[11px] text-slate-500">Xử lý trung bình</p>
                        <p class="mt-1 text-lg font-black tabular-nums text-slate-950">
                            {{ Math.round(analytics?.summary.avg_processing_seconds ?? 0) }}s
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-500">Người dùng hoạt động</p>
                        <p class="mt-1 text-lg font-black tabular-nums text-slate-950">{{ numberValue(analytics?.summary.users_active ?? 0) }}</p>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-600">Catalog health</p>
                        <h2 class="mt-1 text-base font-bold text-slate-950">Sức khỏe hệ thống</h2>
                    </div>
                    <KeyRound class="h-5 w-5 text-blue-600" />
                </div>
                <div class="mt-5 space-y-4">
                    <div v-for="item in catalogHealth" :key="item.label" class="rounded-[10px] border border-slate-100 bg-slate-50/80 p-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[8px]" :class="item.iconClass">
                                <component :is="item.icon" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="truncate text-xs font-semibold text-slate-700">{{ item.label }}</p>
                                    <p class="text-xs font-bold tabular-nums text-slate-950">{{ item.value }}</p>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-full rounded-full" :class="item.barClass" :style="{ width: `${item.width}%` }" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-indigo-600">Hiệu quả sản phẩm</p>
                        <h2 class="mt-1 text-base font-bold text-slate-950">Dịch vụ doanh thu cao</h2>
                    </div>
                    <RouterLink
                        to="/admin/analytics"
                        class="inline-flex min-h-11 items-center gap-1 text-xs font-semibold text-blue-700 hover:text-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                    >
                        Xem báo cáo <ArrowRight class="h-4 w-4" />
                    </RouterLink>
                </div>
                <div v-if="analytics?.top_services.length" class="mt-4 divide-y divide-slate-100">
                    <div
                        v-for="(service, index) in analytics.top_services.slice(0, 4)"
                        :key="service.product_code"
                        class="flex items-center gap-3 py-3"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[8px] bg-blue-50 text-xs font-bold text-blue-700">{{
                            index + 1
                        }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold uppercase tracking-[0.08em] text-slate-800">{{ service.product_code }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ numberValue(service.total_tasks) }} task</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold tabular-nums text-slate-950">{{ currency(service.revenue) }}</p>
                            <p class="mt-1 text-[11px]" :class="service.profit >= 0 ? 'text-blue-600' : 'text-rose-600'">
                                LN {{ currency(service.profit) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="mt-4 rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-xs text-slate-500"
                >
                    Chưa có dữ liệu dịch vụ.
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-rose-600">Cần chú ý</p>
                        <h2 class="mt-1 text-base font-bold text-slate-950">Task lỗi gần đây</h2>
                    </div>
                    <CircleAlert class="h-5 w-5 text-rose-600" />
                </div>
                <div v-if="analytics?.recent_failed_tasks.length" class="mt-4 space-y-2.5">
                    <div
                        v-for="item in analytics.recent_failed_tasks.slice(0, 3)"
                        :key="item.order_code"
                        class="rounded-[10px] border border-rose-100 bg-rose-50/70 p-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-900">{{ item.order_code }}</p>
                                <p class="mt-1 truncate text-[11px] text-rose-700">{{ item.error_message || 'Không có mô tả lỗi.' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-white px-2 py-1 text-[10px] font-semibold text-rose-700 ring-1 ring-rose-200">{{
                                item.product_code
                            }}</span>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="mt-4 flex min-h-36 flex-col items-center justify-center rounded-[10px] border border-dashed border-blue-200 bg-blue-50/60 px-4 text-center"
                >
                    <ShieldCheck class="h-7 w-7 text-blue-600" />
                    <p class="mt-2 text-sm font-semibold text-slate-900">Không có task lỗi gần đây</p>
                    <p class="mt-1 text-xs text-slate-500">Hệ thống đang xử lý ổn định.</p>
                </div>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-600">Điều hướng nhanh</p>
                    <h2 class="mt-1 text-base font-bold text-slate-950">Các khu vực quản trị thường dùng</h2>
                </div>
                <p class="text-xs text-slate-500">Truy cập trực tiếp vào luồng công việc cần xử lý.</p>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <RouterLink
                    v-for="action in quickActions"
                    :key="action.to"
                    :to="action.to"
                    class="group flex min-h-28 cursor-pointer items-start gap-3 rounded-[10px] border border-slate-200 bg-slate-50/70 p-4 transition duration-200 hover:border-blue-200 hover:bg-blue-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-white text-blue-700 shadow-sm ring-1 ring-slate-200 group-hover:ring-blue-200"
                    >
                        <component :is="action.icon" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="flex items-center gap-1 text-sm font-bold text-slate-900">
                            {{ action.label }} <ArrowRight class="h-4 w-4 text-slate-400 transition group-hover:text-blue-600" />
                        </p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ action.description }}</p>
                    </div>
                </RouterLink>
            </div>
        </section>
    </div>
</template>
