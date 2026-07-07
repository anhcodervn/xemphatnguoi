<script setup lang="ts">
import { clientCaptchaService } from '@/services/client-captcha.service';
import { clientNotificationService } from '@/services/client-notification.service';
import { useUserStore } from '@/stores/user.store';
import type { ClientNotificationItem } from '@/types/client-notification.type';
import { handleErrorResponse } from '@/utils/response';
import { ArrowRight, BellRing, CheckCircle2, Clock3, Coins, KeyRound, ReceiptText, ShieldCheck, Wallet, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const userStore = useUserStore();
const loading = ref(true);
const systemNotification = ref<ClientNotificationItem | null>(null);
const showSystemNoticeModal = ref(false);
const overview = ref({
    summary: {
        total_tasks: 0,
        pending_tasks: 0,
        solved_tasks: 0,
        failed_tasks: 0,
        spent: 0,
    },
    recent_tasks: [] as Array<Record<string, unknown>>,
});

const displayName = computed(() => userStore.displayName || 'bạn');

const metrics = computed(() => [
    { label: 'Tổng yêu cầu', value: overview.value.summary.total_tasks, icon: KeyRound, iconClass: 'bg-blue-100 text-blue-600' },
    { label: 'Đang chờ', value: overview.value.summary.pending_tasks, icon: Clock3, iconClass: 'bg-cyan-100 text-cyan-600' },
    { label: 'Đã giải', value: overview.value.summary.solved_tasks, icon: CheckCircle2, iconClass: 'bg-emerald-100 text-emerald-600' },
    { label: 'Đã chi', value: `${overview.value.summary.spent}`, icon: Coins, iconClass: 'bg-violet-100 text-violet-600' },
]);

const recentTasks = computed(() =>
    overview.value.recent_tasks.slice(0, 8).map((task) => ({
        taskCode: String(task.task_code ?? ''),
        serviceCode: String(task.service_code ?? 'captcha-task'),
        status: String(task.status ?? 'pending').toLowerCase(),
    })),
);

const SYSTEM_NOTICE_STORAGE_KEY = 'giapcaptcha_system_notice_hidden';
const HIDE_NOTICE_DURATION_MS = 2 * 60 * 60 * 1000;

const loadOverview = async (): Promise<void> => {
    try {
        loading.value = true;

        if (!userStore.user) {
            await userStore.bootstrap({ silent: true });
        }

        overview.value = await clientCaptchaService.overview();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const isNoticeHidden = (notificationId: number): boolean => {
    const rawValue = window.localStorage.getItem(SYSTEM_NOTICE_STORAGE_KEY);

    if (!rawValue) {
        return false;
    }

    try {
        const payload = JSON.parse(rawValue) as { id?: number; hidden_until?: number };

        if (payload.id !== notificationId) {
            return false;
        }

        return typeof payload.hidden_until === 'number' && payload.hidden_until > Date.now();
    } catch {
        return false;
    }
};

const hideNoticeForTwoHours = (): void => {
    if (!systemNotification.value) {
        showSystemNoticeModal.value = false;
        return;
    }

    window.localStorage.setItem(
        SYSTEM_NOTICE_STORAGE_KEY,
        JSON.stringify({
            id: systemNotification.value.id,
            hidden_until: Date.now() + HIDE_NOTICE_DURATION_MS,
        }),
    );

    showSystemNoticeModal.value = false;
};

const closeNoticeModal = (): void => {
    showSystemNoticeModal.value = false;
};

const openSystemNotification = async (): Promise<void> => {
    if (!systemNotification.value) {
        return;
    }

    const item = systemNotification.value;

    if (!item.is_read) {
        try {
            await clientNotificationService.markRead(item.id);
            item.is_read = true;
        } catch (error) {
            handleErrorResponse(error);
            return;
        }
    }

    showSystemNoticeModal.value = false;

    if (item.redirect_url) {
        window.location.href = item.redirect_url;
    }
};

const loadSystemNotification = async (): Promise<void> => {
    try {
        const response = await clientNotificationService.list({ scope: 'system', per_page: 1 });
        const latestNotification = response.data[0] ?? null;

        systemNotification.value = latestNotification;

        if (latestNotification && !isNoticeHidden(latestNotification.id)) {
            showSystemNoticeModal.value = true;
        }
    } catch (error) {
        handleErrorResponse(error);
    }
};

onMounted(async () => {
    await Promise.all([loadOverview(), loadSystemNotification()]);
});
</script>

<template>
    <div class="space-y-5">
        <teleport to="body">
            <div v-if="showSystemNoticeModal && systemNotification" class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/35 p-4">
                <div class="w-full max-w-2xl rounded-[24px] border border-sky-100 bg-white shadow-[0_30px_80px_-30px_rgba(37,99,235,0.18)]">
                    <div class="flex items-start justify-between gap-4 border-b border-sky-100 px-6 py-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[16px] bg-sky-100 text-blue-600">
                                <BellRing class="h-6 w-6" />
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-600">Thông báo hệ thống</p>
                                <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">{{ systemNotification.title }}</h2>
                                <p class="mt-2 text-xs text-slate-400">{{ systemNotification.created_at ? new Date(systemNotification.created_at).toLocaleString('vi-VN') : '' }}</p>
                            </div>
                        </div>

                        <button type="button" class="rounded-[12px] border border-sky-100 p-2 text-slate-500 transition hover:bg-sky-50" @click="closeNoticeModal">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="px-6 py-5">
                        <div class="rounded-[18px] bg-sky-50/70 px-4 py-4 text-sm leading-7 text-slate-700">
                            {{ systemNotification.content }}
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-sky-100 px-6 py-5">
                        <button
                            type="button"
                            class="rounded-[12px] border border-sky-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-sky-50"
                            @click="hideNoticeForTwoHours"
                        >
                            Ẩn trong 2 giờ
                        </button>

                        <button
                            type="button"
                            class="rounded-[12px] bg-gradient-to-r from-blue-600 via-cyan-500 to-teal-400 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_12px_24px_rgba(37,99,235,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_32px_rgba(37,99,235,0.26)]"
                            @click="openSystemNotification"
                        >
                            {{ systemNotification.redirect_url ? 'Xem chi tiết' : 'Đã hiểu' }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        <section class="relative overflow-hidden rounded-[24px] border border-sky-100 bg-[linear-gradient(135deg,#ffffff_0%,#eef8ff_48%,#dff7f4_100%)] p-7 shadow-[0_18px_40px_rgba(37,99,235,0.08)]">
            <div class="absolute inset-y-0 right-0 hidden w-[44%] lg:block">
                <div class="absolute right-[-5%] top-[8%] h-80 w-80 rounded-full bg-cyan-200/35 blur-3xl"></div>
                <div class="absolute bottom-[-12%] right-[14%] h-52 w-52 rounded-full bg-blue-200/35 blur-2xl"></div>
                <div class="absolute left-[8%] top-[18%] h-[2px] w-48 rounded-full bg-cyan-300/70"></div>
                <div class="absolute left-[2%] top-[34%] h-32 w-64 rounded-full border border-cyan-300/60"></div>
                <div class="absolute left-[10%] top-[28%] h-40 w-72 rounded-full border border-sky-200/70"></div>
                <div class="absolute bottom-10 right-14 flex h-40 w-40 items-center justify-center rounded-full bg-white/70 shadow-[0_24px_40px_rgba(59,130,246,0.15)]">
                    <div class="flex h-24 w-24 items-center justify-center rounded-[28px] bg-gradient-to-br from-cyan-400 to-teal-400 text-white shadow-[0_16px_32px_rgba(45,212,191,0.32)]">
                        <ShieldCheck class="h-12 w-12" />
                    </div>
                </div>
                <div class="absolute right-10 top-10 h-3 w-3 rounded-full bg-blue-300/80"></div>
                <div class="absolute right-32 top-20 h-2.5 w-2.5 rounded-full bg-cyan-300/80"></div>
                <div class="absolute right-8 top-40 h-4 w-4 rounded-full bg-sky-200/80"></div>
                <div class="absolute bottom-16 left-10 h-3 w-3 rounded-full bg-teal-200/80"></div>
            </div>

            <div class="relative z-10 max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-600">GIACAPTCHA.VN</p>
                <h1 class="mt-4 text-3xl font-black tracking-[-0.05em] text-slate-950 sm:text-[2.55rem]">
                    Dashboard giải captcha qua API cho {{ displayName }}
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-8 text-slate-600">
                    Theo dõi nhanh số lượng yêu cầu đã gửi, trạng thái xử lý, tổng chi phí và số dư để vận hành tích hợp captcha ổn định hơn.
                </p>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <RouterLink
                        to="/services"
                        class="inline-flex items-center justify-center gap-2 rounded-[14px] bg-gradient-to-r from-blue-600 via-cyan-500 to-teal-400 px-6 py-3.5 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(37,99,235,0.24)] transition hover:-translate-y-0.5 hover:shadow-[0_18px_36px_rgba(37,99,235,0.28)]"
                    >
                        Xem bảng dịch vụ
                        <ArrowRight class="h-4 w-4" />
                    </RouterLink>
                    <RouterLink
                        to="/api-docs"
                        class="inline-flex items-center justify-center gap-2 rounded-[14px] border border-sky-200 bg-white px-6 py-3.5 text-sm font-semibold text-blue-700 transition hover:bg-sky-50"
                    >
                        <ReceiptText class="h-4 w-4" />
                        Mở tài liệu API
                    </RouterLink>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="metric in metrics"
                :key="metric.label"
                class="rounded-[18px] border border-slate-200 bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.06)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ metric.label }}</p>
                        <p class="mt-3 text-[2rem] font-black tracking-[-0.05em] text-slate-950">{{ loading ? '--' : metric.value }}</p>
                    </div>
                    <div class="flex h-14 w-14 items-center justify-center rounded-[18px]" :class="metric.iconClass">
                        <component :is="metric.icon" class="h-6 w-6" />
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_360px]">
            <article class="rounded-[22px] border border-slate-200 bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.06)] sm:p-6">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <ReceiptText class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-[1.65rem] font-bold tracking-[-0.03em] text-slate-950">Yêu cầu gần đây</h2>
                        <p class="mt-1 text-sm text-slate-500">Các request solve captcha mới nhất từ tài khoản của bạn.</p>
                    </div>
                </div>

                <div v-if="loading" class="mt-5 rounded-[14px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                    Đang tải dữ liệu...
                </div>

                <div v-else-if="recentTasks.length === 0" class="mt-5 rounded-[14px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                    Chưa có yêu cầu captcha nào.
                </div>

                <div v-else class="mt-5 space-y-3">
                    <div
                        v-for="task in recentTasks"
                        :key="task.taskCode"
                        class="flex items-center gap-3 rounded-[16px] border border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] px-4 py-3.5 transition hover:border-sky-200 hover:bg-sky-50/40"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                            <ReceiptText class="h-4.5 w-4.5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-semibold text-slate-900">{{ task.serviceCode }}</p>
                            <p class="truncate text-sm text-slate-500">{{ task.taskCode }}</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-4 py-1.5 text-xs font-semibold capitalize text-emerald-700">
                            {{ task.status }}
                        </span>
                    </div>
                </div>

                <div class="mt-5">
                    <RouterLink to="/captcha-history" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 transition hover:text-cyan-600">
                        Xem tất cả lịch sử
                        <ArrowRight class="h-4 w-4" />
                    </RouterLink>
                </div>
            </article>

            <article class="relative overflow-hidden rounded-[22px] border border-slate-200 bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.06)] sm:p-6">
                <div class="relative z-10">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                            <Wallet class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-[1.65rem] font-bold tracking-[-0.03em] text-slate-950">Thanh toán theo ví</h2>
                            <p class="mt-1 text-sm text-slate-500">Hệ thống không còn chạy theo gói thuê bao, chi phí solve captcha được trừ trực tiếp theo từng request.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3 text-sm leading-7 text-slate-600">
                        <p>API key có thể tạo ngay sau khi đăng nhập, quản lý whitelist IP và quay secret trong trang hồ sơ.</p>
                        <p>Danh mục dịch vụ, biểu phí và cấu hình xử lý captcha luôn được cập nhật trực tiếp từ hệ thống quản trị.</p>
                    </div>

                    <div class="mt-6 flex flex-col gap-3">
                        <RouterLink
                            to="/wallet"
                            class="inline-flex items-center justify-center gap-2 rounded-[14px] bg-gradient-to-r from-blue-600 via-cyan-500 to-teal-400 px-5 py-3.5 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(37,99,235,0.24)] transition hover:-translate-y-0.5 hover:shadow-[0_18px_36px_rgba(37,99,235,0.28)]"
                        >
                            Mở ví của bạn
                            <ArrowRight class="h-4 w-4" />
                        </RouterLink>
                        <RouterLink
                            to="/services"
                            class="inline-flex items-center justify-center gap-2 rounded-[14px] border border-sky-200 bg-white px-5 py-3.5 text-sm font-semibold text-blue-700 transition hover:bg-sky-50"
                        >
                            Xem dịch vụ đang bật
                            <Layers3 class="h-4 w-4" />
                        </RouterLink>
                    </div>
                </div>

                <div class="pointer-events-none absolute bottom-0 right-0 h-44 w-44 rounded-full bg-cyan-200/25 blur-3xl"></div>
                <div class="pointer-events-none absolute bottom-10 right-8 flex h-28 w-28 items-center justify-center rounded-full bg-gradient-to-br from-blue-50 to-cyan-50 shadow-[0_18px_32px_rgba(59,130,246,0.14)]">
                    <div class="flex h-20 w-20 items-center justify-center rounded-[24px] bg-gradient-to-br from-blue-500 to-cyan-400 text-white shadow-[0_16px_28px_rgba(59,130,246,0.25)]">
                        <Wallet class="h-10 w-10" />
                    </div>
                </div>
                <div class="pointer-events-none absolute bottom-20 left-8 h-4 w-4 rounded-full bg-orange-200"></div>
                <div class="pointer-events-none absolute bottom-12 left-16 h-3 w-3 rounded-full bg-blue-200"></div>
                <div class="pointer-events-none absolute bottom-24 right-28 h-2.5 w-2.5 rounded-full bg-cyan-300"></div>
            </article>
        </section>
    </div>
</template>
