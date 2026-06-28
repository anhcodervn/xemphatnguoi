<script setup lang="ts">
import { clientCronAlertService } from '@/services/client-cron-alert.service';
import { clientCronJobService, type CronJobLogItem, type CronJobResultStatus } from '@/services/client-cron-job.service';
import { clientNotificationService } from '@/services/client-notification.service';
import { useUserStore } from '@/stores/user.store';
import type { ClientNotificationItem } from '@/types/client-notification.type';
import {
    Activity,
    AlarmClockCheck,
    Bell,
    BellRing,
    ChevronRight,
    CircleAlert,
    CircleCheckBig,
    Clock3,
    Cpu,
    Database,
    HardDrive,
    Package,
    Play,
    Plus,
    RadioTower,
    ReceiptText,
    Sparkles,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { handleErrorResponse } from '@/utils/response';

const userStore = useUserStore();
const loading = ref(true);
const notificationsLoading = ref(false);
const summary = ref({
    total_jobs: 0,
    active_jobs: 0,
    runs_today: 0,
    runs_month: 0,
    failed_today: 0,
    quota: {
        daily: null as number | null,
        monthly: null as number | null,
    },
});
const recentLogs = ref<CronJobLogItem[]>([]);
const notifications = ref<ClientNotificationItem[]>([]);
const alertChannelsCount = ref(0);

const currentSubscription = computed(() => userStore.user?.user_subscriptions ?? null);
const packageLimits = computed(() => currentSubscription.value?.package_limits ?? currentSubscription.value?.package?.package_limits ?? null);

const displayName = computed(() => userStore.displayName || 'bạn');
const packageName = computed(() => currentSubscription.value?.package_name || 'Chưa có gói');

const percent = (value: number, max: number | null | undefined): number => {
    if (!max || max <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, Math.round((value / max) * 100)));
};

const packageProgress = computed(() => [
    {
        label: 'Cron Jobs',
        value: `${summary.value.total_jobs} / ${packageLimits.value?.max_cron_jobs ?? 0}`,
        percent: percent(summary.value.total_jobs, packageLimits.value?.max_cron_jobs),
    },
    {
        label: 'Quota tháng',
        value: summary.value.quota.monthly ? `${summary.value.runs_month} / ${summary.value.quota.monthly}` : `${summary.value.runs_month} / Unlimited`,
        percent: percent(summary.value.runs_month, summary.value.quota.monthly),
    },
    {
        label: 'Kênh cảnh báo',
        value: `${alertChannelsCount.value} / ${packageLimits.value?.max_alert_channels ?? 0}`,
        percent: percent(alertChannelsCount.value, packageLimits.value?.max_alert_channels),
    },
]);

const successRate = computed(() => {
    if (summary.value.runs_today === 0) {
        return summary.value.failed_today === 0 ? 100 : 0;
    }

    const successRuns = Math.max(summary.value.runs_today - summary.value.failed_today, 0);
    return Number(((successRuns / summary.value.runs_today) * 100).toFixed(2));
});

const metrics = computed(() => [
    {
        label: 'Cron đang hoạt động',
        value: summary.value.active_jobs,
        caption: `${summary.value.total_jobs} cron jobs trong workspace`,
        icon: Play,
        iconClass: 'bg-blue-50 text-blue-600 ring-1 ring-blue-100',
    },
    {
        label: 'Runs hôm nay',
        value: summary.value.runs_today,
        caption: summary.value.quota.daily ? `Quota ngày ${summary.value.quota.daily}` : 'Quota ngày không giới hạn',
        icon: Activity,
        iconClass: 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100',
    },
    {
        label: 'Runs tháng này',
        value: summary.value.runs_month,
        caption: summary.value.quota.monthly ? `Quota tháng ${summary.value.quota.monthly}` : 'Quota tháng không giới hạn',
        icon: ReceiptText,
        iconClass: 'bg-violet-50 text-violet-600 ring-1 ring-violet-100',
    },
    {
        label: 'Tỉ lệ thành công',
        value: `${successRate.value}%`,
        caption: summary.value.failed_today > 0 ? `${summary.value.failed_today} lỗi mới cần kiểm tra` : 'Không có lỗi mới hôm nay',
        icon: CircleCheckBig,
        iconClass: 'bg-teal-50 text-teal-600 ring-1 ring-teal-100',
    },
    {
        label: 'Cảnh báo đang bật',
        value: alertChannelsCount.value,
        caption: packageLimits.value?.allow_alerts ? 'Sẵn sàng gửi cảnh báo realtime' : 'Gói hiện tại chưa hỗ trợ alerts',
        icon: BellRing,
        iconClass: 'bg-amber-50 text-amber-600 ring-1 ring-amber-100',
    },
]);

const quickActions = computed(() => [
    {
        title: 'Tạo cron job',
        description: 'Thêm task HTTP mới',
        to: '/cron-jobs/create',
        icon: Plus,
        iconClass: 'bg-blue-50 text-blue-600',
    },
    {
        title: 'Xem cron jobs',
        description: 'Quản lý tác vụ',
        to: '/cron-jobs',
        icon: AlarmClockCheck,
        iconClass: 'bg-violet-50 text-violet-600',
    },
    {
        title: 'Quản lý cảnh báo',
        description: 'Kênh & quy tắc',
        to: '/alerts',
        icon: Bell,
        iconClass: 'bg-amber-50 text-amber-600',
    },
    {
        title: 'Xem logs',
        description: 'Nhật ký chạy',
        to: '/logs',
        icon: ReceiptText,
        iconClass: 'bg-emerald-50 text-emerald-600',
    },
]);

const systemStatusItems = computed(() => [
    {
        label: 'API & Scheduler',
        detail: 'Kết nối dashboard ổn định',
        state: 'Hoạt động',
        icon: RadioTower,
    },
    {
        label: 'Workers',
        detail: summary.value.failed_today > 0 ? 'Có job cần kiểm tra thêm' : 'Không phát hiện lỗi mới',
        state: summary.value.failed_today > 0 ? 'Theo dõi' : 'Hoạt động',
        icon: Cpu,
    },
    {
        label: 'Quota package',
        detail: summary.value.quota.monthly ? `${summary.value.runs_month}/${summary.value.quota.monthly} runs tháng này` : 'Quota tháng không giới hạn',
        state: 'Sẵn sàng',
        icon: Package,
    },
    {
        label: 'Logs / job',
        detail: `${packageLimits.value?.max_logs_per_job ?? 0} logs mỗi job`,
        state: 'Đã cấu hình',
        icon: HardDrive,
    },
]);

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '--';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat('vi-VN', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          }).format(date);
};

const formatRelativeTime = (value: string | null): string => {
    if (!value) {
        return '--';
    }

    const date = new Date(value);
    const diffMinutes = Math.round((date.getTime() - Date.now()) / 60000);

    if (Number.isNaN(diffMinutes)) {
        return value;
    }

    const rtf = new Intl.RelativeTimeFormat('vi', { numeric: 'auto' });

    if (Math.abs(diffMinutes) < 60) {
        return rtf.format(diffMinutes, 'minute');
    }

    const diffHours = Math.round(diffMinutes / 60);

    if (Math.abs(diffHours) < 24) {
        return rtf.format(diffHours, 'hour');
    }

    return rtf.format(Math.round(diffHours / 24), 'day');
};

const formatDuration = (durationMs: number | null): string => {
    if (durationMs === null) {
        return '--';
    }

    if (durationMs < 1000) {
        return `${durationMs} ms`;
    }

    return `${(durationMs / 1000).toFixed(durationMs >= 10000 ? 0 : 2)} s`;
};

const statusBadgeClass = (status: CronJobResultStatus): string => {
    switch (status) {
        case 'success':
            return 'bg-emerald-100 text-emerald-700';
        case 'failed':
            return 'bg-rose-100 text-rose-700';
        case 'timeout':
            return 'bg-amber-100 text-amber-700';
        case 'blocked':
            return 'bg-slate-200 text-slate-700';
        default:
            return 'bg-orange-100 text-orange-700';
    }
};

const notificationBadgeClass = (item: ClientNotificationItem): string => {
    if (!item.is_read) {
        return 'bg-violet-100 text-violet-700';
    }

    if ((item.type || '').toLowerCase().includes('warning')) {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-emerald-100 text-emerald-700';
};

const notificationBadgeText = (item: ClientNotificationItem): string => {
    if (!item.is_read) {
        return 'Mới';
    }

    return item.type ? item.type : 'Đã cập nhật';
};

const openNotification = async (item: ClientNotificationItem): Promise<void> => {
    if (!item.is_read) {
        try {
            await clientNotificationService.markRead(item.id);
            item.is_read = true;
        } catch (error) {
            handleErrorResponse(error);
        }
    }

    if (item.redirect_url) {
        window.location.href = item.redirect_url;
    }
};

const loadDashboard = async (): Promise<void> => {
    loading.value = true;
    notificationsLoading.value = true;

    try {
        if (!userStore.user) {
            await userStore.bootstrap({ silent: true });
        }

        const [dashboardResponse, logsResponse, alertsResponse, notificationsResponse] = await Promise.all([
            clientCronJobService.list({ per_page: 1 }),
            clientCronJobService.globalLogs({ per_page: 5 }),
            clientCronAlertService.list({ per_page: 50 }),
            clientNotificationService.list({ scope: 'system', per_page: 3 }),
        ]);

        summary.value = dashboardResponse.summary;
        recentLogs.value = logsResponse.data;
        alertChannelsCount.value = alertsResponse.data.filter((channel) => channel.is_enabled).length;
        notifications.value = notificationsResponse.data;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
        notificationsLoading.value = false;
    }
};

onMounted(async () => {
    await loadDashboard();
});
</script>

<template>
    <div class="space-y-5 pb-4">
        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_330px]">
            <article class="relative overflow-hidden rounded-[10px] border border-blue-100 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.16),_transparent_38%),linear-gradient(135deg,#ffffff_0%,#f7fbff_42%,#eef5ff_100%)] p-4 shadow-[0_24px_60px_-32px_rgba(37,99,235,0.38)] sm:p-6 lg:p-8">
                <div class="absolute -left-16 top-10 h-40 w-40 rounded-full bg-blue-200/30 blur-3xl"></div>
                <div class="absolute right-10 top-10 h-20 w-20 rounded-full bg-sky-200/40 blur-2xl"></div>
                <div class="relative grid gap-8 xl:grid-cols-[minmax(0,1fr)_240px] xl:items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-white/90 px-3 py-1.5 text-xs font-semibold text-blue-700 shadow-sm">
                            <Sparkles class="h-3.5 w-3.5" />
                            Xin chào, {{ displayName }}
                        </div>

                        <h1 class="mt-4 max-w-3xl text-3xl font-black tracking-[-0.05em] text-slate-950 sm:text-[2.75rem]">
                            Quản lý cron jobs, quota và theo dõi hệ thống trong một dashboard rõ ràng hơn.
                        </h1>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-[15px]">
                            Theo dõi hiệu suất, kiểm tra logs gần đây và nắm nhanh tình trạng gói dịch vụ để xử lý tác vụ định kỳ mà không phải đi qua nhiều màn hình.
                        </p>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <RouterLink
                                to="/cron-jobs/create"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-[10px] bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_32px_-16px_rgba(37,99,235,0.75)] transition hover:bg-blue-500 sm:w-auto"
                            >
                                <Plus class="h-4 w-4" />
                                Tạo cron job mới
                            </RouterLink>
                            <RouterLink
                                to="/alerts"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-[10px] border border-slate-200 bg-white/90 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto"
                            >
                                <BellRing class="h-4 w-4" />
                                Quản lý cảnh báo
                            </RouterLink>
                        </div>
                    </div>

                    <div class="relative hidden xl:block">
                        <div class="mx-auto flex h-52 w-52 items-center justify-center rounded-[10px] border border-white/70 bg-white/80 shadow-[0_24px_60px_-34px_rgba(15,23,42,0.45)] backdrop-blur">
                            <div class="flex h-24 w-24 items-center justify-center rounded-[10px] bg-blue-50 text-blue-600 shadow-inner">
                                <Clock3 class="h-11 w-11" />
                            </div>
                        </div>
                        <div class="absolute -right-2 top-12 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg">
                            <CircleCheckBig class="h-6 w-6" />
                        </div>
                        <div class="absolute left-0 top-5 h-3 w-3 rounded-full bg-blue-200"></div>
                        <div class="absolute left-10 top-36 h-2.5 w-2.5 rounded-full bg-sky-200"></div>
                        <div class="absolute right-12 bottom-6 h-3 w-3 rounded-full bg-violet-200"></div>
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_24px_50px_-36px_rgba(15,23,42,0.4)] sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Gói hiện tại</p>
                        <h2 class="mt-3 text-[1.9rem] font-black tracking-[-0.05em] text-slate-950">{{ packageName }}</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ currentSubscription?.expires_at ? `Gia hạn: ${formatDateTime(currentSubscription.expires_at)}` : 'Chưa có subscription hoạt động' }}
                        </p>
                    </div>
                    <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">
                        {{ currentSubscription?.status || 'Free' }}
                    </span>
                </div>

                <div class="mt-6 space-y-4">
                    <div v-for="item in packageProgress" :key="item.label">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-slate-600">{{ item.label }}</span>
                            <span class="font-semibold text-slate-900">{{ item.value }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-gradient-to-r from-blue-500 to-sky-400" :style="{ width: `${item.percent}%` }"></div>
                        </div>
                    </div>
                </div>

                <RouterLink
                    to="/package"
                    class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-[10px] border border-slate-200 px-4 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-50"
                >
                    <Package class="h-4 w-4" />
                    Quản lý gói dịch vụ
                </RouterLink>
            </article>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article
                v-for="metric in metrics"
                :key="metric.label"
                class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_20px_45px_-34px_rgba(15,23,42,0.38)]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ metric.label }}</p>
                        <p class="mt-3 text-[1.85rem] font-black tracking-[-0.05em] text-slate-950">
                            {{ loading ? '--' : metric.value }}
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-[10px]" :class="metric.iconClass">
                        <component :is="metric.icon" class="h-5 w-5" />
                    </div>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-500">{{ metric.caption }}</p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_330px]">
            <div class="space-y-5">
                <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-[0_24px_50px_-36px_rgba(15,23,42,0.38)] sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-blue-50 text-blue-600">
                                <Bell class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold tracking-[-0.03em] text-slate-950">Thông báo hệ thống</h2>
                                <p class="mt-1 text-sm text-slate-500">Các cập nhật mới nhất về hệ thống, cảnh báo và thay đổi tính năng.</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="notificationsLoading" class="mt-5 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                        Đang tải thông báo...
                    </div>

                    <div v-else-if="notifications.length === 0" class="mt-5 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                        Chưa có thông báo hệ thống nào.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <button
                            v-for="item in notifications"
                            :key="item.id"
                            type="button"
                            class="flex w-full flex-col gap-3 rounded-[10px] border border-slate-200 bg-slate-50/80 px-4 py-4 text-left transition hover:border-blue-200 hover:bg-blue-50/40 sm:flex-row sm:items-start sm:justify-between"
                            @click="openNotification(item)"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="notificationBadgeClass(item)">
                                        {{ notificationBadgeText(item) }}
                                    </span>
                                    <span class="text-xs text-slate-400">{{ formatRelativeTime(item.created_at) }}</span>
                                </div>
                                <p class="mt-3 text-base font-bold text-slate-950">{{ item.title }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ item.content }}</p>
                            </div>
                            <div class="shrink-0 text-xs font-medium text-slate-400">
                                {{ formatDateTime(item.created_at) }}
                            </div>
                        </button>
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-[0_24px_50px_-36px_rgba(15,23,42,0.38)] sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold tracking-[-0.03em] text-slate-950">Nhật ký gần đây</h2>
                            <p class="mt-1 text-sm text-slate-500">Các lần chạy mới nhất để bạn kiểm tra nhanh tình trạng cron jobs.</p>
                        </div>
                        <RouterLink to="/logs" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-700">
                            Xem tất cả logs
                            <ChevronRight class="h-4 w-4" />
                        </RouterLink>
                    </div>

                    <div v-if="loading" class="mt-5 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                        Đang tải logs...
                    </div>

                    <div v-else-if="recentLogs.length === 0" class="mt-5 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                        Chưa có log chạy nào.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <div class="space-y-3 md:hidden">
                            <article v-for="log in recentLogs" :key="`mobile-${log.id}`" class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(log.status)">
                                        {{ log.status }}
                                    </span>
                                    <span class="text-xs font-medium text-slate-500">{{ log.method }}</span>
                                    <span class="text-xs text-slate-400">{{ formatDateTime(log.started_at) }}</span>
                                </div>
                                <p class="mt-3 break-all text-sm font-semibold text-slate-900">{{ log.url }}</p>
                                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Thời gian chạy</p>
                                        <p class="mt-1 font-semibold text-slate-900">{{ formatDuration(log.duration_ms) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Kết quả</p>
                                        <p class="mt-1 font-semibold text-slate-900">{{ log.status }}</p>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div class="hidden overflow-x-auto rounded-[10px] border border-slate-200 md:block">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50/90">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        <th class="px-4 py-3">Thời gian</th>
                                        <th class="px-4 py-3">URL</th>
                                        <th class="px-4 py-3">Thời gian chạy</th>
                                        <th class="px-4 py-3">Kết quả</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-700">
                                    <tr v-for="log in recentLogs" :key="log.id" class="align-top">
                                        <td class="px-4 py-3 font-medium text-slate-500">{{ formatDateTime(log.started_at) }}</td>
                                        <td class="px-4 py-3">
                                            <div class="max-w-[420px]">
                                                <p class="font-semibold text-slate-900">{{ log.method }}</p>
                                                <p class="truncate text-slate-500">{{ log.url }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ formatDuration(log.duration_ms) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(log.status)">
                                                {{ log.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </div>

            <div class="space-y-5">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_24px_50px_-36px_rgba(15,23,42,0.38)] sm:p-6">
                    <h2 class="text-xl font-bold tracking-[-0.03em] text-slate-950">Trạng thái hệ thống</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Tổng hợp nhanh từ trạng thái dashboard, quota và dữ liệu runtime hiện tại.</p>

                    <div class="mt-5 space-y-4">
                        <div v-for="item in systemStatusItems" :key="item.label" class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-emerald-50 text-emerald-600">
                                <component :is="item.icon" class="h-4.5 w-4.5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="font-semibold text-slate-900">{{ item.label }}</p>
                                    <span class="text-sm font-semibold text-emerald-600">{{ item.state }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ item.detail }}</p>
                            </div>
                        </div>
                    </div>

                    <RouterLink to="/logs" class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-blue-700">
                        Xem chi tiết trạng thái
                        <ChevronRight class="h-4 w-4" />
                    </RouterLink>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_24px_50px_-36px_rgba(15,23,42,0.38)] sm:p-6">
                    <h2 class="text-xl font-bold tracking-[-0.03em] text-slate-950">Tác vụ nhanh</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Đi thẳng vào các thao tác bạn dùng nhiều nhất hằng ngày.</p>

                    <div class="mt-5 grid gap-3">
                        <RouterLink
                            v-for="action in quickActions"
                            :key="action.title"
                            :to="action.to"
                            class="flex items-center gap-3 rounded-[10px] border border-slate-200 px-4 py-4 transition hover:border-blue-200 hover:bg-blue-50/40"
                        >
                            <div class="flex h-11 w-11 items-center justify-center rounded-[10px]" :class="action.iconClass">
                                <component :is="action.icon" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ action.title }}</p>
                                <p class="text-sm text-slate-500">{{ action.description }}</p>
                            </div>
                        </RouterLink>
                    </div>
                </article>

                <article class="rounded-[10px] border border-blue-100 bg-[linear-gradient(180deg,#eff6ff_0%,#ffffff_100%)] p-4 shadow-[0_24px_50px_-36px_rgba(37,99,235,0.4)] sm:p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[10px] bg-white text-blue-600 shadow-sm">
                            <Database class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Nhịp vận hành hôm nay</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ summary.failed_today > 0
                                    ? `Có ${summary.failed_today} lỗi mới, bạn nên mở logs hoặc alert channels để xử lý sớm.`
                                    : 'Các tác vụ đang vận hành ổn định, chưa ghi nhận lỗi mới trong hôm nay.' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[10px] border border-white/80 bg-white/90 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Run now</p>
                            <p class="mt-2 font-semibold text-slate-900">
                                {{ packageLimits?.allow_run_now ? 'Được phép trong gói hiện tại' : 'Chưa hỗ trợ trong gói hiện tại' }}
                            </p>
                        </div>
                        <div class="rounded-[10px] border border-white/80 bg-white/90 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Logs / job</p>
                            <p class="mt-2 font-semibold text-slate-900">{{ packageLimits?.max_logs_per_job ?? 0 }} logs</p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-[10px] border border-blue-100 bg-white/80 px-4 py-3 text-sm text-slate-600">
                        <div class="flex items-start gap-2">
                            <CircleAlert class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
                            <p>
                                Cron expression:
                                <span class="font-semibold text-slate-900">{{ packageLimits?.allow_cron_expression ? 'được phép' : 'không hỗ trợ' }}</span>
                                • Alerts:
                                <span class="font-semibold text-slate-900">{{ packageLimits?.allow_alerts ? 'được phép' : 'không hỗ trợ' }}</span>
                            </p>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
