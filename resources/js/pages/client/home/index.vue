<script setup lang="ts">
import AdminNotificationList from '@/pages/client/home/components/dashboard/AdminNotificationList.vue';
import ExpiringProxyList from '@/pages/client/home/components/dashboard/ExpiringProxyList.vue';
import QuickActions from '@/pages/client/home/components/dashboard/QuickActions.vue';
import RecentActivityList from '@/pages/client/home/components/dashboard/RecentActivityList.vue';
import SummaryCard from '@/pages/client/home/components/dashboard/SummaryCard.vue';
import { clientNotificationService } from '@/services/client-notification.service';
import { clientProxyService, type ProxyDashboard } from '@/services/client-proxy.service';
import type { ClientNotificationItem } from '@/types/client-notification.type';
import { Activity, ArrowRight, BellRing, Clock3, Layers3, LoaderCircle, Network, RefreshCcw, Server, WalletCards } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const dashboard = ref<ProxyDashboard | null>(null);
const loading = ref(true);
const errorMessage = ref('');
const allNotificationsLoaded = ref(false);

const money = (value: string | number | null | undefined): string =>
    `${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Number(value ?? 0))}đ`;

const loadDashboard = async (): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';

    try {
        dashboard.value = await clientProxyService.dashboard();
    } catch {
        errorMessage.value = 'Không thể tải dữ liệu tổng quan. Vui lòng thử lại.';
    } finally {
        loading.value = false;
    }
};

const openNotification = async (notification: ClientNotificationItem): Promise<void> => {
    if (!notification.is_read) {
        try {
            await clientNotificationService.markRead(notification.id);
            notification.is_read = true;

            if (dashboard.value) {
                dashboard.value.summary.unread_notifications = Math.max(0, dashboard.value.summary.unread_notifications - 1);
            }
        } catch {
            errorMessage.value = 'Không thể cập nhật trạng thái thông báo.';
            return;
        }
    }

    if (notification.redirect_url) {
        window.location.href = notification.redirect_url;
    }
};

const viewAllNotifications = async (): Promise<void> => {
    try {
        const response = await clientNotificationService.list({ scope: 'system', per_page: 100 });

        if (dashboard.value) {
            dashboard.value.notifications = response.data;
        }

        allNotificationsLoaded.value = true;
    } catch {
        errorMessage.value = 'Không thể tải toàn bộ thông báo. Vui lòng thử lại.';
    }
};

onMounted(loadDashboard);
</script>

<template>
    <div class="grid gap-5 sm:gap-6">
        <section
            class="relative overflow-hidden rounded-2xl border border-blue-400/20 bg-[linear-gradient(115deg,#0b4bd9_0%,#062b78_55%,#071f4f_100%)] px-6 py-7 text-white shadow-[0_24px_65px_-38px_rgba(3,105,161,0.75)] sm:px-8 sm:py-9"
        >
            <div class="relative z-10 max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-cyan-200">DailyProxy Control Center</p>
                <h1 class="mt-3 text-2xl font-black tracking-tight sm:text-3xl">Tổng quan hệ thống proxy của bạn</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-blue-100">
                    Theo dõi nhanh tài khoản, proxy sắp hết hạn và thông báo từ quản trị viên.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <RouterLink
                        to="/proxy-orders"
                        class="inline-flex h-10 items-center gap-2 rounded-lg bg-white px-4 text-sm font-bold text-blue-800 shadow-sm transition hover:bg-blue-50"
                    >
                        Quản lý proxy <ArrowRight class="h-4 w-4" />
                    </RouterLink>
                    <RouterLink
                        to="/services"
                        class="inline-flex h-10 items-center gap-2 rounded-lg border border-white/40 bg-white/5 px-4 text-sm font-bold text-white transition hover:bg-white/10"
                    >
                        Mua proxy <Layers3 class="h-4 w-4" />
                    </RouterLink>
                </div>
            </div>

            <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-[38%] items-center justify-center lg:flex" aria-hidden="true">
                <div class="relative flex h-40 w-52 items-center justify-center">
                    <Network class="absolute h-44 w-44 text-cyan-300/15" />
                    <div class="relative grid gap-2">
                        <div
                            class="flex h-12 w-32 items-center justify-between rounded-xl border border-cyan-300/30 bg-cyan-300/10 px-4 shadow-lg backdrop-blur-sm"
                        >
                            <Server class="h-5 w-5 text-cyan-200" /><span class="h-2 w-2 rounded-full bg-emerald-300 shadow-[0_0_12px_#6ee7b7]" />
                        </div>
                        <div
                            class="flex h-12 w-36 items-center justify-between rounded-xl border border-blue-300/30 bg-blue-300/10 px-4 shadow-lg backdrop-blur-sm"
                        >
                            <Server class="h-5 w-5 text-blue-200" /><span class="h-2 w-2 rounded-full bg-cyan-300 shadow-[0_0_12px_#67e8f9]" />
                        </div>
                        <div
                            class="flex h-12 w-32 items-center justify-between rounded-xl border border-cyan-300/30 bg-cyan-300/10 px-4 shadow-lg backdrop-blur-sm"
                        >
                            <Server class="h-5 w-5 text-cyan-200" /><span class="h-2 w-2 rounded-full bg-emerald-300 shadow-[0_0_12px_#6ee7b7]" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div
            v-if="errorMessage"
            class="flex items-center justify-between gap-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
        >
            <span>{{ errorMessage }}</span>
            <button type="button" class="inline-flex shrink-0 items-center gap-2 font-bold" @click="loadDashboard">
                <RefreshCcw class="h-4 w-4" /> Thử lại
            </button>
        </div>

        <section v-if="loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Đang tải thống kê">
            <div v-for="index in 4" :key="index" class="h-32 animate-pulse rounded-2xl border border-slate-200 bg-white/70" />
        </section>

        <section v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <SummaryCard title="Số dư" :value="money(dashboard?.summary.balance)" action="Nạp tiền" to="/wallet" :icon="WalletCards" tone="blue" />
            <SummaryCard
                title="Proxy đang hoạt động"
                :value="dashboard?.summary.active_proxies ?? 0"
                action="Xem proxy"
                to="/proxy-orders"
                :icon="Activity"
                tone="green"
            />
            <SummaryCard
                title="Sắp hết hạn"
                :value="dashboard?.summary.expiring_proxies ?? 0"
                action="Gia hạn ngay"
                to="/proxy-orders"
                :icon="Clock3"
                tone="orange"
            />
            <SummaryCard
                title="Thông báo mới"
                :value="dashboard?.summary.unread_notifications ?? 0"
                action="Xem thông báo"
                to="#dashboard-notifications"
                :icon="BellRing"
                tone="violet"
            />
        </section>

        <section v-if="loading" class="flex h-64 items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm text-slate-500">
            <LoaderCircle class="mr-2 h-5 w-5 animate-spin" /> Đang tải dữ liệu quản lý...
        </section>

        <section v-else class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.95fr)]">
            <ExpiringProxyList :proxies="dashboard?.expiring_proxies ?? []" />
            <AdminNotificationList
                :notifications="dashboard?.notifications ?? []"
                :show-all="allNotificationsLoaded"
                @open="openNotification"
                @view-all="viewAllNotifications"
            />
            <RecentActivityList :activities="dashboard?.recent_activities ?? []" />
            <QuickActions />
        </section>
    </div>
</template>
