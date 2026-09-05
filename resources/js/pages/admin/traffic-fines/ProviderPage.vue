<script setup lang="ts">
import DataTable from '@/components/shared/DataTable/index.vue';
import Modal from '@/components/shared/Modal/index.vue';
import {
    adminTrafficFineService,
    type AdminProviderStatus,
    type AdminProviderStore,
    type AdminProviderUpdate,
} from '@/services/admin-traffic-fine.service';
import { handleErrorResponse } from '@/utils/response';
import { Pencil, Plus, RefreshCw, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { onMounted, ref } from 'vue';

type ProviderRow = AdminProviderStatus & {
    balance: string | null;
    balance_currency: string;
    balance_loading: boolean;
    balance_error: boolean;
};

type ProviderForm = {
    label: string;
    code: string;
    driver: string;
    enabled: boolean;
    url: string;
    token: string;
    timeout: number;
    connect_timeout: number;
    retry_times: number;
    retry_sleep_ms: number;
    url_configured: boolean;
    credential_configured: boolean;
};

const columns = [
    { accessorKey: 'label', header: 'Nguồn' },
    { accessorKey: 'driver', header: 'Driver' },
    { accessorKey: 'enabled', header: 'Trạng thái' },
    { accessorKey: 'balance', header: 'Số dư' },
    { accessorKey: 'last_error', header: 'Lỗi gần nhất' },
    { accessorKey: 'actions', header: 'Thao tác' },
];

const providers = ref<ProviderRow[]>([]);
const drivers = ref<string[]>([]);
const loading = ref(true);
const saving = ref(false);
const showEditorModal = ref(false);
const editingProvider = ref<ProviderRow | null>(null);

const emptyForm = (): ProviderForm => ({
    label: '',
    code: '',
    driver: drivers.value[0] ?? 'xephatnguoi',
    enabled: false,
    url: '',
    token: '',
    timeout: 10,
    connect_timeout: 3,
    retry_times: 2,
    retry_sleep_ms: 200,
    url_configured: false,
    credential_configured: false,
});

const form = ref<ProviderForm>(emptyForm());

const loadBalance = async (provider: ProviderRow, refresh = false): Promise<void> => {
    if (!provider.credential_configured || !provider.url_configured) {
        provider.balance_error = true;
        return;
    }

    provider.balance_loading = true;
    provider.balance_error = false;

    try {
        const result = await adminTrafficFineService.providerBalance(provider.name, refresh);
        provider.balance = result.balance;
        provider.balance_currency = result.currency;
    } catch {
        provider.balance_error = true;
    } finally {
        provider.balance_loading = false;
    }
};

const load = async (): Promise<void> => {
    loading.value = true;

    try {
        const overview = await adminTrafficFineService.provider();
        drivers.value = overview.drivers;
        providers.value = overview.providers.map((provider) => ({
            ...provider,
            balance: null,
            balance_currency: 'VND',
            balance_loading: false,
            balance_error: false,
        }));
        void Promise.allSettled(providers.value.map((provider) => loadBalance(provider)));
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = (): void => {
    editingProvider.value = null;
    form.value = emptyForm();
    showEditorModal.value = true;
};

const openEditModal = (provider: ProviderRow): void => {
    editingProvider.value = provider;
    form.value = {
        label: provider.label,
        code: provider.name,
        driver: provider.driver,
        enabled: provider.enabled,
        url: '',
        token: '',
        timeout: provider.timeout,
        connect_timeout: provider.connect_timeout,
        retry_times: provider.retry_times,
        retry_sleep_ms: provider.retry_sleep_ms,
        url_configured: provider.url_configured,
        credential_configured: provider.credential_configured,
    };
    showEditorModal.value = true;
};

const saveProvider = async (): Promise<void> => {
    saving.value = true;

    try {
        if (editingProvider.value) {
            const payload: AdminProviderUpdate = {
                label: form.value.label.trim(),
                enabled: form.value.enabled,
                url: form.value.url.trim(),
                timeout: form.value.timeout,
                connect_timeout: form.value.connect_timeout,
                retry_times: form.value.retry_times,
                retry_sleep_ms: form.value.retry_sleep_ms,
                ...(form.value.token.trim() ? { token: form.value.token.trim() } : {}),
            };
            await adminTrafficFineService.updateProvider(editingProvider.value.name, payload);
        } else {
            const payload: AdminProviderStore = {
                label: form.value.label.trim(),
                code: form.value.code.trim(),
                driver: form.value.driver,
                enabled: form.value.enabled,
                url: form.value.url.trim(),
                token: form.value.token.trim(),
                timeout: form.value.timeout,
                connect_timeout: form.value.connect_timeout,
                retry_times: form.value.retry_times,
                retry_sleep_ms: form.value.retry_sleep_ms,
            };
            await adminTrafficFineService.createProvider(payload);
        }

        showEditorModal.value = false;
        await load();
        void Swal.fire('Thành công', editingProvider.value ? 'Đã cập nhật nguồn dữ liệu.' : 'Đã thêm nguồn dữ liệu.', 'success');
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};

const toggleProvider = async (provider: ProviderRow): Promise<void> => {
    try {
        await adminTrafficFineService.updateProvider(provider.name, {
            enabled: !provider.enabled,
            url: '',
            timeout: provider.timeout,
            connect_timeout: provider.connect_timeout,
            retry_times: provider.retry_times,
            retry_sleep_ms: provider.retry_sleep_ms,
        });
        await load();
    } catch (error) {
        handleErrorResponse(error);
    }
};

const deleteProvider = async (provider: ProviderRow): Promise<void> => {
    const confirmation = await Swal.fire({
        icon: 'warning',
        title: 'Xoá nguồn dữ liệu?',
        text: provider.enabled ? 'Nguồn đang hoạt động sẽ bị tắt trước khi xoá.' : `Bạn sắp xoá ${provider.label}.`,
        showCancelButton: true,
        confirmButtonText: 'Xoá nguồn',
        cancelButtonText: 'Huỷ',
        confirmButtonColor: '#dc2626',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    try {
        await adminTrafficFineService.deleteProvider(provider.name);
        await load();
        void Swal.fire('Đã xoá', 'Nguồn dữ liệu đã được xoá.', 'success');
    } catch (error) {
        handleErrorResponse(error);
    }
};

const formatBalance = (balance: string | null, currency: string): string => {
    if (balance === null || Number.isNaN(Number(balance))) {
        return '—';
    }

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: currency || 'VND',
        maximumFractionDigits: 2,
    }).format(Number(balance));
};

const goToPage = async (): Promise<void> => undefined;

onMounted(load);
</script>

<template>
    <div class="grid gap-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-bold text-sky-700">Provider</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Nguồn dữ liệu</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Quản lý các nguồn tra cứu. Hệ thống chỉ cho phép một provider hoạt động tại một thời điểm.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700"
                @click="openCreateModal"
            >
                <Plus class="h-4 w-4" /> Thêm nguồn
            </button>
        </header>

        <DataTable
            :data="providers"
            :columns="columns"
            :loading="loading"
            :current-page="1"
            :total-pages="1"
            :go-to-page="goToPage"
            class-custom="w-full min-w-[980px]"
        >
            <template #label="{ row }">
                <div>
                    <p class="font-bold text-slate-900">{{ row.label }}</p>
                    <p class="font-mono text-xs text-slate-500">{{ row.name }}</p>
                </div>
            </template>

            <template #driver="{ row }">
                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ row.driver }}</span>
            </template>

            <template #enabled="{ row }">
                <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-bold transition"
                    :class="row.enabled ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    @click.stop="toggleProvider(row)"
                >
                    {{ row.enabled ? 'Đang sử dụng' : 'Đang tắt' }}
                </button>
            </template>

            <template #balance="{ row }">
                <div class="flex items-center gap-2">
                    <span v-if="row.balance_loading" class="text-xs text-slate-400">Đang tải...</span>
                    <span v-else-if="row.balance_error" class="text-xs font-semibold text-rose-600">Không lấy được</span>
                    <span v-else class="font-bold tabular-nums text-slate-900">{{ formatBalance(row.balance, row.balance_currency) }}</span>
                    <button
                        type="button"
                        class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 disabled:opacity-50"
                        title="Làm mới số dư"
                        :disabled="row.balance_loading"
                        @click.stop="loadBalance(row, true)"
                    >
                        <RefreshCw class="h-3.5 w-3.5" :class="row.balance_loading ? 'animate-spin' : ''" />
                    </button>
                </div>
            </template>

            <template #last_error="{ row }">
                <span class="text-xs text-slate-600">{{ row.last_error ? new Date(row.last_error).toLocaleString('vi-VN') : 'Chưa ghi nhận' }}</span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50"
                        title="Sửa"
                        @click.stop="openEditModal(row)"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                    <button
                        v-if="row.deletable"
                        type="button"
                        class="rounded-lg border border-rose-200 p-2 text-rose-600 hover:bg-rose-50"
                        title="Xoá"
                        @click.stop="deleteProvider(row)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </DataTable>

        <Modal v-model="showEditorModal" panel-class="max-w-3xl">
            <template #header>
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-xl font-black text-slate-950">{{ editingProvider ? 'Sửa nguồn dữ liệu' : 'Thêm nguồn dữ liệu' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Token được mã hoá và không hiển thị lại sau khi lưu.</p>
                </div>
            </template>

            <form id="provider-editor-form" class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="saveProvider">
                <label class="grid gap-1.5">
                    <span class="text-sm font-semibold text-slate-700">Tên hiển thị</span>
                    <input
                        v-model="form.label"
                        required
                        maxlength="120"
                        :disabled="editingProvider !== null && !editingProvider.deletable"
                        class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm disabled:bg-slate-100"
                    />
                </label>
                <label class="grid gap-1.5">
                    <span class="text-sm font-semibold text-slate-700">Mã nguồn</span>
                    <input
                        v-model="form.code"
                        required
                        maxlength="64"
                        pattern="[a-z0-9][a-z0-9_-]*"
                        :disabled="editingProvider !== null"
                        class="min-h-11 rounded-lg border border-slate-300 px-3 font-mono text-sm disabled:bg-slate-100"
                        placeholder="xephatnguoi_phu"
                    />
                </label>
                <label class="grid gap-1.5">
                    <span class="text-sm font-semibold text-slate-700">Driver</span>
                    <select
                        v-model="form.driver"
                        :disabled="editingProvider !== null"
                        class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm disabled:bg-slate-100"
                    >
                        <option v-for="driver in drivers" :key="driver" :value="driver">{{ driver }}</option>
                    </select>
                </label>
                <label
                    class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700"
                >
                    Bật nguồn sau khi lưu
                    <input v-model="form.enabled" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-emerald-600" />
                </label>
                <label class="grid gap-1.5 sm:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">API URL</span>
                    <input
                        v-model="form.url"
                        type="url"
                        :required="!form.url_configured"
                        class="min-h-11 rounded-lg border border-slate-300 px-3 font-mono text-sm"
                        :placeholder="form.url_configured ? 'Để trống nếu không đổi API URL' : 'https://api.xephatnguoi.com/v1/search'"
                    />
                </label>
                <label class="grid gap-1.5 sm:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Bearer token</span>
                    <input
                        v-model="form.token"
                        type="password"
                        autocomplete="new-password"
                        :required="!form.credential_configured"
                        class="min-h-11 rounded-lg border border-slate-300 px-3 font-mono text-sm"
                        :placeholder="form.credential_configured ? 'Để trống nếu không đổi token' : 'Nhập token provider'"
                    />
                </label>
                <label class="grid gap-1.5">
                    <span class="text-sm font-semibold text-slate-700">Timeout (giây)</span>
                    <input
                        v-model.number="form.timeout"
                        type="number"
                        min="1"
                        max="15"
                        required
                        class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm"
                    />
                </label>
                <label class="grid gap-1.5">
                    <span class="text-sm font-semibold text-slate-700">Connect timeout (giây)</span>
                    <input
                        v-model.number="form.connect_timeout"
                        type="number"
                        min="1"
                        max="5"
                        required
                        class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm"
                    />
                </label>
                <label class="grid gap-1.5">
                    <span class="text-sm font-semibold text-slate-700">Số lần thử</span>
                    <input
                        v-model.number="form.retry_times"
                        type="number"
                        min="1"
                        max="2"
                        required
                        class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm"
                    />
                </label>
                <label class="grid gap-1.5">
                    <span class="text-sm font-semibold text-slate-700">Nghỉ giữa lần thử (ms)</span>
                    <input
                        v-model.number="form.retry_sleep_ms"
                        type="number"
                        min="0"
                        max="2000"
                        required
                        class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm"
                    />
                </label>
            </form>

            <template #footer>
                <div class="w-full border-t border-slate-200 px-5 py-4">
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="min-h-10 rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700"
                            @click="showEditorModal = false"
                        >
                            Huỷ
                        </button>
                        <button
                            form="provider-editor-form"
                            type="submit"
                            :disabled="saving"
                            class="min-h-10 rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white disabled:opacity-60"
                        >
                            {{ saving ? 'Đang lưu...' : editingProvider ? 'Lưu thay đổi' : 'Thêm nguồn' }}
                        </button>
                    </div>
                </div>
            </template>
        </Modal>
    </div>
</template>
