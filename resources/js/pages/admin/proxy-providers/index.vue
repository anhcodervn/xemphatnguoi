<script setup lang="ts">
import Modal from '@/components/shared/Modal/index.vue';
import { adminProxyProviderService } from '@/services/admin-proxy-provider.service';
import { CircleGauge, KeyRound, LoaderCircle, Pencil, Plus, Power, Server, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';

type Provider = {
    id: number;
    name: string;
    code: string | null;
    order_method: 'automatic' | 'manual';
    balance: string | null;
    has_credentials: boolean;
    is_active: boolean;
    priority: number;
};

type ProviderDetail = Provider & {
    credentials: Record<string, unknown>;
};

const providers = ref<Provider[]>([]);
const loading = ref(true);
const saving = ref(false);
const editorOpen = ref(false);
const editingId = ref<number | null>(null);
const openingId = ref<number | null>(null);

const emptyForm = () => ({
    name: '',
    code: '',
    order_method: 'automatic' as Provider['order_method'],
    credentials_json: '',
    clear_credentials: false,
    priority: 100,
    is_active: true,
});

const form = reactive(emptyForm());
const isEditing = computed(() => editingId.value !== null);
const activeCount = computed(() => providers.value.filter((provider) => provider.is_active).length);
const methodLabel = (method: Provider['order_method']) => (method === 'manual' ? 'Thủ công' : 'Tự động');

const load = async () => {
    loading.value = true;
    try {
        providers.value = (await adminProxyProviderService.list({ per_page: 100 })).providers.data;
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    editingId.value = null;
    Object.assign(form, emptyForm());
    editorOpen.value = true;
};

const openEdit = async (provider: Provider) => {
    openingId.value = provider.id;

    try {
        const detail = (await adminProxyProviderService.show(provider.id)).provider as ProviderDetail;
        editingId.value = detail.id;
        Object.assign(form, {
            name: detail.name,
            code: detail.code || '',
            order_method: detail.order_method,
            credentials_json: Object.keys(detail.credentials).length > 0 ? JSON.stringify(detail.credentials, null, 2) : '',
            clear_credentials: false,
            priority: detail.priority || 100,
            is_active: detail.is_active,
        });
        editorOpen.value = true;
    } catch (error: any) {
        await Swal.fire('Không thể tải cấu hình', error?.response?.data?.message || 'Vui lòng thử lại.', 'error');
    } finally {
        openingId.value = null;
    }
};

const save = async () => {
    let credentials: Record<string, unknown> | null = null;

    if (form.credentials_json.trim()) {
        try {
            const parsed = JSON.parse(form.credentials_json);
            if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
                throw new Error('Credentials must be a JSON object.');
            }
            credentials = parsed as Record<string, unknown>;
        } catch {
            await Swal.fire('JSON không hợp lệ', 'Dữ liệu nguồn phải là một JSON object, ví dụ { "base_url": "...", "api_key": "..." }.', 'warning');
            return;
        }
    }

    saving.value = true;
    const payload: Record<string, unknown> = {
        name: form.name.trim(),
        code: form.code.trim().toLowerCase(),
        order_method: form.order_method,
        priority: form.priority,
        is_active: form.is_active,
    };

    if (form.clear_credentials) {
        payload.credentials = {};
    } else if (credentials) {
        payload.credentials = credentials;
    }

    try {
        if (editingId.value) {
            await adminProxyProviderService.update(editingId.value, payload);
        } else {
            await adminProxyProviderService.create(payload);
        }

        editorOpen.value = false;
        await load();
        await Swal.fire(
            'Đã lưu',
            isEditing.value ? 'Cấu hình nhà cung cấp đã được cập nhật.' : 'Nhà cung cấp đã sẵn sàng để gán sản phẩm.',
            'success',
        );
    } catch (error: any) {
        await Swal.fire('Không thể lưu', error?.response?.data?.message || 'Kiểm tra lại dữ liệu.', 'error');
    } finally {
        saving.value = false;
    }
};

const applyCredentialsTemplate = (type: 'single' | 'pair') => {
    if (form.code.trim().toLowerCase() === 'proxy_vn') {
        form.credentials_json = JSON.stringify({ base_url: 'https://proxy.vn/apiv2', api_key: 'YOUR_API_KEY' }, null, 2);
        return;
    }

    form.credentials_json =
        type === 'single'
            ? JSON.stringify({ base_url: 'https://provider.example/api', api_key: 'YOUR_API_KEY' }, null, 2)
            : JSON.stringify(
                  {
                      base_url: 'https://provider.example/api',
                      api_key: 'YOUR_API_KEY',
                      secret_key: 'YOUR_SECRET_KEY',
                      purchase_path: '/orders',
                      status_path: '/orders/{order_id}',
                  },
                  null,
                  2,
              );
};

const toggle = async (provider: Provider) => {
    await adminProxyProviderService.update(provider.id, { is_active: !provider.is_active });
    await load();
};

const remove = async (provider: Provider) => {
    const result = await Swal.fire({
        title: `Xóa ${provider.name}?`,
        text: 'Chỉ nên xóa nguồn chưa có dữ liệu liên quan. Với nguồn đang sử dụng, hãy chuyển sang trạng thái tạm dừng.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Xóa nhà cung cấp',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#dc2626',
    });
    if (!result.isConfirmed) return;

    try {
        await adminProxyProviderService.delete(provider.id);
        await load();
    } catch (error: any) {
        await Swal.fire('Không thể xóa', error?.response?.data?.message || 'Nhà cung cấp đang có dữ liệu liên quan.', 'error');
    }
};

onMounted(load);
</script>

<template>
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-blue-600">Nguồn cung ứng</p>
                <h1 class="mt-1 text-3xl font-black text-slate-950">Nhà cung cấp proxy</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Quản lý kết nối API, mức ưu tiên và trạng thái nguồn. Credential được mã hóa và không hiển thị lại.
                </p>
            </div>
            <button
                type="button"
                class="proxy-focus inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-blue-200 hover:bg-blue-700"
                @click="openCreate"
            >
                <Plus class="h-4 w-4" /> Thêm nhà cung cấp
            </button>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <article class="proxy-panel p-5">
                <p class="text-sm text-slate-500">Tổng nguồn</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ providers.length }}</p>
            </article>
            <article class="proxy-panel p-5">
                <p class="text-sm text-slate-500">Đang hoạt động</p>
                <p class="mt-2 text-2xl font-black text-emerald-600">{{ activeCount }}</p>
            </article>
            <article class="proxy-panel p-5">
                <p class="text-sm text-slate-500">Đã có credential</p>
                <p class="mt-2 text-2xl font-black text-blue-600">{{ providers.filter((item) => item.has_credentials).length }}</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div v-if="loading" class="py-20 text-center text-slate-500">
                <LoaderCircle class="mx-auto mb-3 h-6 w-6 animate-spin text-blue-600" />Đang tải nhà cung cấp...
            </div>
            <div v-else-if="providers.length === 0" class="py-20 text-center text-slate-500">
                <Server class="mx-auto mb-3 h-8 w-8 text-slate-300" />Chưa có nhà cung cấp nào.
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Nhà cung cấp</th>
                            <th class="px-5 py-4">Phương thức</th>
                            <th class="px-5 py-4">Credential</th>
                            <th class="px-5 py-4">Ưu tiên</th>
                            <th class="px-5 py-4">Trạng thái</th>
                            <th class="px-5 py-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="provider in providers" :key="provider.id" class="transition hover:bg-blue-50/30">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ provider.name }}</p>
                                <p class="mt-1 font-mono text-xs text-slate-400">{{ provider.code || 'Chưa có code' }}</p>
                            </td>
                            <td class="max-w-xs px-5 py-4">
                                <p class="truncate text-slate-600">
                                    {{ methodLabel(provider.order_method) }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="provider.has_credentials ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700'"
                                    ><KeyRound class="h-3.5 w-3.5" />{{ provider.has_credentials ? 'Đã cấu hình' : 'Chưa có' }}</span
                                >
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 text-slate-600"
                                    ><CircleGauge class="h-4 w-4" />{{ provider.priority }}</span
                                >
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="provider.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                    >{{ provider.is_active ? 'Hoạt động' : 'Tạm dừng' }}</span
                                >
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-1">
                                    <button
                                        type="button"
                                        class="proxy-focus rounded-lg p-2 text-blue-600 hover:bg-blue-50"
                                        title="Chỉnh sửa"
                                        :disabled="openingId === provider.id"
                                        @click="openEdit(provider)"
                                    >
                                        <LoaderCircle v-if="openingId === provider.id" class="h-4 w-4 animate-spin" /><Pencil
                                            v-else
                                            class="h-4 w-4"
                                        /></button
                                    ><button
                                        type="button"
                                        class="proxy-focus rounded-lg p-2 hover:bg-slate-100"
                                        :class="provider.is_active ? 'text-emerald-600' : 'text-slate-400'"
                                        :title="provider.is_active ? 'Tạm dừng' : 'Kích hoạt'"
                                        @click="toggle(provider)"
                                    >
                                        <Power class="h-4 w-4" /></button
                                    ><button
                                        type="button"
                                        class="proxy-focus rounded-lg p-2 text-rose-600 hover:bg-rose-50"
                                        title="Xóa"
                                        @click="remove(provider)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <Modal v-model="editorOpen" panel-class="max-w-3xl">
            <template #header>
                <div class="border-b border-slate-200 px-6 py-5 pr-14">
                    <h2 class="text-xl font-black text-slate-950">{{ isEditing ? 'Chỉnh sửa nhà cung cấp' : 'Thêm nhà cung cấp' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Tách riêng thông tin nhận diện và cấu hình kỹ thuật để dễ kiểm soát.</p>
                </div>
            </template>

            <form class="space-y-6 p-6" @submit.prevent="save">
                <fieldset class="space-y-4">
                    <legend class="text-sm font-bold uppercase tracking-wide text-blue-700">Thông tin chung</legend>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2"
                            ><span class="text-sm font-semibold text-slate-700">Tên nhà cung cấp <b class="text-rose-500">*</b></span
                            ><input
                                v-model="form.name"
                                required
                                class="proxy-focus min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Ví dụ: Proxy.vn"
                        /></label>
                        <label class="space-y-2"
                            ><span class="text-sm font-semibold text-slate-700">Code <b class="text-rose-500">*</b></span
                            ><input
                                v-model="form.code"
                                required
                                maxlength="100"
                                pattern="[a-z0-9_-]+"
                                class="proxy-focus min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 font-mono shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Ví dụ: proxy_vn"
                            /><span class="block text-xs text-slate-400">Mã viết thường, không dấu và không khoảng trắng.</span></label
                        >
                        <label class="space-y-2"
                            ><span class="text-sm font-semibold text-slate-700">Phương thức <b class="text-rose-500">*</b></span
                            ><select
                                v-model="form.order_method"
                                required
                                class="proxy-focus min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                                <option value="automatic">Tự động</option>
                                <option value="manual">Thủ công</option>
                            </select></label
                        >
                        <label class="space-y-2"
                            ><span class="text-sm font-semibold text-slate-700">Mức ưu tiên</span
                            ><input
                                v-model.number="form.priority"
                                type="number"
                                min="1"
                                class="proxy-focus min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            /><span class="block text-xs text-slate-400">Số nhỏ hơn được ưu tiên trước.</span></label
                        >
                        <label
                            class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border-2 border-slate-300 bg-slate-50 px-4 py-4 shadow-sm transition hover:border-blue-400 hover:bg-blue-50/50"
                            ><span
                                ><b class="block text-sm text-slate-800">Cho phép sử dụng</b
                                ><small class="text-slate-500">Tắt để ngừng nhận đơn mới.</small></span
                            ><input
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-6 w-6 cursor-pointer rounded-md border-2 border-slate-400 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        /></label>
                    </div>
                </fieldset>

                <fieldset v-if="form.order_method === 'automatic'" class="space-y-4 rounded-2xl border-2 border-blue-200 bg-blue-50/40 p-5">
                    <legend class="px-2 text-sm font-bold uppercase tracking-wide text-blue-700">Dữ liệu kết nối JSON</legend>
                    <div class="space-y-2">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-sm font-semibold text-slate-700">JSON kết nối hiện tại</span>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="proxy-focus rounded-lg border-2 border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:border-blue-400 hover:bg-blue-50"
                                    @click="applyCredentialsTemplate('single')"
                                >
                                    Mẫu API key
                                </button>
                                <button
                                    type="button"
                                    class="proxy-focus rounded-lg border-2 border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:border-blue-400 hover:bg-blue-50"
                                    @click="applyCredentialsTemplate('pair')"
                                >
                                    Mẫu API + secret
                                </button>
                            </div>
                        </div>
                        <textarea
                            v-model="form.credentials_json"
                            rows="7"
                            spellcheck="false"
                            autocomplete="off"
                            class="proxy-focus w-full rounded-xl border-2 border-slate-400 bg-slate-950 px-4 py-3 font-mono text-sm leading-6 text-cyan-100 shadow-sm transition placeholder:text-slate-500 hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            placeholder='{&#10;  "base_url": "https://provider.example/api",&#10;  "api_key": "YOUR_API_KEY",&#10;  "secret_key": "YOUR_SECRET_KEY"&#10;}'
                        ></textarea>
                        <p class="text-xs leading-5 text-slate-500">
                            URL, key và các tham số kết nối được mã hóa khi lưu. Dữ liệu chỉ được tải lại trong màn hình chỉnh sửa dành cho admin.
                        </p>
                        <label
                            v-if="isEditing && providers.find((item) => item.id === editingId)?.has_credentials"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:border-rose-400"
                        >
                            <input
                                v-model="form.clear_credentials"
                                type="checkbox"
                                class="h-5 w-5 cursor-pointer rounded border-2 border-rose-300 text-rose-600 focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                            />
                            Xóa credential đang lưu khi cập nhật
                        </label>
                    </div>
                </fieldset>

                <div class="sticky bottom-0 flex flex-col-reverse gap-3 border-t border-slate-200 bg-white pt-5 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="proxy-focus rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        :disabled="saving"
                        @click="editorOpen = false"
                    >
                        Hủy</button
                    ><button
                        type="submit"
                        class="proxy-focus inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        :disabled="saving"
                    >
                        <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />{{ isEditing ? 'Lưu thay đổi' : 'Tạo nhà cung cấp' }}
                    </button>
                </div>
            </form>
        </Modal>
    </div>
</template>
