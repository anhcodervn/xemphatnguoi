<script setup lang="ts">
import Editor from '@/components/shared/Editor/index.vue';
import { adminMonitoringPlanService } from '@/services/admin-monitoring-plan.service';
import type { MonitoringPlan, MonitoringPlanPayload } from '@/types/monitoring-plan.type';
import { richTextToPlainText, sanitizeRichText } from '@/utils/rich-text';
import { Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { onMounted, reactive, ref } from 'vue';

const plans = ref<MonitoringPlan[]>([]);
const loading = ref(true);
const saving = ref(false);
const editingId = ref<number | null>(null);
const showForm = ref(false);
const emptyForm = (): MonitoringPlanPayload => ({
    name: '',
    description: '',
    is_custom: false,
    vehicle_limit: 5,
    price: 0,
    unit_price: null,
    min_vehicle_count: 20,
    duration_days: 30,
    is_active: true,
    sort_order: 0,
});
const form = reactive<MonitoringPlanPayload>(emptyForm());
const money = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 });

const load = async (): Promise<void> => {
    loading.value = true;
    try {
        plans.value = await adminMonitoringPlanService.index();
    } catch {
        void Swal.fire({ icon: 'error', title: 'Không thể tải dữ liệu', text: 'Không thể tải cấu hình gói theo dõi.' });
    } finally {
        loading.value = false;
    }
};

const openCreate = (): void => {
    Object.assign(form, emptyForm());
    editingId.value = null;
    showForm.value = true;
};

const openEdit = (plan: MonitoringPlan): void => {
    Object.assign(form, {
        name: plan.name,
        description: plan.description ?? '',
        is_custom: plan.is_custom,
        vehicle_limit: plan.vehicle_limit,
        price: plan.price === null ? null : Number(plan.price),
        unit_price: plan.unit_price === null ? null : Number(plan.unit_price),
        min_vehicle_count: plan.min_vehicle_count,
        duration_days: plan.duration_days,
        is_active: Boolean(plan.is_active),
        sort_order: plan.sort_order ?? 0,
    });
    editingId.value = plan.id;
    showForm.value = true;
};

const save = async (): Promise<void> => {
    saving.value = true;
    try {
        if (editingId.value) await adminMonitoringPlanService.update(editingId.value, { ...form });
        else await adminMonitoringPlanService.create({ ...form });
        const successMessage = editingId.value ? 'Đã cập nhật gói theo dõi.' : 'Đã tạo gói theo dõi.';
        showForm.value = false;
        await load();
        void Swal.fire({ icon: 'success', title: 'Thành công', text: successMessage });
    } catch (error) {
        const message = (error as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Không thể lưu gói theo dõi.';
        void Swal.fire({ icon: 'error', title: 'Không thể lưu gói', text: message });
    } finally {
        saving.value = false;
    }
};

const remove = async (plan: MonitoringPlan): Promise<void> => {
    const confirmation = await Swal.fire({
        icon: 'warning',
        title: 'Xóa gói dịch vụ?',
        text: `Bạn có chắc muốn xóa gói “${plan.name}”?`,
        showCancelButton: true,
        confirmButtonText: 'Xóa gói',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#dc2626',
        focusCancel: true,
    });

    if (!confirmation.isConfirmed) return;

    try {
        await adminMonitoringPlanService.remove(plan.id);
        await load();
        void Swal.fire({ icon: 'success', title: 'Đã xóa', text: 'Đã xóa gói theo dõi.' });
    } catch (error) {
        const message = (error as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Không thể xóa gói.';
        void Swal.fire({ icon: 'error', title: 'Không thể xóa gói', text: message });
    }
};

onMounted(load);
</script>

<template>
    <div class="grid gap-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-sky-700">Monitoring</p>
                <h1 class="mt-1 text-2xl font-black text-slate-950">Gói theo dõi xe</h1>
                <p class="mt-2 text-sm text-slate-500">Gói cố định giới hạn sẵn số xe; gói tùy chỉnh tính theo đơn giá mỗi xe, tối thiểu 20 xe.</p>
            </div>
            <button
                type="button"
                class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-sky-700 px-4 text-sm font-bold text-white"
                @click="openCreate"
            >
                <Plus class="h-4 w-4" /> Thêm gói
            </button>
        </header>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="loading" class="p-10 text-center text-sm text-slate-500">Đang tải...</div>
            <div v-else-if="!plans.length" class="p-10 text-center text-sm text-slate-500">Chưa có gói theo dõi. Hãy tạo gói đầu tiên.</div>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="p-4">Tên gói</th>
                            <th class="p-4">Loại</th>
                            <th class="p-4">Hạn mức / giá</th>
                            <th class="p-4">Thời hạn</th>
                            <th class="p-4">Trạng thái</th>
                            <th class="p-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="plan in plans" :key="plan.id">
                            <td class="p-4">
                                <strong class="text-slate-900">{{ plan.name }}</strong>
                                <p class="mt-1 max-w-xs truncate text-xs text-slate-500">
                                    {{ richTextToPlainText(sanitizeRichText(plan.description)) }}
                                </p>
                            </td>
                            <td class="p-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold"
                                    :class="plan.is_custom ? 'bg-violet-100 text-violet-700' : 'bg-sky-100 text-sky-700'"
                                    >{{ plan.is_custom ? 'Tùy chỉnh' : 'Cố định' }}</span
                                >
                            </td>
                            <td class="p-4 text-slate-700">
                                <template v-if="plan.is_custom"
                                    >Từ {{ plan.min_vehicle_count }} xe<br /><span class="text-xs text-slate-500"
                                        >{{ money.format(Number(plan.unit_price)) }}/xe</span
                                    ></template
                                ><template v-else
                                    >{{ plan.vehicle_limit }} xe<br /><span class="text-xs text-slate-500">{{
                                        money.format(Number(plan.price))
                                    }}</span></template
                                >
                            </td>
                            <td class="p-4">{{ plan.duration_days }} ngày</td>
                            <td class="p-4">
                                <span :class="plan.is_active ? 'text-emerald-700' : 'text-slate-400'">{{
                                    plan.is_active ? 'Đang bán' : 'Đã tắt'
                                }}</span>
                            </td>
                            <td class="p-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        aria-label="Sửa gói"
                                        class="app-focus rounded-lg border border-slate-200 p-2 text-slate-600"
                                        @click="openEdit(plan)"
                                    >
                                        <Pencil class="h-4 w-4" /></button
                                    ><button
                                        type="button"
                                        aria-label="Xóa gói"
                                        class="app-focus rounded-lg border border-red-200 p-2 text-red-600"
                                        @click="remove(plan)"
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

        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 z-[100] grid place-items-center overflow-y-auto bg-slate-950/50 p-4">
                <form class="my-6 w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl" @submit.prevent="save">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-black text-slate-950">{{ editingId ? 'Sửa gói' : 'Thêm gói' }}</h2>
                        <button type="button" aria-label="Đóng" class="p-2" @click="showForm = false"><X class="h-5 w-5" /></button>
                    </div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-bold text-slate-700 sm:col-span-2"
                            >Tên gói<input
                                v-model="form.name"
                                required
                                maxlength="100"
                                class="app-focus mt-2 min-h-11 w-full rounded-lg border border-slate-300 px-3 font-normal"
                        /></label>
                        <div class="text-sm font-bold text-slate-700 sm:col-span-2">
                            <span>Mô tả định dạng</span>
                            <p class="mt-1 text-xs font-normal text-slate-500">Có thể dùng tiêu đề, màu sắc, danh sách, liên kết và hình ảnh.</p>
                            <div class="mt-2 overflow-hidden rounded-lg border border-slate-200">
                                <Editor v-model:value="form.description" format="html" :height="320" :debounce="0" />
                            </div>
                        </div>
                        <label class="flex items-center gap-3 text-sm font-bold text-slate-700 sm:col-span-2"
                            ><input v-model="form.is_custom" type="checkbox" class="h-5 w-5 rounded border-slate-300" /> Cho phép người dùng tự chọn
                            số xe</label
                        >
                        <template v-if="form.is_custom">
                            <label class="text-sm font-bold text-slate-700"
                                >Đơn giá một xe<input
                                    v-model.number="form.unit_price"
                                    required
                                    type="number"
                                    min="0"
                                    class="app-focus mt-2 min-h-11 w-full rounded-lg border border-slate-300 px-3 font-normal"
                            /></label>
                            <label class="text-sm font-bold text-slate-700"
                                >Số xe tối thiểu<input
                                    v-model.number="form.min_vehicle_count"
                                    required
                                    type="number"
                                    min="20"
                                    class="app-focus mt-2 min-h-11 w-full rounded-lg border border-slate-300 px-3 font-normal"
                            /></label>
                        </template>
                        <template v-else>
                            <label class="text-sm font-bold text-slate-700"
                                >Giới hạn xe<input
                                    v-model.number="form.vehicle_limit"
                                    required
                                    type="number"
                                    min="1"
                                    class="app-focus mt-2 min-h-11 w-full rounded-lg border border-slate-300 px-3 font-normal"
                            /></label>
                            <label class="text-sm font-bold text-slate-700"
                                >Giá gói<input
                                    v-model.number="form.price"
                                    required
                                    type="number"
                                    min="0"
                                    class="app-focus mt-2 min-h-11 w-full rounded-lg border border-slate-300 px-3 font-normal"
                            /></label>
                        </template>
                        <label class="text-sm font-bold text-slate-700"
                            >Thời hạn (ngày)<input
                                v-model.number="form.duration_days"
                                required
                                type="number"
                                min="1"
                                max="3650"
                                class="app-focus mt-2 min-h-11 w-full rounded-lg border border-slate-300 px-3 font-normal"
                        /></label>
                        <label class="text-sm font-bold text-slate-700"
                            >Thứ tự<input
                                v-model.number="form.sort_order"
                                type="number"
                                min="0"
                                class="app-focus mt-2 min-h-11 w-full rounded-lg border border-slate-300 px-3 font-normal"
                        /></label>
                        <label class="flex items-center gap-3 text-sm font-bold text-slate-700 sm:col-span-2"
                            ><input v-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300" /> Đang mở bán</label
                        >
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-bold" @click="showForm = false">
                            Hủy</button
                        ><button
                            type="submit"
                            :disabled="saving"
                            class="min-h-11 rounded-lg bg-sky-700 px-5 text-sm font-bold text-white disabled:opacity-50"
                        >
                            {{ saving ? 'Đang lưu...' : 'Lưu gói' }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>
    </div>
</template>
