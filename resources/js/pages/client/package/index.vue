<script setup lang="ts">
import Modal from '@/components/shared/Modal/index.vue';
import { clientPackageService } from '@/services/client-package.service';
import { useUserStore } from '@/stores/user.store';
import type { PackageLimitsType } from '@/types/user-subscription.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import {
    BadgeCheck,
    BellRing,
    Check,
    CircleHelp,
    Clock3,
    Gem,
    HeartHandshake,
    Layers3,
    Package2,
    RefreshCcw,
    ShieldCheck,
    Sparkles,
    X,
    Zap,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';

type PackageItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    duration_days: number;
    package_limits?: PackageLimitsType;
};

type ComparisonRow = {
    key: string;
    label: string;
    icon: typeof Layers3;
    values: Array<string | boolean>;
};

const userStore = useUserStore();
const loading = ref(false);
const autoRenewUpdating = ref(false);
const showGuideModal = ref(false);
const packages = ref<PackageItem[]>([]);

const currentSubscription = computed(() => userStore.user?.user_subscriptions ?? null);
const currentPackageLimits = computed(() => currentSubscription.value?.package_limits ?? null);
const currentSubscriptionActive = computed(() => currentSubscription.value?.status === 'active');

const sortedPackages = computed(() =>
    [...packages.value].sort((a, b) => Number(a.price || 0) - Number(b.price || 0)),
);

const featuredPackageId = computed(() => {
    const bySlug = sortedPackages.value.find((item) =>
        ['basic', 'co-ban', 'standard', 'pro'].some((keyword) => item.slug.toLowerCase().includes(keyword)),
    );

    if (bySlug) {
        return bySlug.id;
    }

    if (sortedPackages.value.length >= 3) {
        return sortedPackages.value[1].id;
    }

    return sortedPackages.value[0]?.id ?? null;
});

const comparisonRows = computed<ComparisonRow[]>(() => [
    {
        key: 'cron_jobs',
        label: 'Cron jobs',
        icon: Layers3,
        values: sortedPackages.value.map((item) => formatNumber(item.package_limits?.max_cron_jobs ?? 0)),
    },
    {
        key: 'min_interval',
        label: 'Min interval',
        icon: Clock3,
        values: sortedPackages.value.map((item) => intervalLabel(item.package_limits?.min_interval_seconds ?? null)),
    },
    {
        key: 'monthly_quota',
        label: 'Quota tháng',
        icon: Zap,
        values: sortedPackages.value.map((item) =>
            item.package_limits?.monthly_run_quota ? formatNumber(item.package_limits.monthly_run_quota) : 'Unlimited',
        ),
    },
    {
        key: 'log_count',
        label: 'Logs / job',
        icon: ShieldCheck,
        values: sortedPackages.value.map((item) => formatNumber(item.package_limits?.max_logs_per_job ?? 0)),
    },
    {
        key: 'run_now',
        label: 'Run now',
        icon: Sparkles,
        values: sortedPackages.value.map((item) => Boolean(item.package_limits?.allow_run_now)),
    },
    {
        key: 'alerts',
        label: 'Alerts',
        icon: BellRing,
        values: sortedPackages.value.map((item) => Boolean(item.package_limits?.allow_alerts)),
    },
]);

const heroHighlights = [
    {
        title: 'Hiệu suất cao',
        description: 'Server ổn định, chạy 24/7',
        icon: Zap,
    },
    {
        title: 'Bảo mật tốt',
        description: 'Cô lập request và giới hạn an toàn',
        icon: ShieldCheck,
    },
    {
        title: 'Dễ mở rộng',
        description: 'Nâng cấp nhanh theo số lượng jobs',
        icon: Layers3,
    },
];

const currentPackageStats = computed(() => {
    const limits = currentPackageLimits.value;
    const cronLimit = Number(limits?.max_cron_jobs ?? 0);
    const usedCronJobs = Number(currentSubscription.value?.used_account ?? 0);
    const logsPerJob = Number(limits?.max_logs_per_job ?? 0);
    const requestQuota = Number(limits?.daily_run_quota ?? limits?.monthly_run_quota ?? 0);

    return [
        {
            label: 'Cron Jobs',
            value: cronLimit > 0 ? `${formatNumber(usedCronJobs)} / ${formatNumber(cronLimit)}` : formatNumber(usedCronJobs),
            helper: cronLimit > 0 ? `${formatNumber(Math.max(0, cronLimit - usedCronJobs))} còn trống` : 'Không giới hạn',
            progress: cronLimit > 0 ? `${Math.min(100, Math.round((usedCronJobs / cronLimit) * 100))}%` : '72%',
        },
        {
            label: 'Log lưu trữ',
            value: logsPerJob > 0 ? `${formatNumber(logsPerJob)} / job` : '--',
            helper: 'Theo giới hạn gói',
            progress: logsPerJob > 0 ? `${Math.min(100, Math.max(34, logsPerJob))}%` : '48%',
        },
        {
            label: 'Requests / ngày',
            value: requestQuota > 0 ? formatNumber(requestQuota) : 'Unlimited',
            helper: requestQuota > 0 ? 'Quota chạy mỗi ngày' : 'Không khóa quota ngày',
            progress: requestQuota > 0 ? '78%' : '56%',
        },
    ];
});

const formatMoney = (value: string | number): string =>
    new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

const formatNumber = (value: number | string | null | undefined): string =>
    new Intl.NumberFormat('vi-VN').format(Number(value || 0));

const formatDate = (value: string | null | undefined): string => {
    if (!value) {
        return '--';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(value));
};

const intervalLabel = (seconds: number | null | undefined): string => {
    if (!seconds) {
        return 'Cron expression';
    }

    if (seconds % 3600 === 0) {
        return `${seconds / 3600} giờ`;
    }

    if (seconds % 60 === 0) {
        return `${seconds / 60} phút`;
    }

    return `${seconds} giây`;
};

const slugLabel = (slug: string): string => slug.replace(/[-_]+/g, ' ').toUpperCase();

const toneFor = (item: PackageItem) => {
    const slug = item.slug.toLowerCase();

    if (slug.includes('pro')) {
        return {
            accent: 'text-violet-600',
            soft: 'bg-violet-50 text-violet-700 border-violet-200',
            button: 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white hover:from-violet-500 hover:to-fuchsia-500',
            ring: 'border-violet-200',
            tint: 'from-violet-50 to-white',
        };
    }

    if (item.id === featuredPackageId.value) {
        return {
            accent: 'text-blue-600',
            soft: 'bg-blue-50 text-blue-700 border-blue-200',
            button: 'bg-gradient-to-r from-blue-600 to-sky-500 text-white hover:from-blue-500 hover:to-sky-400',
            ring: 'border-blue-300',
            tint: 'from-blue-50 to-white',
        };
    }

    return {
        accent: 'text-emerald-600',
        soft: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        button: 'border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
        ring: 'border-emerald-200',
        tint: 'from-emerald-50 to-white',
    };
};

const packageSummary = (limits?: PackageLimitsType) => [
    {
        label: 'Cron jobs',
        value: formatNumber(limits?.max_cron_jobs ?? 0),
        icon: Layers3,
    },
    {
        label: 'Interval',
        value: intervalLabel(limits?.min_interval_seconds ?? null),
        icon: Clock3,
    },
    {
        label: 'Quota tháng',
        value: limits?.monthly_run_quota ? formatNumber(limits.monthly_run_quota) : 'Unlimited',
        icon: Zap,
    },
    {
        label: 'Logs / job',
        value: formatNumber(limits?.max_logs_per_job ?? 0),
        icon: ShieldCheck,
    },
];

const packageHighlights = (limits?: PackageLimitsType): string[] => {
    if (!limits) {
        return ['Đang cập nhật cấu hình gói'];
    }

    return [
        `${formatNumber(limits.max_cron_jobs)} cron jobs`,
        `Interval từ ${intervalLabel(limits.min_interval_seconds)}`,
        `${formatNumber(limits.max_logs_per_job)} logs mỗi job`,
        limits.allow_run_now ? 'Có chạy ngay' : 'Không có run now',
    ];
};

const featureBadges = (limits?: PackageLimitsType): Array<{ label: string; enabled: boolean }> => [
    { label: 'Run now', enabled: Boolean(limits?.allow_run_now) },
    { label: 'Cron expression', enabled: Boolean(limits?.allow_cron_expression) },
    { label: 'Custom headers', enabled: Boolean(limits?.allow_custom_headers) },
    { label: 'Custom body', enabled: Boolean(limits?.allow_custom_body) },
    { label: 'Alerts', enabled: Boolean(limits?.allow_alerts) },
];

const isCurrentPackage = (item: PackageItem): boolean => currentSubscription.value?.package_id === item.id;

const loadPackages = async (): Promise<void> => {
    loading.value = true;

    try {
        if (!userStore.user) {
            await userStore.bootstrap({ silent: true });
        }

        const response = await clientPackageService.list();
        packages.value = response.packages.map((item: any) => ({
            ...item,
            package_limits: item.package_limits ?? item.package?.package_limits,
        }));
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const orderPackage = async (packageItem: PackageItem): Promise<void> => {
    const confirmed = await Swal.fire({
        icon: 'question',
        title: `Đăng ký gói ${packageItem.name}?`,
        text: 'Đơn hàng sẽ được tạo và thanh toán bằng ví chính nếu đủ số dư.',
        input: 'checkbox',
        inputPlaceholder: 'Bật tự gia hạn khi gói hết hạn',
        showCancelButton: true,
        confirmButtonText: 'Tiếp tục',
        cancelButtonText: 'Hủy',
    });

    if (!confirmed.isConfirmed) {
        return;
    }

    try {
        const orderResponse = await clientPackageService.createOrder({
            package_id: packageItem.id,
            payment_method: 'wallet',
            auto_renew_enabled: Boolean(confirmed.value),
        });
        const orderId = orderResponse.data.data.id;
        const payResponse = await clientPackageService.payOrder(orderId, {
            payment_method: 'wallet',
        });

        handleSuccessResponse(payResponse, 'Đã thanh toán gói bằng ví chính.');
        await userStore.fetchCurrentUser({ silent: true });
        await loadPackages();
    } catch (error) {
        handleErrorResponse(error);
    }
};

const toggleCurrentSubscriptionAutoRenew = async (): Promise<void> => {
    if (!currentSubscription.value) {
        return;
    }

    try {
        autoRenewUpdating.value = true;

        const nextValue = !currentSubscription.value.auto_renew_enabled;
        const response = await clientPackageService.updateSubscriptionAutoRenew(currentSubscription.value.id, {
            auto_renew_enabled: nextValue,
        });

        handleSuccessResponse(response, nextValue ? 'Đã bật tự động gia hạn.' : 'Đã tắt tự động gia hạn.');
        await userStore.fetchCurrentUser({ silent: true });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        autoRenewUpdating.value = false;
    }
};

onMounted(async () => {
    await loadPackages();
});
</script>

<template>
    <div class="space-y-4 pb-4 sm:space-y-5">
        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_380px]">
            <article class="relative overflow-hidden rounded-[12px] border border-[#dce6fb] bg-[radial-gradient(circle_at_top_left,_rgba(80,109,255,0.14),_transparent_34%),linear-gradient(180deg,_#ffffff_0%,_#f8fbff_100%)] p-4 shadow-[0_20px_46px_-38px_rgba(37,99,235,0.26)] sm:p-5">
                <div class="absolute inset-y-0 right-0 hidden w-[34%] lg:block">
                    <div class="absolute right-6 top-6 w-40 rounded-[12px] border border-[#e0e7ff] bg-white/90 p-3 shadow-[0_18px_36px_-30px_rgba(79,70,229,0.3)] backdrop-blur">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-black tracking-[0.24em] text-[#3158ff]">
                                {{ slugLabel(sortedPackages[1]?.slug ?? 'pro') }}
                            </p>
                            <Check class="h-4 w-4 text-[#3158ff]" />
                        </div>
                        <div class="mt-3 space-y-2">
                            <div class="h-2 rounded-full bg-[#dfe8ff]"></div>
                            <div class="h-2 w-4/5 rounded-full bg-[#edf2ff]"></div>
                            <div class="h-2 w-3/5 rounded-full bg-[#dfe8ff]"></div>
                        </div>
                        <div class="mt-4 flex items-end gap-1.5">
                            <div class="h-6 w-3 rounded-t-full bg-[#dbe6ff]"></div>
                            <div class="h-10 w-3 rounded-t-full bg-[#bdd1ff]"></div>
                            <div class="h-8 w-3 rounded-t-full bg-[#dbe6ff]"></div>
                            <div class="h-12 w-3 rounded-t-full bg-[#3158ff]"></div>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <p class="text-[11px] font-black uppercase tracking-[0.32em] text-[#4f67ff]">AutoCron Plans</p>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border border-[#d7e3ff] bg-white px-3 py-1.5 text-xs font-semibold text-[#4660ff] transition hover:bg-[#f7faff]"
                            @click="showGuideModal = true"
                        >
                            <CircleHelp class="h-3.5 w-3.5" />
                            Xem hướng dẫn
                        </button>
                    </div>

                    <h1 class="mt-3 max-w-xl text-[1.95rem] font-black leading-tight tracking-[-0.05em] text-[#0c1b4d] sm:text-[2.7rem]">
                        Chọn gói phù hợp cho hệ thống cron của bạn
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-[15px]">
                        Nâng cấp để tăng số cron jobs, quota chạy, log lưu trữ và mở khóa thêm alerts, run now, custom headers.
                    </p>

                    <div class="mt-4 grid gap-2.5 sm:grid-cols-3">
                        <div
                            v-for="item in heroHighlights"
                            :key="item.title"
                            class="rounded-[10px] border border-[#dfe6fb] bg-white/85 px-3.5 py-3 shadow-[0_12px_26px_-24px_rgba(37,99,235,0.24)]"
                        >
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#eef2ff] text-[#4f67ff]">
                                    <component :is="item.icon" class="h-4 w-4" />
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-[#12204f]">{{ item.title }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ item.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-[12px] border border-[#dce6fb] bg-[linear-gradient(180deg,_#ffffff_0%,_#fbfdff_100%)] p-4 shadow-[0_20px_46px_-38px_rgba(37,99,235,0.24)] sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-500">
                        <Package2 class="h-6 w-6" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-500">Gói hiện tại</p>
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold"
                                :class="currentSubscriptionActive ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-700'"
                            >
                                {{ currentSubscriptionActive ? 'Đang hoạt động' : 'Cần gia hạn' }}
                            </span>
                        </div>
                        <h2 class="mt-2 text-[1.8rem] font-black tracking-[-0.05em] text-[#111c44]">
                            {{ currentSubscription?.package_name || 'Chưa có gói' }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Hết hạn:
                            <span class="font-bold text-[#3158ff]">{{ formatDate(currentSubscription?.expires_at) }}</span>
                        </p>
                    </div>
                </div>

                <div
                    v-if="currentSubscription"
                    class="mt-4 flex items-center justify-between gap-3 rounded-[10px] border border-[#dfe6fb] bg-white px-3.5 py-3"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#eef3ff] text-[#4f67ff]">
                            <RefreshCcw class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-[#111c44]">Tự gia hạn</p>
                            <p class="mt-0.5 text-xs text-slate-500">Tự động gia hạn khi đến hạn</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="currentSubscription.auto_renew_enabled"
                        class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-60"
                        :class="currentSubscription.auto_renew_enabled ? 'bg-emerald-400' : 'bg-slate-300'"
                        :disabled="autoRenewUpdating"
                        @click="toggleCurrentSubscriptionAutoRenew"
                    >
                        <span class="sr-only">Bật hoặc tắt tự gia hạn</span>
                        <span
                            class="inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform"
                            :class="currentSubscription.auto_renew_enabled ? 'translate-x-6' : 'translate-x-1'"
                        />
                    </button>
                </div>

                <div class="mt-4 grid gap-2.5 sm:grid-cols-3">
                    <article
                        v-for="metric in currentPackageStats"
                        :key="metric.label"
                        class="rounded-[10px] border border-[#dfe6fb] bg-white px-3.5 py-3.5"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ metric.label }}</p>
                        <p class="mt-2 text-lg font-black tracking-[-0.04em] text-[#3158ff]">{{ metric.value }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ metric.helper }}</p>
                        <div class="mt-3 h-1.5 rounded-full bg-[#ebf0ff]">
                            <div class="h-1.5 rounded-full bg-[#4f67ff]" :style="{ width: metric.progress }"></div>
                        </div>
                    </article>
                </div>
            </article>
        </section>

        <section
            v-if="loading"
            class="rounded-[12px] border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500 shadow-sm"
        >
            Đang tải danh sách gói...
        </section>

        <section v-else class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            <article
                v-for="item in sortedPackages"
                :key="item.id"
                class="relative flex h-full flex-col overflow-hidden rounded-[12px] border bg-white p-4 shadow-[0_18px_42px_-34px_rgba(15,23,42,0.2)]"
                :class="[
                    isCurrentPackage(item) ? 'border-emerald-300 ring-1 ring-emerald-100' : toneFor(item).ring,
                    `bg-gradient-to-b ${toneFor(item).tint}`,
                ]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                v-if="item.id === featuredPackageId"
                                class="rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-700"
                            >
                                Phổ biến
                            </span>
                            <span
                                v-if="isCurrentPackage(item)"
                                class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700"
                            >
                                Đang dùng
                            </span>
                        </div>
                        <p class="mt-3 text-[11px] font-bold uppercase tracking-[0.28em]" :class="toneFor(item).accent">
                            {{ slugLabel(item.slug) }}
                        </p>
                        <h2 class="mt-2 text-[1.9rem] font-black tracking-[-0.05em] text-slate-950">{{ item.name }}</h2>
                    </div>
                </div>

                <div class="mt-3 flex items-end gap-2">
                    <p class="text-[2.2rem] font-black tracking-[-0.05em] text-slate-950">{{ formatMoney(item.price) }}</p>
                    <span class="pb-1 text-sm text-slate-500">/ {{ item.duration_days }} ngày</span>
                </div>

                <p class="mt-3 min-h-[42px] text-sm leading-6 text-slate-600">
                    {{ item.description || 'Gói phù hợp để vận hành HTTP cron jobs với giới hạn và tính năng rõ ràng.' }}
                </p>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <div
                        v-for="summaryItem in packageSummary(item.package_limits)"
                        :key="`${item.id}-${summaryItem.label}`"
                        class="rounded-[10px] border border-slate-200 bg-white/90 px-3 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <component :is="summaryItem.icon" class="h-4 w-4" :class="toneFor(item).accent" />
                            <p class="text-xs font-medium text-slate-500">{{ summaryItem.label }}</p>
                        </div>
                        <p class="mt-1.5 text-sm font-bold text-slate-950">{{ summaryItem.value }}</p>
                    </div>
                </div>

                <ul class="mt-4 space-y-2 text-sm text-slate-600">
                    <li v-for="highlight in packageHighlights(item.package_limits)" :key="highlight" class="flex items-start gap-2">
                        <Check class="mt-0.5 h-4 w-4 shrink-0" :class="toneFor(item).accent" />
                        <span>{{ highlight }}</span>
                    </li>
                </ul>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span
                        v-for="badge in featureBadges(item.package_limits)"
                        :key="`${item.id}-${badge.label}`"
                        class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold"
                        :class="badge.enabled ? toneFor(item).soft : 'border border-slate-200 bg-slate-50 text-slate-500'"
                    >
                        {{ badge.label }}
                    </span>
                </div>

                <div class="mt-auto border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-center rounded-[10px] px-4 py-3 text-sm font-semibold transition"
                        :class="isCurrentPackage(item) ? 'border border-slate-200 bg-slate-100 text-slate-500' : toneFor(item).button"
                        :disabled="isCurrentPackage(item)"
                        @click="orderPackage(item)"
                    >
                        {{ isCurrentPackage(item) ? 'Đang dùng gói này' : item.id === featuredPackageId ? 'Chọn gói cơ bản' : `Chọn gói ${item.name}` }}
                    </button>
                </div>
            </article>
        </section>

        <section
            v-if="sortedPackages.length > 0"
            class="rounded-[12px] border border-slate-200 bg-white p-4 shadow-[0_18px_42px_-34px_rgba(15,23,42,0.18)] sm:p-5"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-950">So sánh nhanh tính năng chính</h2>
                    <p class="mt-1 text-sm text-slate-500">Bảng này giúp nhìn nhanh khác biệt giữa các gói.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                    @click="showGuideModal = true"
                >
                    <CircleHelp class="h-3.5 w-3.5" />
                    Cách đọc bảng
                </button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="text-left text-sm font-semibold text-slate-500">
                            <th class="px-3 py-3">Tính năng</th>
                            <th
                                v-for="item in sortedPackages"
                                :key="`head-${item.id}`"
                                class="px-3 py-3 text-center"
                                :class="isCurrentPackage(item) ? 'text-emerald-600' : item.id === featuredPackageId ? 'text-blue-600' : 'text-violet-600'"
                            >
                                {{ item.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <tr v-for="row in comparisonRows" :key="row.key">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2 font-medium text-slate-700">
                                    <component :is="row.icon" class="h-4 w-4 text-slate-400" />
                                    {{ row.label }}
                                </div>
                            </td>
                            <td v-for="(value, index) in row.values" :key="`${row.key}-${index}`" class="px-3 py-3 text-center">
                                <template v-if="typeof value === 'boolean'">
                                    <span class="inline-flex items-center justify-center" :class="value ? 'text-emerald-600' : 'text-slate-300'">
                                        <Check v-if="value" class="h-4 w-4" />
                                        <X v-else class="h-4 w-4" />
                                    </span>
                                </template>
                                <template v-else>
                                    {{ value }}
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-3 lg:grid-cols-3">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-[10px] bg-blue-50 text-blue-600">
                        <HeartHandshake class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-950">Thanh toán bằng ví</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Đơn hàng package sẽ ưu tiên thanh toán trực tiếp bằng số dư ví hiện có.
                        </p>
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-[10px] bg-emerald-50 text-emerald-600">
                        <BadgeCheck class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-950">Nâng cấp không mất dữ liệu</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Cron jobs hiện tại được giữ nguyên, hệ thống chỉ cập nhật giới hạn và tính năng của gói.
                        </p>
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-[10px] bg-violet-50 text-violet-600">
                        <Gem class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-950">Phù hợp từ test đến production</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Có thể bắt đầu với gói nhỏ và mở rộng dần khi lượng jobs, quota hoặc retention tăng lên.
                        </p>
                    </div>
                </div>
            </article>
        </section>

        <Modal v-model="showGuideModal" panel-class="max-w-5xl">
            <template #header>
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-xl font-black tracking-[-0.03em] text-slate-950">Hướng dẫn đọc thông tin gói</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-500">
                        Giải thích nhanh các giới hạn và tính năng để bạn chọn đúng gói cho hệ thống AutoCron.
                    </p>
                </div>
            </template>

            <div class="space-y-5 px-6 py-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-[10px] border border-sky-100 bg-sky-50/50 px-4 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Cron jobs</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Là số lượng task HTTP bạn có thể tạo trong tài khoản. Mỗi job là một URL được hệ thống gọi tự động theo lịch.
                        </p>
                    </div>

                    <div class="rounded-[10px] border border-sky-100 bg-sky-50/50 px-4 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Interval tối thiểu</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Là khoảng thời gian chạy nhanh nhất mà gói cho phép. Ví dụ gói 5 phút thì bạn không thể tạo job chạy mỗi 1 phút.
                        </p>
                    </div>

                    <div class="rounded-[10px] border border-sky-100 bg-sky-50/50 px-4 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Quota tháng</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Là tổng số lượt chạy được phép trong 1 tháng. Khi chạm ngưỡng này, job sẽ không chạy tiếp cho đến khi quota được làm mới.
                        </p>
                    </div>

                    <div class="rounded-[10px] border border-sky-100 bg-sky-50/50 px-4 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Logs mỗi job</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Là số lượng log tối đa hệ thống giữ lại cho mỗi cron job. Khi vượt ngưỡng, log cũ nhất sẽ bị xóa trước.
                        </p>
                    </div>
                </div>

                <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Ý nghĩa các thẻ tính năng</h3>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            <span class="font-semibold text-slate-900">Run now:</span> cho phép bấm chạy ngay mà không cần đợi đến lịch tiếp theo.
                        </div>
                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            <span class="font-semibold text-slate-900">Cron expression:</span> cho phép dùng lịch tùy chỉnh thay vì chỉ chọn interval cố định.
                        </div>
                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            <span class="font-semibold text-slate-900">Custom headers:</span> cho phép gửi thêm header riêng như Authorization, token, signature.
                        </div>
                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            <span class="font-semibold text-slate-900">Custom body:</span> cho phép gửi JSON, form data hoặc raw body khi gọi HTTP.
                        </div>
                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            <span class="font-semibold text-slate-900">Alerts:</span> cho phép gắn kênh Discord, Telegram, webhook hoặc email để nhận cảnh báo.
                        </div>
                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            <span class="font-semibold text-slate-900">Body check:</span> cho phép kiểm tra nội dung response có chứa hoặc không chứa chuỗi mong muốn.
                        </div>
                    </div>
                </div>

                <div class="rounded-[10px] border border-amber-200 bg-amber-50 px-4 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Khi gói hết hạn thì chuyện gì xảy ra?</h3>
                    <div class="mt-2 space-y-2 text-sm leading-7 text-slate-700">
                        <p>Hiện tại hệ thống không tự xóa cron job khi gói hết hạn.</p>
                        <p>Job cũ vẫn được giữ trong tài khoản, nhưng hệ thống sẽ chặn các thao tác cần gói đang hoạt động như tạo mới, cập nhật, resume hoặc run now.</p>
                        <p>Với job đang để trạng thái active, scheduler sẽ không chạy thật khi không còn subscription hợp lệ, mà ghi nhận lần đó là bị chặn.</p>
                        <p>Sau khi bạn đăng ký hoặc gia hạn lại gói, các job đang active có thể tiếp tục chạy lại theo lịch kế tiếp. Nếu job nào đang paused thì bạn cần bật lại thủ công.</p>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="border-t border-slate-200 px-6 py-4">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-[10px] border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        @click="showGuideModal = false"
                    >
                        Đóng
                    </button>
                </div>
            </template>
        </Modal>
    </div>
</template>
