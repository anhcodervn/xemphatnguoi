<script setup lang="ts">
import type { DepositRequestItem } from '@/types/recharge-config.type';
import { AlertTriangle, ArrowLeft, CheckCircle2, Clock3, Copy, Landmark, QrCode, RefreshCw, ReceiptText } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    request: DepositRequestItem;
    copiedField: string | null;
    countdownLabel: string;
    countdownProgress: number;
    isExpired: boolean;
    isRefreshing: boolean;
    isConfirming: boolean;
}>();

const emit = defineEmits<{
    back: [];
    refresh: [];
    confirm: [];
    copy: [field: 'bankName' | 'accountNumber' | 'accountName' | 'content' | 'amount' | 'code'];
}>();

const statusMeta = computed(() => {
    switch (props.request.status) {
        case 'paid':
            return { label: 'Đã thanh toán', className: 'border-emerald-200 bg-emerald-50 text-emerald-700' };
        case 'processing':
            return { label: 'Đang đối soát', className: 'border-sky-200 bg-sky-50 text-sky-700' };
        case 'expired':
            return { label: 'Hết thời gian', className: 'border-rose-200 bg-rose-50 text-rose-700' };
        case 'failed':
            return { label: 'Thất bại', className: 'border-rose-200 bg-rose-50 text-rose-700' };
        case 'cancelled':
            return { label: 'Đã hủy', className: 'border-slate-200 bg-slate-100 text-slate-600' };
        default:
            return { label: 'Đang chờ thanh toán', className: 'border-amber-200 bg-amber-50 text-amber-700' };
    }
});

const totalReceiveLabel = computed(() => formatMoney(props.request.amount + props.request.bonus_amount));
const amountLabel = computed(() => formatMoney(props.request.amount));
const bonusLabel = computed(() => formatMoney(props.request.bonus_amount));
const canConfirm = computed(() => ['pending', 'processing'].includes(props.request.status) && !props.isExpired);

function formatMoney(value: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 space-y-3">
                    <button type="button" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-500" @click="emit('back')">
                        <ArrowLeft class="h-4 w-4" />
                        Quay lại nạp tiền
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-[24px] font-black tracking-[-0.05em] text-slate-950 sm:text-[34px]">Thanh toán chuyển khoản</h2>
                        <p class="mt-2 break-words text-sm leading-6 text-slate-500">Hoàn tất thanh toán trước khi hết thời gian và giữ nguyên nội dung chuyển khoản để hệ thống đối soát nhanh hơn.</p>
                    </div>
                </div>

                <span class="inline-flex w-fit shrink-0 rounded-[10px] border px-3 py-2 text-sm font-semibold" :class="statusMeta.className">
                    {{ statusMeta.label }}
                </span>
            </div>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] bg-indigo-50 text-indigo-600">
                        <Clock3 class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-950">Còn lại {{ countdownLabel }}</p>
                        <p class="mt-1 break-words text-sm text-slate-500">
                            {{ isExpired ? 'Lệnh nạp đã hết thời gian. Hãy tạo lệnh mới nếu cần tiếp tục.' : 'Đơn sẽ tự hủy khi hết thời gian thanh toán.' }}
                        </p>
                    </div>
                </div>

                <p class="shrink-0 text-[30px] font-black tracking-[-0.06em] text-indigo-600 sm:text-[48px]">{{ countdownLabel }}</p>
            </div>

            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-indigo-500 transition-[width] duration-1000" :style="{ width: `${countdownProgress}%` }"></div>
            </div>
        </section>

        <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="min-w-0 rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="grid gap-4 lg:grid-cols-[220px_minmax(0,1fr)]">
                    <article class="min-w-0 rounded-[10px] border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center gap-2">
                            <QrCode class="h-4.5 w-4.5 text-indigo-600" />
                            <h3 class="text-lg font-bold text-slate-950">QR thanh toán</h3>
                        </div>

                        <div class="mt-4 flex min-h-[220px] items-center justify-center rounded-[10px] border border-slate-200 bg-white p-4">
                            <img v-if="props.request.qr_url" :src="props.request.qr_url" alt="QR thanh toán" class="h-full max-h-[210px] w-full max-w-[210px] object-contain" />
                            <div v-else class="space-y-2 text-center text-sm text-slate-500">
                                <p>Chưa có QR thanh toán.</p>
                                <p>Vui lòng dùng thông tin chuyển khoản bên cạnh.</p>
                            </div>
                        </div>

                        <p class="mt-4 text-sm leading-6 text-slate-500">Quét mã bằng ứng dụng ngân hàng để điền sẵn số tiền và nội dung chuyển khoản.</p>
                    </article>

                    <article class="min-w-0 rounded-[10px] border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center gap-2">
                            <Landmark class="h-4.5 w-4.5 text-indigo-600" />
                            <h3 class="text-lg font-bold text-slate-950">Thông tin chuyển khoản</h3>
                        </div>

                        <div class="mt-4 grid gap-3">
                            <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                                <div class="flex min-w-0 items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Ngân hàng</p>
                                        <p class="mt-2 break-words text-base font-semibold text-slate-950">{{ props.request.bank_name || '--' }}</p>
                                    </div>
                                    <button type="button" class="shrink-0 rounded-[10px] border border-slate-200 bg-white p-2 text-slate-400 transition hover:text-indigo-600" @click="emit('copy', 'bankName')">
                                        <CheckCircle2 v-if="props.copiedField === 'bankName'" class="h-4 w-4" />
                                        <Copy v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                                    <div class="flex min-w-0 items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Số tài khoản</p>
                                            <p class="mt-2 break-all text-base font-semibold text-slate-950">{{ props.request.account_number || '--' }}</p>
                                        </div>
                                        <button type="button" class="shrink-0 rounded-[10px] border border-slate-200 bg-white p-2 text-slate-400 transition hover:text-indigo-600" @click="emit('copy', 'accountNumber')">
                                            <CheckCircle2 v-if="props.copiedField === 'accountNumber'" class="h-4 w-4" />
                                            <Copy v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                                    <div class="flex min-w-0 items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Chủ tài khoản</p>
                                            <p class="mt-2 break-words text-base font-semibold text-slate-950">{{ props.request.account_name || '--' }}</p>
                                        </div>
                                        <button type="button" class="shrink-0 rounded-[10px] border border-slate-200 bg-white p-2 text-slate-400 transition hover:text-indigo-600" @click="emit('copy', 'accountName')">
                                            <CheckCircle2 v-if="props.copiedField === 'accountName'" class="h-4 w-4" />
                                            <Copy v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="rounded-[10px] border border-slate-200 bg-indigo-50 px-4 py-3">
                                    <div class="flex min-w-0 items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-400">Mã giao dịch</p>
                                            <p class="mt-2 break-all text-base font-black text-indigo-700">{{ props.request.content || '--' }}</p>
                                        </div>
                                        <button type="button" class="shrink-0 rounded-[10px] border border-indigo-200 bg-white p-2 text-indigo-500 transition hover:text-indigo-700" @click="emit('copy', 'content')">
                                            <CheckCircle2 v-if="props.copiedField === 'content'" class="h-4 w-4" />
                                            <Copy v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                                    <div class="flex min-w-0 items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Số tiền</p>
                                            <p class="mt-2 text-base font-black text-slate-950">{{ amountLabel }}</p>
                                        </div>
                                        <button type="button" class="shrink-0 rounded-[10px] border border-slate-200 bg-white p-2 text-slate-400 transition hover:text-indigo-600" @click="emit('copy', 'amount')">
                                            <CheckCircle2 v-if="props.copiedField === 'amount'" class="h-4 w-4" />
                                            <Copy v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[10px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                                <div class="flex items-start gap-3">
                                    <AlertTriangle class="mt-0.5 h-4.5 w-4.5 shrink-0 text-amber-600" />
                                    <p>Vui lòng chuyển đúng số tiền và giữ nguyên mã giao dịch để hệ thống đối soát tự động.</p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <aside class="min-w-0 space-y-4">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <ReceiptText class="h-4.5 w-4.5 text-indigo-600" />
                        <h3 class="text-lg font-bold text-slate-950">Tóm tắt giao dịch</h3>
                    </div>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Mã GD</dt>
                            <dd class="min-w-0 break-all text-right font-semibold text-slate-950">{{ props.request.code }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Số tiền</dt>
                            <dd class="font-semibold text-slate-950">{{ amountLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Bonus</dt>
                            <dd class="font-semibold text-emerald-600">{{ bonusLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                            <dt class="text-slate-500">Tổng nhận</dt>
                            <dd class="text-[24px] font-black tracking-[-0.04em] text-emerald-600 sm:text-[28px]">{{ totalReceiveLabel }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 space-y-3">
                        <button
                            type="button"
                            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-[10px] bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300"
                            :disabled="!canConfirm || props.isConfirming"
                            @click="emit('confirm')"
                        >
                            <RefreshCw v-if="props.isConfirming" class="h-4 w-4 animate-spin" />
                            <template v-else>Tôi đã chuyển khoản</template>
                        </button>

                        <button
                            type="button"
                            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-[10px] border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
                            :disabled="props.isRefreshing"
                            @click="emit('refresh')"
                        >
                            <RefreshCw class="h-4 w-4" :class="props.isRefreshing ? 'animate-spin' : ''" />
                            Tải lại trạng thái
                        </button>
                    </div>

                    <p class="mt-4 text-center text-xs leading-5 text-slate-500">Trang này tự động kiểm tra trạng thái giao dịch mỗi 5 giây.</p>
                </article>
            </aside>
        </div>
    </div>
</template>
