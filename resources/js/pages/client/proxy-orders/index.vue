<script setup lang="ts">
import {
    clientProxyService,
    type ManagedProxy,
    type ManagedProxyStatus,
    type PaginationMeta,
    type ProxyOrder,
    type ProxyOrderStatus,
    type ProxyOrderType,
} from '@/services/client-proxy.service';
import { useUserStore } from '@/stores/user.store';
import { handleErrorResponse } from '@/utils/response';
import { echo } from '@laravel/echo-vue';
import type { AxiosError } from 'axios';
import { Clipboard, Clock3, History, ListChecks, LoaderCircle, RefreshCcw, Repeat2, RotateCcw, Search } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

type Tab = 'static' | 'rotating' | 'orders';

const activeTab = ref<Tab>('static');
const userStore = useUserStore();
const pendingLoads = ref(0);
const loading = computed(() => pendingLoads.value > 0);
const actingId = ref<number | null>(null);
const bulkActing = ref(false);
const changeProxyFee = 1000;
const proxies = ref<ManagedProxy[]>([]);
const orders = ref<ProxyOrder[]>([]);
const trackedOperations = reactive(new Map<number, ProxyOrder>());
const fetchedRotatingProxies = reactive<Record<number, string>>({});
const selected = ref<number[]>([]);
const proxyMeta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const orderMeta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const proxyFilters = reactive({
    search: '',
    status: 'active',
    protocol: '',
    sort: 'latest',
    page: 1,
});
const orderFilters = reactive({
    search: '',
    status: '',
    type: '',
    page: 1,
});

const selectedProxies = computed(() => proxies.value.filter((proxy) => selected.value.includes(proxy.id)));
const allVisibleSelected = computed(() => proxies.value.length > 0 && proxies.value.every((proxy) => selected.value.includes(proxy.id)));
const isProxyTab = computed(() => activeTab.value !== 'orders');

const activeProxyOperation = (proxyId: number): ProxyOrder | undefined =>
    [...trackedOperations.values()].find((order) => order.target_proxy_id === proxyId);

const isProxyBusy = (proxyId: number): boolean => activeProxyOperation(proxyId) !== undefined;

const proxyOperationLabel = (proxyId: number): string | null => {
    const operation = activeProxyOperation(proxyId);
    if (!operation) return null;

    return operation.type === 'change' ? 'Đang đổi proxy' : 'Đang gia hạn';
};

const statusLabels: Record<ManagedProxyStatus | ProxyOrderStatus, string> = {
    pending: 'Đang chờ',
    processing: 'Đang xử lý',
    fulfilled: 'Hoàn tất',
    failed: 'Thất bại',
    refunded: 'Đã hoàn tiền',
    active: 'Hoạt động',
    changing: 'Đang đổi',
    expired: 'Hết hạn',
    disabled: 'Đã khóa',
    error: 'Có lỗi',
};

const typeLabels: Record<ProxyOrderType, string> = {
    purchase: 'Mua mới',
    change: 'Đổi proxy',
    renew: 'Gia hạn',
};

const statusClass = (status: ManagedProxyStatus | ProxyOrderStatus): string => {
    if (['active', 'fulfilled'].includes(status)) return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (['error', 'failed', 'refunded'].includes(status)) return 'border-rose-200 bg-rose-50 text-rose-700';
    if (['expired', 'disabled'].includes(status)) return 'border-slate-200 bg-slate-100 text-slate-600';
    return 'border-amber-200 bg-amber-50 text-amber-700';
};

const money = (value: string | number) => `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))} đ`;
const dateTime = (value: string | null) =>
    value ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '--';

const remainingDays = (expiresAt: string | null): string => {
    if (!expiresAt) return '--';

    const expirationTime = new Date(expiresAt).getTime();
    if (Number.isNaN(expirationTime)) return '--';

    const days = Math.ceil((expirationTime - Date.now()) / 86_400_000);

    return days > 0 ? `${days} ngày` : 'Đã hết hạn';
};

const connectionText = (proxy: ManagedProxy): string => {
    if (proxy.status === 'changing') return '';
    if (proxy.proxy_type === 'rotating') return fetchedRotatingProxies[proxy.id] || proxy.access_key || '';
    if (!proxy.connection) return '';

    return [proxy.connection.host, proxy.connection.port, proxy.connection.username, proxy.connection.password]
        .filter((value) => value !== null && value !== '')
        .join(':');
};

const cleanParams = (filters: Record<string, unknown>) =>
    Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null));

const loadProxies = async () => {
    pendingLoads.value += 1;
    try {
        const pendingChanges = proxies.value.filter((proxy) => proxy.status === 'changing');
        const shouldKeepPendingChanges =
            activeTab.value === 'static' && proxyFilters.status === 'active' && proxyFilters.search === '' && proxyFilters.protocol === '';
        const params = cleanParams({ ...proxyFilters, proxy_type: activeTab.value });
        const [data, changingData] = await Promise.all([
            clientProxyService.proxies(params),
            shouldKeepPendingChanges
                ? clientProxyService.proxies({ status: 'changing', proxy_type: 'static', sort: 'latest', page: 1, per_page: 100 })
                : Promise.resolve(null),
        ]);
        const serverPendingChanges = changingData?.proxies ?? [];
        const serverProxyIds = new Set([...serverPendingChanges, ...data.proxies].map((proxy) => proxy.id));
        const missingPendingChanges = shouldKeepPendingChanges ? pendingChanges.filter((pendingProxy) => !serverProxyIds.has(pendingProxy.id)) : [];
        const visibleProxies = [...serverPendingChanges, ...missingPendingChanges, ...data.proxies];

        proxies.value = visibleProxies;
        proxyMeta.value = {
            ...data.meta,
            total: data.meta.total + (changingData?.meta.total ?? 0) + missingPendingChanges.length,
        };
        selected.value = selected.value.filter((id) => visibleProxies.some((proxy) => proxy.id === id));
    } catch (error) {
        handleErrorResponse(error as never);
    } finally {
        pendingLoads.value = Math.max(0, pendingLoads.value - 1);
    }
};

const loadOrders = async () => {
    pendingLoads.value += 1;
    try {
        const data = await clientProxyService.orders(cleanParams(orderFilters));
        orders.value = data.orders;
        orderMeta.value = data.meta;
        data.orders.forEach(monitorOperation);
    } catch (error) {
        handleErrorResponse(error as never);
    } finally {
        pendingLoads.value = Math.max(0, pendingLoads.value - 1);
    }
};

const loadAll = async () => {
    await Promise.all([loadProxies(), loadOrders(), recoverActiveOperations()]);
};

const reload = async () => {
    if (isProxyTab.value) {
        await loadProxies();
        return;
    }

    await loadOrders();
};

const orderStatusPriority: Record<ProxyOrderStatus, number> = {
    pending: 0,
    processing: 1,
    fulfilled: 2,
    failed: 2,
    refunded: 3,
};
const terminalOrderStatuses: ProxyOrderStatus[] = ['fulfilled', 'failed', 'refunded'];
const notifiedTerminalOrderIds = new Set<number>();
const operationMonitors = new Map<number, ReturnType<typeof setTimeout>>();
const operationMonitorStartedAt = new Map<number, number>();
const proxyRefreshTimers = new Map<number, ReturnType<typeof setTimeout>>();
let operationAlerts: Promise<unknown> = Promise.resolve();

type ProxyOperationErrorPayload = {
    message?: string;
    errors?: Record<string, string[]>;
};

type BulkOperationFailure = {
    proxyId: number;
    message: string;
};

const proxyOperationErrorMessage = (error: unknown): string => {
    const axiosError = error as AxiosError<ProxyOperationErrorPayload>;
    const validationMessage = Object.values(axiosError.response?.data?.errors ?? {}).flat()[0];

    return validationMessage || axiosError.response?.data?.message || axiosError.message || 'Không thể gửi yêu cầu lúc này.';
};

const showProxyOperationError = async (error: unknown): Promise<void> => {
    await Swal.fire({
        title: 'Không thể thực hiện thao tác',
        text: proxyOperationErrorMessage(error),
        icon: 'error',
        confirmButtonText: 'Đã hiểu',
    });
};

const escapeHtml = (value: string): string => {
    const element = document.createElement('div');
    element.textContent = value;

    return element.innerHTML;
};

const upsertOrder = (incomingOrder: ProxyOrder) => {
    const currentOrder = orders.value.find((order) => order.id === incomingOrder.id);

    if (currentOrder) {
        const currentStatus = currentOrder.status;
        Object.assign(currentOrder, incomingOrder);

        if (orderStatusPriority[currentStatus] > orderStatusPriority[incomingOrder.status]) {
            currentOrder.status = currentStatus;
        }

        return;
    }

    orders.value.unshift(incomingOrder);
    orderMeta.value.total += 1;

    if (orders.value.length > orderMeta.value.per_page) orders.value.pop();
};

const replaceProxy = (updatedProxy: ManagedProxy) => {
    const currentProxy = proxies.value.find((proxy) => proxy.id === updatedProxy.id);
    if (currentProxy) Object.assign(currentProxy, updatedProxy);
};

const refreshProxy = async (proxyId: number) => {
    const data = await clientProxyService.proxy(proxyId);
    replaceProxy(data.proxy);
};

const refreshProxyWithRetry = async (proxyId: number, attempt = 0): Promise<void> => {
    try {
        await refreshProxy(proxyId);
        const timer = proxyRefreshTimers.get(proxyId);
        if (timer) clearTimeout(timer);
        proxyRefreshTimers.delete(proxyId);
    } catch {
        if (attempt >= 4) return;

        const timer = setTimeout(() => void refreshProxyWithRetry(proxyId, attempt + 1), 1000 * 2 ** attempt);
        proxyRefreshTimers.set(proxyId, timer);
    }
};

type ProxyOrderUpdatedEvent = {
    order_id: number;
    target_proxy_id: number | null;
    type: ProxyOrderType;
    status: ProxyOrderStatus;
    error_message: string | null;
};

const stopOperationMonitor = (orderId: number) => {
    const timer = operationMonitors.get(orderId);
    if (timer) clearTimeout(timer);
    operationMonitors.delete(orderId);
    operationMonitorStartedAt.delete(orderId);
    trackedOperations.delete(orderId);
};

const monitorOperation = (order: ProxyOrder) => {
    if (order.type === 'purchase' || order.target_proxy_id === null || terminalOrderStatuses.includes(order.status)) return;

    trackedOperations.set(order.id, order);

    if (operationMonitors.has(order.id)) return;

    operationMonitorStartedAt.set(order.id, Date.now());

    const scheduleNextPoll = (poll: () => Promise<void>) => {
        const elapsed = Date.now() - (operationMonitorStartedAt.get(order.id) ?? Date.now());
        const delay = document.hidden || elapsed >= 60_000 ? 10_000 : 2_000;
        operationMonitors.set(order.id, setTimeout(poll, delay));
    };

    const poll = async () => {
        try {
            const data = await clientProxyService.order(order.id);
            upsertOrder(data.order);
            trackedOperations.set(data.order.id, data.order);

            if (terminalOrderStatuses.includes(data.order.status)) {
                await applyRealtimeUpdate({
                    order_id: data.order.id,
                    target_proxy_id: data.order.target_proxy_id,
                    type: data.order.type,
                    status: data.order.status,
                    error_message: data.order.error_message,
                });
                return;
            }
        } catch {
            // Socket vẫn là luồng chính; polling sẽ thử lại nếu request trạng thái tạm thời thất bại.
        }

        scheduleNextPoll(poll);
    };

    operationMonitors.set(order.id, setTimeout(poll, 1500));
};

async function recoverActiveOperations(): Promise<void> {
    try {
        const [pending, processing] = await Promise.all([
            clientProxyService.orders({ status: 'pending', per_page: 100 }),
            clientProxyService.orders({ status: 'processing', per_page: 100 }),
        ]);

        [...pending.orders, ...processing.orders].forEach(monitorOperation);
    } catch {
        // Kênh socket và lần reload thủ công vẫn có thể tiếp tục cập nhật trạng thái.
    }
}

const applyRealtimeUpdate = async (event: ProxyOrderUpdatedEvent) => {
    const isTerminal = terminalOrderStatuses.includes(event.status);
    const trackedOrder = trackedOperations.get(event.order_id);

    if (trackedOrder && orderStatusPriority[event.status] >= orderStatusPriority[trackedOrder.status]) {
        trackedOrder.status = event.status;
        trackedOrder.error_message = event.error_message;
    }

    const order = orders.value.find((item) => item.id === event.order_id);
    if (order && orderStatusPriority[event.status] >= orderStatusPriority[order.status]) {
        order.status = event.status;
        order.error_message = event.error_message;
    }

    if (isTerminal) {
        stopOperationMonitor(event.order_id);

        if (event.target_proxy_id !== null) {
            await refreshProxyWithRetry(event.target_proxy_id);
        }
    }

    if (event.status !== 'fulfilled' && isTerminal && !notifiedTerminalOrderIds.has(event.order_id)) {
        notifiedTerminalOrderIds.add(event.order_id);

        operationAlerts = operationAlerts.then(() =>
            Swal.fire({
                title: event.status === 'refunded' ? 'Thao tác thất bại, tiền đã được hoàn' : 'Thao tác proxy thất bại',
                text: event.error_message || 'Không thể hoàn tất thao tác proxy lúc này. Vui lòng thử lại sau.',
                icon: 'error',
                confirmButtonText: 'Đã hiểu',
            }),
        );

        await operationAlerts;
    }
};

let subscribedUserId: number | null = null;

const leaveProxyOrderChannel = () => {
    if (subscribedUserId === null) return;

    echo().leave(`users.${subscribedUserId}.proxy-orders`);
    subscribedUserId = null;
};

watch(
    () => userStore.user?.id ?? null,
    (userId) => {
        if (userId === subscribedUserId) return;

        leaveProxyOrderChannel();

        if (userId === null) return;

        subscribedUserId = userId;
        echo()
            .private(`users.${userId}.proxy-orders`)
            .listen('.proxy.order.updated', (event: ProxyOrderUpdatedEvent) => void applyRealtimeUpdate(event));
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    leaveProxyOrderChannel();
    operationMonitors.forEach((timer) => clearTimeout(timer));
    operationMonitors.clear();
    operationMonitorStartedAt.clear();
    proxyRefreshTimers.forEach((timer) => clearTimeout(timer));
    proxyRefreshTimers.clear();
    trackedOperations.clear();
});

const switchTab = async (tab: Tab) => {
    if (tab !== activeTab.value && tab !== 'orders') proxyFilters.page = 1;
    selected.value = [];
    activeTab.value = tab;
    await reload();
};

const applyFilters = async () => {
    if (isProxyTab.value) proxyFilters.page = 1;
    else orderFilters.page = 1;
    await reload();
};

const changePage = async (page: number) => {
    if (isProxyTab.value) proxyFilters.page = page;
    else orderFilters.page = page;
    await reload();
};

const toggleAll = () => {
    if (allVisibleSelected.value) {
        selected.value = selected.value.filter((id) => !proxies.value.some((proxy) => proxy.id === id));
        return;
    }

    selected.value = [...new Set([...selected.value, ...proxies.value.map((proxy) => proxy.id)])];
};

const copyText = async (value: string, message = 'Đã sao chép proxy') => {
    if (!value) {
        await Swal.fire('Chưa có thông tin proxy', 'Proxy đang được cập nhật hoặc đã phát sinh lỗi.', 'info');
        return;
    }

    await navigator.clipboard.writeText(value);
    await Swal.fire({ title: message, icon: 'success', timer: 1000, showConfirmButton: false });
};

const copySelected = async () => {
    const values = selectedProxies.value.map(connectionText).filter(Boolean);
    await copyText(values.join('\n'), `Đã sao chép ${values.length} proxy`);
};

const showBulkResult = async (action: string, succeeded: number, failures: BulkOperationFailure[]) => {
    if (failures.length === 0) return;

    const failureItems = failures
        .map(
            ({ proxyId, message }) =>
                `<li class="rounded-lg bg-rose-50 px-3 py-2 text-left text-sm text-rose-800"><strong>#${proxyId}</strong> - ${escapeHtml(message)}</li>`,
        )
        .join('');

    await Swal.fire({
        title: succeeded > 0 ? `${action} chỉ được tiếp nhận một phần` : `Không thể ${action.toLocaleLowerCase('vi-VN')}`,
        html: `<p class="mb-3 text-sm text-slate-600">Đã gửi ${succeeded} yêu cầu, lỗi ${failures.length} yêu cầu.</p><ul class="max-h-72 space-y-2 overflow-y-auto">${failureItems}</ul>`,
        icon: succeeded > 0 ? 'warning' : 'error',
        confirmButtonText: 'Đã hiểu',
    });
};

const markProxyAsChanging = (proxy: ManagedProxy) => {
    proxy.status = 'changing';
    proxy.access_key = null;
    proxy.connection = null;
};

const fetchRotatingProxy = async (proxy: ManagedProxy) => {
    actingId.value = proxy.id;

    try {
        const data = await clientProxyService.fetchRotatingProxy(proxy.id);
        fetchedRotatingProxies[data.proxy_id] = data.proxy;
    } catch (error) {
        await showProxyOperationError(error);
    } finally {
        actingId.value = null;
    }
};

const fetchSelectedRotatingProxies = async () => {
    const eligibleProxies = selectedProxies.value.filter((proxy) => proxy.proxy_type === 'rotating' && proxy.status === 'active' && proxy.access_key);

    if (eligibleProxies.length === 0) {
        await Swal.fire('Không thể lấy proxy', 'Chỉ proxy xoay đang hoạt động mới có thể lấy dữ liệu.', 'info');
        return;
    }

    bulkActing.value = true;
    let succeeded = 0;
    const failures: BulkOperationFailure[] = [];

    try {
        for (const proxy of eligibleProxies) {
            try {
                const data = await clientProxyService.fetchRotatingProxy(proxy.id);
                fetchedRotatingProxies[data.proxy_id] = data.proxy;
                succeeded += 1;
            } catch (error) {
                failures.push({ proxyId: proxy.id, message: proxyOperationErrorMessage(error) });
            }
        }

        await showBulkResult('Lấy proxy', succeeded, failures);
    } finally {
        bulkActing.value = false;
    }
};

const changeSelectedProxies = async () => {
    const eligibleProxies = selectedProxies.value.filter((proxy) => proxy.status === 'active' && !isProxyBusy(proxy.id));

    if (eligibleProxies.length === 0) {
        await Swal.fire('Không thể đổi proxy', 'Chỉ proxy đang hoạt động mới có thể đổi.', 'info');
        return;
    }

    const confirmation = await Swal.fire({
        title: `Đổi ${eligibleProxies.length} proxy?`,
        text: `Phí đổi là ${money(changeProxyFee)}/proxy. Tổng phí dự kiến: ${money(changeProxyFee * eligibleProxies.length)}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Xác nhận thanh toán ${money(changeProxyFee * eligibleProxies.length)}`,
        cancelButtonText: 'Hủy',
    });
    if (!confirmation.isConfirmed) return;

    bulkActing.value = true;
    let succeeded = 0;
    const failures: BulkOperationFailure[] = [];

    try {
        for (const proxy of eligibleProxies) {
            try {
                const data = await clientProxyService.changeProxy(proxy.id);
                markProxyAsChanging(proxy);
                upsertOrder(data.order);
                monitorOperation(data.order);
                succeeded += 1;
            } catch (error) {
                failures.push({ proxyId: proxy.id, message: proxyOperationErrorMessage(error) });
            }
        }

        await showBulkResult('Đổi proxy', succeeded, failures);
    } finally {
        bulkActing.value = false;
    }
};

const renewSelectedProxies = async () => {
    const eligibleProxies = selectedProxies.value.filter((proxy) => ['active', 'expired'].includes(proxy.status) && !isProxyBusy(proxy.id));

    if (eligibleProxies.length === 0) {
        await Swal.fire('Không thể gia hạn', 'Chỉ proxy đang hoạt động hoặc đã hết hạn mới có thể gia hạn.', 'info');
        return;
    }

    const result = await Swal.fire({
        title: `Gia hạn ${eligibleProxies.length} proxy`,
        input: 'number',
        inputLabel: 'Số ngày gia hạn cho mỗi proxy',
        inputValue: 30,
        inputAttributes: { min: '1', max: '3650', step: '1' },
        showCancelButton: true,
        confirmButtonText: 'Xác nhận gia hạn',
        cancelButtonText: 'Hủy',
        inputValidator: (value) => {
            const days = Number(value);
            return Number.isInteger(days) && days >= 1 && days <= 3650 ? undefined : 'Số ngày phải từ 1 đến 3650.';
        },
    });
    if (!result.isConfirmed) return;

    bulkActing.value = true;
    let succeeded = 0;
    const failures: BulkOperationFailure[] = [];

    try {
        for (const proxy of eligibleProxies) {
            try {
                const data = await clientProxyService.renewProxy(proxy.id, Number(result.value));
                upsertOrder(data.order);
                monitorOperation(data.order);
                succeeded += 1;
            } catch (error) {
                failures.push({ proxyId: proxy.id, message: proxyOperationErrorMessage(error) });
            }
        }

        await showBulkResult('Gia hạn proxy', succeeded, failures);
    } finally {
        bulkActing.value = false;
    }
};

const changeProxy = async (proxy: ManagedProxy) => {
    const confirmation = await Swal.fire({
        title: 'Đổi proxy này?',
        text: `Phí đổi proxy là ${money(changeProxyFee)}/proxy. Phí sẽ được trừ khi xác nhận đổi.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Xác nhận thanh toán ${money(changeProxyFee)}`,
        cancelButtonText: 'Hủy',
    });
    if (!confirmation.isConfirmed) return;

    actingId.value = proxy.id;
    try {
        const data = await clientProxyService.changeProxy(proxy.id);
        markProxyAsChanging(proxy);
        upsertOrder(data.order);
        monitorOperation(data.order);
    } catch (error) {
        await showProxyOperationError(error);
    } finally {
        actingId.value = null;
    }
};

const renewProxy = async (proxy: ManagedProxy) => {
    const result = await Swal.fire({
        title: 'Gia hạn proxy',
        input: 'number',
        inputLabel: 'Số ngày gia hạn',
        inputValue: 30,
        inputAttributes: { min: '1', max: '3650', step: '1' },
        showCancelButton: true,
        confirmButtonText: 'Tạo yêu cầu',
        cancelButtonText: 'Hủy',
        inputValidator: (value) => {
            const days = Number(value);
            return Number.isInteger(days) && days >= 1 && days <= 3650 ? undefined : 'Số ngày phải từ 1 đến 3650.';
        },
    });
    if (!result.isConfirmed) return;

    actingId.value = proxy.id;
    try {
        const data = await clientProxyService.renewProxy(proxy.id, Number(result.value));
        upsertOrder(data.order);
        monitorOperation(data.order);
    } catch (error) {
        await showProxyOperationError(error);
    } finally {
        actingId.value = null;
    }
};

onMounted(loadAll);
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[5px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">DailyProxy</p>
                    <h1 class="text-xl font-black text-slate-950">Quản lý proxy</h1>
                    <p class="text-sm text-slate-500">Xem proxy, đơn hàng và gửi yêu cầu đổi hoặc gia hạn.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-2 rounded-[5px] bg-blue-600 px-3 text-sm font-semibold text-white disabled:opacity-50"
                        :disabled="loading"
                        @click="reload"
                    >
                        <RefreshCcw class="h-4 w-4" :class="{ 'animate-spin': loading }" /> Reload
                    </button>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-1 rounded-[5px] border border-slate-200 bg-white p-1">
            <button
                type="button"
                class="flex h-9 items-center gap-2 rounded-[5px] px-4 text-sm font-semibold transition"
                :class="activeTab === 'static' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                @click="switchTab('static')"
            >
                <History class="h-4 w-4" /> Proxy tĩnh
            </button>
            <button
                type="button"
                class="flex h-9 items-center gap-2 rounded-[5px] px-4 text-sm font-semibold transition"
                :class="activeTab === 'rotating' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                @click="switchTab('rotating')"
            >
                <RefreshCcw class="h-4 w-4" /> Proxy xoay
            </button>
            <button
                type="button"
                class="flex h-9 items-center gap-2 rounded-[5px] px-4 text-sm font-semibold transition"
                :class="activeTab === 'orders' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                @click="switchTab('orders')"
            >
                <Clock3 class="h-4 w-4" /> Đơn hàng <span class="opacity-70">({{ orderMeta.total }})</span>
            </button>
        </div>

        <form class="grid gap-2 rounded-[5px] border border-slate-200 bg-white p-3 md:grid-cols-2 xl:grid-cols-5" @submit.prevent="applyFilters">
            <label class="relative xl:col-span-2">
                <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input
                    v-if="isProxyTab"
                    v-model.trim="proxyFilters.search"
                    class="h-9 w-full rounded-[5px] border border-slate-300 pl-9 pr-3 text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Tìm mã đơn, tên hoặc nhãn proxy"
                />
                <input
                    v-else
                    v-model.trim="orderFilters.search"
                    class="h-9 w-full rounded-[5px] border border-slate-300 pl-9 pr-3 text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Tìm mã đơn hoặc sản phẩm"
                />
            </label>

            <template v-if="isProxyTab">
                <select v-model="proxyFilters.status" class="h-9 rounded-[5px] border border-slate-300 px-2 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Hoạt động</option>
                    <option value="pending">Đang chờ</option>
                    <option value="changing">Đang đổi</option>
                    <option value="expired">Hết hạn</option>
                    <option value="error">Có lỗi</option>
                </select>
                <select v-model="proxyFilters.protocol" class="h-9 rounded-[5px] border border-slate-300 px-2 text-sm uppercase">
                    <option value="">Tất cả giao thức</option>
                    <option value="http">HTTP</option>
                    <option value="https">HTTPS</option>
                    <option value="socks4">SOCKS4</option>
                    <option value="socks5">SOCKS5</option>
                </select>
            </template>
            <template v-else>
                <select v-model="orderFilters.status" class="h-9 rounded-[5px] border border-slate-300 px-2 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending">Đang chờ</option>
                    <option value="processing">Đang xử lý</option>
                    <option value="fulfilled">Hoàn tất</option>
                    <option value="failed">Thất bại</option>
                    <option value="refunded">Đã hoàn tiền</option>
                </select>
                <select v-model="orderFilters.type" class="h-9 rounded-[5px] border border-slate-300 px-2 text-sm">
                    <option value="">Tất cả loại đơn</option>
                    <option value="purchase">Mua mới</option>
                    <option value="change">Đổi proxy</option>
                    <option value="renew">Gia hạn</option>
                </select>
            </template>

            <button type="submit" class="h-9 rounded-[5px] bg-slate-800 px-4 text-sm font-semibold text-white">Lọc dữ liệu</button>
        </form>

        <section
            v-if="isProxyTab && selectedProxies.length > 0"
            class="flex flex-col justify-between gap-3 rounded-[5px] border border-blue-200 bg-blue-50 p-3 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-2 text-sm font-semibold text-blue-900">
                <ListChecks class="h-4 w-4" />
                Đã chọn {{ selectedProxies.length }} proxy
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-2 rounded-[5px] bg-blue-600 px-3 text-sm font-semibold text-white hover:bg-blue-700"
                    :disabled="bulkActing"
                    @click="copySelected"
                >
                    <Clipboard class="h-4 w-4" /> Copy proxy
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-2 rounded-[5px] border border-blue-300 bg-white px-3 text-sm font-semibold text-blue-800 hover:bg-blue-100"
                    :disabled="bulkActing"
                    @click="activeTab === 'rotating' ? fetchSelectedRotatingProxies() : changeSelectedProxies()"
                >
                    <RefreshCcw v-if="activeTab === 'rotating'" class="h-4 w-4" />
                    <Repeat2 v-else class="h-4 w-4" />
                    {{ activeTab === 'rotating' ? 'Lấy proxy' : 'Đổi proxy' }}
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-2 rounded-[5px] border border-amber-300 bg-white px-3 text-sm font-semibold text-amber-800 hover:bg-amber-100"
                    :disabled="bulkActing"
                    @click="renewSelectedProxies"
                >
                    <RotateCcw class="h-4 w-4" /> Gia hạn
                </button>
            </div>
        </section>

        <section class="overflow-hidden rounded-[5px] border border-slate-200 bg-white shadow-sm">
            <div v-if="loading" class="flex h-48 items-center justify-center text-sm text-slate-500">
                <LoaderCircle class="mr-2 h-5 w-5 animate-spin" /> Đang tải dữ liệu...
            </div>

            <div v-else-if="isProxyTab" class="overflow-x-auto">
                <table class="w-full min-w-[1150px] text-left text-sm">
                    <thead class="bg-slate-900 text-xs uppercase text-white">
                        <tr>
                            <th class="w-10 px-3 py-3"><input type="checkbox" :checked="allVisibleSelected" @change="toggleAll" /></th>
                            <th class="px-3 py-3">Sản phẩm</th>
                            <th class="px-3 py-3">Thông tin proxy</th>
                            <th class="px-3 py-3">Giao thức</th>
                            <th class="px-3 py-3">Hết hạn</th>
                            <th class="px-3 py-3">Còn lại</th>
                            <th class="px-3 py-3">Trạng thái</th>
                            <th class="px-3 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="proxies.length === 0">
                            <td colspan="8" class="px-4 py-14 text-center text-slate-500">Chưa có proxy nào trong tài khoản.</td>
                        </tr>
                        <tr v-for="proxy in proxies" :key="proxy.id" :class="proxy.status === 'error' ? 'bg-rose-50/70' : 'hover:bg-slate-50'">
                            <td class="px-3 py-3">
                                <input v-model="selected" type="checkbox" :value="proxy.id" />
                            </td>
                            <td class="px-3 py-3">
                                <p class="font-semibold text-slate-900">{{ proxy.product?.name || 'Sản phẩm proxy' }}</p>
                                <p class="text-xs text-slate-500">#{{ proxy.id }} · {{ proxy.country_code || 'Global' }}</p>
                            </td>
                            <td class="max-w-md px-3 py-3">
                                <div v-if="proxy.proxy_type === 'rotating'" class="grid gap-1.5 font-mono text-xs">
                                    <button
                                        v-if="proxy.access_key"
                                        type="button"
                                        class="flex min-w-0 items-center gap-2 text-left text-blue-700 hover:underline"
                                        :title="proxy.access_key"
                                        @click="copyText(proxy.access_key, 'Đã sao chép key')"
                                    >
                                        <span class="shrink-0 font-sans font-semibold text-slate-500">Key:</span>
                                        <span class="truncate font-semibold">{{ proxy.access_key }}</span>
                                    </button>
                                    <div v-else class="flex items-center gap-2 text-slate-500">
                                        <span class="font-sans font-semibold">Key:</span>
                                        <span>--</span>
                                    </div>
                                    <button
                                        v-if="fetchedRotatingProxies[proxy.id]"
                                        type="button"
                                        class="flex min-w-0 items-center gap-2 text-left text-blue-700 hover:underline"
                                        :title="fetchedRotatingProxies[proxy.id]"
                                        @click="copyText(fetchedRotatingProxies[proxy.id])"
                                    >
                                        <span class="shrink-0 font-sans font-semibold text-slate-500">Proxy:</span>
                                        <span class="truncate font-semibold">{{ fetchedRotatingProxies[proxy.id] }}</span>
                                    </button>
                                    <div v-else class="flex items-center gap-2 text-slate-500">
                                        <span class="font-sans font-semibold">Proxy:</span>
                                        <span>--</span>
                                    </div>
                                </div>
                                <button
                                    v-else-if="connectionText(proxy)"
                                    type="button"
                                    class="block max-w-full truncate font-mono text-xs font-semibold text-blue-700 hover:underline"
                                    :title="connectionText(proxy)"
                                    @click="copyText(connectionText(proxy))"
                                >
                                    {{ connectionText(proxy) }}
                                </button>
                                <div
                                    v-else-if="proxy.status === 'error'"
                                    class="rounded-[5px] border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
                                >
                                    <b>Mua proxy thất bại</b>
                                    <p>{{ proxy.error_message || 'Không thể cập nhật thông tin proxy.' }}</p>
                                </div>
                                <span v-else class="inline-flex items-center gap-2 text-xs font-semibold text-blue-600">
                                    <LoaderCircle v-if="proxy.status === 'changing'" class="h-3.5 w-3.5 animate-spin" />
                                    {{ proxy.status === 'changing' ? 'Đang lấy dữ liệu proxy' : 'Đang cập nhật thông tin proxy' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 font-semibold uppercase text-slate-700">{{ proxy.protocol }}</td>
                            <td class="px-3 py-3 text-xs text-slate-600">
                                <div class="grid gap-1">
                                    <p><span class="font-semibold text-slate-700">Ngày mua:</span> {{ dateTime(proxy.created_at) }}</p>
                                    <p><span class="font-semibold text-slate-700">Hết hạn:</span> {{ dateTime(proxy.expires_at) }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-xs font-semibold" :class="proxy.status === 'expired' ? 'text-rose-600' : 'text-emerald-700'">
                                {{ remainingDays(proxy.expires_at) }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-[5px] border px-2 py-1 text-xs font-semibold"
                                    :class="isProxyBusy(proxy.id) ? 'border-amber-200 bg-amber-50 text-amber-700' : statusClass(proxy.status)"
                                >
                                    <LoaderCircle v-if="isProxyBusy(proxy.id)" class="h-3.5 w-3.5 animate-spin" />
                                    {{ proxyOperationLabel(proxy.id) || statusLabels[proxy.status] }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 items-center gap-1 rounded-[5px] border border-slate-300 px-2 text-xs font-semibold text-slate-700 disabled:opacity-40"
                                        :disabled="!connectionText(proxy)"
                                        @click="copyText(connectionText(proxy))"
                                    >
                                        <Clipboard class="h-3.5 w-3.5" /> Copy
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-8 items-center gap-1 rounded-[5px] border border-blue-200 bg-blue-50 px-2 text-xs font-semibold text-blue-700 disabled:opacity-40"
                                        :disabled="proxy.status !== 'active' || actingId === proxy.id || isProxyBusy(proxy.id)"
                                        @click="proxy.proxy_type === 'rotating' ? fetchRotatingProxy(proxy) : changeProxy(proxy)"
                                    >
                                        <LoaderCircle
                                            v-if="proxy.proxy_type === 'rotating' && actingId === proxy.id"
                                            class="h-3.5 w-3.5 animate-spin"
                                        />
                                        <RefreshCcw v-else-if="proxy.proxy_type === 'rotating'" class="h-3.5 w-3.5" />
                                        <Repeat2 v-else class="h-3.5 w-3.5" />
                                        {{ proxy.proxy_type === 'rotating' ? 'Lấy proxy' : 'Đổi' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-8 items-center gap-1 rounded-[5px] border border-amber-200 bg-amber-50 px-2 text-xs font-semibold text-amber-700 disabled:opacity-40"
                                        :disabled="!['active', 'expired'].includes(proxy.status) || actingId === proxy.id || isProxyBusy(proxy.id)"
                                        @click="renewProxy(proxy)"
                                    >
                                        <RotateCcw class="h-3.5 w-3.5" /> Gia hạn
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-900 text-xs uppercase text-white">
                        <tr>
                            <th class="px-3 py-3">Mã đơn</th>
                            <th class="px-3 py-3">Sản phẩm</th>
                            <th class="px-3 py-3">Loại</th>
                            <th class="px-3 py-3">SL / Ngày</th>
                            <th class="px-3 py-3">Tổng tiền</th>
                            <th class="px-3 py-3">Trạng thái</th>
                            <th class="px-3 py-3">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="orders.length === 0">
                            <td colspan="7" class="px-4 py-14 text-center text-slate-500">Chưa có đơn hàng phù hợp.</td>
                        </tr>
                        <tr
                            v-for="order in orders"
                            :key="order.id"
                            :class="['failed', 'refunded'].includes(order.status) ? 'bg-rose-50/70' : 'hover:bg-slate-50'"
                        >
                            <td class="px-3 py-3">
                                <p class="font-mono text-xs font-semibold text-slate-900">{{ order.order_code }}</p>
                                <p v-if="order.error_message" class="max-w-xs text-xs text-rose-600">{{ order.error_message }}</p>
                            </td>
                            <td class="px-3 py-3 font-semibold text-slate-800">{{ order.product.name }}</td>
                            <td class="px-3 py-3 text-xs font-semibold">{{ typeLabels[order.type] }}</td>
                            <td class="px-3 py-3 text-xs">{{ order.quantity }} / {{ order.duration_days }}</td>
                            <td class="px-3 py-3 font-semibold text-slate-900">{{ money(order.total_amount) }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-[5px] border px-2 py-1 text-xs font-semibold" :class="statusClass(order.status)">
                                    {{ statusLabels[order.status] }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-600">{{ dateTime(order.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer v-if="!loading" class="flex items-center justify-between gap-3 border-t border-slate-200 px-3 py-2 text-xs text-slate-500">
                <span> Tổng {{ isProxyTab ? proxyMeta.total : orderMeta.total }} bản ghi </span>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-[5px] border border-slate-300 px-3 py-1.5 disabled:opacity-40"
                        :disabled="(isProxyTab ? proxyMeta.current_page : orderMeta.current_page) <= 1"
                        @click="changePage((isProxyTab ? proxyMeta.current_page : orderMeta.current_page) - 1)"
                    >
                        Trước
                    </button>
                    <span>
                        {{ isProxyTab ? proxyMeta.current_page : orderMeta.current_page }}/{{
                            isProxyTab ? proxyMeta.last_page : orderMeta.last_page
                        }}
                    </span>
                    <button
                        type="button"
                        class="rounded-[5px] border border-slate-300 px-3 py-1.5 disabled:opacity-40"
                        :disabled="
                            (isProxyTab ? proxyMeta.current_page : orderMeta.current_page) >= (isProxyTab ? proxyMeta.last_page : orderMeta.last_page)
                        "
                        @click="changePage((isProxyTab ? proxyMeta.current_page : orderMeta.current_page) + 1)"
                    >
                        Sau
                    </button>
                </div>
            </footer>
        </section>
    </div>
</template>
