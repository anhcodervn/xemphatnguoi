<template>
    <section class="space-y-3">
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_280px]">
            <div class="space-y-3">
                <div v-if="!embedded" class="rounded-[8px] border border-white/70 bg-white/90 p-4 shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h1 class="text-[28px] font-black tracking-tight text-slate-950">Chọn gói thuê phù hợp</h1>
                            <p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-500">
                                Chọn gói phù hợp với nhu cầu sử dụng của bạn, sau đó tạo đơn và thanh toán bằng ví chính.
                            </p>
                        </div>

                        <ol class="grid grid-cols-3 gap-2 text-center sm:min-w-[320px]">
                            <li v-for="step in steps" :key="step.number" class="flex flex-col items-center gap-2 text-xs font-semibold">
                                <div class="flex w-full items-center gap-2">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-sm transition"
                                        :class="
                                            step.number === 1
                                                ? 'border-[#465fff] bg-[#465fff] text-white shadow-[0_10px_24px_rgba(70,95,255,0.2)]'
                                                : 'border-slate-200 bg-white text-slate-500'
                                        "
                                    >
                                        {{ step.number }}
                                    </div>
                                    <div class="h-px flex-1 bg-slate-200" :class="{ 'opacity-0': step.number === steps.length }" />
                                </div>
                                <span :class="step.number === 1 ? 'text-[#465fff]' : 'text-slate-400'">{{ step.label }}</span>
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="rounded-[8px] border border-white/70 bg-white/90 p-4 shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
                    <div class="flex flex-wrap gap-2.5">
                        <button
                            v-for="option in billingOptions"
                            :key="option.key"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border px-3.5 py-2 text-sm font-semibold transition"
                            :class="
                                activeBillingKey === option.key
                                    ? 'border-[#c9d1ff] bg-[#eef1ff] text-[#465fff] shadow-[0_8px_20px_rgba(70,95,255,0.1)]'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'
                            "
                            @click="activeBillingKey = option.key"
                        >
                            <span>{{ option.label }}</span>
                            <span
                                v-if="option.badge"
                                class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-emerald-600"
                            >
                                {{ option.badge }}
                            </span>
                        </button>
                    </div>

                    <div v-if="loading" class="mt-4 grid gap-3 lg:grid-cols-3">
                        <div v-for="index in 3" :key="index" class="h-[360px] animate-pulse rounded-[8px] border border-slate-200 bg-slate-100" />
                    </div>

                    <div v-else-if="filteredPackages.length" class="mt-4 grid gap-3 lg:grid-cols-2 2xl:grid-cols-3">
                        <article
                            v-for="item in filteredPackages"
                            :key="item.id"
                            class="relative flex h-full flex-col rounded-[8px] border bg-white p-4 shadow-[0_12px_24px_rgba(15,23,42,0.045)] transition"
                            :class="
                                selectedPackage?.id === item.id
                                    ? 'border-[#8f7dff] shadow-[0_18px_42px_rgba(129,104,255,0.14)] ring-1 ring-[#d9d2ff]'
                                    : 'border-slate-200 hover:-translate-y-1 hover:border-slate-300'
                            "
                        >
                            <div
                                v-if="getPackageBadge(item)"
                                class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-r from-[#6b4eff] to-[#8e63ff] px-3 py-1 text-xs font-bold text-white shadow-[0_10px_20px_rgba(107,78,255,0.22)]"
                            >
                                {{ getPackageBadge(item) }}
                            </div>

                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-2xl font-black text-slate-950">{{ item.name }}</h2>
                                    <p class="mt-1 text-sm font-medium text-slate-400">{{ item.slug }}</p>
                                </div>

                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full"
                                    :class="selectedPackage?.id === item.id ? 'bg-[#f1edff] text-[#7b61ff]' : 'bg-slate-100 text-slate-500'"
                                >
                                    <CalendarDays class="h-4.5 w-4.5" />
                                </div>
                            </div>

                            <div class="mt-5 flex items-end gap-2">
                                <p
                                    class="text-[32px] font-black leading-none"
                                    :class="selectedPackage?.id === item.id ? 'text-[#6b4eff]' : 'text-[#2f58ff]'"
                                >
                                    {{ formatCurrency(Number(item.price)) }}
                                </p>
                                <p class="pb-1 text-sm font-semibold text-slate-400">/ {{ getBillingLabel(item) }}</p>
                            </div>

                            <ul class="mt-5 flex flex-1 flex-col gap-2.5">
                                <li
                                    v-for="feature in getDisplayFeatures(item)"
                                    :key="feature"
                                    class="flex items-start gap-3 text-sm leading-6 text-slate-600"
                                >
                                    <Check class="mt-1 h-4 w-4 shrink-0 text-emerald-500" />
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>

                            <button
                                type="button"
                                class="mt-5 inline-flex h-10 w-full items-center justify-center rounded-[8px] border text-sm font-bold transition"
                                :class="
                                    selectedPackage?.id === item.id
                                        ? 'border-transparent bg-gradient-to-r from-[#465fff] to-[#6b4eff] text-white shadow-[0_14px_24px_rgba(70,95,255,0.2)]'
                                        : 'border-slate-200 bg-white text-[#465fff] hover:border-[#cfd7ff] hover:bg-[#f6f8ff]'
                                "
                                @click="selectPackage(item)"
                            >
                                {{ selectedPackage?.id === item.id ? 'Đã chọn gói' : 'Chọn gói' }}
                            </button>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-[8px] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500"
                    >
                        Chưa có gói nào phù hợp với chu kỳ đã chọn.
                    </div>
                </div>

                <div
                    class="flex flex-col gap-3 rounded-[8px] border border-white/70 bg-white/90 p-4 shadow-[0_14px_32px_rgba(15,23,42,0.05)] sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#eef2ff] text-[#465fff]">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-lg font-bold text-slate-950">Thanh toán an toàn và bảo mật</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                Quote được khóa tạm thời trong 15 phút. Thanh toán sẽ trừ trực tiếp từ ví chính.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="securityLabel in securityLabels"
                            :key="securityLabel"
                            class="inline-flex items-center rounded-[10px] bg-slate-50 px-3 py-2 text-sm font-bold text-[#465fff]"
                        >
                            {{ securityLabel }}
                        </span>
                    </div>
                </div>
            </div>

            <aside class="space-y-3">
                <div class="rounded-[8px] border border-white/70 bg-white/90 p-4 shadow-[0_14px_32px_rgba(15,23,42,0.05)] xl:sticky xl:top-24">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#f3efff] text-[#7b61ff]">
                            <ShoppingCart class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-lg font-black text-slate-950">Thông tin đơn hàng</p>
                        </div>
                    </div>

                    <dl class="mt-6 space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Ví chính</dt>
                            <dd class="text-right font-semibold text-slate-900">{{ formatCurrency(Number(walletBalance)) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Gói đã chọn</dt>
                            <dd class="text-right font-bold text-[#6b4eff]">{{ selectedPackage?.name ?? 'Chưa chọn' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Chu kỳ</dt>
                            <dd class="text-right font-semibold text-slate-900">
                                {{ selectedPackage ? toTitleCase(getBillingLabel(selectedPackage)) : 'Chưa có' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Giá gói</dt>
                            <dd class="text-right font-semibold text-slate-900">
                                {{ quote ? formatCurrency(Number(quote.price)) : '0 đ' }}
                            </dd>
                        </div>
                        <div v-if="quote && Number(quote.discount_amount) > 0" class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Coupon</dt>
                            <dd class="text-right font-semibold text-emerald-600">-{{ formatCurrency(Number(quote.discount_amount)) }}</dd>
                        </div>
                        <div v-if="quote?.credit_amount" class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Credit còn lại</dt>
                            <dd class="text-right font-semibold text-emerald-600">-{{ formatCurrency(Number(quote.credit_amount)) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">VAT (0%)</dt>
                            <dd class="text-right font-semibold text-slate-900">0 đ</dd>
                        </div>
                    </dl>

                    <div v-if="quote?.source_subscription" class="mt-5 rounded-[8px] bg-slate-50 px-3 py-3 text-xs leading-5 text-slate-500">
                        Đang quy đổi phần giá trị còn lại từ gói
                        <span class="font-bold text-slate-700">{{ quote.source_subscription.package_name }}</span
                        >.
                    </div>
                    <div class="mt-5 rounded-[8px] border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label class="text-xs font-semibold tracking-wide text-slate-600">Mã giảm giá</label>
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="quote?.coupon ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="quote?.coupon ? 'bg-emerald-500' : 'bg-slate-400'" />
                                {{ quote?.coupon ? 'Đã áp dụng' : 'Sẵn sàng' }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                            <input
                                v-model="couponCode"
                                type="text"
                                class="min-w-0 flex-1 rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#465fff] focus:ring-2 focus:ring-[#465fff]/20 disabled:cursor-not-allowed disabled:bg-slate-100"
                                placeholder="Nhập mã giảm giá"
                                :disabled="quoteLoading || applyingCoupon || !selectedPackage"
                            />
                            <button
                                type="button"
                                class="w-full shrink-0 rounded-[8px] bg-gradient-to-r from-[#465fff] to-[#5e45ff] px-3 py-2 text-sm font-semibold text-white transition hover:brightness-95 disabled:opacity-60 sm:w-20"
                                :disabled="quoteLoading || applyingCoupon || !selectedPackage"
                                @click="applyCoupon"
                            >
                                {{ applyingCoupon ? 'Đang...' : 'Áp dụng' }}
                            </button>
                            <button
                                v-if="quote?.coupon || couponCode.trim() !== ''"
                                type="button"
                                class="w-full shrink-0 rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 disabled:opacity-60 sm:w-16"
                                :disabled="quoteLoading || applyingCoupon || couponCode.trim() === ''"
                                @click="clearCoupon"
                            >
                                Gỡ
                            </button>
                        </div>

                        <div class="mt-2 min-h-[18px]">
                            <p v-if="quoteError" class="text-xs text-rose-600">{{ quoteError }}</p>
                            <p v-else-if="quote?.coupon" class="text-xs text-emerald-600">
                                Đang dùng mã: <span class="font-semibold">{{ quote.coupon.code }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-dashed border-slate-200 pt-5">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-slate-500">Tổng thanh toán</span>
                            <strong class="text-[28px] font-black tracking-tight text-[#6b4eff]">
                                {{ quote ? formatCurrency(Number(quote.final_amount)) : '0 đ' }}
                            </strong>
                        </div>

                        <div class="mt-2 text-xs text-slate-400">
                            <span v-if="quoteLoading">Đang tính quote...</span>
                            <span v-else-if="quote">Quote hết hạn lúc {{ formatDateTime(quote.expires_at) }}</span>
                        </div>

                        <button
                            type="button"
                            class="mt-5 inline-flex h-11 w-full items-center justify-center gap-2 rounded-[8px] bg-gradient-to-r from-[#465fff] to-[#5e45ff] px-4 text-sm font-bold text-white shadow-[0_12px_22px_rgba(70,95,255,0.18)] transition hover:translate-y-[-1px] hover:shadow-[0_16px_28px_rgba(70,95,255,0.22)] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="!selectedPackage || !quote || !!quoteError || quoteLoading || submittingOrder"
                            @click="purchaseSelectedPackage"
                        >
                            <span>{{ submittingOrder ? 'Đang thanh toán...' : 'Tiếp tục thanh toán' }}</span>
                            <ArrowRight class="h-5 w-5" />
                        </button>

                        <div class="mt-4 flex items-center justify-center gap-2 text-sm font-semibold text-emerald-600">
                            <BadgeCheck class="h-5 w-5" />
                            <span>Order pending sẽ được tạo trước khi trừ ví</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-[8px] border border-white/70 bg-white/90 p-4 shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
                    <p class="text-lg font-black text-slate-950">So sánh nhanh</p>

                    <ul class="mt-4 space-y-2.5">
                        <li
                            v-for="item in filteredPackages"
                            :key="`compare-${item.id}`"
                            class="flex items-center justify-between gap-3 rounded-[8px] px-3 py-2.5"
                            :class="selectedPackage?.id === item.id ? 'bg-[#f5f2ff]' : 'bg-slate-50'"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <CalendarDays
                                    class="h-5 w-5 shrink-0"
                                    :class="selectedPackage?.id === item.id ? 'text-[#6b4eff]' : 'text-slate-400'"
                                />
                                <span
                                    class="truncate text-sm font-semibold"
                                    :class="selectedPackage?.id === item.id ? 'text-[#6b4eff]' : 'text-slate-700'"
                                >
                                    {{ item.name }}
                                </span>
                            </div>
                            <span
                                class="shrink-0 text-sm font-black"
                                :class="selectedPackage?.id === item.id ? 'text-[#6b4eff]' : 'text-emerald-600'"
                            >
                                {{ formatCurrency(Number(item.price)) }} / {{ getBillingLabel(item) }}
                            </span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>
</template>

<script setup lang="ts">
import { clientPackageService } from '@/services/client-package.service';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { ArrowRight, BadgeCheck, CalendarDays, Check, ShieldCheck, ShoppingCart } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

withDefaults(
    defineProps<{
        embedded?: boolean;
    }>(),
    {
        embedded: false,
    },
);

type ClientPackage = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string | number;
    duration_days: number;
    account_limit: number;
    can_buy_extra_account: boolean;
    extra_account_price: string | number;
    request_limit: number;
    request_per_minute: number;
    concurrent_limit: number;
    features: string[];
};

type BillingOption = {
    key: string;
    label: string;
    badge?: string;
};

type PackageQuote = {
    quote_type: string;
    price: number | string;
    discount_amount: number | string;
    credit_amount: number | string;
    final_amount: number | string;
    expires_at: string;
    coupon: {
        id: number;
        code: string;
        name: string;
        type: string;
        value: string;
        max_discount_amount: string | null;
    } | null;
    source_subscription: {
        id: number;
        package_name: string;
    } | null;
};

const steps = [
    { number: 1, label: 'Chọn gói' },
    { number: 2, label: 'Thanh toán' },
    { number: 3, label: 'Hoàn tất' },
];

const securityLabels = ['SSL', 'Wallet', 'Quote 15p'];

const loading = ref(false);
const quoteLoading = ref(false);
const applyingCoupon = ref(false);
const submittingOrder = ref(false);
const packages = ref<ClientPackage[]>([]);
const activePackageIds = ref<number[]>([]);
const selectedPackageId = ref<number | null>(null);
const activeBillingKey = ref<string>('all');
const walletBalance = ref<string>('0');
const quote = ref<PackageQuote | null>(null);
const quoteError = ref('');
const couponCode = ref('');

const selectedPackage = computed<ClientPackage | null>(() => {
    return packages.value.find((item) => item.id === selectedPackageId.value) ?? null;
});

const billingOptions = computed<BillingOption[]>(() => {
    const options: BillingOption[] = [{ key: 'all', label: 'Tất cả' }];
    const uniqueKeys = new Set<string>();

    for (const item of packages.value) {
        const key = getBillingKey(item.duration_days);

        if (uniqueKeys.has(key)) {
            continue;
        }

        uniqueKeys.add(key);

        options.push({
            key,
            label: `Theo ${getBillingLabel(item)}`,
            badge: key === 'year' ? '-20%' : undefined,
        });
    }

    return options;
});

const filteredPackages = computed<ClientPackage[]>(() => {
    if (activeBillingKey.value === 'all') {
        return packages.value;
    }

    return packages.value.filter((item) => getBillingKey(item.duration_days) === activeBillingKey.value);
});

async function fetchPackages(): Promise<void> {
    try {
        loading.value = true;

        const response = await clientPackageService.list();

        packages.value = response.packages ?? [];
        activePackageIds.value = response.active_subscription_package_ids ?? [];
        walletBalance.value = response.summary?.wallet_balance ?? '0';

        if (!packages.value.length) {
            selectedPackageId.value = null;
            quote.value = null;
            return;
        }

        const defaultPackage =
            packages.value.find((item) => !activePackageIds.value.includes(item.id)) ??
            packages.value.find((item) => activePackageIds.value.includes(item.id)) ??
            packages.value[0];

        selectedPackageId.value = defaultPackage.id;
        activeBillingKey.value = getBillingKey(defaultPackage.duration_days);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
}

async function refreshQuote(showToast = false): Promise<void> {
    if (!selectedPackage.value) {
        quote.value = null;
        quoteError.value = '';
        return;
    }

    try {
        quoteLoading.value = true;
        quoteError.value = '';
        quote.value = await clientPackageService.quote({
            package_id: selectedPackage.value.id,
            coupon_code: couponCode.value.trim() || undefined,
        });
    } catch (error: any) {
        quote.value = null;
        quoteError.value = error?.response?.data?.message ?? 'Không thể tính quote cho gói đang chọn.';

        if (showToast) {
            handleErrorResponse(error);
        }
    } finally {
        quoteLoading.value = false;
    }
}

async function applyCoupon(): Promise<void> {
    if (!selectedPackage.value) {
        return;
    }

    try {
        applyingCoupon.value = true;
        await refreshQuote(true);
    } finally {
        applyingCoupon.value = false;
    }
}

async function clearCoupon(): Promise<void> {
    couponCode.value = '';
    quoteError.value = '';
    await refreshQuote();
}

function selectPackage(item: ClientPackage): void {
    selectedPackageId.value = item.id;
}

async function purchaseSelectedPackage(): Promise<void> {
    if (!selectedPackage.value || !quote.value) {
        return;
    }

    try {
        submittingOrder.value = true;

        const orderResponse = await clientPackageService.createOrder({
            package_id: selectedPackage.value.id,
            payment_method: 'wallet',
            coupon_code: couponCode.value.trim() || undefined,
        });

        const packageOrderId = orderResponse.data.data.id as number;
        const payResponse = await clientPackageService.payOrder(packageOrderId, {
            payment_method: 'wallet',
        });

        handleSuccessResponse(payResponse, 'Thanh toán thành công. Gói đã được kích hoạt.');
        walletBalance.value = payResponse.data.data.wallet.balance;
        await fetchPackages();
    } catch (error) {
        handleErrorResponse(error);
        await fetchPackages();
    } finally {
        submittingOrder.value = false;
    }
}

function getBillingKey(durationDays: number): string {
    if (durationDays <= 7) {
        return 'day';
    }

    if (durationDays <= 31) {
        return 'month';
    }

    return 'year';
}

function getBillingLabel(item: ClientPackage): string {
    const key = getBillingKey(item.duration_days);

    if (key === 'day') {
        return 'ngày';
    }

    if (key === 'month') {
        return 'tháng';
    }

    return 'năm';
}

function getPackageBadge(item: ClientPackage): string | null {
    if (activePackageIds.value.includes(item.id)) {
        return 'Đang dùng';
    }

    if (selectedPackage.value?.id === item.id) {
        return 'Đã chọn';
    }

    return null;
}

function getDisplayFeatures(item: ClientPackage): string[] {
    if (item.features?.length) {
        return item.features;
    }

    return [
        `Tối đa ${formatNumber(item.request_limit)} request / ${getBillingLabel(item)}`,
        `${item.concurrent_limit} luồng xử lý đồng thời`,
        `${item.account_limit} account sử dụng`,
    ];
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat('vi-VN').format(value);
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit',
    }).format(new Date(value));
}

function toTitleCase(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

watch(filteredPackages, (items) => {
    if (!items.length) {
        selectedPackageId.value = null;
        quote.value = null;
        return;
    }

    const hasSelectedPackage = items.some((item) => item.id === selectedPackageId.value);

    if (!hasSelectedPackage) {
        selectedPackageId.value = items[0].id;
    }
});

watch(
    () => selectedPackage.value?.id,
    async (packageId, previousPackageId) => {
        if (!packageId || packageId === previousPackageId) {
            return;
        }

        await refreshQuote();
    },
);

onMounted(async () => {
    await fetchPackages();
});
</script>
