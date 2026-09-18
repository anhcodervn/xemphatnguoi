<script setup lang="ts">
import { adminTrafficFineService, type AdminApiBilling } from '@/services/admin-traffic-fine.service';
import formatCash from '@/utils/helpers/formatCash';
import { CircleDollarSign, Power, ReceiptText, Save, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const billing = ref<AdminApiBilling | null>(null);
const v1Price = ref(20);
const v2Price = ref(150);
const v1Cost = ref(0);
const v2Cost = ref(0);
const v1Description = ref('');
const v2Description = ref('');
const v1Enabled = ref(true);
const v2Enabled = ref(true);
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const chartMaximum = computed(() => Math.max(1, ...(billing.value?.chart.map((item) => item.requests) ?? [1])));

const load = async (): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';
    try {
        billing.value = await adminTrafficFineService.billing();
        v1Price.value = billing.value.api_request_price;
        v2Price.value = billing.value.api_v2_request_price;
        v1Cost.value = billing.value.api_request_cost;
        v2Cost.value = billing.value.api_v2_request_cost;
        v1Description.value = billing.value.api_v1_description;
        v2Description.value = billing.value.api_v2_description;
        v1Enabled.value = billing.value.api_v1_enabled;
        v2Enabled.value = billing.value.api_v2_enabled;
    } catch {
        errorMessage.value = 'Không thể tải cấu hình tính phí API.';
    } finally {
        loading.value = false;
    }
};

const save = async (): Promise<void> => {
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    try {
        billing.value = await adminTrafficFineService.updateBilling(
            v1Price.value,
            v2Price.value,
            v1Cost.value,
            v2Cost.value,
            v1Description.value,
            v2Description.value,
            v1Enabled.value,
            v2Enabled.value,
        );
        v1Price.value = billing.value.api_request_price;
        v2Price.value = billing.value.api_v2_request_price;
        v1Cost.value = billing.value.api_request_cost;
        v2Cost.value = billing.value.api_v2_request_cost;
        v1Description.value = billing.value.api_v1_description;
        v2Description.value = billing.value.api_v2_description;
        v1Enabled.value = billing.value.api_v1_enabled;
        v2Enabled.value = billing.value.api_v2_enabled;
        successMessage.value = 'Đã cập nhật trạng thái vận hành, giá và mô tả API.';
    } catch {
        errorMessage.value = 'Không thể cập nhật giá. Vui lòng kiểm tra dữ liệu.';
    } finally {
        saving.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="grid gap-6">
        <header>
            <p class="text-sm font-bold text-sky-700">Cấu hình giá API</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Bảng giá API</h1>
            <p class="mt-2 text-sm text-slate-500">
                Giá bán và giá cost được chụp tại thời điểm trừ ví nên lợi nhuận lịch sử không đổi khi cập nhật giá mới.
            </p>
        </header>
        <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ errorMessage }}</div>
        <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ successMessage }}</div>
        <div v-if="loading" class="h-64 animate-pulse rounded-xl bg-slate-200" />
        <template v-else-if="billing">
            <section class="grid gap-5">
                <form class="rounded-xl border border-slate-200 bg-white p-6" @submit.prevent="save">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                                <CircleDollarSign class="h-6 w-6" />
                            </span>
                            <div>
                                <h2 class="font-bold text-slate-950">Cấu hình API hiện tại</h2>
                                <p class="mt-1 text-xs text-slate-500">Điều khiển trạng thái vận hành và giá từng phiên bản.</p>
                            </div>
                        </div>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-slate-950 px-5 text-sm font-bold text-white disabled:opacity-60"
                        >
                            <Save class="h-4 w-4" />{{ saving ? 'Đang lưu...' : 'Lưu cấu hình' }}
                        </button>
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-sky-200 bg-sky-50/60 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-black uppercase text-sky-700">API v1</span>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="v1Enabled"
                                    aria-label="Bật hoặc bảo trì cổng tra cứu API v1"
                                    class="app-focus inline-flex min-h-10 items-center gap-2 rounded-full px-3 text-xs font-black transition"
                                    :class="v1Enabled ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'"
                                    @click="v1Enabled = !v1Enabled"
                                >
                                    <Power class="h-4 w-4" />{{ v1Enabled ? 'Đang bật' : 'Bảo trì' }}
                                </button>
                            </div>
                            <label for="api-request-price" class="mt-3 block text-sm font-semibold text-slate-700">VND / request thành công</label>
                            <span class="relative mt-2 block">
                                <input
                                    id="api-request-price"
                                    v-model.number="v1Price"
                                    type="number"
                                    min="1"
                                    max="1000000"
                                    step="1"
                                    required
                                    class="app-focus h-12 w-full rounded-lg border border-slate-300 bg-white px-4 pr-14 text-lg font-black"
                                /><span class="absolute inset-y-0 right-4 flex items-center text-sm font-bold text-slate-500">đ</span>
                            </span>
                            <label for="api-request-cost" class="mt-4 block text-sm font-semibold text-slate-700">Giá cost / request</label>
                            <span class="relative mt-2 block">
                                <input
                                    id="api-request-cost"
                                    v-model.number="v1Cost"
                                    type="number"
                                    min="0"
                                    max="1000000"
                                    step="1"
                                    required
                                    class="app-focus h-12 w-full rounded-lg border border-slate-300 bg-white px-4 pr-14 text-lg font-black"
                                /><span class="absolute inset-y-0 right-4 flex items-center text-sm font-bold text-slate-500">đ</span>
                            </span>
                            <span class="mt-2 block text-xs font-semibold text-emerald-700">
                                Lãi dự kiến: {{ formatCash(v1Price - v1Cost) }}đ / request
                            </span>
                            <label for="api-v1-description" class="mt-4 block text-sm font-semibold text-slate-700">Mô tả công khai</label>
                            <textarea
                                id="api-v1-description"
                                v-model.trim="v1Description"
                                required
                                maxlength="300"
                                rows="3"
                                class="app-focus mt-2 w-full resize-none rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal leading-6"
                            />
                        </div>
                        <div class="rounded-xl border border-violet-200 bg-violet-50/60 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-black uppercase text-violet-700"
                                    >API v2</span
                                >
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="v2Enabled"
                                    aria-label="Bật hoặc bảo trì cổng tra cứu API v2"
                                    class="app-focus inline-flex min-h-10 items-center gap-2 rounded-full px-3 text-xs font-black transition"
                                    :class="v2Enabled ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'"
                                    @click="v2Enabled = !v2Enabled"
                                >
                                    <Power class="h-4 w-4" />{{ v2Enabled ? 'Đang bật' : 'Bảo trì' }}
                                </button>
                            </div>
                            <label for="api-v2-request-price" class="mt-3 block text-sm font-semibold text-slate-700">VND / request thành công</label>
                            <span class="relative mt-2 block">
                                <input
                                    id="api-v2-request-price"
                                    v-model.number="v2Price"
                                    type="number"
                                    min="1"
                                    max="1000000"
                                    step="1"
                                    required
                                    class="app-focus h-12 w-full rounded-lg border border-slate-300 bg-white px-4 pr-14 text-lg font-black"
                                /><span class="absolute inset-y-0 right-4 flex items-center text-sm font-bold text-slate-500">đ</span>
                            </span>
                            <label for="api-v2-request-cost" class="mt-4 block text-sm font-semibold text-slate-700">Giá cost / request</label>
                            <span class="relative mt-2 block">
                                <input
                                    id="api-v2-request-cost"
                                    v-model.number="v2Cost"
                                    type="number"
                                    min="0"
                                    max="1000000"
                                    step="1"
                                    required
                                    class="app-focus h-12 w-full rounded-lg border border-slate-300 bg-white px-4 pr-14 text-lg font-black"
                                /><span class="absolute inset-y-0 right-4 flex items-center text-sm font-bold text-slate-500">đ</span>
                            </span>
                            <span class="mt-2 block text-xs font-semibold text-emerald-700">
                                Lãi dự kiến: {{ formatCash(v2Price - v2Cost) }}đ / request
                            </span>
                            <label for="api-v2-description" class="mt-4 block text-sm font-semibold text-slate-700">Mô tả công khai</label>
                            <textarea
                                id="api-v2-description"
                                v-model.trim="v2Description"
                                required
                                maxlength="300"
                                rows="3"
                                class="app-focus mt-2 w-full resize-none rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal leading-6"
                            />
                        </div>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-500">
                        Tắt một phiên bản sẽ trả mã 503 cho toàn bộ cổng tra cứu tương ứng trước khi gọi nhà cung cấp hoặc trừ tiền. Mô tả được hiển
                        thị cho khách hàng. Không nhập tên nguồn, URL hoặc credential.
                    </p>
                </form>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in [
                            { label: 'Request hôm nay', value: billing.summary.requests_today.toLocaleString('vi-VN'), icon: ReceiptText },
                            { label: 'Doanh thu hôm nay', value: `${formatCash(Number(billing.summary.amount_today))}đ`, icon: TrendingUp },
                            { label: 'Chi phí hôm nay', value: `${formatCash(Number(billing.summary.cost_today))}đ`, icon: TrendingDown },
                            { label: 'Lợi nhuận hôm nay', value: `${formatCash(Number(billing.summary.profit_today))}đ`, icon: CircleDollarSign },
                        ]"
                        :key="card.label"
                        class="rounded-xl border border-slate-200 bg-white p-5"
                    >
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700"
                            ><component :is="card.icon" class="h-5 w-5"
                        /></span>
                        <p class="mt-5 text-sm text-slate-500">{{ card.label }}</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ card.value }}</p>
                    </article>
                </div>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <div>
                    <h2 class="font-bold text-slate-950">30 ngày gần nhất</h2>
                    <p class="mt-1 text-sm text-slate-500">Số request đã tính phí theo ngày.</p>
                </div>
                <div class="mt-6 flex h-60 items-end gap-1 border-b border-slate-200">
                    <div v-for="item in billing.chart" :key="item.date" class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-[10px] font-bold text-slate-500 opacity-0 group-hover:opacity-100">{{ item.requests }}</span>
                        <div
                            class="w-full max-w-7 rounded-t bg-sky-600"
                            :style="{ height: `${Math.max(3, (item.requests / chartMaximum) * 180)}px` }"
                            :title="`${item.label}: ${item.requests} request · doanh thu ${formatCash(Number(item.amount))}đ · cost ${formatCash(Number(item.cost))}đ · lãi ${formatCash(Number(item.profit))}đ`"
                        />
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>
