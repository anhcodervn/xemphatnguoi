<template>
    <section class="space-y-4">
        <div class="grid gap-4 xl:grid-cols-[1.08fr_1fr]">
            <Card custom-class="rounded-[10px] border border-slate-200/80 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
                <template #body>
                    <div class="flex h-full flex-col justify-between gap-4 p-3.5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3.5">
                                <img
                                    :src="avatarUrl"
                                    :alt="displayName"
                                    class="h-16 w-16 rounded-full border border-slate-200 bg-slate-100 object-cover shadow-sm"
                                />

                                <div class="min-w-0">
                                    <p class="truncate text-2xl font-black tracking-[-0.04em] text-slate-900">
                                        Xin chào, {{ userStore.user?.email }}
                                    </p>

                                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                        <span :class="subscriptionStateMeta.badgeClass">{{ subscriptionStateMeta.label }}</span>
                                        <span
                                            v-if="currentSubscription?.package_name"
                                            class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700"
                                        >
                                            {{ currentSubscription.package_name }}
                                        </span>
                                    </div>

                                    <div class="mt-4">
                                        <p class="text-xs font-medium text-slate-400">Số dư ví</p>
                                        <p class="mt-1 text-4xl font-black tracking-[-0.05em] text-slate-900">
                                            {{ formattedWalletBalance }}đ
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="hidden h-14 w-14 items-center justify-center rounded-[10px] bg-emerald-100/70 text-emerald-300 lg:flex">
                                <Wallet class="h-8 w-8" />
                            </div>
                        </div>

                        <div class="grid gap-2.5 sm:grid-cols-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-[8px] bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_14px_28px_-20px_rgba(16,185,129,0.8)] transition hover:bg-emerald-600"
                                @click="goRecharge"
                            >
                                <Wallet class="h-4 w-4" />
                                Nạp tiền
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-[8px] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-sky-200 hover:bg-sky-50"
                                @click="goPackage"
                            >
                                <Crown class="h-4 w-4" />
                                Nâng cấp gói
                            </button>
                        </div>
                    </div>
                </template>
            </Card>

            <Card custom-class="rounded-[10px] border border-slate-200/80 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
                <template #body>
                    <div class="flex h-full flex-col gap-4 p-3.5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-medium text-slate-400">Gói đang sử dụng</p>
                                <p class="mt-1.5 text-3xl font-black tracking-[-0.04em] text-slate-900">
                                    {{ packageTitle }}
                                </p>
                                <p class="mt-2 text-sm font-semibold" :class="subscriptionStateMeta.textClass">
                                    {{ packageSubtitle }}
                                </p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-sky-100 text-sky-600">
                                <CalendarDays class="h-6 w-6" />
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="font-medium text-slate-600">{{ usedSlotsLabel }}</span>
                                <span class="font-semibold text-slate-500">{{ usedPercent }}%</span>
                            </div>

                            <div class="mt-2.5 h-2 rounded-full bg-slate-100">
                                <div
                                    class="h-2 rounded-full transition-all"
                                    :class="subscriptionStateMeta.progressClass"
                                    :style="{ width: `${usedPercent}%` }"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <CalendarClock class="h-4 w-4" />
                            <span>{{ periodMessage }}</span>
                        </div>

                        <div class="grid gap-2.5 sm:grid-cols-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-[8px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_14px_28px_-20px_rgba(37,99,235,0.8)] transition hover:bg-blue-700"
                                @click="goPackage"
                            >
                                <RefreshCcw class="h-4 w-4" />
                                {{ renewalLabel }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-[8px] border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-600 transition hover:bg-blue-50"
                                @click="goPackage"
                            >
                                <Info class="h-4 w-4" />
                                Chi tiết
                            </button>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <Card
                v-for="metric in metrics"
                :key="metric.label"
                custom-class="rounded-[10px] border border-slate-200/80 shadow-[0_14px_34px_-30px_rgba(15,23,42,0.16)]"
            >
                <template #body>
                    <div class="flex items-center gap-3 p-3.5">
                        <div :class="metric.iconClass">
                            <component :is="metric.icon" class="h-5 w-5" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-400">{{ metric.label }}</p>
                            <p class="mt-1 truncate text-3xl font-black tracking-[-0.05em] text-slate-900">
                                {{ metric.value }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">{{ metric.note }}</p>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </section>
</template>

<script setup lang="ts">
import Card from "@/components/Card/index.vue";
import { useUserStore } from "@/stores/user.store";
import { formatTime } from "@/utils/helpers/format";
import formatCash from "@/utils/helpers/formatCash";
import { CalendarClock, CalendarDays, Crown, Info, Package2, ReceiptText, RefreshCcw, UsersRound, Wallet } from "lucide-vue-next";
import { computed } from "vue";
import { useRouter } from "vue-router";

type SubscriptionState = "not_registered" | "expired" | "active";

const userStore = useUserStore();
const router = useRouter();

const goRecharge = async (): Promise<void> => {
    await router.push({ name: "client.recharge" });
};

const goPackage = async (): Promise<void> => {
    await router.push({ name: "client.package" });
};

const currentSubscription = computed(() => userStore.user?.user_subscriptions ?? null);
const walletBalance = computed(() => Number(userStore.user?.wallet?.balance ?? 0));
const formattedWalletBalance = computed(() => formatCash(walletBalance.value));
const avatarUrl = computed(
    () =>
        userStore.user?.avatar ||
        "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160' fill='none'%3E%3Crect width='160' height='160' rx='80' fill='%23F1F5F9'/%3E%3Ccircle cx='80' cy='60' r='26' fill='%231E293B'/%3E%3Cpath d='M38 128c9-20 24-31 42-31s33 11 42 31' fill='%231E293B'/%3E%3C/svg%3E",
);
const displayName = computed(() => userStore.user?.full_name || userStore.user?.username || "Client");

const subscriptionState = computed<SubscriptionState>(() => {
    if (!currentSubscription.value) {
        return "not_registered";
    }

    const expiresAt = currentSubscription.value.expires_at ? new Date(currentSubscription.value.expires_at) : null;
    const isFuture = expiresAt instanceof Date && !Number.isNaN(expiresAt.getTime()) && expiresAt.getTime() > Date.now();

    if (currentSubscription.value.status === "active" && isFuture) {
        return "active";
    }

    return "expired";
});

const totalAccountLimit = computed(() => {
    if (!currentSubscription.value) {
        return 0;
    }

    return currentSubscription.value.base_account_limit + currentSubscription.value.extra_account_limit;
});

const usedPercent = computed(() => {
    if (!currentSubscription.value || totalAccountLimit.value <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((currentSubscription.value.used_account / totalAccountLimit.value) * 100));
});

const daysRemaining = computed(() => {
    if (!currentSubscription.value?.expires_at) {
        return 0;
    }

    const expiresAt = new Date(currentSubscription.value.expires_at);

    if (Number.isNaN(expiresAt.getTime())) {
        return 0;
    }

    const diffInMs = expiresAt.getTime() - Date.now();

    if (diffInMs <= 0) {
        return 0;
    }

    return Math.ceil(diffInMs / (1000 * 60 * 60 * 24));
});

const packageTitle = computed(() => {
    if (!currentSubscription.value) {
        return "Chưa có gói";
    }

    return currentSubscription.value.package_name;
});

const packageSubtitle = computed(() => {
    if (subscriptionState.value === "active") {
        return `HSD: ${formatTime(currentSubscription.value?.expires_at)}`;
    }

    if (subscriptionState.value === "expired") {
        return `Hết hạn từ ${formatTime(currentSubscription.value?.expires_at)}`;
    }

    return "Đăng ký gói để bắt đầu sử dụng";
});

const usedSlotsLabel = computed(() => {
    if (!currentSubscription.value) {
        return "0/0 slot đã dùng";
    }

    return `${currentSubscription.value.used_account}/${totalAccountLimit.value} slot đã dùng`;
});

const periodMessage = computed(() => {
    if (subscriptionState.value === "active") {
        return `Còn ${daysRemaining.value} ngày`;
    }

    if (subscriptionState.value === "expired") {
        return "Gói cần được gia hạn để tiếp tục sử dụng";
    }

    return "Chưa có thời hạn sử dụng";
});

const renewalLabel = computed(() => {
    if (subscriptionState.value === "active") {
        return "Gia hạn";
    }

    if (subscriptionState.value === "expired") {
        return "Kích hoạt lại";
    }

    return "Chọn gói";
});

const subscriptionStateMeta = computed(() => {
    if (subscriptionState.value === "active") {
        return {
            label: "Đang hoạt động",
            badgeClass: "rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700",
            textClass: "text-emerald-700",
            progressClass: "bg-emerald-500",
        };
    }

    if (subscriptionState.value === "expired") {
        return {
            label: "Hết hạn",
            badgeClass: "rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700",
            textClass: "text-amber-700",
            progressClass: "bg-amber-500",
        };
    }

    return {
        label: "Chưa đăng ký",
            badgeClass: "rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600",
        textClass: "text-slate-500",
        progressClass: "bg-slate-300",
    };
});

const metrics = computed(() => [
    {
        label: "Số dư ví",
        value: `${formattedWalletBalance.value}đ`,
        note: "Sẵn sàng sử dụng",
        icon: Wallet,
        iconClass: "flex h-10 w-10 items-center justify-center rounded-[8px] bg-emerald-100 text-emerald-600",
    },
    {
        label: "Slot đã dùng",
        value: currentSubscription.value ? `${currentSubscription.value.used_account}/${totalAccountLimit.value}` : "0/0",
        note: `Đã dùng ${usedPercent.value}%`,
        icon: UsersRound,
        iconClass: "flex h-10 w-10 items-center justify-center rounded-[8px] bg-violet-100 text-violet-600",
    },
    {
        label: "Đơn hàng",
        value: "0",
        note: "Tổng đơn hàng",
        icon: ReceiptText,
        iconClass: "flex h-10 w-10 items-center justify-center rounded-[8px] bg-blue-100 text-blue-600",
    },
    {
        label: "Ngày còn lại",
        value: subscriptionState.value === "active" ? `${daysRemaining.value} ngày` : "0 ngày",
        note:
            subscriptionState.value === "active"
                ? `Đến ${formatTime(currentSubscription.value?.expires_at, "d/m/Y")}`
                : subscriptionState.value === "expired"
                  ? "Gói đã hết hạn"
                  : "Chưa kích hoạt",
        icon: Package2,
        iconClass: "flex h-10 w-10 items-center justify-center rounded-[8px] bg-amber-100 text-amber-600",
    },
]);
</script>
