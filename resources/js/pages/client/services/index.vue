<script setup lang="ts">
import { clientProxyService, type ProxyCategory, type ProxyProduct, type ProxyProtocol } from '@/services/client-proxy.service';
import { handleErrorResponse } from '@/utils/response';
import { richTextToPlainText, sanitizeRichText } from '@/utils/rich-text';
import { Calculator, FolderTree, LoaderCircle, ShoppingCart } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';

const categories = ref<ProxyCategory[]>([]);
const activeCategoryId = ref<number | null>(null);
const loading = ref(true);
const purchasing = ref<number | null>(null);
const quantities = reactive<Record<number, number>>({});
const durationDays = reactive<Record<number, number>>({});
const selectedProtocols = reactive<Record<number, ProxyProtocol>>({});
const activeCategory = computed(() => categories.value.find((item) => item.id === activeCategoryId.value) || categories.value[0]);
const money = (value: string | number) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' đ';

const load = async () => {
    loading.value = true;
    try {
        const data = await clientProxyService.products();
        categories.value = data.categories;
        activeCategoryId.value = data.categories[0]?.id ?? null;
        data.categories.forEach((category) =>
            category.products.forEach((product) => {
                quantities[product.id] = 1;
                durationDays[product.id] = 1;
                selectedProtocols[product.id] = product.supported_protocols[0] ?? product.protocol;
            }),
        );
    } finally {
        loading.value = false;
    }
};

const totalPrice = (product: ProxyProduct) =>
    Number(product.selling_price || 0) * Number(quantities[product.id] || 1) * Number(durationDays[product.id] || 1);

const purchase = async (product: ProxyProduct) => {
    const quantity = Number(quantities[product.id] || 0);
    const days = Number(durationDays[product.id] || 0);
    const protocol = selectedProtocols[product.id] ?? product.supported_protocols[0] ?? product.protocol;

    if (!Number.isInteger(quantity) || quantity < 1 || quantity > product.max_quantity) {
        await Swal.fire('Số lượng chưa hợp lệ', `Số lượng phải từ 1 đến ${product.max_quantity} proxy.`, 'warning');
        return;
    }

    if (!Number.isInteger(days) || days < 1 || days > 3650) {
        await Swal.fire('Số ngày chưa hợp lệ', 'Số ngày sử dụng phải từ 1 đến 3650.', 'warning');
        return;
    }

    if (!product.supported_protocols.includes(protocol)) {
        await Swal.fire('Giao thức chưa hợp lệ', 'Sản phẩm không hỗ trợ giao thức đã chọn.', 'warning');
        return;
    }

    const confirmation = await Swal.fire({
        title: 'Xác nhận mua proxy?',
        html: `
            <div class="text-sm">
                <b>${product.name}</b><br>
                ${quantity} proxy × ${days} ngày × ${money(product.selling_price)}<br>
                Giao thức: <b>${protocol.toUpperCase()}</b><br>
                Tổng tiền: <b>${money(totalPrice(product))}</b>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Mua ngay',
        cancelButtonText: 'Hủy',
    });
    if (!confirmation.isConfirmed) return;

    purchasing.value = product.id;
    try {
        const data = await clientProxyService.createOrder({
            product_code: product.code,
            quantity,
            duration_days: days,
            protocol,
        });

        if (!data?.order) {
            throw new Error('Phản hồi mua proxy không hợp lệ.');
        }

        await Swal.fire({
            title: 'Mua proxy thành công',
            text: `Đơn ${data.order.order_code} đã thanh toán ${money(data.order.total_amount)} và cấp ${data.proxies.length} proxy.`,
            icon: 'success',
            confirmButtonText: 'Hoàn tất',
        });
    } catch (error) {
        handleErrorResponse(error as never);
    } finally {
        purchasing.value = null;
    }
};

onMounted(load);
</script>

<template>
    <div class="space-y-6">
        <div v-if="loading" class="flex items-center justify-center rounded-3xl border border-slate-200 bg-white py-20 text-slate-500">
            <LoaderCircle class="mr-2 h-5 w-5 animate-spin" /> Đang tải kho proxy...
        </div>
        <div v-else-if="categories.length === 0" class="rounded-3xl border border-dashed border-slate-300 bg-white py-20 text-center text-slate-500">
            Chưa có danh mục và sản phẩm proxy đang mở bán.
        </div>

        <template v-else>
            <nav class="proxy-panel flex gap-3 overflow-x-auto p-3">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    class="proxy-focus inline-flex shrink-0 items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition"
                    :class="
                        activeCategory?.id === category.id
                            ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-md shadow-blue-200'
                            : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'
                    "
                    @click="activeCategoryId = category.id"
                >
                    <FolderTree class="h-4 w-4" />{{ category.name }}
                </button>
            </nav>

            <section v-if="activeCategory" class="space-y-6">
                <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
                    <h2 class="text-2xl font-black text-slate-950">{{ activeCategory.name }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ richTextToPlainText(activeCategory.description, 'Các sản phẩm proxy thuộc chuyên mục này.') }}
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="product in activeCategory.products"
                        :key="product.id"
                        class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-[0_18px_45px_-32px_rgba(37,99,235,0.5)]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-bold uppercase text-blue-700">
                                    {{ product.country_code || 'Global' }}
                                </span>
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold uppercase text-emerald-700">
                                    {{ product.supported_protocols.join(' / ') }}
                                </span>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-400">{{ product.code }}</span>
                        </div>
                        <h4 class="mt-3 text-lg font-bold text-slate-950">{{ product.name }}</h4>
                        <!-- eslint-disable-next-line vue/no-v-html -- sanitized with DOMPurify -->
                        <div
                            v-if="product.description?.trim()"
                            class="product-description mt-1 min-h-10 text-xs leading-5 text-slate-500"
                            v-html="sanitizeRichText(product.description)"
                        ></div>
                        <p v-else class="mt-1 min-h-10 text-xs leading-5 text-slate-500">
                            Chọn số ngày sử dụng và số lượng proxy phù hợp với nhu cầu.
                        </p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="space-y-1">
                                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Ngày sử dụng</span>
                                <input
                                    v-model.number="durationDays[product.id]"
                                    type="number"
                                    min="1"
                                    max="3650"
                                    class="proxy-focus h-10 w-full rounded-[5px] border-2 border-slate-300 bg-white px-3 py-1.5 text-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </label>
                            <label class="space-y-1">
                                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Số lượng proxy</span>
                                <input
                                    v-model.number="quantities[product.id]"
                                    type="number"
                                    min="1"
                                    :max="product.max_quantity"
                                    class="proxy-focus h-10 w-full rounded-[5px] border-2 border-slate-300 bg-white px-3 py-1.5 text-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </label>
                        </div>

                        <label class="mt-3 space-y-1">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Giao thức</span>
                            <select
                                v-model="selectedProtocols[product.id]"
                                class="proxy-focus h-10 w-full rounded-[5px] border-2 border-slate-300 bg-white px-3 py-1.5 text-sm uppercase transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                                <option v-for="protocol in product.supported_protocols" :key="protocol" :value="protocol">
                                    {{ protocol.toUpperCase() }}
                                </option>
                            </select>
                        </label>

                        <div class="mt-4 flex items-end justify-between gap-3 rounded-lg border border-blue-100 bg-blue-50/50 p-3">
                            <div>
                                <p class="text-[11px] text-slate-500">Giá / proxy / ngày</p>
                                <p class="text-lg font-black text-blue-700">{{ money(product.selling_price) }}</p>
                            </div>
                            <p class="text-right text-[11px] text-slate-500">
                                Tổng tiền<br /><b class="text-sm text-slate-900">{{ money(totalPrice(product)) }}</b>
                            </p>
                        </div>
                        <div class="mt-2 flex items-center gap-1.5 text-[11px] text-slate-500">
                            <Calculator class="h-3.5 w-3.5 text-blue-600" /> Số lượng × số ngày × giá/ngày
                        </div>
                        <button
                            type="button"
                            class="proxy-focus mt-3 inline-flex h-10 w-full items-center justify-center gap-2 rounded-[5px] bg-gradient-to-r from-blue-600 to-cyan-500 px-4 text-sm font-semibold text-white shadow-sm transition hover:from-blue-700 hover:to-cyan-600 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="purchasing !== null"
                            @click="purchase(product)"
                        >
                            <LoaderCircle v-if="purchasing === product.id" class="h-4 w-4 animate-spin" />
                            <ShoppingCart v-else class="h-4 w-4" />
                            {{ purchasing === product.id ? 'Đang tạo đơn...' : 'Mua ngay' }}
                        </button>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<style scoped>
.product-description :deep(p),
.product-description :deep(ul),
.product-description :deep(ol),
.product-description :deep(blockquote) {
    margin-bottom: 0.375rem;
}

.product-description :deep(ul),
.product-description :deep(ol) {
    padding-left: 1.25rem;
}

.product-description :deep(ul) {
    list-style: disc;
}

.product-description :deep(ol) {
    list-style: decimal;
}

.product-description :deep(a) {
    color: #2563eb;
    text-decoration: underline;
}

.product-description :deep(h1),
.product-description :deep(h2),
.product-description :deep(h3),
.product-description :deep(h4),
.product-description :deep(strong) {
    font-weight: 700;
    color: #0f172a;
}

.product-description :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
}

.product-description :deep(:last-child) {
    margin-bottom: 0;
}
</style>
