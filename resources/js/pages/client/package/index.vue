<template>
    <div class="px-3">
        <section v-if="currentSubscription" class="space-y-3">
            <div class="overflow-hidden rounded-[10px] border border-slate-200/80 bg-white shadow-[0_12px_30px_-28px_rgba(15,23,42,0.18)]">
                <div class="border-b border-slate-200 bg-white px-3 py-3">
                    <div class="grid gap-2 md:grid-cols-2">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="flex items-center gap-3 rounded-[10px] border px-3 py-2.5 text-left transition"
                            :class="
                                activeTab === tab.key
                                    ? 'border-slate-300 bg-slate-900 text-white shadow-[0_10px_24px_-20px_rgba(15,23,42,0.35)]'
                                    : 'border-slate-200 bg-slate-50/70 text-slate-600 hover:border-slate-300 hover:bg-white hover:text-slate-900'
                            "
                            @click="activeTab = tab.key"
                        >
                            <span class="block min-w-0">
                                <span class="block text-sm font-bold">{{ tab.label }}</span>
                                <span class="mt-0.5 block text-xs leading-5" :class="activeTab === tab.key ? 'text-white/70' : 'text-slate-400'">
                                    {{ tab.description }}
                                </span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="p-3.5 md:p-4">
                    <ManagePackageTab
                        v-if="activeTab === 'manage'"
                        :subscription="currentSubscription"
                        :buying-extra-card="buyingExtraCard"
                        @buy-extra-card="buyExtraCard"
                        @go-bank-manager="goBankManager"
                        @go-upgrade-tab="activeTab = 'upgrade'"
                    />

                    <ListPackage v-else embedded />
                </div>
            </div>
        </section>

        <ListPackage v-else />
    </div>
</template>

<script setup lang="ts">
import { clientPackageService } from '@/services/client-package.service';
import { useUserStore } from '@/stores/user.store';
import type { CurrentUserSubscriptionType } from '@/types/user-subscription.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ListPackage from './ListPackage.vue';
import ManagePackageTab from './ManagePackageTab.vue';

type TabKey = 'manage' | 'upgrade';

const route = useRoute();
const router = useRouter();
const userStore = useUserStore();

const tabs: Array<{ key: TabKey; label: string; description: string }> = [
    { key: 'manage', label: 'Quản lý gói', description: 'Xem hạn dùng, quota và mua thêm thẻ' },
    { key: 'upgrade', label: 'Nâng cấp gói', description: 'Đổi gói hoặc gia hạn từ danh sách gói hiện có' },
];

const activeTab = ref<TabKey>('manage');
const buyingExtraCard = ref(false);

const currentSubscription = computed<CurrentUserSubscriptionType | null>(() => userStore.user?.user_subscriptions ?? null);

const syncRouteTab = (): void => {
    const routeTab = route.query.tab;

    if (routeTab === 'upgrade') {
        activeTab.value = 'upgrade';
        return;
    }

    activeTab.value = 'manage';
};

async function buyExtraCard(): Promise<void> {
    if (!currentSubscription.value) {
        return;
    }

    const confirm = await Swal.fire({
        title: 'Mua thêm 1 thẻ?',
        text: 'Hệ thống sẽ tạo đơn mua thêm 1 thẻ và thanh toán bằng ví chính.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Tiếp tục',
        cancelButtonText: 'Hủy',
    });

    if (!confirm.isConfirmed) {
        return;
    }

    try {
        buyingExtraCard.value = true;

        const orderResponse = await clientPackageService.createExtraAccountOrder({
            user_subscription_id: currentSubscription.value.id,
            quantity: 1,
        });

        const orderId = orderResponse.data.data.id as number;
        const payResponse = await clientPackageService.payExtraAccountOrder(orderId);

        handleSuccessResponse(payResponse, 'Đã mua thêm 1 thẻ cho gói hiện tại.');
        await userStore.fetchCurrentUser({ silent: true });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        buyingExtraCard.value = false;
    }
}

async function goBankManager(): Promise<void> {
    await router.push({ name: 'client.bank-manager' });
}

watch(
    () => route.query.tab,
    () => {
        syncRouteTab();
    },
);

watch(
    () => activeTab.value,
    async (tab) => {
        if (route.query.tab === tab) {
            return;
        }

        await router.replace({
            query: {
                ...route.query,
                tab,
            },
        });
    },
);

onMounted(async () => {
    if (!userStore.user) {
        await userStore.bootstrap({ silent: true });
    }

    syncRouteTab();
});
</script>
