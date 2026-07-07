<script setup lang="ts">
import Breadcrumb from "@/components/MasterLayouts/Breadcrumb/index.vue";
import { clientApiKeyService } from "@/services/client-api-key.service";
import {
    clientPackageService,
    type ClientPackageItem,
    type ClientPackageOrder,
    type ClientPackageQuote,
} from "@/services/client-package.service";
import type { CurrentUserSubscriptionType } from "@/types/user-subscription.type";
import { handleErrorResponse, handleSuccessResponse } from "@/utils/response";
import { Check, CheckCircle2, Copy, LoaderCircle, RefreshCw, ShieldCheck, X } from "lucide-vue-next";
import { computed, onMounted, ref } from "vue";

const loading = ref(true);
const quoteLoading = ref(false);
const purchaseLoading = ref(false);
const modalOpen = ref(false);
const couponCode = ref("");
const autoRenewEnabled = ref(false);
const selectedPackage = ref<ClientPackageItem | null>(null);
const quote = ref<ClientPackageQuote | null>(null);
const packages = ref<ClientPackageItem[]>([]);
const latestOrders = ref<ClientPackageOrder[]>([]);
const currentSubscription = ref<CurrentUserSubscriptionType | null>(null);
const activeSubscriptions = ref<CurrentUserSubscriptionType[]>([]);
const copiedKey = ref("");
const rotatingApiKeyId = ref<number | null>(null);
const summary = ref({
    wallet_balance: "0",
    active_subscription_count: 0,
    latest_order_count: 0,
});

const currency = (value: string | number): string => `${Number(value).toLocaleString("vi-VN")} đ`;

const formatDate = (value: string | null): string => {
    if (!value) {
        return "--";
    }

    return new Date(value).toLocaleDateString("vi-VN");
};

const diffDays = (value: string | null): number => {
    if (!value) {
        return 0;
    }

    const end = new Date(value).getTime();
    const now = Date.now();
    const ms = end - now;

    return ms <= 0 ? 0 : Math.ceil(ms / (1000 * 60 * 60 * 24));
};

const remainingDaysLabel = (subscription: CurrentUserSubscriptionType | null): string => {
    if (!subscription?.expires_at) {
        return "Không giới hạn";
    }

    return `${diffDays(subscription.expires_at)} ngày`;
};

const quotaLabel = (subscription: CurrentUserSubscriptionType): string =>
    subscription.remaining_captcha_quota === null ? "Không giới hạn lượt giải" : `Còn ${subscription.remaining_captcha_quota} lượt`;

const packageQuotaLabel = (item: ClientPackageItem): string =>
    item.package_limits.monthly_captcha_quota === null ? "Không giới hạn" : `${item.package_limits.monthly_captcha_quota} lượt / tháng`;

const packageSubscriptionMap = computed<Record<number, CurrentUserSubscriptionType>>(() => {
    return activeSubscriptions.value.reduce<Record<number, CurrentUserSubscriptionType>>((carry, subscription) => {
        carry[subscription.package_id] = subscription;
        return carry;
    }, {});
});

const subscriptionOfPackage = (packageId: number): CurrentUserSubscriptionType | null => packageSubscriptionMap.value[packageId] ?? null;

const actionLabel = (item: ClientPackageItem): string => (subscriptionOfPackage(item.id) ? "Gia hạn" : "Mua ngay");

const copyText = async (value: string | null | undefined, key: string): Promise<void> => {
    if (!value) {
        return;
    }

    await navigator.clipboard.writeText(value);
    copiedKey.value = key;
    window.setTimeout(() => {
        if (copiedKey.value === key) {
            copiedKey.value = "";
        }
    }, 1600);
};

const loadPackages = async (): Promise<void> => {
    try {
        loading.value = true;
        const response = await clientPackageService.index();
        packages.value = response.packages;
        latestOrders.value = response.latest_orders;
        currentSubscription.value = response.current_subscription;
        activeSubscriptions.value = response.active_subscriptions ?? [];
        summary.value = response.summary;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const openQuoteModal = async (item: ClientPackageItem): Promise<void> => {
    try {
        selectedPackage.value = item;
        modalOpen.value = true;
        couponCode.value = "";
        autoRenewEnabled.value = false;
        quote.value = null;
        await refreshQuote();
    } catch (error) {
        handleErrorResponse(error);
    }
};

const refreshQuote = async (): Promise<void> => {
    if (!selectedPackage.value) {
        return;
    }

    try {
        quoteLoading.value = true;
        quote.value = await clientPackageService.quote({
            package_id: selectedPackage.value.id,
            coupon_code: couponCode.value.trim() || undefined,
        });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        quoteLoading.value = false;
    }
};

const buyPackage = async (): Promise<void> => {
    if (!selectedPackage.value) {
        return;
    }

    try {
        purchaseLoading.value = true;
        const order = await clientPackageService.createOrder({
            package_id: selectedPackage.value.id,
            coupon_code: couponCode.value.trim() || undefined,
            payment_method: "wallet",
            auto_renew_enabled: autoRenewEnabled.value,
        });

        const payment = await clientPackageService.payOrder(order.id, { payment_method: "wallet" });
        const packageKey = payment.package_api_key;

        handleSuccessResponse({
            data: {
                status: true,
                message: packageKey?.api_secret
                    ? `Thanh toán thành công. API key: ${packageKey.api_key.api_key} | secret: ${packageKey.api_secret}`
                    : "Thanh toán gói captcha thành công.",
            },
        });

        modalOpen.value = false;
        await loadPackages();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        purchaseLoading.value = false;
    }
};

const rotatePackageSecret = async (apiKeyId: number): Promise<void> => {
    try {
        rotatingApiKeyId.value = apiKeyId;
        await clientApiKeyService.rotate(apiKeyId);
        await loadPackages();

        handleSuccessResponse({
            data: {
                status: true,
                message: "Đổi secret của gói thành công.",
            },
        });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        rotatingApiKeyId.value = null;
    }
};

onMounted(async () => {
    await loadPackages();
});
</script>

<template>
    <div class="space-y-5">
        <Breadcrumb title="Gói captcha" description="Mua hoặc gia hạn gói và copy ngay API key, secret của từng gói đang hoạt động." />

        <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">
            <article class="rounded-[10px] border border-sky-100 bg-[linear-gradient(135deg,#ffffff_0%,#eef7ff_45%,#ecfeff_100%)] p-5 shadow-[0_16px_40px_rgba(59,130,246,0.08)]">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">Captcha plans</p>
                <h2 class="mt-3 text-3xl font-black tracking-[-0.04em] text-slate-950">Chọn gói phù hợp và dùng bằng API key riêng</h2>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                    Nếu đã mua gói, bạn có thể gia hạn ngay trên đúng card đó và copy trực tiếp API key, secret mà không cần chuyển sang màn hình khác.
                </p>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-gradient-to-br from-sky-500 to-cyan-400 text-white">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Số dư hiện tại</p>
                        <p class="mt-1 text-2xl font-black tracking-[-0.04em] text-slate-950">{{ currency(summary.wallet_balance) }}</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-[8px] bg-slate-50 px-3 py-3">
                        <p class="text-xs text-slate-400">Gói đang chạy</p>
                        <p class="mt-1 text-lg font-bold text-slate-950">{{ summary.active_subscription_count }}</p>
                    </div>
                    <div class="rounded-[8px] bg-slate-50 px-3 py-3">
                        <p class="text-xs text-slate-400">Đơn gần đây</p>
                        <p class="mt-1 text-lg font-bold text-slate-950">{{ summary.latest_order_count }}</p>
                    </div>
                </div>

                <div v-if="currentSubscription" class="mt-4 rounded-[8px] border border-emerald-100 bg-emerald-50/80 px-3 py-3">
                    <p class="text-sm font-semibold text-emerald-800">{{ currentSubscription.package_name }}</p>
                    <p class="mt-1 text-xs text-emerald-700">{{ quotaLabel(currentSubscription) }} • {{ remainingDaysLabel(currentSubscription) }}</p>
                </div>
            </article>
        </section>

        <section v-if="loading" class="grid gap-4 xl:grid-cols-3">
            <div v-for="index in 3" :key="index" class="h-[360px] animate-pulse rounded-[10px] bg-slate-100"></div>
        </section>

        <section v-else class="grid gap-4 xl:grid-cols-3">
            <article
                v-for="item in packages"
                :key="item.id"
                class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.06)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-sky-700">
                            {{ item.slug }}
                        </span>
                        <h2 class="mt-3 text-[28px] font-black tracking-[-0.04em] text-slate-950">{{ item.name }}</h2>
                    </div>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'"
                    >
                        {{ item.status === "active" ? "Đang bán" : "Tạm dừng" }}
                    </span>
                </div>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    {{ item.description || "Quota giải captcha riêng cho các dịch vụ được phép dùng trong gói này." }}
                </p>

                <div class="mt-4 rounded-[8px] bg-[linear-gradient(135deg,#eff6ff_0%,#f0fdfa_100%)] p-4">
                    <div class="flex items-end gap-2">
                        <span class="text-3xl font-black tracking-[-0.05em] text-slate-950">{{ currency(item.price) }}</span>
                        <span class="pb-1 text-sm font-semibold text-slate-500">/ {{ item.duration_days }} ngày</span>
                    </div>
                    <p class="mt-2 text-sm font-medium text-slate-600">{{ packageQuotaLabel(item) }}</p>
                </div>

                <div v-if="subscriptionOfPackage(item.id)" class="mt-4 space-y-3 rounded-[8px] border border-sky-100 bg-sky-50/60 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-sky-700">{{ remainingDaysLabel(subscriptionOfPackage(item.id)) }}</span>
                        <span class="text-xs text-slate-500">Hết hạn {{ formatDate(subscriptionOfPackage(item.id)?.expires_at ?? null) }}</span>
                    </div>

                    <div
                        v-for="apiKey in subscriptionOfPackage(item.id)?.package_api_keys ?? []"
                        :key="apiKey.id"
                        class="space-y-2 rounded-[8px] border border-white bg-white p-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">API key</p>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-sky-700"
                                @click="copyText(apiKey.api_key, `key-${apiKey.id}`)"
                            >
                                <Check v-if="copiedKey === `key-${apiKey.id}`" class="h-3.5 w-3.5" />
                                <Copy v-else class="h-3.5 w-3.5" />
                                {{ copiedKey === `key-${apiKey.id}` ? "Đã copy" : "Copy" }}
                            </button>
                        </div>
                        <p class="break-all text-sm font-semibold text-slate-900">{{ apiKey.api_key }}</p>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Secret</p>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600"
                                    :disabled="rotatingApiKeyId === apiKey.id"
                                    @click="rotatePackageSecret(apiKey.id)"
                                >
                                    <RefreshCw class="h-3.5 w-3.5" :class="rotatingApiKeyId === apiKey.id ? 'animate-spin' : ''" />
                                    Đổi
                                </button>
                                <button
                                    v-if="apiKey.api_secret"
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-sky-700"
                                    @click="copyText(apiKey.api_secret, `secret-${apiKey.id}`)"
                                >
                                    <Check v-if="copiedKey === `secret-${apiKey.id}`" class="h-3.5 w-3.5" />
                                    <Copy v-else class="h-3.5 w-3.5" />
                                    {{ copiedKey === `secret-${apiKey.id}` ? "Đã copy" : "Copy" }}
                                </button>
                            </div>
                        </div>
                        <p class="break-all text-sm font-semibold text-slate-900">{{ apiKey.api_secret || "Chưa có secret" }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Áp dụng cho</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="serviceCode in item.package_limits.service_whitelist"
                            :key="serviceCode"
                            class="rounded-full border border-sky-100 bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700"
                        >
                            {{ serviceCode }}
                        </span>
                    </div>
                </div>

                <div v-if="Array.isArray(item.features) && item.features.length > 0" class="mt-4 space-y-2">
                    <div v-for="feature in item.features" :key="feature" class="flex items-start gap-2 text-sm text-slate-600">
                        <CheckCircle2 class="mt-0.5 h-4 w-4 text-emerald-500" />
                        <span>{{ feature }}</span>
                    </div>
                </div>

                <div class="mt-5 flex gap-2">
                    <button
                        type="button"
                        class="flex-1 rounded-[8px] bg-gradient-to-r from-blue-600 via-sky-500 to-cyan-400 px-4 py-3 text-sm font-semibold text-white"
                        @click="openQuoteModal(item)"
                    >
                        {{ actionLabel(item) }}
                    </button>
                    <button
                        v-if="subscriptionOfPackage(item.id)"
                        type="button"
                        class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700"
                    >
                        {{ remainingDaysLabel(subscriptionOfPackage(item.id)) }}
                    </button>
                </div>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-slate-950">Đơn gói gần đây</h2>
                <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">{{ latestOrders.length }} đơn</span>
            </div>

            <div class="mt-4 space-y-3">
                <div
                    v-for="order in latestOrders"
                    :key="order.id"
                    class="flex flex-col gap-3 rounded-[8px] border border-slate-200 bg-slate-50/70 px-4 py-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ order.package?.name || "Gói captcha" }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ order.order_code }}</p>
                    </div>
                    <div class="text-sm font-semibold text-slate-700">{{ currency(order.final_amount) }}</div>
                    <span
                        class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold"
                        :class="order.payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'"
                    >
                        {{ order.payment_status === "paid" ? "Đã thanh toán" : order.payment_status }}
                    </span>
                </div>

                <div v-if="latestOrders.length === 0" class="rounded-[8px] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    Chưa có đơn gói nào được tạo gần đây.
                </div>
            </div>
        </section>

        <teleport to="body">
            <div v-if="modalOpen && selectedPackage" class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/45 p-4">
                <div class="w-full max-w-xl rounded-[12px] bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-950">
                                {{ subscriptionOfPackage(selectedPackage.id) ? `Gia hạn gói ${selectedPackage.name}` : `Mua gói ${selectedPackage.name}` }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">Thanh toán bằng ví và hệ thống sẽ cập nhật ngay key của gói.</p>
                        </div>
                        <button type="button" class="rounded-[8px] border border-slate-200 p-2 text-slate-500" @click="modalOpen = false">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-5 space-y-4">
                        <label class="space-y-1.5">
                            <span class="text-sm font-semibold text-slate-700">Mã coupon</span>
                            <div class="flex gap-3">
                                <input
                                    v-model="couponCode"
                                    type="text"
                                    class="flex-1 rounded-[8px] border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-sky-400"
                                    placeholder="Nhập nếu có"
                                />
                                <button
                                    type="button"
                                    class="rounded-[8px] border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700"
                                    :disabled="quoteLoading"
                                    @click="refreshQuote"
                                >
                                    {{ quoteLoading ? "Đang tính..." : "Tính lại" }}
                                </button>
                            </div>
                        </label>

                        <label class="flex items-center justify-between rounded-[8px] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span>Bật tự động gia hạn</span>
                            <input v-model="autoRenewEnabled" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                        </label>

                        <div v-if="quote" class="rounded-[8px] border border-sky-100 bg-sky-50/70 p-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-[8px] bg-white/80 px-3 py-3">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Giá gốc</p>
                                    <p class="mt-1 text-lg font-bold text-slate-950">{{ currency(quote.price) }}</p>
                                </div>
                                <div class="rounded-[8px] bg-white/80 px-3 py-3">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Thành tiền</p>
                                    <p class="mt-1 text-lg font-bold text-blue-700">{{ currency(quote.final_amount) }}</p>
                                </div>
                                <div class="rounded-[8px] bg-white/80 px-3 py-3">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Giảm giá</p>
                                    <p class="mt-1 text-sm font-semibold text-emerald-700">{{ currency(quote.discount_amount) }}</p>
                                </div>
                                <div class="rounded-[8px] bg-white/80 px-3 py-3">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Khấu trừ gói cũ</p>
                                    <p class="mt-1 text-sm font-semibold text-amber-700">{{ currency(quote.credit_amount) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
                        <button type="button" class="rounded-[8px] border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700" @click="modalOpen = false">
                            Đóng
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-[8px] bg-gradient-to-r from-blue-600 via-sky-500 to-cyan-400 px-4 py-2.5 text-sm font-semibold text-white"
                            :disabled="purchaseLoading || quoteLoading"
                            @click="buyPackage"
                        >
                            <LoaderCircle v-if="purchaseLoading" class="h-4 w-4 animate-spin" />
                            {{ purchaseLoading ? "Đang thanh toán..." : "Thanh toán bằng ví" }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>
