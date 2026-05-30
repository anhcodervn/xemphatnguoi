<script setup lang="ts">
import { adminPackageOrderService } from '@/services/admin-package-order.service';
import { adminQueueService } from '@/services/admin-queue.service';
import { adminRechargeMethodService } from '@/services/admin-recharge-method.service';
import { adminUserService } from '@/services/admin-user.service';
import { adminWebhookService } from '@/services/admin-webhook.service';
import type { AdminQueueItem } from '@/types/admin-queue.type';
import type { AdminWebhookItem } from '@/types/admin-webhook.type';
import formatCash from '@/utils/helpers/formatCash';
import {
    Activity,
    ArrowRight,
    BadgeDollarSign,
    BellRing,
    ChartNoAxesCombined,
    CircleAlert,
    CreditCard,
    DatabaseBackup,
    GitBranchPlus,
    Package,
    ReceiptText,
    ShieldCheck,
    Users,
    WalletCards,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

type MetricCard = {
    key: string;
    label: string;
    value: string;
    note: string;
    icon: typeof Users;
    iconClass: string;
    toneClass: string;
};

type ShortcutItem = {
    title: string;
    description: string;
    to: string;
    icon: typeof Users;
    badge: string;
};

type HealthItem = {
    key: string;
    title: string;
    value: string;
    note: string;
    toneClass: string;
    icon: typeof Activity;
};

const loading = ref(true);
const userSummary = ref({
    total: 0,
    newToday: 0,
    active: 0,
    blocked: 0,
});
const orderSummary = ref({
    revenue: 0,
    todayOrders: 0,
    activePackages: 0,
});
const rechargeSummary = ref({
    total: 0,
    active: 0,
    inactive: 0,
});

const metrics = ref<MetricCard[]>([
    {
        key: 'users',
        label: 'Tổng thành viên',
        value: '--',
        note: 'Đang tải dữ liệu người dùng',
        icon: Users,
        iconClass: 'bg-blue-100 text-blue-700',
        toneClass: 'from-blue-50 to-white',
    },
    {
        key: 'revenue',
        label: 'Doanh thu gói',
        value: '--',
        note: 'Đang tải dữ liệu doanh thu',
        icon: BadgeDollarSign,
        iconClass: 'bg-emerald-100 text-emerald-700',
        toneClass: 'from-emerald-50 to-white',
    },
    {
        key: 'webhooks',
        label: 'Webhook hoạt động',
        value: '--',
        note: 'Đang tải trạng thái webhook',
        icon: GitBranchPlus,
        iconClass: 'bg-violet-100 text-violet-700',
        toneClass: 'from-violet-50 to-white',
    },
    {
        key: 'queue-failed',
        label: 'Queue lỗi',
        value: '--',
        note: 'Đang tải trạng thái hàng đợi',
        icon: DatabaseBackup,
        iconClass: 'bg-amber-100 text-amber-700',
        toneClass: 'from-amber-50 to-white',
    },
]);

const queueItems = ref<AdminQueueItem[]>([]);
const webhookItems = ref<AdminWebhookItem[]>([]);

const shortcuts: ShortcutItem[] = [
    {
        title: 'Quản lý người dùng',
        description: 'Xem danh sách thành viên, trạng thái tài khoản và điều chỉnh số dư ví.',
        to: '/admin/users',
        icon: Users,
        badge: 'Users',
    },
    {
        title: 'Gói đã bán',
        description: 'Theo dõi lịch sử mua gói, doanh thu và các gói sắp hết hạn.',
        to: '/admin/packages/orders',
        icon: Package,
        badge: 'Orders',
    },
    {
        title: 'Phương thức nạp',
        description: 'Quản lý thẻ nhận tiền, giới hạn nạp và liên kết bank account.',
        to: '/admin/recharge-methods',
        icon: WalletCards,
        badge: 'Recharge',
    },
    {
        title: 'Hàng đợi hệ thống',
        description: 'Kiểm tra queue đang xử lý, log thất bại và retry job khi cần.',
        to: '/admin/queues',
        icon: DatabaseBackup,
        badge: 'Queue',
    },
];

const queueHealth = computed<HealthItem[]>(() => {
    if (queueItems.value.length === 0) {
        return [
            {
                key: 'queue-empty',
                title: 'Chưa có dữ liệu queue',
                value: '--',
                note: 'Chưa lấy được trạng thái hàng đợi từ backend.',
                toneClass: 'border-slate-200 bg-slate-50 text-slate-700',
                icon: DatabaseBackup,
            },
        ];
    }

    return queueItems.value.slice(0, 4).map((item) => {
        const issueCount = item.failed_jobs + item.failed_logs;
        const isWarning = issueCount > 0;

        return {
            key: item.queue,
            title: item.queue,
            value: `${item.pending_jobs} chờ`,
            note: isWarning
                ? `${item.failed_jobs} failed jobs / ${item.failed_logs} failed logs`
                : `${item.processing_logs} đang xử lý / ${item.success_logs} thành công`,
            toneClass: isWarning ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900',
            icon: isWarning ? CircleAlert : ShieldCheck,
        };
    });
});

const webhookHealth = computed<HealthItem[]>(() => {
    if (webhookItems.value.length === 0) {
        return [
            {
                key: 'webhook-empty',
                title: 'Chưa có webhook',
                value: '--',
                note: 'Danh sách webhook đang trống hoặc chưa tải được dữ liệu.',
                toneClass: 'border-slate-200 bg-slate-50 text-slate-700',
                icon: BellRing,
            },
        ];
    }

    return webhookItems.value.slice(0, 4).map((item) => {
        const isHealthy = item.status === 'active' || item.status === 'enabled';
        const totalCalls = item.success_count + item.failed_count;

        return {
            key: String(item.id),
            title: item.user?.email ?? item.url,
            value: item.status,
            note: totalCalls > 0 ? `${item.success_count} thành công / ${item.failed_count} lỗi` : 'Chưa có lần gửi nào gần đây',
            toneClass: isHealthy ? 'border-blue-200 bg-blue-50 text-blue-900' : 'border-rose-200 bg-rose-50 text-rose-900',
            icon: isHealthy ? GitBranchPlus : CircleAlert,
        };
    });
});

const formatNumber = (value: number): string => new Intl.NumberFormat('vi-VN').format(value);

const formatRatio = (value: number): string => `${value.toFixed(1)}%`;

const loadDashboard = async (): Promise<void> => {
    loading.value = true;

    const [usersResult, ordersResult, rechargeMethodsResult, queueResult, webhookResult] = await Promise.allSettled([
        adminUserService.list({ per_page: 1 }),
        adminPackageOrderService.list({ per_page: 5 }),
        adminRechargeMethodService.list({ per_page: 5 }),
        adminQueueService.overview(),
        adminWebhookService.list({ per_page: 5 }),
    ]);

    if (usersResult.status === 'fulfilled') {
        metrics.value = metrics.value.map((item) => {
            if (item.key !== 'users') {
                return item;
            }

            const { stats } = usersResult.value;
            userSummary.value = {
                total: stats.total_users,
                newToday: stats.new_today,
                active: stats.active_users,
                blocked: stats.blocked_users,
            };

            return {
                ...item,
                value: formatNumber(stats.total_users),
                note: `${formatNumber(stats.active_users)} hoạt động / ${formatNumber(stats.blocked_users)} bị khóa`,
            };
        });
    }

    if (ordersResult.status === 'fulfilled') {
        metrics.value = metrics.value.map((item) => {
            if (item.key !== 'revenue') {
                return item;
            }

            const { stats } = ordersResult.value;
            orderSummary.value = {
                revenue: stats.revenue,
                todayOrders: stats.today_orders,
                activePackages: stats.active_packages,
            };

            return {
                ...item,
                value: `${formatCash(stats.revenue)}đ`,
                note: `${formatNumber(stats.today_orders)} đơn hôm nay / ${formatNumber(stats.active_packages)} gói đang chạy`,
            };
        });
    }

    if (webhookResult.status === 'fulfilled') {
        webhookItems.value = webhookResult.value.data;

        metrics.value = metrics.value.map((item) => {
            if (item.key !== 'webhooks') {
                return item;
            }

            const { stats } = webhookResult.value;

            return {
                ...item,
                value: formatNumber(stats.enabled_webhooks),
                note: `${formatRatio(stats.success_rate)} tỉ lệ thành công / ${formatNumber(stats.failed_today)} lỗi hôm nay`,
            };
        });
    }

    if (queueResult.status === 'fulfilled') {
        queueItems.value = queueResult.value.queues;

        metrics.value = metrics.value.map((item) => {
            if (item.key !== 'queue-failed') {
                return item;
            }

            const { summary } = queueResult.value;

            return {
                ...item,
                value: formatNumber(summary.total_failed_jobs + summary.total_failed_logs),
                note: `${formatNumber(summary.total_pending_jobs)} chờ / ${formatNumber(summary.total_processing_logs)} đang xử lý`,
            };
        });
    }

    if (rechargeMethodsResult.status === 'fulfilled') {
        const { summary } = rechargeMethodsResult.value;
        rechargeSummary.value = {
            total: summary.total,
            active: summary.active,
            inactive: summary.inactive,
        };

        const existingCard = metrics.value.find((item) => item.key === 'recharge-methods');

        if (!existingCard) {
            metrics.value.push({
                key: 'recharge-methods',
                label: 'Kênh nạp đang bật',
                value: formatNumber(summary.active),
                note: `${formatNumber(summary.total)} phương thức / ${formatNumber(summary.inactive)} đang tắt`,
                icon: CreditCard,
                iconClass: 'bg-cyan-100 text-cyan-700',
                toneClass: 'from-cyan-50 to-white',
            });
        }
    }

    loading.value = false;
};

onMounted(async () => {
    await loadDashboard();
});
</script>

<template>
    <div class="space-y-5">
        <section
            class="overflow-hidden rounded-[10px] border border-slate-200 bg-[radial-gradient(circle_at_top_right,rgba(96,165,250,0.25),transparent_28%),linear-gradient(135deg,#0f172a_0%,#162456_45%,#1d4ed8_100%)] px-5 py-6 text-white shadow-[0_28px_80px_rgba(15,23,42,0.18)] md:px-6"
        >
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(300px,0.8fr)] xl:items-end">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-blue-100/80">Admin workspace</p>
                    <h1 class="max-w-3xl text-3xl font-black tracking-[-0.04em] text-white md:text-4xl">Bảng điều khiển quản trị hệ thống</h1>
                    <p class="max-w-2xl text-sm leading-7 text-blue-50/80">
                        Theo dõi nhanh vận hành người dùng, doanh thu gói, queue xử lý và các webhook tích hợp trong cùng một màn hình điều phối.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                    <RouterLink
                        to="/admin/users"
                        class="rounded-[10px] border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15"
                    >
                        Mở quản lý user
                    </RouterLink>
                    <RouterLink
                        to="/admin/recharge-methods"
                        class="rounded-[10px] border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15"
                    >
                        Quản lý phương thức nạp
                    </RouterLink>
                    <RouterLink
                        to="/admin/queues"
                        class="rounded-[10px] border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15"
                    >
                        Kiểm tra queue
                    </RouterLink>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="item in metrics"
                :key="item.key"
                class="rounded-[10px] border border-slate-200 bg-gradient-to-br p-4 shadow-sm"
                :class="item.toneClass"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-slate-500">{{ item.label }}</p>
                        <p class="text-2xl font-black tracking-[-0.03em] text-slate-950">{{ item.value }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-[10px]" :class="item.iconClass">
                        <component :is="item.icon" class="h-5 w-5" />
                    </div>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-600">
                    {{ item.note }}
                </p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
            <div class="space-y-4">
                <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Lối tắt quản trị</h2>
                            <p class="mt-1 text-sm text-slate-500">Truy cập nhanh các module đang cần thao tác thường xuyên.</p>
                        </div>
                        <div class="hidden rounded-[10px] bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 md:inline-flex">
                            Quick actions
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <RouterLink
                            v-for="item in shortcuts"
                            :key="item.to"
                            :to="item.to"
                            class="group rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4 transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-slate-950 text-white">
                                    <component :is="item.icon" class="h-5 w-5" />
                                </div>
                                <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">
                                    {{ item.badge }}
                                </span>
                            </div>

                            <h3 class="mt-4 text-base font-semibold text-slate-950">{{ item.title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ item.description }}</p>

                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600">
                                Mở trang
                                <ArrowRight class="h-4 w-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </RouterLink>
                    </div>
                </section>

                <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Sức khỏe hệ thống</h2>
                            <p class="mt-1 text-sm text-slate-500">Theo dõi queue và webhook để phát hiện lỗi vận hành sớm.</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            :disabled="loading"
                            @click="loadDashboard"
                        >
                            {{ loading ? 'Đang tải...' : 'Làm mới' }}
                        </button>
                    </div>

                    <div class="mt-5 grid gap-4 xl:grid-cols-2">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <DatabaseBackup class="h-4 w-4 text-slate-500" />
                                <h3 class="text-sm font-semibold text-slate-900">Queue</h3>
                            </div>

                            <article
                                v-for="item in queueHealth"
                                :key="item.key"
                                class="rounded-[10px] border px-3.5 py-3"
                                :class="item.toneClass"
                            >
                                <div class="flex items-start gap-3">
                                    <component :is="item.icon" class="mt-0.5 h-4 w-4 shrink-0" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ item.title }}</p>
                                        <p class="mt-1 text-sm font-medium">{{ item.value }}</p>
                                        <p class="mt-1 text-xs leading-5 opacity-80">{{ item.note }}</p>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <GitBranchPlus class="h-4 w-4 text-slate-500" />
                                <h3 class="text-sm font-semibold text-slate-900">Webhook</h3>
                            </div>

                            <article
                                v-for="item in webhookHealth"
                                :key="item.key"
                                class="rounded-[10px] border px-3.5 py-3"
                                :class="item.toneClass"
                            >
                                <div class="flex items-start gap-3">
                                    <component :is="item.icon" class="mt-0.5 h-4 w-4 shrink-0" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ item.title }}</p>
                                        <p class="mt-1 text-sm font-medium capitalize">{{ item.value }}</p>
                                        <p class="mt-1 text-xs leading-5 opacity-80">{{ item.note }}</p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-4">
                <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-slate-950 text-white">
                            <ChartNoAxesCombined class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Tóm tắt vận hành</h2>
                            <p class="mt-1 text-sm text-slate-500">Những tín hiệu cần quan sát mỗi ngày.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4 text-blue-600" />
                                <p class="text-sm font-semibold text-slate-900">Thành viên mới</p>
                            </div>
                            <p class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">
                                {{ formatNumber(userSummary.newToday) }}
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Số liệu này giúp theo dõi độ tăng trưởng và chất lượng user active.</p>
                        </div>

                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <div class="flex items-center gap-2">
                                <ReceiptText class="h-4 w-4 text-emerald-600" />
                                <p class="text-sm font-semibold text-slate-900">Doanh thu gói</p>
                            </div>
                            <p class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">
                                {{ `${formatCash(orderSummary.revenue)}đ` }}
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Đối chiếu nhanh hiệu suất bán gói với queue và webhook để phát hiện nghẽn luồng.</p>
                        </div>

                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <div class="flex items-center gap-2">
                                <CreditCard class="h-4 w-4 text-cyan-600" />
                                <p class="text-sm font-semibold text-slate-900">Kênh nạp tiền</p>
                            </div>
                            <p class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">
                                {{ formatNumber(rechargeSummary.active) }}
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Theo dõi số phương thức nạp đang bật để tránh tình trạng thiếu bank nhận tiền.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-rose-100 text-rose-700">
                            <CircleAlert class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Điểm cần lưu ý</h2>
                            <p class="mt-1 text-sm text-slate-500">Ưu tiên kiểm tra những mục này khi hệ thống có dấu hiệu bất thường.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="rounded-[10px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                            Queue lỗi và webhook lỗi cần được xử lý trước vì ảnh hưởng trực tiếp đến đồng bộ giao dịch và callback đối tác.
                        </div>
                        <div class="rounded-[10px] border border-blue-200 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900">
                            Khi user tăng nhanh nhưng doanh thu không tăng tương ứng, hãy kiểm tra lại trạng thái gói, recharge method và hành vi queue mail.
                        </div>
                        <div class="rounded-[10px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-900">
                            Dashboard này đang lấy dữ liệu thật từ API admin nên có thể dùng làm điểm kiểm tra đầu tiên trước khi mở từng module chi tiết.
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
</template>
