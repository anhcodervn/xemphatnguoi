<script setup lang="ts">
import { adminRechargeMethodService, type RechargeMethodItem } from '@/services/admin-recharge-method.service';
import { handleErrorResponse } from '@/utils/response';
import { ChevronLeft, ChevronRight, CreditCard, Landmark, LoaderCircle, Pencil, Plus, Search, ShieldCheck, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const rows = ref<RechargeMethodItem[]>([]);

const filters = reactive({
    search: '',
    status: '',
    page: 1,
});

const meta = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});

const summary = reactive({
    total: 0,
    active: 0,
    inactive: 0,
});

const tableRange = computed(() => {
    if (meta.total === 0) {
        return '0-0';
    }

    const from = (meta.current_page - 1) * 10 + 1;
    const to = Math.min(meta.current_page * 10, meta.total);

    return `${from}-${to}`;
});

const fetchRechargeMethods = async (page = 1): Promise<void> => {
    try {
        loading.value = true;

        const response = await adminRechargeMethodService.list({
            page,
            search: filters.search || undefined,
            status: filters.status || undefined,
        });

        rows.value = response.methods.data;
        meta.current_page = response.methods.current_page;
        meta.last_page = response.methods.last_page;
        meta.total = response.summary.total;
        summary.total = response.summary.total;
        summary.active = response.summary.active;
        summary.inactive = response.summary.inactive;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const applyFilters = async (): Promise<void> => {
    filters.page = 1;
    await fetchRechargeMethods(1);
};

const resetFilters = async (): Promise<void> => {
    filters.search = '';
    filters.status = '';
    filters.page = 1;
    await fetchRechargeMethods(1);
};

const goToPage = async (page: number): Promise<void> => {
    if (page < 1 || page > meta.last_page || page === meta.current_page) {
        return;
    }

    filters.page = page;
    await fetchRechargeMethods(page);
};

const deleteRechargeMethod = async (id: number): Promise<void> => {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Xóa phương thức nạp?',
        text: 'Thông tin thẻ và liên kết tài khoản phụ sẽ bị gỡ khỏi hệ thống.',
        showCancelButton: true,
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await adminRechargeMethodService.delete(id);
        await Swal.fire('Thành công', response.data.message || 'Đã xóa phương thức nạp.', 'success');
        await fetchRechargeMethods(meta.current_page);
    } catch (error) {
        handleErrorResponse(error);
    }
};

const formatCurrency = (value: number | string): string => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'decimal',
        maximumFractionDigits: 0,
    }).format(Number(value));
};

const statusLabel = (value: boolean): string => {
    return value ? 'Hoạt động' : 'Tạm dừng';
};

onMounted(async () => {
    await fetchRechargeMethods();
});
</script>

<template>
    <div class="space-y-4">
        <section
            class="overflow-hidden rounded-[10px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.08),_transparent_28%),linear-gradient(180deg,_rgba(255,255,255,0.98)_0%,_rgba(255,255,255,0.96)_100%)] px-4 py-4 shadow-[0_12px_30px_rgba(15,23,42,0.05)]"
        >
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#465fff]">Admin workspace</p>
                    <h1 class="mt-1.5 text-[24px] font-black tracking-tight text-slate-950">Thông tin thẻ nạp</h1>
                    <p class="mt-1.5 max-w-2xl text-[13px] leading-5 text-slate-500">
                        Quản lý các tài khoản nhận tiền của hệ thống. Mỗi phương thức là một cấu hình thẻ hoặc tài khoản ngân hàng dùng để người dùng
                        chuyển khoản.
                    </p>
                </div>

                <RouterLink
                    to="/admin/recharge-methods/create"
                    class="inline-flex items-center gap-2 self-start rounded-[8px] bg-[#465fff] px-3.5 py-2 text-sm font-semibold text-white shadow-[0_8px_18px_rgba(70,95,255,0.2)] transition hover:bg-[#3c52e0]"
                >
                    <Plus class="h-4 w-4" />
                    Thêm thẻ nhận tiền
                </RouterLink>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-3">
            <article class="rounded-[10px] border border-slate-200 bg-white px-3.5 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.045)]">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-sky-50 text-sky-600">
                        <CreditCard class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Tổng cấu hình thẻ</p>
                        <p class="mt-0.5 text-[24px] font-black tracking-tight text-slate-950">{{ summary.total }}</p>
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white px-3.5 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.045)]">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-emerald-50 text-emerald-600">
                        <ShieldCheck class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Đang hoạt động</p>
                        <p class="mt-0.5 text-[24px] font-black tracking-tight text-slate-950">{{ summary.active }}</p>
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white px-3.5 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.045)]">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-amber-50 text-amber-600">
                        <Landmark class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Tạm dừng</p>
                        <p class="mt-0.5 text-[24px] font-black tracking-tight text-slate-950">{{ summary.inactive }}</p>
                    </div>
                </div>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-3.5 shadow-[0_10px_28px_rgba(15,23,42,0.045)]">
            <div class="grid gap-2.5 lg:grid-cols-[1.6fr_220px_auto]">
                <label class="flex items-center gap-2.5 rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2">
                    <Search class="h-4 w-4 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Tìm theo mã, tên hiển thị, ngân hàng..."
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 outline-none placeholder:text-slate-400"
                        @keyup.enter="applyFilters"
                    />
                </label>

                <select v-model="filters.status" class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option value="true">Hoạt động</option>
                    <option value="false">Tạm dừng</option>
                </select>

                <div class="flex gap-2.5">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-[8px] border border-[#465fff] px-3.5 py-2 text-sm font-semibold text-[#465fff] transition hover:bg-[#eef2ff]"
                        @click="applyFilters"
                    >
                        Lọc
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-[8px] border border-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        @click="resetFilters"
                    >
                        Đặt lại
                    </button>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-[0_12px_32px_rgba(15,23,42,0.05)]">
            <div class="flex flex-col gap-2.5 border-b border-slate-200 px-4 py-3.5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-black tracking-tight text-slate-950">Danh sách thẻ nhận tiền</h2>
                        <span class="inline-flex rounded-[8px] bg-[#eef2ff] px-2.5 py-1 text-xs font-semibold text-[#465fff]"
                            >{{ summary.total }} mục</span
                        >
                    </div>
                    <p class="mt-1 text-[13px] text-slate-500">
                        Tập trung vào thông tin nhận tiền của hệ thống. Không có dữ liệu thẻ gắn với người dùng trong màn này.
                    </p>
                </div>
            </div>

            <div v-if="loading" class="flex items-center justify-center gap-3 px-6 py-14 text-sm text-slate-500">
                <LoaderCircle class="h-5 w-5 animate-spin" />
                Đang tải danh sách thẻ...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[1080px]">
                    <thead class="bg-slate-50 text-left text-[13px] font-semibold text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Mã</th>
                            <th class="px-4 py-2.5">Thông tin hiển thị</th>
                            <th class="px-4 py-2.5">Ngân hàng</th>
                            <th class="px-4 py-2.5">Số tài khoản</th>
                            <th class="px-4 py-2.5">Hạn mức</th>
                            <th class="px-4 py-2.5">Thưởng</th>
                            <th class="px-4 py-2.5">Trạng thái</th>
                            <th class="px-4 py-2.5">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="rows.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">
                                Chưa có cấu hình thẻ nạp nào phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>

                        <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 text-[13px]">
                            <td class="px-4 py-2.5 align-top">
                                <p class="font-semibold text-[#465fff]">{{ row.code }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ row.badge_type === 'auto' ? 'Tự động' : 'Thủ công' }}</p>
                            </td>
                            <td class="px-4 py-2.5 align-top">
                                <p class="font-semibold text-slate-900">{{ row.name }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ row.description || 'Không có mô tả' }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    <span
                                        v-if="row.badge_label"
                                        class="inline-flex rounded-[8px] bg-sky-50 px-2 py-0.5 text-[11px] font-semibold text-sky-700"
                                    >
                                        {{ row.badge_label }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 align-top text-slate-700">{{ row.bank_name || '--' }}</td>
                            <td class="px-4 py-2.5 align-top">
                                <p class="font-semibold text-slate-900">{{ row.account_number || '--' }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ row.account_name || 'Chưa có chủ tài khoản' }}</p>
                            </td>
                            <td class="px-4 py-2.5 align-top text-slate-700">
                                {{ formatCurrency(row.min_amount) }} - {{ formatCurrency(row.max_amount) }}
                            </td>
                            <td class="px-4 py-2.5 align-top font-semibold text-slate-900">{{ row.bonus_percentage }}%</td>
                            <td class="px-4 py-2.5 align-top">
                                <span
                                    class="inline-flex rounded-[8px] px-2 py-0.5 text-[11px] font-semibold"
                                    :class="
                                        row.is_active
                                            ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border border-amber-200 bg-amber-50 text-amber-700'
                                    "
                                >
                                    {{ statusLabel(row.is_active) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 align-top">
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-500 transition hover:border-[#465fff] hover:text-[#465fff]"
                                        title="Sửa"
                                        @click="router.push(`/admin/recharge-methods/${row.id}/edit`)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-[8px] border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50"
                                        title="Xóa"
                                        @click="deleteRechargeMethod(row.id)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 px-4 py-3.5 md:flex-row md:items-center md:justify-between">
                <p class="text-[13px] text-slate-500">Hiển thị {{ tableRange }} trong tổng số {{ summary.total }} cấu hình thẻ</p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-500 disabled:opacity-40"
                        :disabled="meta.current_page === 1"
                        @click="goToPage(meta.current_page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <span class="px-2 text-sm font-semibold text-slate-600">{{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-500 disabled:opacity-40"
                        :disabled="meta.current_page === meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
