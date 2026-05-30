<script setup lang="ts">
import { clientRechargeService } from '@/services/client-recharge.service';
import type { RechargeOrderType, RechargeStatus } from '@/types/recharge.type';
import { handleErrorResponse } from '@/utils/response';
import { ArrowLeft, CircleAlert, Clock3, Copy, CreditCard, Landmark, LoaderCircle, QrCode, RefreshCcw } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const order = ref<RechargeOrderType | null>(null);
const isLoading = ref(false);
const isRefreshing = ref(false);
const remainingSeconds = ref(0);
const isCheckingStatus = ref(false);
const handledPaid = ref(false);

let timer: ReturnType<typeof setInterval> | null = null;
let statusPoller: ReturnType<typeof setInterval> | null = null;

const rechargeId = computed(() => String(route.params.recharge_id ?? ''));
const isAwaitingPayment = computed(() => ['pending', 'processing'].includes(order.value?.status ?? ''));

const formatCurrency = (value: string | number | null): string => `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))}đ`;

const statusLabel = (status: RechargeStatus | null | undefined): string => {
    switch (status) {
        case 'pending':
            return 'Đang chờ thanh toán';
        case 'processing':
            return 'Đang xử lý';
        case 'paid':
            return 'Đã thanh toán';
        case 'failed':
            return 'Thất bại';
        case 'cancelled':
            return 'Đã hủy';
        case 'expired':
            return 'Hết hạn';
        default:
            return 'Không xác định';
    }
};

const statusClass = (status: RechargeStatus | null | undefined): string => {
    switch (status) {
        case 'pending':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'processing':
            return 'border-sky-200 bg-sky-50 text-sky-700';
        case 'paid':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'failed':
            return 'border-rose-200 bg-rose-50 text-rose-700';
        case 'cancelled':
        case 'expired':
            return 'border-slate-200 bg-slate-100 text-slate-600';
        default:
            return 'border-slate-200 bg-slate-100 text-slate-600';
    }
};

const transferRows = computed(() => {
    if (!order.value) {
        return [];
    }

    return [
        { key: 'bank_name', label: 'Ngân hàng', value: order.value.bank_name, highlight: false },
        { key: 'account_number', label: 'Số tài khoản', value: order.value.account_number, highlight: false },
        { key: 'account_name', label: 'Chủ tài khoản', value: order.value.account_name, highlight: false },
        { key: 'transfer_content', label: 'Mã giao dịch', value: order.value.transfer_content, highlight: true },
        { key: 'amount', label: 'Số tiền', value: formatCurrency(order.value.amount), highlight: true },
    ].filter((item) => item.value);
});

const timerDisplay = computed(() => {
    const minutes = Math.floor(remainingSeconds.value / 60);
    const seconds = remainingSeconds.value % 60;

    return {
        minutes: String(Math.max(0, minutes)).padStart(2, '0'),
        seconds: String(Math.max(0, seconds)).padStart(2, '0'),
    };
});

const totalCountdownSeconds = computed(() => {
    if (!order.value?.requested_at || !order.value?.expires_at) {
        return 60 * 60;
    }

    const total = Math.floor((new Date(order.value.expires_at).getTime() - new Date(order.value.requested_at).getTime()) / 1000);
    return total > 0 ? total : 60 * 60;
});

const countdownProgress = computed(() => {
    const total = totalCountdownSeconds.value;
    if (total <= 0) {
        return 0;
    }

    return Math.max(0, Math.min(100, (remainingSeconds.value / total) * 100));
});

const qrSource = computed(() => {
    const metadata = order.value?.metadata;
    if (!metadata || typeof metadata !== 'object') {
        return null;
    }

    const candidates = [metadata.qr_image, metadata.qr_url, metadata.qr_image_url, metadata.qrCode, metadata.qr_code];
    const source = candidates.find((item) => typeof item === 'string' && item.trim() !== '');

    return typeof source === 'string' ? source : null;
});

const stopCountdown = (): void => {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
};

const startCountdown = (): void => {
    stopCountdown();

    const expiresAt = order.value?.expires_at;
    if (!expiresAt || !isAwaitingPayment.value) {
        remainingSeconds.value = 0;
        return;
    }

    const updateRemainingSeconds = (): void => {
        const diff = Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000);
        remainingSeconds.value = Math.max(0, diff);
    };

    updateRemainingSeconds();
    timer = setInterval(updateRemainingSeconds, 1000);
};

const stopStatusPolling = (): void => {
    if (statusPoller) {
        clearInterval(statusPoller);
        statusPoller = null;
    }
};

const handlePaidOrder = async (): Promise<void> => {
    if (handledPaid.value) {
        return;
    }

    handledPaid.value = true;
    stopStatusPolling();
    stopCountdown();

    let latestBalance = '0';
    try {
        const overview = await clientRechargeService.overview();
        latestBalance = String(overview.wallet.balance ?? '0');
    } catch {
        latestBalance = '0';
    }

    await Swal.fire({
        icon: 'success',
        title: 'Giao dịch thành công',
        html: `
            <div class="text-sm text-slate-600">
                <p>Mã giao dịch <strong>${order.value?.transfer_content ?? order.value?.order_code ?? ''}</strong> đã được xác nhận.</p>
                <p style="margin-top: 6px;">Số dư hiện tại: <strong>${formatCurrency(latestBalance)}</strong></p>
            </div>
        `,
    });

    await router.replace({ name: 'client.home' });
};

const syncOrderState = async (nextOrder: RechargeOrderType): Promise<void> => {
    const previousStatus = order.value?.status ?? null;
    order.value = nextOrder;
    startCountdown();

    if (nextOrder.status === 'paid') {
        await handlePaidOrder();
        return;
    }

    if (previousStatus !== nextOrder.status && ['failed', 'cancelled', 'expired'].includes(nextOrder.status)) {
        stopStatusPolling();
    }
};

const loadOrder = async (showRefreshing = false): Promise<void> => {
    if (rechargeId.value === '') {
        return;
    }

    if (showRefreshing) {
        isRefreshing.value = true;
    } else {
        isLoading.value = true;
    }

    try {
        const nextOrder = await clientRechargeService.getOrder(rechargeId.value);
        await syncOrderState(nextOrder);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        isLoading.value = false;
        isRefreshing.value = false;
    }
};

const checkTransactionStatus = async (): Promise<void> => {
    if (isCheckingStatus.value || rechargeId.value === '' || handledPaid.value) {
        return;
    }

    isCheckingStatus.value = true;
    try {
        const nextOrder = await clientRechargeService.getOrder(rechargeId.value);
        await syncOrderState(nextOrder);
    } catch {
        // Ignore polling errors, user can still refresh manually.
    } finally {
        isCheckingStatus.value = false;
    }
};

const startStatusPolling = (): void => {
    stopStatusPolling();
    statusPoller = setInterval(async () => {
        if (!isAwaitingPayment.value || handledPaid.value) {
            stopStatusPolling();
            return;
        }

        await checkTransactionStatus();
    }, 5000);
};

const copyToClipboard = async (value: string | null | undefined, label: string): Promise<void> => {
    if (!value) {
        return;
    }

    await navigator.clipboard.writeText(value);
    await Swal.fire({
        icon: 'success',
        title: 'Đã sao chép',
        text: `${label} đã được sao chép.`,
        timer: 1200,
        showConfirmButton: false,
    });
};

const refreshOrder = async (): Promise<void> => {
    await loadOrder(true);
};

const confirmTransferred = async (): Promise<void> => {
    await refreshOrder();
    if (order.value?.status === 'paid') {
        return;
    }

    await Swal.fire({
        icon: 'info',
        title: 'Đã ghi nhận',
        text: 'Hệ thống sẽ tự động đối soát theo mã giao dịch. Vui lòng chờ trong giây lát.',
    });
};

onMounted(async () => {
    await loadOrder();
    if (isAwaitingPayment.value) {
        startStatusPolling();
    }
});

onBeforeUnmount(() => {
    stopCountdown();
    stopStatusPolling();
});
</script>

<template>
    <div class="mx-auto max-w-[1180px] space-y-4 bg-[#f3f6fa] pb-4">
        <section class="rounded-[10px] border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <RouterLink :to="{ name: 'client.recharge' }" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700">
                <ArrowLeft class="h-4 w-4" />
                Quay lại nạp tiền
            </RouterLink>

            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-[1.9rem] font-bold tracking-[-0.03em] text-slate-950">Thanh toán chuyển khoản</h1>
                <span class="inline-flex w-fit items-center rounded-md border px-2 py-1 text-xs font-medium" :class="statusClass(order?.status)">
                    {{ statusLabel(order?.status) }}
                </span>
            </div>

            <p class="mt-1 text-sm text-slate-500">Hoàn tất thanh toán trước khi hết thời gian.</p>
        </section>

        <div v-if="isLoading" class="rounded-[10px] border border-slate-200 bg-white px-4 py-10 shadow-sm">
            <div class="flex items-center justify-center gap-3 text-sm text-slate-500">
                <LoaderCircle class="h-4 w-4 animate-spin" />
                Đang tải thông tin thanh toán...
            </div>
        </div>

        <div v-else-if="order" class="grid gap-4 xl:grid-cols-[minmax(0,1.65fr)_minmax(290px,0.85fr)]">
            <section class="space-y-4">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <Clock3 class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-950">Còn lại {{ timerDisplay.minutes }}:{{ timerDisplay.seconds }}</p>
                                <p class="mt-1 text-xs text-slate-500">Đơn sẽ tự huỷ khi hết thời gian thanh toán.</p>
                            </div>
                        </div>

                        <p class="text-4xl font-bold tracking-[0.14em] text-indigo-600 sm:text-[2.6rem]">{{ timerDisplay.minutes }}:{{ timerDisplay.seconds }}</p>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-indigo-500 transition-all duration-1000" :style="{ width: `${countdownProgress}%` }" />
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="grid gap-4 lg:grid-cols-[220px_minmax(0,1fr)]">
                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2">
                                <QrCode class="h-4 w-4 text-slate-700" />
                                <p class="text-sm font-semibold text-slate-900">QR thanh toán</p>
                            </div>

                            <div class="mt-4 flex justify-center">
                                <div class="flex h-[200px] w-[200px] items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                                    <img v-if="qrSource" :src="qrSource" alt="QR thanh toán" class="h-full w-full object-contain" />
                                    <div v-else class="flex flex-col items-center gap-2 text-slate-400">
                                        <QrCode class="h-12 w-12" />
                                        <p class="text-xs">QR sẽ hiển thị khi có dữ liệu</p>
                                    </div>
                                </div>
                            </div>

                            <p class="mt-3 text-center text-xs text-slate-500">Quét mã để chuyển khoản nhanh</p>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <Landmark class="h-4 w-4 text-slate-700" />
                                <h2 class="text-base font-semibold text-slate-950">Thông tin chuyển khoản</h2>
                            </div>

                            <div class="mt-4 space-y-2.5">
                                <div
                                    v-for="row in transferRows"
                                    :key="row.key"
                                    class="flex items-start justify-between gap-3 rounded-lg border px-3 py-2.5"
                                    :class="row.highlight ? 'border-indigo-100 bg-indigo-50/70' : 'border-slate-200 bg-slate-50'"
                                >
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-medium text-slate-500">{{ row.label }}</p>
                                        <p class="mt-1 break-all text-sm" :class="row.highlight ? 'font-semibold text-indigo-600' : 'font-semibold text-slate-900'">
                                            {{ row.value }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:text-indigo-600"
                                        @click="copyToClipboard(String(row.value), row.label)"
                                    >
                                        <Copy class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-700">
                                    <CircleAlert class="mt-0.5 h-4 w-4 shrink-0" />
                                    <p>Vui lòng chuyển đúng số tiền và mã giao dịch để hệ thống đối soát tự động.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <CreditCard class="h-4 w-4 text-slate-700" />
                        <h2 class="text-base font-semibold text-slate-950">Tóm tắt giao dịch</h2>
                    </div>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Mã GD</span>
                            <span class="text-right font-medium text-slate-900">{{ order.order_code }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Số tiền</span>
                            <span class="text-right font-medium text-slate-900">{{ formatCurrency(order.amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Bonus</span>
                            <span class="text-right font-medium text-emerald-600">+{{ formatCurrency(order.bonus_amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                            <span class="text-slate-500">Tổng nhận</span>
                            <span class="text-right text-2xl font-bold text-emerald-600">{{ formatCurrency(order.total_amount) }}</span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <button
                            type="button"
                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isRefreshing || !isAwaitingPayment"
                            @click="confirmTransferred"
                        >
                            <RefreshCcw class="h-4 w-4" :class="isRefreshing ? 'animate-spin' : ''" />
                            {{ isRefreshing ? 'Đang kiểm tra...' : 'Tôi đã chuyển khoản' }}
                        </button>

                        <button
                            type="button"
                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            @click="refreshOrder"
                        >
                            <RefreshCcw class="h-4 w-4" />
                            Tải lại trạng thái
                        </button>
                    </div>

                    <p class="mt-3 text-center text-xs text-slate-500">Trang này tự động kiểm tra trạng thái giao dịch mỗi 5 giây.</p>
                </article>
            </aside>
        </div>
    </div>
</template>
