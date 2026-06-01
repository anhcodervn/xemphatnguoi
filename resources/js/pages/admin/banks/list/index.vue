<script setup lang="ts">
import { adminBankService } from '@/services/admin-bank.service';
import type { AdminBankItem } from '@/types/admin-bank.type';
import { handleErrorResponse } from '@/utils/response';
import { CheckCircle2, LoaderCircle, Pencil, Plus, Search, ShieldAlert, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import Swal from 'sweetalert2';

const router = useRouter();
const loading = ref(false);
const rows = ref<AdminBankItem[]>([]);

const filters = reactive({
    search: '',
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

const fetchBanks = async (page = 1): Promise<void> => {
    try {
        loading.value = true;

        const response = await adminBankService.list({
            page,
            search: filters.search || undefined,
        });

        rows.value = response.banks.data;
        meta.current_page = response.banks.current_page;
        meta.last_page = response.banks.last_page;
        meta.total = response.banks.total;
        Object.assign(summary, response.summary);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const applyFilters = async (): Promise<void> => {
    filters.page = 1;
    await fetchBanks(1);
};

const goToPage = async (page: number): Promise<void> => {
    if (page < 1 || page > meta.last_page || page === meta.current_page) {
        return;
    }

    filters.page = page;
    await fetchBanks(page);
};

const removeBank = async (row: AdminBankItem): Promise<void> => {
    const result = await Swal.fire({
        title: 'Xóa ngân hàng?',
        text: 'Toàn bộ bank account đang dùng bank code này sẽ bị xóa theo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#dc2626',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await adminBankService.remove(row.id);

        await Swal.fire(
            'Đã xóa',
            `${response.data.message} Đã xóa ${response.data.data?.deleted_bank_accounts ?? 0} bank account liên quan.`,
            'success',
        );

        await fetchBanks(meta.current_page);
    } catch (error) {
        handleErrorResponse(error);
    }
};

onMounted(async () => {
    await fetchBanks();
});
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-950">Quản lý bank</h1>
                    <p class="mt-1 text-sm text-slate-500">Danh sách ngân hàng hỗ trợ trong hệ thống và giới hạn sync theo từng bank.</p>
                </div>

                <RouterLink
                    to="/admin/banks/create"
                    class="inline-flex items-center gap-2 rounded-[8px] bg-[#465fff] px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-[#3c52e0]"
                >
                    <Plus class="h-4 w-4" />
                    Thêm bank
                </RouterLink>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-3">
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tổng bank</p>
                <p class="mt-1 text-2xl font-bold text-slate-950">{{ summary.total }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Đang hoạt động</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ summary.active }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tạm dừng</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ summary.inactive }}</p>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-3.5 shadow-sm">
            <div class="flex flex-col gap-2.5 lg:flex-row">
                <label class="flex flex-1 items-center gap-2.5 rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2">
                    <Search class="h-4 w-4 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 outline-none placeholder:text-slate-400"
                        placeholder="Tìm theo code, tên, short name..."
                        @keyup.enter="applyFilters"
                    />
                </label>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-[8px] border border-[#465fff] px-3.5 py-2 text-sm font-semibold text-[#465fff] transition hover:bg-[#eef2ff]"
                    @click="applyFilters"
                >
                    Lọc
                </button>
            </div>
        </section>

        <section class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm">
            <div v-if="loading" class="flex items-center justify-center gap-2 px-6 py-16 text-sm text-slate-500">
                <LoaderCircle class="h-5 w-5 animate-spin" />
                Đang tải danh sách bank...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[980px]">
                    <thead class="bg-slate-50 text-left text-[13px] font-semibold text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Thông tin</th>
                            <th class="px-4 py-3">Request/phút</th>
                            <th class="px-4 py-3">Thứ tự</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">Chưa có bank nào.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ row.code }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p class="font-semibold text-slate-900">{{ row.name }}</p>
                                <p>{{ row.short_name || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ row.limit_request_per_minute }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ row.sort_order }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold"
                                    :class="row.is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
                                >
                                    <CheckCircle2 v-if="row.is_active" class="h-3.5 w-3.5" />
                                    <ShieldAlert v-else class="h-3.5 w-3.5" />
                                    {{ row.is_active ? 'Hoạt động' : 'Tạm dừng' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-[8px] border border-slate-200 px-2.5 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                        @click="router.push(`/admin/banks/${row.id}/edit`)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                        Sửa
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-[8px] border border-rose-200 px-2.5 py-1.5 text-sm font-semibold text-rose-600 hover:bg-rose-50"
                                        @click="removeBank(row)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm text-slate-500">
                <p>Đang hiển thị {{ tableRange }} / {{ meta.total }}</p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-[8px] border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="meta.current_page <= 1"
                        @click="goToPage(meta.current_page - 1)"
                    >
                        Trước
                    </button>
                    <span>Trang {{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button
                        type="button"
                        class="rounded-[8px] border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                    >
                        Sau
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
