<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 rounded border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Danh sách gói thuê</h2>
                    <p class="text-sm text-slate-500">Quản lý gói thuê đang hoạt động trong hệ thống.</p>
                </div>

                <RouterLink
                    to="/admin/packages/create"
                    class="inline-flex items-center justify-center rounded bg-blue-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-600"
                >
                    Tạo gói mới
                </RouterLink>
            </div>

            <form class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_auto]" @submit.prevent="applyFilters">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Tìm theo tên hoặc slug..."
                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500"
                />

                <select v-model="filters.status" class="w-full rounded border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Hoạt động</option>
                    <option value="inactive">Không hoạt động</option>
                </select>

                <button type="submit" class="rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Lọc</button>
            </form>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tổng gói</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.total }}</p>
                </div>
                <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Đang hoạt động</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ summary.active }}</p>
                </div>
                <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Không hoạt động</p>
                    <p class="mt-2 text-2xl font-bold text-slate-600">{{ summary.inactive }}</p>
                </div>
            </div>
        </div>

        <DataTable
            :data="rows"
            :columns="columns"
            :goToPage="goToPage"
            :totalPages="totalPages"
            :currentPage="currentPage"
            :loading="loading"
            class-custom="min-w-[900px] sm:min-w-full"
        >
            <template #name="{ row }">
                <div class="flex flex-col gap-1">
                    <span class="font-semibold text-slate-900">{{ row.name }}</span>
                    <span class="text-xs text-slate-500">{{ row.description || 'Không có mô tả' }}</span>
                </div>
            </template>

            <template #price="{ value }">
                <span>{{ formatCurrency(Number(value)) }}</span>
            </template>

            <template #status="{ value }">
                <span
                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                    :class="value === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                >
                    {{ value === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                </span>
            </template>

            <template #action="{ row }">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-50"
                        @click="router.push(`/admin/packages/${row.id}/edit`)"
                    >
                        Sửa
                    </button>
                    <button
                        type="button"
                        class="rounded border border-rose-300 px-3 py-1 text-sm text-rose-600 hover:bg-rose-50"
                        @click="deletePackage(row.id)"
                    >
                        Xóa
                    </button>
                </div>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import DataTable from '@/components/shared/DataTable/index.vue';
import { adminPackageService } from '@/services/admin-package.service';
import { handleErrorResponse } from '@/utils/response';
import Swal from 'sweetalert2';
import { onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

type PackageRow = {
    id: number;
    name: string;
    slug: string;
    description: string;
    price: string | number;
    duration_days: number;
    user_subscriptions_count: number;
    status: 'active' | 'inactive';
};

const router = useRouter();

const columns = [
    { accessorKey: 'id', header: '#' },
    { accessorKey: 'name', header: 'Tên gói' },
    { accessorKey: 'slug', header: 'Slug' },
    { accessorKey: 'price', header: 'Giá' },
    { accessorKey: 'duration_days', header: 'Số ngày' },
    { accessorKey: 'user_subscriptions_count', header: 'Đã thuê' },
    { accessorKey: 'status', header: 'Trạng thái' },
    { accessorKey: 'action', header: 'Thao tác' },
];

const rows = ref<PackageRow[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const summary = reactive({
    total: 0,
    active: 0,
    inactive: 0,
});
const filters = reactive({
    search: '',
    status: '',
});

async function fetchPackages(page = 1): Promise<void> {
    try {
        loading.value = true;

        const response = await adminPackageService.list({
            page,
            search: filters.search || undefined,
            status: filters.status || undefined,
        });

        rows.value = response.packages.data;
        currentPage.value = response.packages.current_page;
        totalPages.value = response.packages.last_page;
        summary.total = response.summary.total;
        summary.active = response.summary.active;
        summary.inactive = response.summary.inactive;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
}

async function goToPage(page: number): Promise<void> {
    await fetchPackages(page);
}

async function applyFilters(): Promise<void> {
    await fetchPackages(1);
}

async function deletePackage(id: number): Promise<void> {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Xóa gói thuê?',
        text: 'Gói sẽ bị xóa mềm khỏi hệ thống.',
        showCancelButton: true,
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await adminPackageService.delete(id);

        await Swal.fire('Thành công', response.data.message || 'Xóa gói thành công.', 'success');
        await fetchPackages(currentPage.value);
    } catch (error) {
        handleErrorResponse(error);
    }
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'decimal',
        maximumFractionDigits: 0,
    }).format(value);
}

onMounted(async () => {
    await fetchPackages();
});
</script>
