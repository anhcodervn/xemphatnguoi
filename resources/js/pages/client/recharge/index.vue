<script setup lang="ts">
import { clientRechargeService } from '@/services/client-recharge.service';
import type { RechargeMethodType, RechargeOrderType, RechargeOverviewType, RechargeStatsType } from '@/types/recharge.type';
import { handleErrorResponse } from '@/utils/response';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import RechargeDepositPanel from './components/RechargeDepositPanel.vue';
import RechargeHeader from './components/RechargeHeader.vue';
import RechargeHistoryPanel from './components/RechargeHistoryPanel.vue';
import RechargeTabs from './components/RechargeTabs.vue';

type TabKey = 'deposit' | 'history';

const router = useRouter();

const activeTab = ref<TabKey>('deposit');
const loadingOverview = ref(false);
const creatingOrder = ref(false);
const overview = ref<RechargeOverviewType | null>(null);

const depositForm = reactive({
    method: '',
    amount: 500000,
});

const historyFilters = reactive({
    search: '',
    status: 'all',
    per_page: 10,
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const walletBalance = computed(() => overview.value?.wallet.balance ?? '0');
const fallbackBonusPercentage = computed(() => overview.value?.bonus_percentage ?? 0);
const fallbackMinimumAmount = computed(() => overview.value?.minimum_amount ?? 50000);
const fallbackMaximumAmount = computed(() => overview.value?.maximum_amount ?? 100000000);
const transferContentPreview = computed(() => overview.value?.transfer_content_preview ?? '');
const methods = computed(() => overview.value?.methods ?? []);
const orders = computed(() => overview.value?.history.data ?? []);
const historyMeta = computed(() => overview.value?.history.meta ?? { current_page: 1, last_page: 1, per_page: 10, total: 0 });
const stats = computed<RechargeStatsType>(() => overview.value?.stats ?? { total_recharge: '0', total_bonus: '0', total_orders: 0 });

const hasDestinationAccount = (method: RechargeMethodType): boolean => {
    return Boolean(method.bank_name && method.account_number && method.account_name);
};

const availableMethods = computed(() => methods.value.filter((method) => method.active && hasDestinationAccount(method)));

const selectedMethod = computed(() => {
    return (
        availableMethods.value.find((method) => method.key === depositForm.method) ??
        availableMethods.value[0] ??
        methods.value.find((method) => method.key === depositForm.method) ??
        methods.value[0] ??
        null
    );
});

const effectiveBonusPercentage = computed(() => selectedMethod.value?.bonus_percentage ?? fallbackBonusPercentage.value);
const effectiveMinimumAmount = computed(() => selectedMethod.value?.minimum_amount ?? fallbackMinimumAmount.value);
const effectiveMaximumAmount = computed(() => selectedMethod.value?.maximum_amount ?? fallbackMaximumAmount.value);

const syncDefaultForm = (): void => {
    if (availableMethods.value.length > 0 && !availableMethods.value.some((method) => method.key === depositForm.method)) {
        depositForm.method = availableMethods.value[0].key;
    }

    if (depositForm.amount < effectiveMinimumAmount.value) {
        depositForm.amount = Math.max(500000, effectiveMinimumAmount.value);
    }

    if (depositForm.amount > effectiveMaximumAmount.value) {
        depositForm.amount = effectiveMaximumAmount.value;
    }
};

const loadOverview = async (): Promise<void> => {
    try {
        loadingOverview.value = true;

        overview.value = await clientRechargeService.overview({
            search: historyFilters.search || undefined,
            status: historyFilters.status as 'all' | undefined,
            per_page: historyFilters.per_page,
        });

        syncDefaultForm();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingOverview.value = false;
    }
};

const resetDepositForm = (): void => {
    const defaultMethod = availableMethods.value[0] ?? methods.value[0] ?? null;

    depositForm.method = defaultMethod?.key ?? '';
    depositForm.amount = Math.min(
        defaultMethod?.maximum_amount ?? fallbackMaximumAmount.value,
        Math.max(500000, defaultMethod?.minimum_amount ?? fallbackMinimumAmount.value),
    );
};

const openRechargePayment = async (orderId: number): Promise<void> => {
    await router.push({
        name: 'client.recharge.payment',
        params: {
            recharge_id: orderId,
        },
    });
};

const submitRechargeOrder = async (): Promise<void> => {
    try {
        creatingOrder.value = true;

        const order = await clientRechargeService.createOrder({
            method: depositForm.method,
            amount: depositForm.amount,
        });

        await Swal.fire('Thành công', 'Tạo yêu cầu nạp tiền thành công.', 'success');
        await openRechargePayment(order.id);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        creatingOrder.value = false;
    }
};

const continueRechargeOrder = async (order: RechargeOrderType): Promise<void> => {
    if (!['pending', 'processing'].includes(order.status)) {
        return;
    }

    await openRechargePayment(order.id);
};

watch(
    () => depositForm.method,
    () => {
        syncDefaultForm();
    },
);

watch(
    () => historyFilters.status,
    async () => {
        if (activeTab.value === 'history') {
            await loadOverview();
        }
    },
);

watch(
    () => activeTab.value,
    async (tab) => {
        if (tab === 'history') {
            await loadOverview();
        }
    },
);

watch(
    () => historyFilters.search,
    (value) => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(
            async () => {
                if (activeTab.value === 'history') {
                    await loadOverview();
                }
            },
            value.trim() === '' ? 0 : 300,
        );
    },
);

onMounted(async () => {
    await loadOverview();
});
</script>

<template>
    <div class="space-y-3 overflow-x-hidden bg-[#f4f7fb] pb-4">
        <RechargeHeader :balance="walletBalance" :bonus-percentage="effectiveBonusPercentage" />

        <section class="overflow-hidden rounded-[10px] border border-slate-200/70 bg-white shadow-sm">
            <RechargeTabs v-model="activeTab" />

            <div class="p-4">
                <RechargeDepositPanel
                    v-if="activeTab === 'deposit'"
                    :methods="methods"
                    :selected-method-key="depositForm.method"
                    :amount="depositForm.amount"
                    :minimum-amount="effectiveMinimumAmount"
                    :maximum-amount="effectiveMaximumAmount"
                    :bonus-percentage="effectiveBonusPercentage"
                    :transfer-content="transferContentPreview"
                    :processing="creatingOrder"
                    @update:selected-method-key="depositForm.method = $event"
                    @update:amount="depositForm.amount = $event"
                    @reset="resetDepositForm"
                    @submit="submitRechargeOrder"
                />

                <RechargeHistoryPanel
                    v-else
                    :orders="orders"
                    :meta="historyMeta"
                    :stats="stats"
                    :search="historyFilters.search"
                    :status="historyFilters.status"
                    :loading="loadingOverview"
                    @update:search="historyFilters.search = $event"
                    @update:status="historyFilters.status = $event"
                    @go-create="activeTab = 'deposit'"
                    @view-order="continueRechargeOrder"
                />
            </div>
        </section>
    </div>
</template>
