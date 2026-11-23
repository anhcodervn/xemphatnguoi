<script setup lang="ts">
import DataTable from '@/components/shared/DataTable/index.vue';
import { adminMonitoringPlanService } from '@/services/admin-monitoring-plan.service';
import type { AdminMonitoringVehicle } from '@/types/monitoring-plan.type';
import { RefreshCw, Search } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { onMounted, reactive, ref } from 'vue';

const vehicles = ref<AdminMonitoringVehicle[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const total = ref(0);
const error = ref('');
const filters = reactive({ search: '', status: 'enabled', vehicleType: '' });
const vehicleTypes: Record<string, string> = { car: 'Ô tô', motorbike: 'Xe máy', electric_motorbike: 'Xe máy điện' };
const columns = [
    { accessorKey: 'vehicle', header: 'Xe / biển số' },
    { accessorKey: 'user', header: 'Khách hàng' },
    { accessorKey: 'subscription', header: 'Gói còn hiệu lực' },
    { accessorKey: 'enabled', header: 'Theo dõi' },
    { accessorKey: 'email_notifications', header: 'Thông báo email' },
    { accessorKey: 'last_checked_at', header: 'Kiểm tra gần nhất' },
    { accessorKey: 'last_violation_count', header: 'Số vi phạm gần nhất' },
];
const date = new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' });
const formatDate = (value: string | null): string => (value ? date.format(new Date(value)) : 'Chưa kiểm tra');
let latestRequest = 0;

const load = async (page = currentPage.value): Promise<void> => {
    const request = ++latestRequest;
    loading.value = true;
    error.value = '';

    try {
        const data = await adminMonitoringPlanService.vehicles({
            page,
            per_page: 20,
            search: filters.search.trim() || undefined,
            status: filters.status,
            vehicle_type: filters.vehicleType || undefined,
        });
        if (request !== latestRequest) return;
        vehicles.value = data.vehicles;
        currentPage.value = data.meta.current_page;
        totalPages.value = data.meta.last_page;
        total.value = data.meta.total;
    } catch {
        if (request !== latestRequest) return;
        vehicles.value = [];
        total.value = 0;
        error.value = 'Không thể tải danh sách xe. Vui lòng thử lại.';
        void Swal.fire({ icon: 'error', title: 'Không thể tải dữ liệu', text: error.value });
    } finally {
        if (request === latestRequest) loading.value = false;
    }
};

const applyFilters = (): Promise<void> => load(1);
onMounted(() => load());
</script>

<template>
    <section class="space-y-5">
        <header class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Quản lý gói</p>
            <h1 class="mt-1 text-2xl font-black text-slate-950">Xe đang theo dõi</h1>
            <p class="mt-2 text-sm text-slate-500">Danh sách xe theo dõi của tất cả khách hàng, gói còn hiệu lực và lần kiểm tra gần nhất.</p>
        </header>

        <form
            class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_180px_160px_auto]"
            @submit.prevent="applyFilters"
        >
            <label class="relative">
                <span class="sr-only">Tìm biển số, tên xe hoặc khách hàng</span>
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Biển số, tên xe hoặc khách hàng"
                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                />
            </label>
            <select
                v-model="filters.status"
                aria-label="Trạng thái theo dõi"
                class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                @change="applyFilters"
            >
                <option value="enabled">Đang bật theo dõi</option>
                <option value="disabled">Đã tắt theo dõi</option>
                <option value="all">Tất cả trạng thái</option>
            </select>
            <select
                v-model="filters.vehicleType"
                aria-label="Loại xe"
                class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                @change="applyFilters"
            >
                <option value="">Tất cả loại xe</option>
                <option v-for="(label, value) in vehicleTypes" :key="value" :value="value">{{ label }}</option>
            </select>
            <button
                type="submit"
                :disabled="loading"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-50"
            >
                <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                Lọc dữ liệu
            </button>
        </form>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" :aria-busy="loading">
            <p v-if="error" role="alert" class="mb-3 text-sm text-red-600">{{ error }}</p>
            <div class="mb-3 text-sm text-slate-500" aria-live="polite">
                Tổng cộng <strong class="text-slate-800">{{ total }}</strong> xe theo bộ lọc
            </div>
            <DataTable
                :data="vehicles"
                :columns="columns"
                :go-to-page="load"
                :total-pages="totalPages"
                :current-page="currentPage"
                :loading="loading"
                class-custom="min-w-[1100px] w-full"
            >
                <template #vehicle="{ row }">
                    <p class="font-bold text-slate-900">{{ row.vehicle.plate }}</p>
                    <p class="text-xs text-slate-500">
                        {{ row.vehicle.name || 'Chưa đặt tên' }} · {{ vehicleTypes[row.vehicle.vehicle_type] || row.vehicle.vehicle_type }}
                    </p>
                </template>
                <template #user="{ row }">
                    <RouterLink
                        :to="{ name: 'admin.users.show', params: { user_id: row.user.id } }"
                        class="font-semibold text-blue-700 hover:underline"
                        >{{ row.user.full_name || row.user.username }}</RouterLink
                    >
                    <p class="text-xs text-slate-500">{{ row.user.email || row.user.username }}</p>
                </template>
                <template #subscription="{ row }">
                    <template v-if="row.subscription">
                        <p class="font-semibold text-slate-800">{{ row.subscription.plan_name }}</p>
                        <p class="whitespace-nowrap text-xs text-slate-500">Hết hạn: {{ formatDate(row.subscription.expires_at) }}</p>
                    </template>
                    <span v-else class="text-sm text-amber-700">Không có gói còn hiệu lực</span>
                </template>
                <template #enabled="{ row }">
                    <span
                        class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold"
                        :class="row.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                        >{{ row.enabled ? 'Đang bật' : 'Đã tắt' }}</span
                    >
                </template>
                <template #email_notifications="{ row }">{{ row.email_notifications ? 'Đang bật' : 'Đã tắt' }}</template>
                <template #last_checked_at="{ row }"
                    ><span class="whitespace-nowrap">{{ formatDate(row.last_checked_at) }}</span></template
                >
                <template #last_violation_count="{ row }">{{ row.last_violation_count ?? 'Chưa có dữ liệu' }}</template>
            </DataTable>
        </div>
    </section>
</template>
