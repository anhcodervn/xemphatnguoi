<script setup lang="ts">
import Breadcrumb from "@/components/MasterLayouts/Breadcrumb/index.vue";
import { adminAnalyticsService } from "@/services/admin-analytics.service";
import type { AdminAnalyticsResponse, AnalyticsRange } from "@/types/admin-analytics.type";
import { handleErrorResponse, handleSuccessResponse } from "@/utils/response";
import { computed, onMounted, ref } from "vue";

const loading = ref(true);
const testingWebhook = ref<number | null>(null);
const range = ref<AnalyticsRange>("7d");
const analytics = ref<AdminAnalyticsResponse | null>(null);

const currency = (value: number): string => `${Math.round(value).toLocaleString("vi-VN")} đ`;
const numberValue = (value: number): string => value.toLocaleString("vi-VN");
const secondsValue = (value: number): string => `${Math.round(value)}s`;

const summaryCards = computed(() => {
    if (!analytics.value) {
        return [];
    }

    const summary = analytics.value.summary;

    return [
        { label: "Người dùng mới", value: numberValue(summary.users_new), note: `Hoạt động: ${numberValue(summary.users_active)}` },
        { label: "Nạp tiền", value: currency(summary.deposit_success_amount), note: `Tổng ví: ${currency(summary.wallet_recharge_total)}` },
        { label: "Doanh thu", value: currency(summary.proxy_revenue), note: `Chi phí nguồn: ${currency(summary.provider_cost)}` },
        { label: "Lợi nhuận", value: currency(summary.gross_profit), note: `Biên lợi nhuận: ${summary.gross_margin}%` },
        { label: "Tỷ lệ thành công", value: `${summary.task_success_rate}%`, note: `Tốc độ TB: ${secondsValue(summary.avg_processing_seconds)}` },
        { label: "Webhook", value: `${summary.active_webhooks}/${summary.configured_webhooks}`, note: `API log: ${numberValue(summary.api_requests)}` },
    ];
});

const dailyOverviewMax = computed(() => {
    const items = analytics.value?.daily_overview ?? [];

    return {
        deposits: Math.max(...items.map((item) => item.deposits), 1),
        revenue: Math.max(...items.map((item) => item.revenue), 1),
        tasks: Math.max(...items.map((item) => item.tasks_solved), 1),
    };
});

const serviceRevenueMax = computed(() => {
    const items = analytics.value?.top_services ?? [];
    return Math.max(...items.map((item) => item.revenue), 1);
});

const loadAnalytics = async (): Promise<void> => {
    try {
        loading.value = true;
        analytics.value = await adminAnalyticsService.dashboard(range.value);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const sendTestWebhook = async (webhookIndex: number): Promise<void> => {
    try {
        testingWebhook.value = webhookIndex;
        await adminAnalyticsService.testDiscordWebhook({
            webhook_index: webhookIndex,
            event: "test_ping",
        });
        handleSuccessResponse({ data: { status: true, message: "Webhook Discord đã nhận gói kiểm tra." } });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        testingWebhook.value = null;
    }
};

onMounted(async () => {
    await loadAnalytics();
});
</script>

<template>
    <div class="space-y-4">
        <Breadcrumb title="Báo cáo vận hành" description="Theo dõi người dùng, dòng tiền, lãi lỗ và trạng thái webhook." />

        <section class="overflow-hidden rounded-[10px] border border-sky-100 bg-[radial-gradient(circle_at_top_right,_rgba(34,211,238,0.18),_transparent_30%),linear-gradient(135deg,#f8fdff_0%,#ffffff_45%,#eefcff_100%)] p-4 shadow-[0_12px_30px_rgba(14,116,144,0.08)]">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl space-y-2">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-sky-600">Admin analytics</p>
                    <h1 class="text-2xl font-black tracking-[-0.03em] text-slate-950">Thống kê vận hành hệ thống proxy</h1>
                    <p class="text-sm leading-6 text-slate-600">Theo dõi tăng trưởng, doanh thu, hiệu suất xử lý và cảnh báo vận hành theo thời gian thực.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="option in analytics?.filters.ranges ?? []"
                        :key="option.value"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                        :class="range === option.value ? 'bg-sky-500 text-white shadow-[0_8px_18px_rgba(14,165,233,0.22)]' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-slate-900'"
                        @click="
                            range = option.value;
                            loadAnalytics();
                        "
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>
        </section>

        <section v-if="loading" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <div v-for="index in 6" :key="index" class="h-24 animate-pulse rounded-[10px] bg-slate-100" />
        </section>

        <template v-else-if="analytics">
            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)]"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ card.label }}</p>
                    <p class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">{{ card.value }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ card.note }}</p>
                </article>
            </section>

            <section class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Biểu đồ hiệu suất theo ngày</h2>
                            <p class="text-xs text-slate-500">So sánh nạp tiền, doanh thu và số task đã giải.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-[8px] border border-sky-100 bg-sky-50/70 p-3">
                            <div class="mb-3 flex items-center justify-between text-xs">
                                <span class="font-semibold text-sky-700">Nạp tiền</span>
                                <span class="text-slate-500">Max {{ currency(dailyOverviewMax.deposits) }}</span>
                            </div>

                            <div class="flex h-40 items-end gap-2">
                                <div v-for="item in analytics.daily_overview" :key="`deposit-${item.label}`" class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                    <div class="flex h-28 w-full items-end rounded-[8px] bg-white/80 px-1 py-1">
                                        <div
                                            class="w-full rounded-[6px] bg-gradient-to-t from-sky-500 to-cyan-300"
                                            :style="{ height: `${Math.max((item.deposits / dailyOverviewMax.deposits) * 100, item.deposits > 0 ? 10 : 0)}%` }"
                                        />
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[11px] font-semibold text-slate-700">{{ item.label }}</p>
                                        <p class="text-[10px] text-slate-500">{{ currency(item.deposits) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[8px] border border-emerald-100 bg-emerald-50/70 p-3">
                            <div class="mb-3 flex items-center justify-between text-xs">
                                <span class="font-semibold text-emerald-700">Doanh thu</span>
                                <span class="text-slate-500">Max {{ currency(dailyOverviewMax.revenue) }}</span>
                            </div>

                            <div class="flex h-40 items-end gap-2">
                                <div v-for="item in analytics.daily_overview" :key="`revenue-${item.label}`" class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                    <div class="flex h-28 w-full items-end rounded-[8px] bg-white/80 px-1 py-1">
                                        <div
                                            class="w-full rounded-[6px] bg-gradient-to-t from-emerald-500 to-lime-300"
                                            :style="{ height: `${Math.max((item.revenue / dailyOverviewMax.revenue) * 100, item.revenue > 0 ? 10 : 0)}%` }"
                                        />
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[11px] font-semibold text-slate-700">{{ item.label }}</p>
                                        <p class="text-[10px] text-slate-500">{{ currency(item.revenue) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[8px] border border-violet-100 bg-violet-50/70 p-3">
                            <div class="mb-3 flex items-center justify-between text-xs">
                                <span class="font-semibold text-violet-700">Task đã giải</span>
                                <span class="text-slate-500">Max {{ numberValue(dailyOverviewMax.tasks) }}</span>
                            </div>

                            <div class="flex h-40 items-end gap-2">
                                <div v-for="item in analytics.daily_overview" :key="`tasks-${item.label}`" class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                    <div class="flex h-28 w-full items-end rounded-[8px] bg-white/80 px-1 py-1">
                                        <div
                                            class="w-full rounded-[6px] bg-gradient-to-t from-violet-500 to-fuchsia-300"
                                            :style="{ height: `${Math.max((item.tasks_solved / dailyOverviewMax.tasks) * 100, item.tasks_solved > 0 ? 10 : 0)}%` }"
                                        />
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[11px] font-semibold text-slate-700">{{ item.label }}</p>
                                        <p class="text-[10px] text-slate-500">{{ numberValue(item.tasks_solved) }} task</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
                    <h2 class="text-sm font-bold text-slate-900">Top dịch vụ theo doanh thu</h2>
                    <p class="mt-1 text-xs text-slate-500">Biểu đồ đóng góp của từng service.</p>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="service in analytics.top_services"
                            :key="service.product_code"
                            class="rounded-[8px] border border-slate-100 bg-slate-50/80 p-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-600">{{ service.product_code }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ numberValue(service.total_tasks) }} task</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-slate-900">{{ currency(service.revenue) }}</p>
                                    <p class="text-[11px]" :class="service.profit >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                        LN {{ currency(service.profit) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-sky-500 via-cyan-400 to-emerald-400"
                                    :style="{ width: `${Math.max((service.revenue / serviceRevenueMax) * 100, service.revenue > 0 ? 8 : 0)}%` }"
                                />
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                                <span>Chi phí nguồn: {{ currency(service.cost) }}</span>
                                <span>{{ Math.round((service.revenue / serviceRevenueMax) * 100) || 0 }}%</span>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
                    <h2 class="text-sm font-bold text-slate-900">Task thất bại gần đây</h2>
                    <p class="mt-1 text-xs text-slate-500">Các lỗi mới nhất để theo dõi nhanh.</p>

                    <div class="mt-4 space-y-2.5">
                        <div v-for="item in analytics.recent_failed_tasks" :key="item.order_code" class="rounded-[8px] border border-rose-100 bg-rose-50/70 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-600">{{ item.product_code }}</p>
                                    <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ item.order_code }}</p>
                                </div>
                                <p class="shrink-0 text-[11px] text-slate-500">{{ item.created_at ? new Date(item.created_at).toLocaleString("vi-VN") : "--" }}</p>
                            </div>
                            <p class="mt-2 text-xs text-slate-600">User: {{ item.user || "--" }}</p>
                            <p class="mt-1 text-xs text-rose-700">{{ item.error_message || "Không có thông tin lỗi." }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
                    <h2 class="text-sm font-bold text-slate-900">Discord webhook giám sát</h2>
                    <p class="mt-1 text-xs text-slate-500">Danh sách webhook đang theo dõi hệ thống.</p>

                    <div class="mt-4 space-y-2.5">
                        <div
                            v-for="(webhook, index) in analytics.discord.webhooks"
                            :key="`${webhook.name}-${index}`"
                            class="rounded-[8px] border border-slate-100 bg-slate-50/80 p-3"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ webhook.name }}</p>
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="webhook.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-200 text-slate-600'"
                                        >
                                            {{ webhook.is_active ? "Đang bật" : "Đang tắt" }}
                                        </span>
                                    </div>
                                    <p class="mt-1 truncate text-[11px] text-slate-500">{{ webhook.url }}</p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="event in webhook.events"
                                            :key="event"
                                            class="rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-sky-100"
                                        >
                                            {{ event }}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-[8px] bg-sky-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-sky-600 disabled:opacity-60"
                                    :disabled="testingWebhook === index"
                                    @click="sendTestWebhook(index)"
                                >
                                    {{ testingWebhook === index ? "Đang gửi..." : "Gửi test" }}
                                </button>
                            </div>
                        </div>

                        <div v-if="analytics.discord.webhooks.length === 0" class="rounded-[8px] border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-xs text-slate-500">
                            Chưa có webhook Discord nào được cấu hình.
                        </div>
                    </div>
                </article>
            </section>
        </template>
    </div>
</template>
