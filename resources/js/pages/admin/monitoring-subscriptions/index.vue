<script setup lang="ts">
import DataTable from '@/components/shared/DataTable/index.vue';
import { adminMonitoringPlanService } from '@/services/admin-monitoring-plan.service';
import type { AdminMonitoringSubscription } from '@/types/monitoring-plan.type';
import { RefreshCw, Search } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { onMounted, reactive, ref } from 'vue';

const subscriptions = ref<AdminMonitoringSubscription[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const total = ref(0);
const filters = reactive({
    search: '',
    status: '',
    autoRenew: '',
});

const columns = [
    { accessorKey: 'id', header: 'Mã' },
    { accessorKey: 'user', header: 'Khách hàng' },
    { accessorKey: 'plan_name', header: 'Gói thuê' },
    { accessorKey: 'vehicle_limit', header: 'Hạn mức' },
    { accessorKey: 'total_price', header: 'Tổng tiền' },
    { accessorKey: 'status', header: 'Trạng thái' },
    { accessorKey: 'auto_renew', header: 'Tự gia hạn' },
    { accessorKey: 'started_at', header: 'Bắt đầu' },
    { accessorKey: 'expires_at', header: 'Hết hạn' },
];

const money = new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
});
const date = new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' });

const load = async (page = currentPage.value): Promise<void> => {
    loading.value = true;

    try {
        const data = await adminMonitoringPlanService.subscriptions({
            page,
            per_page: 20,
            search: filters.search.trim() || undefined,
            status: filters.status || undefined,
            auto_renew: filters.autoRenew === '' ? undefined : filters.autoRenew === 'true',
        });
        subscriptions.value = data.subscriptions;
        currentPage.value = data.meta.current_page;
        totalPages.value = data.meta.last_page;
        total.value = data.meta.total;
    } catch {
        void Swal.fire({
            icon: 'error',
            title: 'Không thể tải dữ liệu',
            text: 'Không thể tải danh sách gói đã cho thuê.',
        });
    } finally {
        loading.value = false;
    }
};

const applyFilters = async (): Promise<void> => load(1);

const formatDate = (value: string): string => date.format(new Date(value));

const customerName = (subscription: AdminMonitoringSubscription): string =>
    subscription.user?.full_name || subscription.user?.username || 'Tài khoản không tồn tại';

const statusLabel = (subscription: AdminMonitoringSubscription): string => {
    if (subscription.is_active) return 'Đang thuê';
    if (subscription.status === 'renewed') return 'Đã gia hạn';
    if (subscription.status === 'upgraded') return 'Đã nâng cấp';
    if (subscription.auto_renew) return 'Chờ gia hạn';
    return 'Hết hạn';
};

const statusClass = (subscription: AdminMonitoringSubscription): string => {
    if (subscription.is_active) return 'bg-emerald-100 text-emerald-700';
    if (subscription.status === 'renewed') return 'bg-blue-100 text-blue-700';
    if (subscription.status === 'upgraded') return 'bg-violet-100 text-violet-700';
    if (subscription.auto_renew) return 'bg-amber-100 text-amber-700';
    return 'bg-slate-100 text-slate-600';
};

onMounted(() => load());
</script>

<template>
    <section class="space-y-5">
        <header class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Theo dõi xe</p>
            <h1 class="mt-1 text-2xl font-black text-slate-950">Gói đã cho thuê</h1>
            <p class="mt-2 text-sm text-slate-500">Danh sách gói khách hàng đã mua, thời hạn sử dụng và trạng thái gia hạn.</p>
        </header>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <form class="grid gap-3 md:grid-cols-[minmax(220px,1fr)_180px_180px_auto]" @submit.prevent="applyFilters">
                <label class="relative">
                    <span class="sr-only">Tìm kiếm</span>
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Khách hàng hoặc tên gói"
                        class="w-full rounded-lg border border-slate-300 py-2.5 pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    />
                </label>
                <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm" @change="applyFilters">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Đang thuê</option>
                    <option value="expired">Hết hạn / chờ gia hạn</option>
                </select>
                <select v-model="filters.autoRenew" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm" @change="applyFilters">
                    <option value="">Tất cả gia hạn</option>
                    <option value="true">Bật tự gia hạn</option>
                    <option value="false">Tắt tự gia hạn</option>
                </select>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700"
                >
                    <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                    Lọc dữ liệu
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 text-sm text-slate-500">
                Tổng cộng <strong class="text-slate-800">{{ total }}</strong> gói đã cho thuê
            </div>
            <DataTable
                :data="subscriptions"
                :columns="columns"
                :go-to-page="load"
                :total-pages="totalPages"
                :current-page="currentPage"
                :loading="loading"
                class-custom="min-w-[1050px] w-full"
            >
                <template #id="{ row }">
                    <span class="font-semibold text-slate-700">#{{ row.id }}</span>
                </template>
                <template #user="{ row }">
                    <div class="min-w-40">
                        <p class="font-semibold text-slate-800">{{ customerName(row) }}</p>
                        <p class="text-xs text-slate-500">{{ row.user?.email || row.user?.username || '—' }}</p>
                    </div>
                </template>
                <template #plan_name="{ row }">
                    <div class="min-w-32">
                        <p class="font-semibold text-slate-800">{{ row.plan_name }}</p>
                        <p class="text-xs text-slate-500">{{ row.duration_days }} ngày</p>
                    </div>
                </template>
                <template #vehicle_limit="{ row }">{{ row.vehicle_limit }} xe</template>
                <template #total_price="{ row }">
                    <span class="font-semibold">{{ money.format(Number(row.total_price)) }}</span>
                </template>
                <template #status="{ row }">
                    <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClass(row)">
                        {{ statusLabel(row) }}
                    </span>
                </template>
                <template #auto_renew="{ row }">
                    <span :class="row.auto_renew ? 'text-emerald-700' : 'text-slate-400'">{{ row.auto_renew ? 'Đang bật' : 'Đã tắt' }}</span>
                </template>
                <template #started_at="{ row }">
                    <span class="whitespace-nowrap">{{ formatDate(row.started_at) }}</span>
                </template>
                <template #expires_at="{ row }">
                    <span class="whitespace-nowrap">{{ formatDate(row.expires_at) }}</span>
                </template>
            </DataTable>
        </div>
    </section>
</template>
