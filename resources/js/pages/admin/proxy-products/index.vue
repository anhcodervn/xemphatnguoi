<script setup lang="ts">
import Editor from '@/components/shared/Editor/index.vue';
import Modal from '@/components/shared/Modal/index.vue';
import { adminProxyProductService } from '@/services/admin-proxy-product.service';
import { Boxes, Calculator, LoaderCircle, PackagePlus, Pencil, Power } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';

type Provider = { id: number; name: string };
type Category = { id: number; name: string };
type ProxyProtocol = 'http' | 'https' | 'socks4' | 'socks5';
type ProxyType = 'static' | 'rotating';
type Product = {
    id: number;
    proxy_category_id: number;
    code: string;
    name: string;
    country_code: string | null;
    protocol: string;
    supported_protocols: ProxyProtocol[];
    description: string | null;
    provider_product_code: string | null;
    default_provider_id: number | null;
    sort_order: number;
    base_price: string;
    selling_price: string;
    max_quantity: number;
    is_active: boolean;
    settings?: {
        proxy_type?: ProxyType;
        rotating_carrier?: string;
        rotating_province?: string;
        rotating_whitelist?: string;
    };
    provider?: Provider | null;
    category?: Category | null;
};

const products = ref<Product[]>([]);
const providers = ref<Provider[]>([]);
const categories = ref<Category[]>([]);
const loading = ref(true);
const saving = ref(false);
const editorOpen = ref(false);
const editingId = ref<number | null>(null);

const emptyForm = () => ({
    proxy_category_id: null as number | null,
    code: '',
    name: '',
    country_code: 'VN',
    supported_protocols: ['http'] as ProxyProtocol[],
    description: '',
    provider_product_code: '',
    proxy_type: 'static' as ProxyType,
    rotating_carrier: 'random',
    rotating_province: '0',
    rotating_whitelist: '',
    default_provider_id: null as number | null,
    base_price: 0,
    selling_price: 0,
    max_quantity: 100,
    sort_order: 0,
    is_active: true,
});

const form = reactive(emptyForm());
const protocolOptions: Array<{ value: ProxyProtocol; label: string }> = [
    { value: 'http', label: 'HTTP' },
    { value: 'https', label: 'HTTPS' },
    { value: 'socks4', label: 'SOCKS4' },
    { value: 'socks5', label: 'SOCKS5' },
];
const isEditing = computed(() => editingId.value !== null);
const activeCount = computed(() => products.value.filter((product) => product.is_active).length);
const formControlClass =
    'proxy-focus min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100';

const load = async () => {
    loading.value = true;
    try {
        const data = await adminProxyProductService.list({ per_page: 100 });
        products.value = data.products.data;
        providers.value = data.providers;
        categories.value = data.categories;
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    editingId.value = null;
    Object.assign(form, emptyForm());
    editorOpen.value = true;
};

const openEdit = (product: Product) => {
    editingId.value = product.id;
    Object.assign(form, {
        proxy_category_id: product.proxy_category_id,
        code: product.code,
        name: product.name,
        country_code: product.country_code || '',
        supported_protocols: product.supported_protocols?.length ? [...product.supported_protocols] : [product.protocol as ProxyProtocol],
        description: product.description || '',
        provider_product_code: product.provider_product_code || '',
        proxy_type: product.settings?.proxy_type || 'static',
        rotating_carrier: product.settings?.rotating_carrier || 'random',
        rotating_province: product.settings?.rotating_province || '0',
        rotating_whitelist: product.settings?.rotating_whitelist || '',
        default_provider_id: product.default_provider_id,
        base_price: Number(product.base_price),
        selling_price: Number(product.selling_price),
        max_quantity: product.max_quantity,
        sort_order: product.sort_order || 0,
        is_active: product.is_active,
    });
    editorOpen.value = true;
};

const save = async () => {
    if (form.supported_protocols.length === 0) {
        await Swal.fire('Chưa chọn giao thức', 'Hãy chọn ít nhất một giao thức được sản phẩm hỗ trợ.', 'warning');
        return;
    }

    if (Number(form.selling_price) < Number(form.base_price)) {
        await Swal.fire('Giá chưa hợp lệ', 'Giá bán mỗi ngày không được thấp hơn giá nhập mỗi ngày.', 'warning');
        return;
    }

    saving.value = true;
    const { proxy_type, rotating_carrier, rotating_province, rotating_whitelist, ...productFields } = form;
    const payload = {
        ...productFields,
        country_code: form.country_code.trim() ? form.country_code.trim().toUpperCase() : null,
        provider_product_code: form.provider_product_code.trim() || null,
        base_price: Number(form.base_price),
        selling_price: Number(form.selling_price),
        max_quantity: Number(form.max_quantity),
        settings: {
            proxy_type,
            rotating_carrier: rotating_carrier.trim() || 'random',
            rotating_province: rotating_province.trim() || '0',
            rotating_whitelist: rotating_whitelist.trim(),
        },
    };
    try {
        if (editingId.value) await adminProxyProductService.update(editingId.value, payload);
        else await adminProxyProductService.create(payload);
        editorOpen.value = false;
        await load();
        await Swal.fire('Đã lưu', isEditing.value ? 'Sản phẩm proxy đã được cập nhật.' : 'Sản phẩm proxy đã được tạo.', 'success');
    } catch (error: any) {
        await Swal.fire('Không thể lưu', error?.response?.data?.message || 'Kiểm tra lại dữ liệu.', 'error');
    } finally {
        saving.value = false;
    }
};

const toggle = async (product: Product) => {
    await adminProxyProductService.update(product.id, { is_active: !product.is_active });
    await load();
};

const money = (value: string | number) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' đ';
const protocolLabel = (product: Product) =>
    (product.supported_protocols?.length ? product.supported_protocols : [product.protocol]).join(', ').toUpperCase();

onMounted(load);
</script>

<template>
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-blue-600">Tầng sản phẩm bán</p>
                <h1 class="mt-1 text-3xl font-black text-slate-950">Sản phẩm proxy</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Danh sách sản phẩm bán cho khách. Mỗi sản phẩm thuộc trực tiếp một chuyên mục và liên kết với nguồn cung ứng.
                </p>
            </div>
            <button
                type="button"
                class="proxy-focus inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-blue-200 hover:bg-blue-700"
                @click="openCreate"
            >
                <PackagePlus class="h-4 w-4" />Thêm sản phẩm proxy
            </button>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <article class="proxy-panel p-5">
                <p class="text-sm text-slate-500">Tổng sản phẩm</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ products.length }}</p>
            </article>
            <article class="proxy-panel p-5">
                <p class="text-sm text-slate-500">Đang mở bán</p>
                <p class="mt-2 text-2xl font-black text-emerald-600">{{ activeCount }}</p>
            </article>
            <article class="proxy-panel p-5">
                <p class="text-sm text-slate-500">Chuyên mục khả dụng</p>
                <p class="mt-2 text-2xl font-black text-blue-600">{{ categories.length }}</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div v-if="loading" class="py-20 text-center text-slate-500">
                <LoaderCircle class="mx-auto mb-3 h-6 w-6 animate-spin text-blue-600" />Đang tải sản phẩm proxy...
            </div>
            <div v-else-if="products.length === 0" class="py-20 text-center text-slate-500">
                <Boxes class="mx-auto mb-3 h-8 w-8 text-slate-300" />Chưa có sản phẩm proxy nào.
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Sản phẩm proxy</th>
                            <th class="px-5 py-4">Phân loại</th>
                            <th class="px-5 py-4">Nguồn</th>
                            <th class="px-5 py-4">Thông số</th>
                            <th class="px-5 py-4">Giá bán</th>
                            <th class="px-5 py-4">Trạng thái</th>
                            <th class="px-5 py-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="product in products" :key="product.id" class="transition hover:bg-blue-50/30">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ product.name }}</p>
                                <p class="mt-1 font-mono text-xs text-slate-400">{{ product.code }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p>{{ product.category?.name || 'Chưa phân loại' }}</p>
                            </td>
                            <td class="px-5 py-4">{{ product.provider?.name || '--' }}</td>
                            <td class="px-5 py-4">
                                <p>{{ product.country_code || 'Global' }} · {{ protocolLabel(product) }}</p>
                                <p class="mt-1 text-xs text-slate-400">Tối đa {{ product.max_quantity }} proxy mỗi đơn</p>
                            </td>
                            <td class="px-5 py-4 font-semibold text-blue-700">{{ money(product.selling_price) }} / proxy / ngày</td>
                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="product.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                    >{{ product.is_active ? 'Mở bán' : 'Tạm ẩn' }}</span
                                >
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-1">
                                    <button
                                        type="button"
                                        class="proxy-focus rounded-lg p-2 text-blue-600 hover:bg-blue-50"
                                        title="Chỉnh sửa"
                                        @click="openEdit(product)"
                                    >
                                        <Pencil class="h-4 w-4" /></button
                                    ><button
                                        type="button"
                                        class="proxy-focus rounded-lg p-2 hover:bg-slate-100"
                                        :class="product.is_active ? 'text-emerald-600' : 'text-slate-400'"
                                        :title="product.is_active ? 'Tạm ẩn' : 'Mở bán'"
                                        @click="toggle(product)"
                                    >
                                        <Power class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <Modal v-model="editorOpen" panel-class="max-w-5xl">
            <template #header
                ><div class="border-b border-slate-200 px-6 py-5 pr-14">
                    <h2 class="text-xl font-black text-slate-950">{{ isEditing ? 'Chỉnh sửa sản phẩm proxy' : 'Thêm sản phẩm proxy' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Các trường được chia theo nghiệp vụ để giảm nhầm lẫn khi cấu hình.</p>
                </div></template
            >
            <form class="space-y-6 p-6" @submit.prevent="save">
                <fieldset class="space-y-4">
                    <legend class="text-sm font-bold uppercase tracking-wide text-blue-700">1. Phân loại và nguồn</legend>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2"
                            ><span class="text-sm font-semibold text-slate-700">Chuyên mục proxy <b class="text-rose-500">*</b></span
                            ><select v-model="form.proxy_category_id" required :class="formControlClass">
                                <option :value="null" disabled>Chọn chuyên mục proxy</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select></label
                        ><label class="space-y-2"
                            ><span class="text-sm font-semibold text-slate-700">Nhà cung cấp <b class="text-rose-500">*</b></span
                            ><select v-model="form.default_provider_id" required :class="formControlClass">
                                <option :value="null" disabled>Chọn nhà cung cấp</option>
                                <option v-for="provider in providers" :key="provider.id" :value="provider.id">{{ provider.name }}</option>
                            </select></label
                        >
                    </div>
                </fieldset>

                <fieldset class="space-y-4 rounded-2xl border-2 border-slate-300 p-5 shadow-sm">
                    <legend class="px-2 text-sm font-bold uppercase tracking-wide text-blue-700">2. Nhận diện sản phẩm</legend>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2 xl:col-span-2"
                            ><span class="text-sm font-semibold text-slate-700">Tên sản phẩm <b class="text-rose-500">*</b></span
                            ><input v-model="form.name" required :class="formControlClass" placeholder="Proxy dân cư Việt Nam" /></label
                        ><label class="space-y-2"
                            ><span class="text-sm font-semibold text-slate-700">Mã nội bộ <b class="text-rose-500">*</b></span
                            ><input v-model="form.code" required class="font-mono" :class="formControlClass" placeholder="RES-VN" /></label
                        ><label class="space-y-2"
                            ><span class="text-sm font-semibold text-slate-700">Quốc gia</span
                            ><input v-model="form.country_code" maxlength="2" class="uppercase" :class="formControlClass" placeholder="VN"
                        /></label>
                        <div class="space-y-2 xl:col-span-3">
                            <span class="text-sm font-semibold text-slate-700">Giao thức hỗ trợ <b class="text-rose-500">*</b></span>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                <label v-for="option in protocolOptions" :key="option.value" class="cursor-pointer">
                                    <input v-model="form.supported_protocols" type="checkbox" :value="option.value" class="sr-only" />
                                    <span
                                        class="proxy-focus flex min-h-12 items-center justify-center rounded-[5px] border-2 px-4 text-sm font-semibold transition"
                                        :class="
                                            form.supported_protocols.includes(option.value)
                                                ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm'
                                                : 'border-slate-300 bg-white text-slate-500 hover:border-blue-300'
                                        "
                                    >
                                        {{ option.label }}
                                    </span>
                                </label>
                            </div>
                        </div>
                        <label class="space-y-2"
                            ><span class="text-sm font-semibold text-slate-700">Thứ tự hiển thị</span
                            ><input v-model.number="form.sort_order" type="number" min="0" :class="formControlClass"
                        /></label>
                        <div class="space-y-2 md:col-span-2 xl:col-span-4">
                            <span class="text-sm font-semibold text-slate-700">Mô tả</span
                            ><Editor v-model:value="form.description" format="html" :height="280" :debounce="0" />
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-5 rounded-2xl border-2 border-blue-200 bg-blue-50/40 p-5 shadow-sm">
                    <div>
                        <legend class="text-sm font-bold uppercase tracking-wide text-blue-700">3. Đơn giá theo ngày</legend>
                        <p class="mt-1 text-sm text-slate-500">
                            Cấu hình giá cho một proxy trong một ngày. Khách hàng tự nhập số ngày thuê khi đặt hàng.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700">Giá nhập / proxy / ngày</span>
                            <input v-model.number="form.base_price" required type="number" min="0" step="0.0001" :class="formControlClass" />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700">Giá bán / proxy / ngày</span>
                            <input v-model.number="form.selling_price" required type="number" min="0" step="0.0001" :class="formControlClass" />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700">Số lượng tối đa mỗi đơn</span>
                            <input v-model.number="form.max_quantity" required type="number" min="1" max="10000" :class="formControlClass" />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700">SKU bên nguồn</span>
                            <input
                                v-model="form.provider_product_code"
                                class="font-mono"
                                :class="formControlClass"
                                placeholder="provider-product-code"
                            />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700">Loại cấp phát</span>
                            <select v-model="form.proxy_type" :class="formControlClass">
                                <option value="static">Proxy tĩnh</option>
                                <option value="rotating">Proxy xoay</option>
                            </select>
                        </label>
                    </div>

                    <div v-if="form.proxy_type === 'rotating'" class="grid gap-4 rounded-[5px] border border-cyan-200 bg-cyan-50 p-4 md:grid-cols-3">
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700">Nhà mạng mặc định</span>
                            <input v-model="form.rotating_carrier" :class="formControlClass" placeholder="random, viettel, vnpt, fpt" />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700">Mã tỉnh thành</span>
                            <input v-model="form.rotating_province" :class="formControlClass" placeholder="0" />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700">IP whitelist</span>
                            <input v-model="form.rotating_whitelist" :class="formControlClass" placeholder="Để trống nếu không dùng" />
                        </label>
                        <p class="text-xs text-cyan-800 md:col-span-3">
                            Chu kỳ API tự chọn theo số ngày: chia hết 30 dùng tháng, chia hết 7 dùng tuần, còn lại dùng ngày.
                        </p>
                    </div>

                    <div class="flex items-start gap-3 rounded-2xl border border-blue-200 bg-white p-4 text-sm text-slate-600">
                        <Calculator class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" />
                        <p>Tổng tiền đơn hàng = <b>số lượng proxy × số ngày thuê × giá bán mỗi ngày</b>.</p>
                    </div>
                </fieldset>

                <label
                    class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border-2 border-slate-300 bg-slate-50 px-4 py-4 shadow-sm transition hover:border-blue-400 hover:bg-blue-50/50"
                    ><span
                        ><b class="block text-sm text-slate-800">Mở bán sản phẩm này</b
                        ><small class="text-slate-500">Tắt để ẩn khỏi catalog khách hàng.</small></span
                    ><input
                        v-model="form.is_active"
                        type="checkbox"
                        class="h-6 w-6 cursor-pointer rounded-md border-2 border-slate-400 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                /></label>

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
                        <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />{{ isEditing ? 'Lưu thay đổi' : 'Tạo sản phẩm proxy' }}
                    </button>
                </div>
            </form>
        </Modal>
    </div>
</template>
