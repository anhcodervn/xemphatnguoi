<script setup lang="ts">
import Breadcrumb from '@/components/MasterLayouts/Breadcrumb/index.vue';
import { clientApiKeyService } from '@/services/client-api-key.service';
import {
    clientPackageService,
    type ClientPackageItem,
    type ClientPackageOrder,
    type ClientPackageQuote,
} from '@/services/client-package.service';
import type { CurrentUserSubscriptionType } from '@/types/user-subscription.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import {
    AlertCircle,
    CheckCircle2,
    CreditCard,
    KeyRound,
    LoaderCircle,
    Wallet,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

type PackageFilterKey = 'all' | 'bank' | 'advanced';

type PackageFilter = {
    key: PackageFilterKey;
    label: string;
};

const loading = ref(true);
const quoteLoading = ref(false);
const purchaseLoading = ref(false);
const modalOpen = ref(false);
const couponCode = ref('');
const autoRenewEnabled = ref(false);
const selectedPackage = ref<ClientPackageItem | null>(null);
const quote = ref<ClientPackageQuote | null>(null);
const packages = ref<ClientPackageItem[]>([]);
const latestOrders = ref<ClientPackageOrder[]>([]);
const currentSubscription = ref<CurrentUserSubscriptionType | null>(null);
const activeSubscriptions = ref<CurrentUserSubscriptionType[]>([]);
const copiedKey = ref('');
const rotatingApiKeyId = ref<number | null>(null);
const activeFilter = ref<PackageFilterKey>('all');
const summary = ref({
    wallet_balance: '0',
    active_subscription_count: 0,
    latest_order_count: 0,
});

const filters: PackageFilter[] = [
    { key: 'all', label: 'Tất cả' },
    { key: 'bank', label: 'Bank captcha' },
    { key: 'advanced', label: 'API nâng cao' },
];

const currency = (value: string | number): string => `${Number(value).toLocaleString('vi-VN')} đ`;

const formatDate = (value: string | null): string => {
    if (!value) {
        return '--';
    }

    return new Date(value).toLocaleDateString('vi-VN');
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
        return 'Không giới hạn';
    }

    return `${diffDays(subscription.expires_at)} ngày`;
};

const quotaLabel = (subscription: CurrentUserSubscriptionType): string =>
    subscription.remaining_captcha_quota === null ? 'Không giới hạn lượt giải' : `Còn ${subscription.remaining_captcha_quota} lượt`;

const packageQuotaLabel = (item: ClientPackageItem): string =>
    item.package_limits.monthly_captcha_quota === null ? 'Không giới hạn lượt giải' : `${item.package_limits.monthly_captcha_quota} lượt / tháng`;

const packageSubscriptionMap = computed<Record<number, CurrentUserSubscriptionType>>(() => {
    return activeSubscriptions.value.reduce<Record<number, CurrentUserSubscriptionType>>((carry, subscription) => {
        carry[subscription.package_id] = subscription;
        return carry;
    }, {});
});

const subscriptionOfPackage = (packageId: number): CurrentUserSubscriptionType | null => packageSubscriptionMap.value[packageId] ?? null;

const packageById = (packageId: number): ClientPackageItem | null =>
    packages.value.find((item) => item.id === packageId) ?? null;

const openQuoteByPackageId = async (packageId: number): Promise<void> => {
    const item = packageById(packageId);

    if (!item) {
        return;
    }

    await openQuoteModal(item);
};

const latestPackageKey = computed(() => activeSubscriptions.value[0]?.package_api_keys?.[0] ?? null);

const activePackageRows = computed(() =>
    activeSubscriptions.value.flatMap((subscription) => {
        const keys = subscription.package_api_keys ?? [];

        if (keys.length === 0) {
            return [{
                subscription,
                apiKey: null,
            }];
        }

        return keys.map((apiKey) => ({
            subscription,
            apiKey,
        }));
    }),
);

const isBankPackage = (item: ClientPackageItem): boolean => {
    const codes = item.package_limits.service_whitelist.map((code) => code.toLowerCase());
    const bankKeywords = ['vcb', 'mbb', 'vib', 'bidv', 'bank', 'acb', 'techcom', 'vietin'];

    return codes.some((code) => bankKeywords.some((keyword) => code.includes(keyword)));
};

const filteredPackages = computed(() => {
    if (activeFilter.value === 'all') {
        return packages.value;
    }

    return packages.value.filter((item) => {
        const bankPackage = isBankPackage(item);

        return activeFilter.value === 'bank' ? bankPackage : !bankPackage;
    });
});

const packageStatusLabel = (item: ClientPackageItem): string => {
    if (subscriptionOfPackage(item.id)) {
        return 'Đang sử dụng';
    }

    return item.status === 'active' ? 'Đang bán' : 'Tạm dừng';
};

const packageStatusClass = (item: ClientPackageItem): string => {
    if (subscriptionOfPackage(item.id)) {
        return 'bg-blue-50 text-blue-700 border border-blue-200';
    }

    return item.status === 'active'
        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
        : 'bg-amber-50 text-amber-700 border border-amber-200';
};

const actionLabel = (item: ClientPackageItem): string => (subscriptionOfPackage(item.id) ? 'Gia hạn' : 'Mua ngay');

const packageHint = (item: ClientPackageItem): string => {
    if (item.package_limits.max_whitelisted_ips <= 1) {
        return 'Chỉ sử dụng được 1 IP';
    }

    if (isBankPackage(item)) {
        return 'Dành cho captcha ngân hàng';
    }

    return 'API key riêng theo gói';
};

const packageFeatures = (item: ClientPackageItem): string[] => {
    if (Array.isArray(item.features) && item.features.length > 0) {
        return item.features.slice(0, 5).map((feature) => String(feature));
    }

    return [
        packageQuotaLabel(item),
        `${item.package_limits.requests_per_minute} request / phút`,
        `${item.package_limits.max_api_keys} API key tối đa`,
        `${item.package_limits.max_whitelisted_ips} IP whitelist`,
        item.package_limits.supports_callback ? 'Hỗ trợ callback' : 'Dùng ngay với luồng create/check',
    ].slice(0, 5);
};

const visibleServiceCodes = (item: ClientPackageItem): string[] => item.package_limits.service_whitelist.slice(0, 3);

const remainingServiceCount = (item: ClientPackageItem): number => Math.max(0, item.package_limits.service_whitelist.length - 3);

const truncateKey = (value: string | null | undefined, start = 10, end = 6): string => {
    if (!value) {
        return '--';
    }

    if (value.length <= start + end + 3) {
        return value;
    }

    return `${value.slice(0, start)}...${value.slice(-end)}`;
};

const copyText = async (value: string | null | undefined, key: string): Promise<void> => {
    if (!value) {
        return;
    }

    await navigator.clipboard.writeText(value);
    copiedKey.value = key;
    window.setTimeout(() => {
        if (copiedKey.value === key) {
            copiedKey.value = '';
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
        couponCode.value = '';
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
            payment_method: 'wallet',
            auto_renew_enabled: autoRenewEnabled.value,
        });

        const payment = await clientPackageService.payOrder(order.id, { payment_method: 'wallet' });
        const packageKey = payment.package_api_key;

        handleSuccessResponse({
            data: {
                status: true,
                message: packageKey?.api_secret
                    ? `Thanh toán thành công. API key: ${packageKey.api_key.api_key} | secret: ${packageKey.api_secret}`
                    : 'Thanh toán gói captcha thành công.',
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
                message: 'Đổi secret của gói thành công.',
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
    <div class="space-y-6">
        <section class="rounded-[14px] border border-slate-200 bg-white px-5 py-5 shadow-sm sm:px-6">
            <Breadcrumb
                title="Gói captcha"
                description="Mua gói riêng cho từng nhóm captcha, mỗi gói có API key và quota độc lập."
            />
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <Wallet class="h-5 w-5" />
                        </div>
                        <p class="text-sm font-medium text-slate-500">Số dư ví</p>
                        <p class="text-2xl font-bold tracking-tight text-slate-950">{{ currency(summary.wallet_balance) }}</p>
                    </div>
                    <RouterLink
                        to="/wallet"
                        class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Nạp thêm
                    </RouterLink>
                </div>
            </article>

            <article class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <p class="text-sm font-medium text-slate-500">Gói đang dùng</p>
                        <p class="text-lg font-semibold text-slate-950">
                            {{ currentSubscription?.package_name || 'Chưa có gói' }}
                        </p>
                    </div>
                    <span
                        v-if="currentSubscription"
                        class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                    >
                        Active
                    </span>
                </div>
                <p v-if="currentSubscription" class="mt-3 text-sm text-slate-500">
                    {{ quotaLabel(currentSubscription) }} • {{ remainingDaysLabel(currentSubscription) }}
                </p>
            </article>

            <article class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                            <KeyRound class="h-5 w-5" />
                        </div>
                        <p class="text-sm font-medium text-slate-500">API key mới nhất</p>
                        <p class="text-lg font-semibold text-slate-950">
                            {{ truncateKey(latestPackageKey?.api_key) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:text-slate-300"
                        :disabled="!latestPackageKey?.api_key"
                        @click="copyText(latestPackageKey?.api_key, 'latest-package-key')"
                    >
                        {{ copiedKey === 'latest-package-key' ? 'Đã copy' : 'Copy' }}
                    </button>
                </div>
            </article>
        </section>

        <section class="rounded-[14px] border border-blue-200 bg-blue-50 px-4 py-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-white text-blue-600">
                        <AlertCircle class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Thanh toán song song</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Mỗi lần mua gói, hệ thống cấp API key riêng cho gói đó. Key ví vẫn dùng để trừ số dư trực tiếp.
                        </p>
                    </div>
                </div>
                <RouterLink to="/api-docs" class="text-sm font-semibold text-blue-700 transition hover:text-blue-800">
                    Tìm hiểu thêm
                </RouterLink>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-950">Chọn gói phù hợp</h2>
                    <p class="mt-1 text-sm text-slate-500">So sánh nhanh các gói và mua ngay bằng số dư ví.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="filter in filters"
                        :key="filter.key"
                        type="button"
                        class="rounded-full border px-3.5 py-2 text-sm font-semibold transition"
                        :class="activeFilter === filter.key ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                        @click="activeFilter = filter.key"
                    >
                        {{ filter.label }}
                    </button>
                </div>
            </div>

            <section v-if="loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="index in 3" :key="index" class="h-[360px] animate-pulse rounded-[16px] border border-slate-200 bg-white"></div>
            </section>

            <section v-else-if="filteredPackages.length > 0" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="item in filteredPackages"
                    :key="item.id"
                    class="flex h-full flex-col rounded-[16px] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"
                    :class="subscriptionOfPackage(item.id) ? 'border-blue-300 bg-slate-50/50' : ''"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-3">
                            <h3 class="text-xl font-semibold text-slate-950">{{ item.name }}</h3>
                            <span
                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                :class="packageStatusClass(item)"
                            >
                                {{ packageStatusLabel(item) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                            {{ packageHint(item) }}
                        </span>
                        <p class="text-sm leading-6 text-slate-500">
                            {{ item.description || 'Gói captcha có quota độc lập, dùng API key riêng để quản lý theo từng nhóm dịch vụ.' }}
                        </p>
                    </div>

                    <div class="mt-5 border-b border-slate-100 pb-5">
                        <div class="flex items-end gap-2">
                            <span class="text-[2rem] font-bold tracking-tight text-slate-950">{{ currency(item.price) }}</span>
                            <span class="pb-1 text-sm text-slate-500">/ {{ item.duration_days }} ngày</span>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div
                            v-for="feature in packageFeatures(item)"
                            :key="feature"
                            class="flex items-start gap-2.5 text-sm text-slate-600"
                        >
                            <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                            <span>{{ feature }}</span>
                        </div>
                    </div>

                    <div class="mt-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Áp dụng</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="serviceCode in visibleServiceCodes(item)"
                                :key="serviceCode"
                                class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600"
                            >
                                {{ serviceCode }}
                            </span>
                            <span
                                v-if="remainingServiceCount(item) > 0"
                                class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600"
                            >
                                +{{ remainingServiceCount(item) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="subscriptionOfPackage(item.id)" class="mt-5 rounded-[12px] border border-slate-200 bg-white px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Đang kích hoạt</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Hết hạn {{ formatDate(subscriptionOfPackage(item.id)?.expires_at ?? null) }}
                                </p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ remainingDaysLabel(subscriptionOfPackage(item.id)) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-auto pt-5">
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="flex-1 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                                @click="openQuoteModal(item)"
                            >
                                {{ actionLabel(item) }}
                            </button>
                            <button
                                v-if="subscriptionOfPackage(item.id)"
                                type="button"
                                class="rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-700"
                            >
                                {{ remainingDaysLabel(subscriptionOfPackage(item.id)) }}
                            </button>
                        </div>
                    </div>
                </article>
            </section>

            <section v-else class="rounded-[16px] border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-500">
                Không có gói nào phù hợp với bộ lọc hiện tại.
            </section>
        </section>

        <section class="rounded-[16px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Gói đang sử dụng</h2>
                    <p class="mt-1 text-sm text-slate-500">Theo dõi nhanh key gói, hạn dùng và thao tác gia hạn.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ activePackageRows.length }} mục
                </span>
            </div>

            <div v-if="activePackageRows.length > 0" class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Gói</th>
                            <th class="px-3 py-3 font-semibold">API key</th>
                            <th class="px-3 py-3 font-semibold">Trạng thái</th>
                            <th class="px-3 py-3 font-semibold">Bắt đầu</th>
                            <th class="px-3 py-3 font-semibold">Hết hạn</th>
                            <th class="px-3 py-3 font-semibold text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr v-for="row in activePackageRows" :key="`${row.subscription.id}-${row.apiKey?.id ?? 'none'}`">
                            <td class="px-3 py-4">
                                <p class="font-semibold text-slate-900">{{ row.subscription.package_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ quotaLabel(row.subscription) }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <p class="font-medium text-slate-900">{{ truncateKey(row.apiKey?.api_key) }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                    {{ row.subscription.status }}
                                </span>
                            </td>
                            <td class="px-3 py-4">{{ formatDate(row.subscription.starts_at) }}</td>
                            <td class="px-3 py-4">{{ formatDate(row.subscription.expires_at) }}</td>
                            <td class="px-3 py-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        v-if="row.apiKey?.api_key"
                                        type="button"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        @click="copyText(row.apiKey?.api_key, `table-key-${row.apiKey?.id}`)"
                                    >
                                        {{ copiedKey === `table-key-${row.apiKey?.id}` ? 'Đã copy' : 'Copy key' }}
                                    </button>
                                    <button
                                        v-if="row.apiKey"
                                        type="button"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        :disabled="rotatingApiKeyId === row.apiKey.id"
                                        @click="rotatePackageSecret(row.apiKey.id)"
                                    >
                                        {{ rotatingApiKeyId === row.apiKey.id ? 'Đang đổi...' : 'Đổi secret' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                                        @click="openQuoteByPackageId(row.subscription.package_id)"
                                    >
                                        Gia hạn
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="mt-4 rounded-[12px] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                Chưa có gói nào đang hoạt động.
            </div>
        </section>

        <section class="rounded-[16px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Đơn gói gần đây</h2>
                    <p class="mt-1 text-sm text-slate-500">Theo dõi nhanh đơn mua và trạng thái thanh toán.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ latestOrders.length }} đơn
                </span>
            </div>

            <div v-if="latestOrders.length > 0" class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Mã đơn</th>
                            <th class="px-3 py-3 font-semibold">Gói</th>
                            <th class="px-3 py-3 font-semibold">Giá</th>
                            <th class="px-3 py-3 font-semibold">Trạng thái</th>
                            <th class="px-3 py-3 font-semibold">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr v-for="order in latestOrders" :key="order.id">
                            <td class="px-3 py-4 font-medium text-slate-900">{{ order.order_code }}</td>
                            <td class="px-3 py-4">{{ order.package?.name || 'Gói captcha' }}</td>
                            <td class="px-3 py-4">{{ currency(order.final_amount) }}</td>
                            <td class="px-3 py-4">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="order.payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                >
                                    {{ order.payment_status === 'paid' ? 'Đã thanh toán' : order.payment_status }}
                                </span>
                            </td>
                            <td class="px-3 py-4">{{ formatDate(order.expires_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="mt-4 rounded-[12px] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                Chưa có đơn gói nào được tạo gần đây.
            </div>
        </section>

        <teleport to="body">
            <div v-if="modalOpen && selectedPackage" class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/40 p-4">
                <div class="w-full max-w-xl rounded-[16px] border border-slate-200 bg-white p-6 shadow-xl">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-950">
                                {{ subscriptionOfPackage(selectedPackage.id) ? `Gia hạn gói ${selectedPackage.name}` : `Mua gói ${selectedPackage.name}` }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">Thanh toán bằng ví và hệ thống sẽ cập nhật ngay key của gói.</p>
                        </div>
                        <button type="button" class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50" @click="modalOpen = false">
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
                                    class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300"
                                    placeholder="Nhập nếu có"
                                />
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    :disabled="quoteLoading"
                                    @click="refreshQuote"
                                >
                                    {{ quoteLoading ? 'Đang tính...' : 'Tính lại' }}
                                </button>
                            </div>
                        </label>

                        <label class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span>Bật tự động gia hạn</span>
                            <input v-model="autoRenewEnabled" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                        </label>

                        <div v-if="quote" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Giá gốc</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-950">{{ currency(quote.price) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Thành tiền</p>
                                    <p class="mt-1 text-lg font-semibold text-blue-700">{{ currency(quote.final_amount) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Giảm giá</p>
                                    <p class="mt-1 text-sm font-semibold text-emerald-700">{{ currency(quote.discount_amount) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Khấu trừ gói cũ</p>
                                    <p class="mt-1 text-sm font-semibold text-amber-700">{{ currency(quote.credit_amount) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
                        <button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="modalOpen = false">
                            Đóng
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:bg-slate-200 disabled:text-slate-400"
                            :disabled="purchaseLoading || quoteLoading"
                            @click="buyPackage"
                        >
                            <LoaderCircle v-if="purchaseLoading" class="h-4 w-4 animate-spin" />
                            <CreditCard v-else class="h-4 w-4" />
                            {{ purchaseLoading ? 'Đang thanh toán...' : 'Thanh toán bằng ví' }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>
