<script setup lang="ts">
import { clientWalletService } from '@/services/client-wallet.service';
import { useUserStore } from '@/stores/user.store';
import type { DepositRequestItem, RechargeConfigType } from '@/types/recharge-config.type';
import type { WalletBalanceChangedEvent, WalletDepositCreditedEvent, WalletType } from '@/types/wallet.type';
import { handleErrorResponse } from '@/utils/response';
import Swal from 'sweetalert2';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import DepositPanel from './components/DepositPanel.vue';
import HistoryPanel from './components/HistoryPanel.vue';
import PaymentPanel from './components/PaymentPanel.vue';
import WalletHeader from './components/WalletHeader.vue';
import WalletTabs from './components/WalletTabs.vue';
import type { DepositTabKey } from './types';

const MAX_DEPOSIT_AMOUNT = 50000000;
const PAYMENT_STORAGE_KEY = 'client-wallet-active-payment';

const userStore = useUserStore();
const route = useRoute();
const router = useRouter();

const activeTab = ref<DepositTabKey>('deposit');
const loadingOverview = ref(true);
const loadingHistory = ref(false);
const creatingRequest = ref(false);
const confirmingRequest = ref(false);
const refreshingPayment = ref(false);
const wallet = ref<WalletType | null>(null);
const rechargeConfig = ref<RechargeConfigType | null>(null);
const rechargeConfigs = ref<RechargeConfigType[]>([]);
const selectedConfigId = ref<number | null>(null);
const activePaymentRequest = ref<DepositRequestItem | null>(null);
const amountInput = ref('200000');
const copiedField = ref<string | null>(null);
const historySearch = ref('');
const historyStatus = ref('all');
const currentPage = ref(1);
const historyRows = ref<DepositRequestItem[]>([]);
const historyMeta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});
const countdownSeconds = ref(0);
const handlingPaidRequest = ref(false);

let countdownTimerId: number | null = null;

watch([historySearch, historyStatus], () => {
    currentPage.value = 1;
    void loadHistory();
});

watch(currentPage, () => {
    if (activeTab.value === 'history' && !isPaymentView.value) {
        void loadHistory();
    }
});

watch(
    () => route.query.view,
    () => {
        if (route.query.view !== 'payment' && activePaymentRequest.value) {
            clearActivePaymentState();
        }
    },
);

const amountValue = computed(() => Math.max(0, Number(amountInput.value.replace(/\D+/g, '') || 0)));
const amountDisplay = computed(() => (amountValue.value > 0 ? formatNumber(amountValue.value) : ''));
const balanceLabel = computed(() => formatMoney(wallet.value?.balance));
const bonusRate = computed(() => 0);
const selectedConfig = computed(() => {
    if (selectedConfigId.value === null) {
        return rechargeConfig.value;
    }

    return rechargeConfigs.value.find((item) => item.id === selectedConfigId.value) ?? rechargeConfig.value;
});
const hasConfig = computed(() => {
    if (!selectedConfig.value || !selectedConfig.value.is_active) {
        return false;
    }

    if (selectedConfig.value.provider === 'apibankvn_api') {
        return selectedConfig.value.api_ready;
    }

    return true;
});
const canSubmit = computed(() => hasConfig.value && amountValue.value >= 1000 && amountValue.value <= MAX_DEPOSIT_AMOUNT && !creatingRequest.value);
const amountError = computed(() => {
    if (!selectedConfig.value) {
        return 'Hệ thống chưa có cấu hình nhận tiền khả dụng.';
    }

    if (!selectedConfig.value.is_active) {
        return 'Phương thức đang chọn hiện tạm tắt. Vui lòng chọn bank khác.';
    }

    if (selectedConfig.value.provider === 'apibankvn_api' && !selectedConfig.value.api_ready) {
        return 'Kênh API của bank này chưa sẵn sàng. Vui lòng chọn phương thức khác.';
    }

    if (amountValue.value <= 0) {
        return 'Vui lòng nhập số tiền cần nạp.';
    }

    if (amountValue.value < 1000) {
        return 'Số tiền nạp tối thiểu là 1.000đ.';
    }

    if (amountValue.value > MAX_DEPOSIT_AMOUNT) {
        return 'Số tiền nạp tối đa là 50.000.000đ.';
    }

    return '';
});
const rechargeMethods = computed(() =>
    rechargeConfigs.value.map((config, index) => ({
        id: config.id,
        title: config.bank_name || `Ngân hàng ${index + 1}`,
        subtitle: config.provider === 'apibankvn_api' ? 'Đồng bộ từ hệ thống API' : 'Cấu hình local của hệ thống',
        providerLabel: config.provider === 'apibankvn_api' ? 'API' : 'Local',
        accountName: config.account_name,
        accountNumber: config.account_number,
        isActive: config.is_active && (config.provider !== 'apibankvn_api' || config.api_ready),
        isSelected: config.id === selectedConfigId.value,
    })),
);
const transferInfo = computed(() => ({
    bankName: selectedConfig.value?.bank_name ?? '--',
    accountNumber: selectedConfig.value?.account_number ?? '--',
    accountName: selectedConfig.value?.account_name ?? '--',
    content: selectedConfig.value?.transfer_content ?? '--',
    qrUrl: selectedConfig.value?.qr_url ?? null,
}));
const hasPendingRequest = computed(() => historyRows.value.some((item) => item.can_confirm));
const historyStats = computed(() => {
    const paidRows = historyRows.value.filter((item) => item.status === 'paid');

    return {
        totalDeposited: formatMoney(paidRows.reduce((sum, item) => sum + item.amount, 0)),
        depositCount: historyMeta.value.total,
        totalBonus: formatMoney(paidRows.reduce((sum, item) => sum + item.bonus_amount, 0)),
    };
});
const isPaymentView = computed(() => route.query.view === 'payment' && activePaymentRequest.value !== null);
const isPaymentExpired = computed(
    () => activePaymentRequest.value?.status === 'expired' || (countdownSeconds.value <= 0 && activePaymentRequest.value?.status === 'pending'),
);
const countdownLabel = computed(() => formatCountdown(countdownSeconds.value));
const countdownProgress = computed(() => {
    const request = activePaymentRequest.value;

    if (!request?.created_at || !request.expires_at) {
        return 0;
    }

    const createdAt = new Date(request.created_at).getTime();
    const expiresAt = new Date(request.expires_at).getTime();
    const total = Math.max(1, Math.floor((expiresAt - createdAt) / 1000));

    return Math.max(0, Math.min(100, (countdownSeconds.value / total) * 100));
});

onMounted(async () => {
    window.addEventListener('wallet:deposit-credited', onWalletDepositCredited);
    window.addEventListener('wallet:balance-changed', onWalletBalanceChanged);

    if (!userStore.user) {
        await userStore.bootstrap({ silent: true });
    }

    hydratePaymentStateFromStorage();
    await Promise.all([loadOverview(), loadHistory()]);

    if (route.query.view === 'payment' && activePaymentRequest.value) {
        startPaymentTimers();
        void refreshActivePaymentRequest(true);
    }
});

onBeforeUnmount(() => {
    stopPaymentTimers();
    window.removeEventListener('wallet:deposit-credited', onWalletDepositCredited);
    window.removeEventListener('wallet:balance-changed', onWalletBalanceChanged);
});

async function loadOverview(): Promise<void> {
    try {
        loadingOverview.value = true;

        const response = await clientWalletService.overview({
            amount: amountValue.value,
        });

        wallet.value = response.wallet;
        rechargeConfig.value = response.recharge_config;
        rechargeConfigs.value = response.recharge_configs ?? [];

        const nextSelectedId = selectedConfigId.value;
        const hasSelected = nextSelectedId !== null && rechargeConfigs.value.some((item) => item.id === nextSelectedId);

        if (!hasSelected) {
            selectedConfigId.value = response.recharge_config?.id ?? rechargeConfigs.value[0]?.id ?? null;
        }
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingOverview.value = false;
    }
}

async function loadHistory(): Promise<void> {
    try {
        loadingHistory.value = true;
        const response = await clientWalletService.listDepositRequests({
            page: currentPage.value,
            per_page: 10,
            search: historySearch.value.trim() || undefined,
            status: historyStatus.value,
        });

        historyRows.value = response.data;
        historyMeta.value = response.meta;
        currentPage.value = response.meta.current_page;
        hydratePaymentRequestFromRows(response.data);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingHistory.value = false;
    }
}

function onAmountInput(value: string): void {
    amountInput.value = value.replace(/\D+/g, '');
}

function selectPreset(nextAmount: number): void {
    amountInput.value = String(nextAmount);
}

async function refreshWalletData(): Promise<void> {
    await Promise.all([loadOverview(), loadHistory()]);
}

function selectConfig(configId: number): void {
    selectedConfigId.value = configId;
}

async function copyTransferField(field: 'bankName' | 'accountNumber' | 'accountName' | 'content'): Promise<void> {
    const fieldMap = {
        bankName: transferInfo.value.bankName,
        accountNumber: transferInfo.value.accountNumber,
        accountName: transferInfo.value.accountName,
        content: transferInfo.value.content,
    };

    await copyValue(fieldMap[field], field);
}

async function copyPaymentField(field: 'bankName' | 'accountNumber' | 'accountName' | 'content' | 'amount' | 'code'): Promise<void> {
    if (!activePaymentRequest.value) {
        return;
    }

    const fieldMap = {
        bankName: activePaymentRequest.value.bank_name || '--',
        accountNumber: activePaymentRequest.value.account_number || '--',
        accountName: activePaymentRequest.value.account_name || '--',
        content: activePaymentRequest.value.content || '--',
        amount: formatMoney(activePaymentRequest.value.amount),
        code: activePaymentRequest.value.code,
    };

    await copyValue(fieldMap[field], field);
}

async function copyValue(value: string, key: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(value);
        copiedField.value = key;
        window.setTimeout(() => {
            if (copiedField.value === key) {
                copiedField.value = null;
            }
        }, 1500);
    } catch (error) {
        handleErrorResponse(error);
    }
}

async function createRequest(): Promise<void> {
    if (!canSubmit.value) {
        return;
    }

    try {
        creatingRequest.value = true;
        const response = await clientWalletService.createDepositRequest({
            amount: amountValue.value,
            config_id: selectedConfigId.value,
        });

        await openPaymentView(response.deposit_request, 'deposit');
        await Promise.all([loadHistory(), loadOverview()]);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        creatingRequest.value = false;
    }
}

async function openLatestPendingPayment(): Promise<void> {
    const latestPending = historyRows.value.find((item) => item.can_confirm);

    if (!latestPending) {
        return;
    }

    await openPaymentView(latestPending, 'history');
}

async function openPaymentView(item: DepositRequestItem, fromTab: DepositTabKey): Promise<void> {
    activePaymentRequest.value = item;
    persistPaymentState(item);
    copiedField.value = null;
    activeTab.value = fromTab;
    syncCountdown();
    startPaymentTimers();

    await router.replace({
        name: 'client.wallet',
        query: {
            view: 'payment',
            request: String(item.id),
            from: fromTab,
        },
    });
}

async function closePaymentView(): Promise<void> {
    const fallbackTab = route.query.from === 'history' ? 'history' : 'deposit';
    clearActivePaymentState();
    activeTab.value = fallbackTab;
    copiedField.value = null;

    await router.replace({
        name: 'client.wallet',
        query: {},
    });
}

async function confirmActivePaymentRequest(): Promise<void> {
    if (!activePaymentRequest.value) {
        return;
    }

    try {
        confirmingRequest.value = true;
        await clientWalletService.confirmDepositRequest(activePaymentRequest.value.id);
        await Promise.all([refreshActivePaymentRequest(true), loadHistory(), loadOverview()]);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        confirmingRequest.value = false;
    }
}

async function refreshActivePaymentRequest(silent = false): Promise<void> {
    if (!activePaymentRequest.value) {
        return;
    }

    try {
        refreshingPayment.value = true;
        const response = await clientWalletService.listDepositRequests({
            page: 1,
            per_page: 10,
            search: activePaymentRequest.value.code,
            status: 'all',
        });

        const matched = response.data.find(
            (item) => String(item.id) === String(activePaymentRequest.value?.id) || item.code === activePaymentRequest.value?.code,
        );

        if (matched) {
            activePaymentRequest.value = matched;
            persistPaymentState(matched);
            syncCountdown();

            if (matched.status === 'paid') {
                await handlePaidDepositSuccess();
                return;
            }
        }

        if (!silent) {
            historyRows.value = response.data;
            historyMeta.value = response.meta;
        }
    } catch (error) {
        if (!silent) {
            handleErrorResponse(error);
        }
    } finally {
        refreshingPayment.value = false;
    }
}

function goToCreate(): void {
    activeTab.value = 'deposit';
    void closePaymentView();
}

function changePage(page: number): void {
    if (page < 1 || page > historyMeta.value.last_page) {
        return;
    }

    currentPage.value = page;
}

function persistPaymentState(item: DepositRequestItem): void {
    window.sessionStorage.setItem(PAYMENT_STORAGE_KEY, JSON.stringify(item));
}

function hydratePaymentStateFromStorage(): void {
    if (route.query.view !== 'payment') {
        return;
    }

    const stored = window.sessionStorage.getItem(PAYMENT_STORAGE_KEY);

    if (!stored) {
        return;
    }

    try {
        const parsed = JSON.parse(stored) as DepositRequestItem;
        const requestId = route.query.request ? String(route.query.request) : null;

        if (requestId === null || String(parsed.id) === requestId || parsed.code === requestId) {
            activePaymentRequest.value = parsed;
            syncCountdown();
        }
    } catch {
        window.sessionStorage.removeItem(PAYMENT_STORAGE_KEY);
    }
}

function hydratePaymentRequestFromRows(rows: DepositRequestItem[]): void {
    if (route.query.view !== 'payment') {
        return;
    }

    const requestId = route.query.request ? String(route.query.request) : null;

    if (!requestId) {
        return;
    }

    const matched = rows.find((item) => String(item.id) === requestId || item.code === requestId);

    if (!matched) {
        return;
    }

    activePaymentRequest.value = matched;
    persistPaymentState(matched);
    syncCountdown();
}

function clearActivePaymentState(): void {
    activePaymentRequest.value = null;
    countdownSeconds.value = 0;
    window.sessionStorage.removeItem(PAYMENT_STORAGE_KEY);
    stopPaymentTimers();
}

async function applyRealtimeWalletCredit(event: WalletDepositCreditedEvent): Promise<void> {
    if (wallet.value) {
        wallet.value.balance = event.balance;
        wallet.value.total_recharge = event.total_recharge;
    }

    historyRows.value = historyRows.value.map((item) =>
        String(item.id) === String(event.payment_transaction_id) || item.code === event.transaction_code
            ? { ...item, status: 'paid', confirmed_at: event.credited_at, can_confirm: false }
            : item,
    );

    const isActivePayment =
        String(activePaymentRequest.value?.id) === String(event.payment_transaction_id) ||
        activePaymentRequest.value?.code === event.transaction_code;

    if (isActivePayment && activePaymentRequest.value) {
        activePaymentRequest.value = {
            ...activePaymentRequest.value,
            status: 'paid',
            confirmed_at: event.credited_at,
            can_confirm: false,
        };
        persistPaymentState(activePaymentRequest.value);
        await handlePaidDepositSuccess(event.balance);

        return;
    }

    await Swal.fire({
        icon: 'success',
        title: 'Nạp tiền thành công',
        text: `${formatMoney(event.amount)} đã được cộng vào ví. Số dư mới: ${formatMoney(event.balance)}.`,
        timer: 5000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
    });
}

function onWalletDepositCredited(event: Event): void {
    const walletEvent = event as CustomEvent<WalletDepositCreditedEvent>;

    void applyRealtimeWalletCredit(walletEvent.detail);
}

function onWalletBalanceChanged(event: Event): void {
    const walletEvent = event as CustomEvent<WalletBalanceChangedEvent>;

    if (!wallet.value || wallet.value.type !== walletEvent.detail.wallet_type) {
        return;
    }

    wallet.value = {
        ...wallet.value,
        balance: walletEvent.detail.balance,
        hold_balance: walletEvent.detail.hold_balance,
        total_recharge: walletEvent.detail.total_recharge,
        total_spent: walletEvent.detail.total_spent,
    };
}

async function handlePaidDepositSuccess(creditedBalance?: string): Promise<void> {
    if (handlingPaidRequest.value) {
        return;
    }

    handlingPaidRequest.value = true;
    stopPaymentTimers();

    try {
        if (creditedBalance === undefined) {
            await loadOverview();
        }

        await Swal.fire({
            icon: 'success',
            title: 'Nạp tiền thành công',
            text: `Số dư mới của bạn là ${balanceLabel.value}.`,
            confirmButtonText: 'Đã hiểu',
            allowOutsideClick: false,
        });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        redirectToWalletOverview();
        handlingPaidRequest.value = false;
    }
}

function redirectToWalletOverview(): void {
    clearActivePaymentState();
    activeTab.value = 'deposit';
    void router.replace({ name: 'client.wallet', query: {} });
}

function startPaymentTimers(): void {
    stopPaymentTimers();
    syncCountdown();

    if (!activePaymentRequest.value) {
        return;
    }

    countdownTimerId = window.setInterval(() => {
        syncCountdown();
    }, 1000);
}

function stopPaymentTimers(): void {
    if (countdownTimerId !== null) {
        window.clearInterval(countdownTimerId);
        countdownTimerId = null;
    }
}

function syncCountdown(): void {
    if (!activePaymentRequest.value?.expires_at) {
        countdownSeconds.value = 0;
        return;
    }

    const expiresAt = new Date(activePaymentRequest.value.expires_at).getTime();
    const remaining = Math.floor((expiresAt - Date.now()) / 1000);
    countdownSeconds.value = Math.max(0, remaining);
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat('vi-VN').format(value);
}

function formatMoney(value: number | string | null | undefined): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

function formatCountdown(totalSeconds: number): string {
    const normalized = Math.max(0, totalSeconds);
    const hours = Math.floor(normalized / 3600);
    const minutes = Math.floor((normalized % 3600) / 60);
    const seconds = normalized % 60;

    if (hours > 0) {
        return [hours, minutes, seconds].map((item) => String(item).padStart(2, '0')).join(':');
    }

    return [minutes, seconds].map((item) => String(item).padStart(2, '0')).join(':');
}
</script>

<template>
    <div class="space-y-4 overflow-x-hidden rounded-[10px] bg-[#f4f7fb] pb-3 sm:space-y-5 sm:pb-4">
        <WalletHeader :balance-label="balanceLabel" :bonus-rate="bonusRate" />

        <section class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm">
            <WalletTabs v-if="!isPaymentView" v-model="activeTab" />

            <div class="p-3 sm:p-4 lg:p-5">
                <div v-if="loadingOverview" class="space-y-3">
                    <div class="h-16 animate-pulse rounded-[10px] bg-slate-100"></div>
                    <div class="h-52 animate-pulse rounded-[10px] bg-slate-100"></div>
                </div>

                <template v-else>
                    <PaymentPanel
                        v-if="isPaymentView && activePaymentRequest"
                        :request="activePaymentRequest"
                        :copied-field="copiedField"
                        :countdown-label="countdownLabel"
                        :countdown-progress="countdownProgress"
                        :is-expired="isPaymentExpired"
                        :is-refreshing="refreshingPayment"
                        :is-confirming="confirmingRequest"
                        @back="closePaymentView"
                        @copy="copyPaymentField"
                        @refresh="refreshActivePaymentRequest(true)"
                        @confirm="confirmActivePaymentRequest"
                    />

                    <DepositPanel
                        v-else-if="activeTab === 'deposit'"
                        :has-config="hasConfig"
                        :amount-input="amountInput"
                        :amount-display="amountDisplay"
                        :amount-error="amountError"
                        :transfer-info="transferInfo"
                        :copied-field="copiedField"
                        :can-submit="canSubmit"
                        :has-pending-request="hasPendingRequest && !confirmingRequest"
                        :methods="rechargeMethods"
                        :selected-method-id="selectedConfigId"
                        @update:amount="onAmountInput"
                        @select-preset="selectPreset"
                        @select-method="selectConfig"
                        @copy="copyTransferField"
                        @refresh="refreshWalletData"
                        @submit="createRequest"
                        @confirm="openLatestPendingPayment"
                    />

                    <HistoryPanel
                        v-else
                        :stats="historyStats"
                        :filters="{ search: historySearch, status: historyStatus, dateLabel: '30 ngày gần nhất' }"
                        :loading="loadingHistory"
                        :rows="historyRows"
                        :total="historyMeta.total"
                        :current-page="historyMeta.current_page"
                        :last-page="historyMeta.last_page"
                        @update:search="historySearch = $event"
                        @update:status="historyStatus = $event"
                        @change-page="changePage"
                        @go-create="goToCreate"
                        @confirm="openPaymentView($event, 'history')"
                    />
                </template>
            </div>
        </section>
    </div>
</template>
