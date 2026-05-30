<script setup lang="ts">
import type { RechargeMethodType } from '@/types/recharge.type';
import { Check, CircleDollarSign, Copy, CreditCard, Landmark, QrCode, ReceiptText, Smartphone, TriangleAlert, Wallet } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed } from 'vue';

const props = defineProps<{
    methods: RechargeMethodType[];
    selectedMethodKey: string;
    amount: number;
    minimumAmount: number;
    maximumAmount: number;
    bonusPercentage: number;
    transferContent: string;
    processing: boolean;
}>();

const emit = defineEmits<{
    (event: 'update:selectedMethodKey', value: string): void;
    (event: 'update:amount', value: number): void;
    (event: 'submit'): void;
    (event: 'reset'): void;
}>();

const presetAmounts = [100000, 200000, 500000, 1000000];

const iconMap: Record<string, unknown> = {
    banking: Landmark,
    momo: Smartphone,
    vnpay: QrCode,
    card: CreditCard,
};

const backgroundMap: Record<string, string> = {
    banking: 'linear-gradient(135deg,#34d399,#10b981)',
    momo: 'linear-gradient(135deg,#ec4899,#db2777)',
    vnpay: 'linear-gradient(135deg,#fb923c,#f97316)',
    card: 'linear-gradient(135deg,#3b82f6,#2563eb)',
};

const selectedMethod = computed(() => {
    return (
        props.methods.find((method) => method.key === props.selectedMethodKey) ??
        props.methods.find((method) => method.active) ??
        props.methods[0] ??
        null
    );
});

const hasTransferDestination = computed(() => {
    return Boolean(selectedMethod.value?.bank_name && selectedMethod.value?.account_number && selectedMethod.value?.account_name);
});

const calculatedBonus = computed(() => Math.floor(props.amount * (props.bonusPercentage / 100)));
const totalAmount = computed(() => props.amount + calculatedBonus.value);

const transferDetails = computed(() => {
    if (!selectedMethod.value) {
        return [];
    }

    return [
        { key: 'bank', label: 'Ngân hàng', value: selectedMethod.value.bank_name, highlight: false },
        { key: 'account_number', label: 'Số tài khoản', value: selectedMethod.value.account_number, highlight: false },
        { key: 'account_name', label: 'Chủ tài khoản', value: selectedMethod.value.account_name, highlight: false },
        { key: 'content', label: 'Nội dung', value: props.transferContent, highlight: true },
    ].filter((item) => item.value);
});

const formatCurrency = (value: number | string): string => {
    return `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))}đ`;
};

const copyValue = async (value: string | null): Promise<void> => {
    if (value === null) return;

    try {
        await navigator.clipboard.writeText(value);

        await Swal.fire({
            icon: 'success',
            title: 'Đã sao chép',
            text: value,
            timer: 1400,
            showConfirmButton: false,
        });
    } catch {
        await Swal.fire('Không thể sao chép', 'Trình duyệt không cho phép sao chép tự động.', 'error');
    }
};
</script>

<template>
    <section class="grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1fr)_280px]">
        <article class="min-w-0 overflow-hidden rounded-[10px] border border-slate-200/70 bg-white p-4 shadow-sm">
            <div class="space-y-4">
                <div class="flex flex-col items-start gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Tạo yêu cầu nạp</h2>
                        <p class="mt-1 text-xs text-slate-500">Chọn phương thức từ hệ thống và nhập số tiền cần nạp.</p>
                    </div>
                    <span class="max-w-full rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-600">1-3 phút</span>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Phương thức nạp</p>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="method in methods"
                            :key="method.key"
                            type="button"
                            class="group w-full min-w-0 overflow-hidden rounded-[10px] border px-3 py-3 text-left transition"
                            :class="
                                !method.active
                                    ? 'cursor-not-allowed border-slate-200 bg-slate-100/80 opacity-60'
                                    : selectedMethodKey === method.key
                                      ? 'border-indigo-300 bg-indigo-50/60'
                                      : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                            "
                            :disabled="!method.active"
                            @click="emit('update:selectedMethodKey', method.key)"
                        >
                            <div class="flex w-full min-w-0 items-start gap-3">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] text-white"
                                    :style="{ background: backgroundMap[method.key] ?? backgroundMap.banking }"
                                >
                                    <component :is="iconMap[method.key] ?? Wallet" class="h-4 w-4" />
                                </div>

                                <div class="w-full min-w-0 flex-1 overflow-hidden">
                                    <div class="flex min-w-0 flex-col items-start gap-1 sm:flex-row sm:flex-wrap sm:items-center sm:gap-1.5">
                                        <p class="min-w-0 break-words text-sm font-semibold leading-5 text-slate-950">{{ method.label }}</p>
                                        <span
                                            v-if="method.badge_label"
                                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="method.badge_type === 'auto' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                        >
                                            {{ method.badge_label }}
                                        </span>
                                        <span
                                            v-if="!method.active"
                                            class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600"
                                        >
                                            Tạm tắt
                                        </span>
                                    </div>

                                    <p class="mt-0.5 break-words text-xs leading-5 text-slate-500">
                                        Mức nạp: {{ formatCurrency(method.minimum_amount) }} - {{ formatCurrency(method.maximum_amount) }}
                                    </p>
                                    <p v-if="method.description" class="mt-0.5 break-words text-xs leading-5 text-slate-400">
                                        {{ method.description }}
                                    </p>
                                </div>

                                <div
                                    class="flex h-5 w-5 items-center justify-center rounded-full border"
                                    :class="
                                        selectedMethodKey === method.key
                                            ? 'border-indigo-500 bg-indigo-500 text-white'
                                            : 'border-slate-300 text-transparent'
                                    "
                                >
                                    <Check class="h-3 w-3" />
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="rounded-[10px] border border-slate-200 bg-slate-50 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Số tiền nạp</p>
                        <span class="text-xs font-semibold text-emerald-600">Bonus: {{ formatCurrency(calculatedBonus) }}</span>
                    </div>

                    <div class="mt-2 rounded-[10px] border border-slate-200 bg-white px-3 py-3">
                        <div class="flex flex-wrap items-center gap-3 sm:flex-nowrap">
                            <div class="flex h-9 w-9 items-center justify-center rounded-[10px] bg-indigo-50 text-indigo-600">
                                <CircleDollarSign class="h-4 w-4" />
                            </div>
                            <input
                                :value="amount"
                                type="number"
                                :min="minimumAmount"
                                :max="maximumAmount"
                                step="50000"
                                class="min-w-0 flex-1 bg-transparent text-[24px] font-bold text-slate-950 outline-none placeholder:text-slate-300 sm:text-[28px]"
                                placeholder="0"
                                @input="emit('update:amount', Number(($event.target as HTMLInputElement).value))"
                            />
                            <span
                                class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400 sm:text-sm sm:normal-case sm:tracking-normal"
                            >
                                VND
                            </span>
                        </div>
                    </div>

                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <button
                            v-for="item in presetAmounts"
                            :key="item"
                            type="button"
                            class="rounded-[10px] border px-3 py-2 text-sm font-semibold transition"
                            :class="
                                amount === item
                                    ? 'border-indigo-300 bg-indigo-50 text-indigo-600'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                            "
                            @click="emit('update:amount', item)"
                        >
                            {{ formatCurrency(item) }}
                        </button>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                        <p class="font-medium text-slate-500">Tối thiểu: {{ formatCurrency(minimumAmount) }}</p>
                        <p class="font-medium text-slate-500">Tối đa: {{ formatCurrency(maximumAmount) }}</p>
                    </div>

                    <p v-if="amount < minimumAmount" class="mt-2 text-xs font-medium text-rose-500">Số tiền chưa đạt mức tối thiểu.</p>
                    <p v-else-if="amount > maximumAmount" class="mt-2 text-xs font-medium text-rose-500">Số tiền vượt quá hạn mức tối đa.</p>
                </div>

                <div v-if="hasTransferDestination" class="overflow-hidden rounded-[10px] border border-indigo-100 bg-indigo-50/40 p-3">
                    <div class="mb-2 flex flex-col items-start gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                        <p class="text-sm font-semibold text-slate-950">Thông tin chuyển khoản</p>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-indigo-600">Từ dữ liệu hệ thống</span>
                    </div>

                    <div class="grid gap-2 md:grid-cols-2">
                        <div
                            v-for="detail in transferDetails"
                            :key="detail.key"
                            class="flex min-w-0 items-start justify-between gap-3 overflow-hidden rounded-[10px] border border-white bg-white px-3 py-2.5"
                        >
                            <div class="min-w-0">
                                <p class="text-[11px] uppercase tracking-[0.12em] text-slate-400">{{ detail.label }}</p>
                                <p class="mt-1 break-all text-sm font-semibold" :class="detail.highlight ? 'text-indigo-600' : 'text-slate-900'">
                                    {{ detail.value }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] border border-slate-200 text-slate-500 hover:text-indigo-600"
                                @click="copyValue(detail.value)"
                            >
                                <Copy class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">Mã giao dịch sẽ được tạo tự động sau khi bạn tạo yêu cầu nạp.</p>
                </div>

                <div class="flex flex-col gap-2 border-t border-slate-100 pt-3 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-xs text-slate-500">Yêu cầu sẽ được xử lý theo thông tin nhận tiền đang cấu hình trong hệ thống.</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                            @click="emit('reset')"
                        >
                            Làm mới
                        </button>
                        <button
                            type="button"
                            class="rounded-[10px] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="
                                processing || amount < minimumAmount || amount > maximumAmount || !selectedMethod?.active || !hasTransferDestination
                            "
                            @click="emit('submit')"
                        >
                            {{ processing ? 'Đang tạo...' : 'Tạo yêu cầu' }}
                        </button>
                    </div>
                </div>
            </div>
        </article>

        <aside class="min-w-0 space-y-3 overflow-hidden">
            <article class="rounded-[10px] border border-slate-200/70 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <ReceiptText class="h-4 w-4 text-indigo-600" />
                    <p class="text-sm font-semibold text-slate-950">Tóm tắt</p>
                </div>

                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Phương thức</span>
                        <span class="font-semibold text-slate-950">{{ selectedMethod?.label ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Số tiền</span>
                        <span class="font-semibold text-slate-950">{{ formatCurrency(amount) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Bonus</span>
                        <span class="font-semibold text-emerald-600">+{{ formatCurrency(calculatedBonus) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-2">
                        <span class="text-slate-500">Tổng nhận</span>
                        <span class="font-bold text-emerald-600">{{ formatCurrency(totalAmount) }}</span>
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200/70 bg-white p-4 shadow-sm">
                <p class="text-sm font-semibold text-slate-950">Hướng dẫn</p>
                <div class="mt-3 space-y-2 text-xs text-slate-500">
                    <p>1. Chọn phương thức nạp đang hoạt động.</p>
                    <p>2. Nhập số tiền trong hạn mức cho phép.</p>
                    <p>3. Chuyển khoản đúng thông tin hệ thống cung cấp.</p>
                    <p>4. Theo dõi trạng thái ở tab lịch sử giao dịch.</p>
                </div>
            </article>

            <article class="rounded-[10px] border border-amber-200 bg-amber-50 p-3 shadow-sm">
                <div class="flex items-start gap-2">
                    <TriangleAlert class="mt-0.5 h-4 w-4 text-amber-500" />
                    <div class="text-xs text-slate-600">
                        <p class="font-semibold text-slate-900">Lưu ý</p>
                        <p class="mt-1">Nếu nạp quá lâu chưa ghi nhận, hãy kiểm tra lại nội dung chuyển khoản hoặc liên hệ hỗ trợ.</p>
                    </div>
                </div>
            </article>
        </aside>
    </section>
</template>
