<script setup lang="ts">
import { adminCaptchaSourceService } from '@/services/admin-captcha-source.service';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';

type SourceRow = {
    id: number;
    name: string;
    driver: string;
    api_base_url: string | null;
    balance: string | null;
    credentials: Record<string, unknown> | null;
    settings: Record<string, unknown> | null;
    priority: number;
    is_active: boolean;
    services_count?: number | null;
};

type SourceForm = {
    name: string;
    driver: string;
    api_base_url: string;
    priority: number;
    is_active: boolean;
    credentials_json: string;
    settings_json: string;
};

const rows = ref<SourceRow[]>([]);
const loading = ref(true);
const saving = ref(false);
const loadingEdit = ref(false);
const loadingEditId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const editingId = ref<number | null>(null);
const modalOpen = ref(false);

const emptyForm = (): SourceForm => ({
    name: '',
    driver: 'manual',
    api_base_url: '',
    priority: 100,
    is_active: true,
    credentials_json: '{\n  "api_key": ""\n}',
    settings_json: '{}',
});

const form = ref<SourceForm>(emptyForm());
const isEditing = computed(() => editingId.value !== null);

const parseJsonField = (value: string, label: string): Record<string, unknown> => {
    const trimmed = value.trim();

    if (trimmed === '') {
        return {};
    }

    try {
        const parsed = JSON.parse(trimmed);

        if (parsed === null || Array.isArray(parsed) || typeof parsed !== 'object') {
            throw new Error(`${label} phải là object JSON.`);
        }

        return parsed as Record<string, unknown>;
    } catch {
        throw new Error(`${label} không đúng định dạng JSON.`);
    }
};

const resetForm = (): void => {
    form.value = emptyForm();
    editingId.value = null;
};

const openCreateModal = (): void => {
    resetForm();
    modalOpen.value = true;
};

const openEditModal = async (source: SourceRow): Promise<void> => {
    try {
        loadingEdit.value = true;
        loadingEditId.value = source.id;

        const response = await adminCaptchaSourceService.show(source.id);
        const detail = (response.source ?? source) as SourceRow;

        editingId.value = detail.id;
        form.value = {
            name: String(detail.name ?? ''),
            driver: String(detail.driver ?? 'manual'),
            api_base_url: String(detail.api_base_url ?? ''),
            priority: Number(detail.priority ?? 100),
            is_active: Boolean(detail.is_active),
            credentials_json: JSON.stringify(detail.credentials ?? {}, null, 2),
            settings_json: JSON.stringify(detail.settings ?? {}, null, 2),
        };
        modalOpen.value = true;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingEdit.value = false;
        loadingEditId.value = null;
    }
};

const closeModal = (): void => {
    if (saving.value) {
        return;
    }

    modalOpen.value = false;
    resetForm();
};

const loadSources = async (): Promise<void> => {
    try {
        loading.value = true;
        const response = await adminCaptchaSourceService.list({ per_page: 100 });
        rows.value = (response.sources?.data ?? []) as SourceRow[];
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const submitForm = async (): Promise<void> => {
    try {
        saving.value = true;

        const payload = {
            name: form.value.name.trim(),
            driver: form.value.driver.trim(),
            api_base_url: form.value.api_base_url.trim() || null,
            priority: form.value.priority,
            is_active: form.value.is_active,
            credentials: parseJsonField(form.value.credentials_json, 'Credentials JSON'),
            settings: parseJsonField(form.value.settings_json, 'Settings JSON'),
        };

        const response = isEditing.value && editingId.value !== null
            ? await adminCaptchaSourceService.update(editingId.value, payload)
            : await adminCaptchaSourceService.create(payload);

        handleSuccessResponse(response, isEditing.value ? 'Đã cập nhật nguồn captcha.' : 'Đã tạo nguồn captcha.');
        modalOpen.value = false;
        resetForm();
        await loadSources();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};

const deleteSource = async (row: SourceRow): Promise<void> => {
    const result = await Swal.fire({
        title: 'Xóa nguồn captcha?',
        text: `Nguồn "${row.name}" sẽ bị xóa khỏi hệ thống.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Xóa nguồn',
        cancelButtonText: 'Hủy',
        reverseButtons: true,
        focusCancel: true,
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        deletingId.value = row.id;
        const response = await adminCaptchaSourceService.delete(row.id);
        handleSuccessResponse(response, 'Đã xóa nguồn captcha.');

        if (editingId.value === row.id) {
            resetForm();
            modalOpen.value = false;
        }

        await loadSources();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        deletingId.value = null;
    }
};

onMounted(async () => {
    await loadSources();
});
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[12px] border border-emerald-100 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-[-0.04em] text-slate-950">Nguồn captcha</h1>
                    <p class="mt-2 text-sm text-slate-500">
                        Quản lý cụm xử lý nội bộ, URL API, token JSON và số dư hiện có của từng nguồn.
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-[10px] bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500"
                    @click="openCreateModal"
                >
                    Thêm nguồn
                </button>
            </div>
        </section>

        <section class="overflow-hidden rounded-[12px] border border-emerald-100 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px]">
                    <thead class="bg-emerald-50 text-left text-sm font-semibold text-emerald-700">
                        <tr>
                            <th class="px-4 py-3">Nguồn</th>
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3">API URL</th>
                            <th class="px-4 py-3">Balance</th>
                            <th class="px-4 py-3">Credentials</th>
                            <th class="px-4 py-3">Priority</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-emerald-100 text-sm text-slate-700">
                        <tr v-if="loading">
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">Đang tải nguồn captcha...</td>
                        </tr>

                        <tr v-else-if="rows.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">Chưa có nguồn captcha nào.</td>
                        </tr>

                        <tr v-for="row in rows" :key="row.id">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ row.name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ row.services_count ?? 0 }} dịch vụ đang dùng</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ row.driver }}</td>
                            <td class="px-4 py-3">
                                <p class="max-w-[280px] break-all">{{ row.api_base_url || '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    {{ row.balance ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <pre class="max-w-[260px] overflow-hidden whitespace-pre-wrap break-words rounded-[10px] bg-emerald-50 px-3 py-2 text-[11px] leading-5 text-slate-600">{{ JSON.stringify(row.credentials ?? {}, null, 2) }}</pre>
                            </td>
                            <td class="px-4 py-3">{{ row.priority }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="row.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'">
                                    {{ row.is_active ? 'active' : 'inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-[10px] border border-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                                        :disabled="loadingEdit"
                                        @click="openEditModal(row)"
                                    >
                                        {{ loadingEditId === row.id ? 'Đang tải...' : 'Sửa' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-[10px] bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-500 disabled:opacity-60"
                                        :disabled="deletingId === row.id"
                                        @click="deleteSource(row)"
                                    >
                                        {{ deletingId === row.id ? 'Đang xóa...' : 'Xóa' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <teleport to="body">
            <div v-if="modalOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/55 p-4">
                <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-[14px] border border-emerald-100 bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-emerald-100 px-5 py-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">{{ isEditing ? 'Chỉnh sửa nguồn captcha' : 'Thêm nguồn captcha' }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Nhập URL API, credentials JSON và cấu hình phụ trợ cho từng nguồn.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-[10px] border border-emerald-100 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
                            :disabled="saving || loadingEdit"
                            @click="closeModal"
                        >
                            Đóng
                        </button>
                    </div>

                    <div class="space-y-5 px-5 py-5">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Tên nguồn</span>
                                <input v-model="form.name" type="text" class="w-full rounded-[10px] border border-slate-200 px-3 py-2.5 text-sm" placeholder="Ví dụ: AutoCaptcha Pro 1" />
                            </label>

                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Driver</span>
                                <input v-model="form.driver" type="text" class="w-full rounded-[10px] border border-slate-200 px-3 py-2.5 text-sm" placeholder="Ví dụ: autocaptchapro" />
                            </label>

                            <label class="space-y-1 lg:col-span-2">
                                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">API URL</span>
                                <input
                                    v-model="form.api_base_url"
                                    type="text"
                                    class="w-full rounded-[10px] border border-slate-200 px-3 py-2.5 text-sm"
                                    placeholder="Ví dụ: https://autocaptcha.pro/apiv3/process"
                                />
                            </label>

                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Priority</span>
                                <input v-model.number="form.priority" type="number" min="1" class="w-full rounded-[10px] border border-slate-200 px-3 py-2.5 text-sm" />
                            </label>

                            <label class="flex items-center justify-between rounded-[10px] border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">Đang hoạt động</span>
                                    <span class="mt-1 block text-xs text-slate-500">Tắt nếu chưa muốn hệ thống dùng nguồn này.</span>
                                </span>
                                <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                            </label>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Credentials JSON</span>
                                <textarea
                                    v-model="form.credentials_json"
                                    rows="10"
                                    class="w-full rounded-[10px] border border-slate-200 px-3 py-2.5 font-mono text-sm"
                                    placeholder='{
  "api_key": "your-key",
  "client_id": "demo",
  "client_secret": "secret"
}'
                                />
                            </label>

                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Settings JSON</span>
                                <textarea
                                    v-model="form.settings_json"
                                    rows="10"
                                    class="w-full rounded-[10px] border border-slate-200 px-3 py-2.5 font-mono text-sm"
                                    placeholder='{
  "timeout": 30
}'
                                />
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-emerald-100 px-5 py-4">
                        <button
                            type="button"
                            class="rounded-[10px] border border-emerald-100 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
                            @click="closeModal"
                        >
                            Hủy
                        </button>
                        <button
                            type="button"
                            class="rounded-[10px] bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-60"
                            :disabled="saving || loadingEdit"
                            @click="submitForm"
                        >
                            {{ saving ? 'Đang lưu...' : isEditing ? 'Lưu thay đổi' : 'Thêm nguồn' }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>
