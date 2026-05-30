<script setup lang="ts">
import { adminPackageOrderService, type AdminPackageOrderItem } from '@/services/admin-package-order.service';
import { adminPackageService } from '@/services/admin-package.service';
import { handleErrorResponse } from '@/utils/response';
import { ArrowRight, CalendarRange, ChevronLeft, ChevronRight, Eye, Layers3, LoaderCircle, RefreshCcw, Search, Wallet } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';

type Filters = {
    search: string;
    status: string;
    package_id: string;
    date_from: string;
    date_to: string;
    per_page: number;
    page: number;
};

type PackageOption = {
    id: number;
    name: string;
};

const loading = ref(false);
const loadingPackages = ref(false);
const orders = ref<AdminPackageOrderItem[]>([]);
const packageOptions = ref<PackageOption[]>([]);

const filters = reactive<Filters>({
    search: '',
    status: '',
    package_id: '',
    date_from: '',
    date_to: '',
    per_page: 10,
    page: 1,
});

const meta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

const stats = reactive({
    total_orders: 0,
    revenue: 0,
    today_orders: 0,
    active_packages: 0,
    expiring_soon: 0,
    expired_packages: 0,
    renewal_rate: 0,
    monthly_revenue: 0,
});

const tableRange = computed(() => {
    if (meta.total === 0) {
        return '0-0';
    }

    const from = (meta.current_page - 1) * meta.per_page + 1;
    const to = Math.min(meta.current_page * meta.per_page, meta.total);

    return `${from}-${to}`;
});

const formatNumber = (value: number): string => new Intl.NumberFormat('vi-VN').format(value);

const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '--';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(value));
};

const getRemainingDays = (value: string | null): number | null => {
    if (!value) {
        return null;
    }

    const diff = new Date(value).getTime() - Date.now();

    return Math.ceil(diff / (1000 * 60 * 60 * 24));
};

const getLifecycleStatus = (item: AdminPackageOrderItem): { label: string; classes: string } => {
    const remainingDays = getRemainingDays(item.expired_at);

    if (item.payment_status === 'cancelled' || item.status === 'cancelled') {
        return {
            label: 'Đã hủy',
            classes: 'border border-slate-200 bg-slate-100 text-slate-600',
        };
    }

    if (remainingDays !== null && remainingDays < 0) {
        return {
            label: 'Đã hết hạn',
            classes: 'border border-rose-200 bg-rose-50 text-rose-600',
        };
    }

    if (remainingDays !== null && remainingDays <= 7) {
        return {
            label: 'Sắp hết hạn',
            classes: 'border border-amber-200 bg-amber-50 text-amber-700',
        };
    }

    if (item.payment_status === 'paid' && item.status === 'completed') {
        return {
            label: 'Đang hoạt động',
            classes: 'border border-emerald-200 bg-emerald-50 text-emerald-700',
        };
    }

    if (item.payment_status === 'pending' || item.status === 'pending' || item.status === 'processing') {
        return {
            label: 'Đang xử lý',
            classes: 'border border-sky-200 bg-sky-50 text-sky-700',
        };
    }

    return {
        label: item.status || 'Không xác định',
        classes: 'border border-slate-200 bg-slate-100 text-slate-600',
    };
};

const getRemainingText = (value: string | null): string => {
    const remainingDays = getRemainingDays(value);

    if (remainingDays === null) {
        return '--';
    }

    if (remainingDays < 0) {
        return 'Hết hạn';
    }

    if (remainingDays === 0) {
        return 'Hôm nay';
    }

    return `${remainingDays} ngày`;
};

const getRemainingClass = (value: string | null): string => {
    const remainingDays = getRemainingDays(value);

    if (remainingDays === null) {
        return 'text-slate-500';
    }

    if (remainingDays < 0) {
        return 'text-rose-600';
    }

    if (remainingDays <= 7) {
        return 'text-amber-600';
    }

    return 'text-emerald-600';
};

const getPaymentLabel = (status: string | null): string => {
    switch (status) {
        case 'paid':
            return 'Đã thanh toán';
        case 'pending':
            return 'Chờ thanh toán';
        case 'failed':
            return 'Thất bại';
        case 'refunded':
            return 'Hoàn tiền';
        case 'cancelled':
            return 'Đã hủy';
        default:
            return status || '--';
    }
};

const getPaymentClass = (status: string | null): string => {
    switch (status) {
        case 'paid':
            return 'border border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'pending':
            return 'border border-amber-200 bg-amber-50 text-amber-700';
        case 'failed':
        case 'cancelled':
            return 'border border-rose-200 bg-rose-50 text-rose-600';
        case 'refunded':
            return 'border border-sky-200 bg-sky-50 text-sky-700';
        default:
            return 'border border-slate-200 bg-slate-100 text-slate-600';
    }
};

const fetchOrders = async (): Promise<void> => {
    loading.value = true;

    try {
        const response = await adminPackageOrderService.list({
            search: filters.search || undefined,
            status: filters.status || undefined,
            package_id: filters.package_id || undefined,
            date_from: filters.date_from || undefined,
            date_to: filters.date_to || undefined,
            per_page: filters.per_page,
            page: filters.page,
        });

        orders.value = response.data;
        meta.current_page = response.meta.current_page;
        meta.last_page = response.meta.last_page;
        meta.per_page = response.meta.per_page;
        meta.total = response.meta.total;
        stats.total_orders = response.stats.total_orders;
        stats.revenue = response.stats.revenue;
        stats.today_orders = response.stats.today_orders;
        stats.active_packages = response.stats.active_packages;
        stats.expiring_soon = response.stats.expiring_soon;
        stats.expired_packages = response.stats.expired_packages;
        stats.renewal_rate = response.stats.renewal_rate;
        stats.monthly_revenue = response.stats.monthly_revenue;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const fetchPackages = async (): Promise<void> => {
    loadingPackages.value = true;

    try {
        const response = await adminPackageService.list({ per_page: 50 });

        packageOptions.value = (response.packages?.data || []).map((item: { id: number; name: string }) => ({
            id: item.id,
            name: item.name,
        }));
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingPackages.value = false;
    }
};

const applyFilters = async (): Promise<void> => {
    filters.page = 1;
    await fetchOrders();
};

const resetFilters = async (): Promise<void> => {
    filters.search = '';
    filters.status = '';
    filters.package_id = '';
    filters.date_from = '';
    filters.date_to = '';
    filters.per_page = 10;
    filters.page = 1;
    await fetchOrders();
};

const goToPage = async (page: number): Promise<void> => {
    if (page < 1 || page > meta.last_page || page === meta.current_page) {
        return;
    }

    filters.page = page;
    await fetchOrders();
};

onMounted(async () => {
    await Promise.all([fetchOrders(), fetchPackages()]);
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
                    <h1 class="mt-1.5 text-[24px] font-black tracking-tight text-slate-950">Gói người dùng đã mua</h1>
                    <p class="mt-1.5 max-w-2xl text-[13px] leading-5 text-slate-500">
                        Theo dõi đơn mua gói, trạng thái thuê bao đang dùng, các gói sắp hết hạn và doanh thu theo thời gian thực.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 self-start rounded-[8px] border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"
                    @click="fetchOrders"
                >
                    <RefreshCcw class="h-4 w-4" />
                    Làm mới
                </button>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-[10px] border border-slate-200 bg-white px-3.5 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.045)]">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-emerald-50 text-emerald-600">
                        <Layers3 class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Đang hoạt động</p>
                        <p class="mt-0.5 text-[24px] font-black tracking-tight text-slate-950">{{ formatNumber(stats.active_packages) }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-emerald-600">+{{ formatNumber(stats.today_orders) }} đơn hôm nay</p>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white px-3.5 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.045)]">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-amber-50 text-amber-600">
                        <CalendarRange class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Sắp hết hạn</p>
                        <p class="mt-0.5 text-[24px] font-black tracking-tight text-slate-950">{{ formatNumber(stats.expiring_soon) }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-amber-600">Trong 7 ngày tới</p>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white px-3.5 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.045)]">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-rose-50 text-rose-600">
                        <CalendarRange class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Đã hết hạn</p>
                        <p class="mt-0.5 text-[24px] font-black tracking-tight text-slate-950">{{ formatNumber(stats.expired_packages) }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-rose-600">Cần theo dõi gia hạn</p>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white px-3.5 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.045)]">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-violet-50 text-violet-600">
                        <RefreshCcw class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Tỷ lệ gia hạn</p>
                        <p class="mt-0.5 text-[24px] font-black tracking-tight text-slate-950">{{ stats.renewal_rate.toFixed(1) }}%</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-violet-600">Theo đơn đã thanh toán</p>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white px-3.5 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.045)]">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-sky-50 text-sky-600">
                        <Wallet class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Doanh thu tháng</p>
                        <p class="mt-0.5 text-[22px] font-black tracking-tight text-slate-950">{{ formatCurrency(stats.monthly_revenue) }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-sky-600">Tổng: {{ formatCurrency(stats.revenue) }}</p>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-3.5 shadow-[0_10px_28px_rgba(15,23,42,0.045)]">
            <div class="grid gap-2.5 2xl:grid-cols-[1.7fr_repeat(4,_minmax(0,_0.88fr))_auto]">
                <label class="flex items-center gap-2.5 rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2">
                    <Search class="h-4 w-4 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Tìm mã đơn, email người dùng, tên gói..."
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 outline-none placeholder:text-slate-400"
                        @keyup.enter="applyFilters"
                    />
                </label>

                <select v-model="filters.status" class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending">Chờ xử lý</option>
                    <option value="processing">Đang xử lý</option>
                    <option value="completed">Hoàn tất</option>
                    <option value="cancelled">Đã hủy</option>
                </select>

                <select
                    v-model="filters.package_id"
                    class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none"
                >
                    <option value="">{{ loadingPackages ? 'Đang tải gói...' : 'Tất cả gói thuê' }}</option>
                    <option v-for="pkg in packageOptions" :key="pkg.id" :value="String(pkg.id)">{{ pkg.name }}</option>
                </select>

                <label class="flex items-center gap-2.5 rounded-[8px] border border-slate-200 bg-white px-3 py-2">
                    <CalendarRange class="h-4 w-4 text-slate-400" />
                    <input v-model="filters.date_from" type="date" class="w-full border-0 bg-transparent p-0 text-sm text-slate-600 outline-none" />
                </label>

                <label class="flex items-center gap-2.5 rounded-[8px] border border-slate-200 bg-white px-3 py-2">
                    <CalendarRange class="h-4 w-4 text-slate-400" />
                    <input v-model="filters.date_to" type="date" class="w-full border-0 bg-transparent p-0 text-sm text-slate-600 outline-none" />
                </label>

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
                        class="inline-flex items-center justify-center rounded-[8px] bg-[#465fff] px-3.5 py-2 text-sm font-semibold text-white shadow-[0_8px_18px_rgba(70,95,255,0.2)]"
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
                        <h2 class="text-lg font-black tracking-tight text-slate-950">Danh sách gói đã mua</h2>
                        <span class="inline-flex rounded-[8px] bg-[#eef2ff] px-2.5 py-1 text-xs font-semibold text-[#465fff]">
                            {{ formatNumber(meta.total) }} đơn
                        </span>
                    </div>
                    <p class="mt-1 text-[13px] text-slate-500">
                        Một màn hình để kiểm tra đơn mua, vòng đời thuê bao và điều hướng sang user hoặc gói liên quan.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-[8px] border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                        Sắp hết hạn 7 ngày: {{ formatNumber(stats.expiring_soon) }}
                    </span>
                    <span class="inline-flex rounded-[8px] border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600">
                        Đã hết hạn: {{ formatNumber(stats.expired_packages) }}
                    </span>
                </div>
            </div>

            <div v-if="loading" class="flex items-center justify-center gap-3 px-6 py-14 text-sm text-slate-500">
                <LoaderCircle class="h-5 w-5 animate-spin" />
                Đang tải danh sách gói đã mua...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[1120px]">
                    <thead class="bg-slate-50 text-left text-[13px] font-semibold text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Mã đơn</th>
                            <th class="px-4 py-2.5">Người dùng</th>
                            <th class="px-4 py-2.5">Gói thuê</th>
                            <th class="px-4 py-2.5">Giá</th>
                            <th class="px-4 py-2.5">Bắt đầu</th>
                            <th class="px-4 py-2.5">Hết hạn</th>
                            <th class="px-4 py-2.5">Còn lại</th>
                            <th class="px-4 py-2.5">Thanh toán</th>
                            <th class="px-4 py-2.5">Trạng thái</th>
                            <th class="px-4 py-2.5">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="orders.length === 0">
                            <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500">
                                Không có đơn mua gói nào phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>

                        <tr v-for="item in orders" :key="item.id" class="border-t border-slate-100 text-[13px]">
                            <td class="px-4 py-2.5 align-top">
                                <p class="font-semibold text-[#465fff]">{{ item.code }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ formatDate(item.created_at) }}</p>
                            </td>
                            <td class="px-4 py-2.5 align-top">
                                <RouterLink
                                    v-if="item.user"
                                    :to="{ name: 'admin.users.show', params: { user_id: item.user.id } }"
                                    class="font-semibold text-slate-900 transition hover:text-[#465fff]"
                                >
                                    {{ item.user.name }}
                                </RouterLink>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ item.user?.email || '--' }}</p>
                                <p class="text-[11px] text-slate-400">{{ item.user?.phone || 'Chưa có số điện thoại' }}</p>
                            </td>
                            <td class="px-4 py-2.5 align-top">
                                <div class="flex flex-col gap-1">
                                    <p class="font-semibold text-slate-900">{{ item.package?.name || '--' }}</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="inline-flex rounded-[8px] bg-[#f4f3ff] px-2 py-0.5 text-[11px] font-semibold text-violet-600">
                                            {{ item.duration_days ? `${formatNumber(item.duration_days)} ngày` : 'Không giới hạn' }}
                                        </span>
                                        <span
                                            class="inline-flex rounded-[8px] px-2 py-0.5 text-[11px] font-semibold"
                                            :class="item.is_renewal ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-600'"
                                        >
                                            {{ item.is_renewal ? 'Gia hạn' : 'Mua mới' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 align-top font-semibold text-slate-900">{{ formatCurrency(item.price) }}</td>
                            <td class="px-4 py-2.5 align-top text-slate-600">{{ formatDate(item.started_at) }}</td>
                            <td class="px-4 py-2.5 align-top text-slate-600">{{ formatDate(item.expired_at) }}</td>
                            <td class="px-4 py-2.5 align-top font-semibold" :class="getRemainingClass(item.expired_at)">
                                {{ getRemainingText(item.expired_at) }}
                            </td>
                            <td class="px-4 py-2.5 align-top">
                                <span
                                    class="inline-flex rounded-[8px] px-2 py-0.5 text-[11px] font-semibold"
                                    :class="getPaymentClass(item.payment_status)"
                                >
                                    {{ getPaymentLabel(item.payment_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 align-top">
                                <span
                                    class="inline-flex rounded-[8px] px-2 py-0.5 text-[11px] font-semibold"
                                    :class="getLifecycleStatus(item).classes"
                                >
                                    {{ getLifecycleStatus(item).label }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 align-top">
                                <div class="flex items-center gap-1.5">
                                    <RouterLink
                                        v-if="item.user"
                                        :to="{ name: 'admin.users.show', params: { user_id: item.user.id } }"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-500 transition hover:border-[#465fff] hover:text-[#465fff]"
                                        title="Xem người dùng"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </RouterLink>
                                    <RouterLink
                                        v-if="item.package"
                                        :to="{ name: 'admin.packages.edit', params: { package_id: item.package.id } }"
                                        class="inline-flex h-8 items-center justify-center gap-1 rounded-[8px] border border-slate-200 bg-white px-2.5 text-[11px] font-semibold text-slate-600 transition hover:border-[#465fff] hover:text-[#465fff]"
                                    >
                                        <span>Gói</span>
                                        <ArrowRight class="h-3.5 w-3.5" />
                                    </RouterLink>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 px-4 py-3.5 md:flex-row md:items-center md:justify-between">
                <p class="text-[13px] text-slate-500">Hiển thị {{ tableRange }} trong tổng số {{ formatNumber(meta.total) }} đơn mua gói</p>

                <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                    <label class="flex items-center gap-2 text-[13px] text-slate-500">
                        <span>Số dòng mỗi trang</span>
                        <select
                            v-model="filters.per_page"
                            class="rounded-[8px] border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-600 outline-none"
                            @change="applyFilters"
                        >
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                        </select>
                    </label>

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
            </div>
        </section>
    </div>
</template>
