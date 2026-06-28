<template>
    <section class="space-y-3">
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1.15fr)_320px]">
            <div class="space-y-3">
                <article class="rounded-[10px] border border-slate-200/80 bg-white p-4 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Gói đang sử dụng</p>
                            <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-slate-950">{{ subscription.package_name }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                {{ packageDescription }}
                            </p>
                        </div>

                        <span
                            class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-bold"
                            :class="isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                        >
                            {{ isActive ? 'Đang hoạt động' : 'Cần gia hạn' }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <div class="rounded-[10px] bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Hạn sử dụng</p>
                            <p class="mt-1 text-lg font-black text-slate-900">{{ expiryLabel }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ expirySubLabel }}</p>
                        </div>

                        <div class="rounded-[10px] bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Số thẻ đã thêm</p>
                            <p class="mt-1 text-lg font-black text-slate-900">{{ subscription.used_account }}/{{ totalSlots }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ availableSlots }} thẻ còn có thể thêm</p>
                        </div>

                        <div class="rounded-[10px] bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Giá mua thêm</p>
                            <p class="mt-1 text-lg font-black text-slate-900">
                                {{ canBuyExtraCard ? formatCurrency(Number(subscription.package.extra_account_price || 0)) : 'Không hỗ trợ' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">Áp dụng cho 1 thẻ thêm bằng ví chính</p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3 rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-3 py-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Tự gia hạn</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                {{ subscription.auto_renew_enabled ? 'Đang bật' : 'Đang tắt' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ subscription.auto_renew_enabled ? 'Khi gói hết hạn hệ thống sẽ thử trừ ví để gia hạn tự động.' : 'Bạn có thể bật lại khi mua hoặc gia hạn gói tiếp theo.' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="subscription.auto_renew_enabled"
                            class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-60"
                            :class="subscription.auto_renew_enabled ? 'bg-emerald-500' : 'bg-slate-300'"
                            :disabled="updatingAutoRenew"
                            @click="$emit('toggle-auto-renew')"
                        >
                            <span class="sr-only">Bật hoặc tắt tự gia hạn</span>
                            <span
                                class="inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform"
                                :class="subscription.auto_renew_enabled ? 'translate-x-6' : 'translate-x-1'"
                            />
                        </button>
                    </div>

                    <div class="mt-4 rounded-[10px] border border-dashed border-slate-200 p-3">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="font-semibold text-slate-500">Mức sử dụng slot</span>
                            <span class="font-bold text-slate-700">{{ usedPercent }}%</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-slate-900 transition-all" :style="{ width: `${usedPercent}%` }" />
                        </div>
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200/80 bg-white p-4 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-lg font-black text-slate-950">Mua thêm thẻ</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                Mỗi lần bấm sẽ mua thêm 1 thẻ cho gói hiện tại và thanh toán trực tiếp bằng ví chính.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-11 items-center justify-center rounded-[10px] px-4 text-sm font-bold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                            :class="canBuyExtraCard ? 'bg-slate-900 hover:bg-slate-800' : 'bg-slate-300'"
                            :disabled="!canBuyExtraCard || buyingExtraCard"
                            @click="$emit('buy-extra-card')"
                        >
                            {{ buyingExtraCard ? 'Đang xử lý...' : 'Mua thêm thẻ' }}
                        </button>
                    </div>

                    <div v-if="!canBuyExtraCard" class="mt-3 rounded-[10px] bg-amber-50 px-3 py-2.5 text-sm text-amber-700">
                        Gói hiện tại không hỗ trợ mua thêm thẻ.
                    </div>
                </article>
            </div>

            <aside class="space-y-3">
                <article class="rounded-[10px] border border-slate-200/80 bg-white p-4 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
                    <p class="text-lg font-black text-slate-950">Tổng quan quota</p>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Slot gốc</dt>
                            <dd class="font-semibold text-slate-900">{{ subscription.base_account_limit }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Slot mua thêm</dt>
                            <dd class="font-semibold text-slate-900">{{ subscription.extra_account_limit }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Tổng slot</dt>
                            <dd class="font-semibold text-slate-900">{{ totalSlots }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Đã dùng</dt>
                            <dd class="font-semibold text-slate-900">{{ subscription.used_account }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Còn trống</dt>
                            <dd class="font-semibold text-emerald-600">{{ availableSlots }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-[10px] border border-slate-200/80 bg-[linear-gradient(135deg,_#f8fafc_0%,_#eef2ff_100%)] p-4">
                    <p class="text-lg font-black text-slate-950">Hành động nhanh</p>
                    <div class="mt-4 grid gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-[10px] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                            @click="$emit('go-bank-manager')"
                        >
                            Quản lý thẻ đã thêm
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-[10px] bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                            @click="$emit('go-upgrade-tab')"
                        >
                            Nâng cấp hoặc gia hạn gói
                        </button>
                    </div>
                </article>
            </aside>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { CurrentUserSubscriptionType } from '@/types/user-subscription.type';
import { computed } from 'vue';

const props = defineProps<{
    subscription: CurrentUserSubscriptionType;
    buyingExtraCard: boolean;
    updatingAutoRenew?: boolean;
}>();

defineEmits<{
    'buy-extra-card': [];
    'go-bank-manager': [];
    'go-upgrade-tab': [];
    'toggle-auto-renew': [];
}>();

const totalSlots = computed(() => props.subscription.base_account_limit + props.subscription.extra_account_limit);
const availableSlots = computed(() => Math.max(0, totalSlots.value - props.subscription.used_account));
const canBuyExtraCard = computed(() => props.subscription.package.can_buy_extra_account);
const isActive = computed(() => {
    if (props.subscription.status !== 'active' || !props.subscription.expires_at) {
        return false;
    }

    return new Date(props.subscription.expires_at).getTime() > Date.now();
});

const usedPercent = computed(() => {
    if (totalSlots.value <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((props.subscription.used_account / totalSlots.value) * 100));
});

const expiryLabel = computed(() => {
    if (!props.subscription.expires_at) {
        return 'Chưa có';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(props.subscription.expires_at));
});

const expirySubLabel = computed(() => {
    if (!props.subscription.expires_at) {
        return 'Gói chưa có ngày hết hạn';
    }

    return isActive.value ? 'Đang còn hiệu lực' : 'Gói đã hết hạn, nên gia hạn sớm';
});

const packageDescription = computed(() => {
    const featureCount = Array.isArray(props.subscription.package.features) ? props.subscription.package.features.length : 0;

    if (featureCount > 0) {
        return `Gói này đang có ${featureCount} cấu hình tính năng và ${totalSlots.value} slot sử dụng.`;
    }

    return `Gói này đang có ${totalSlots.value} slot sử dụng, phù hợp để quản lý và mở rộng thêm thẻ khi cần.`;
});

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
}
</script>
