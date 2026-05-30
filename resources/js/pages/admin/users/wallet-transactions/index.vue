<script setup lang="ts">
import { adminWalletTransactionService, type AdminWalletTransactionListItem } from '@/services/admin-wallet-transaction.service';
import { handleErrorResponse } from '@/utils/response';
import { ArrowLeft, CalendarRange, ChevronLeft, ChevronRight, Filter, LoaderCircle, RefreshCcw, Search } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

type Filters = {
    search: string;
    user_id: string;
    type: string;
    status: string;
    date_from: string;
    date_to: string;
    per_page: number;
    page: number;
};

const route = useRoute();
const loading = ref(false);
const transactions = ref<AdminWalletTransactionListItem[]>([]);
const filters = reactive<Filters>({
    search: '',
    user_id: '',
    type: '',
    status: '',
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
    total_in: 0,
    total_out: 0,
    today_count: 0,
    pending_or_failed_count: 0,
});

const routeUserId = computed(() => {
    const raw = route.params.user_id;

    if (!raw) {
        return null;
    }

    const value = Number(raw);

    return Number.isFinite(value) ? value : null;
});

const pageTitle = computed(() => {
    return routeUserId.value ? `Lịch sử ví người dùng #${routeUserId.value}` : 'Lịch sử dòng tiền';
});

const formatNumber = (value: number): string => {
    return new Intl.NumberFormat('vi-VN').format(value);
};

const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (value: string | null, includeTime = false): string => {
    if (!value) {
        return '--';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        ...(includeTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    }).format(new Date(value));
};

const applyRouteUser = (): void => {
    filters.user_id = routeUserId.value ? String(routeUserId.value) : '';
};

const fetchTransactions = async (): Promise<void> => {
    loading.value = true;

    try {
        const response = await adminWalletTransactionService.list({
            search: filters.search || undefined,
            user_id: filters.user_id || undefined,
            type: filters.type || undefined,
            status: filters.status || undefined,
            date_from: filters.date_from || undefined,
            date_to: filters.date_to || undefined,
            per_page: filters.per_page,
            page: filters.page,
        });

        transactions.value = response.data;
        meta.current_page = response.meta.current_page;
        meta.last_page = response.meta.last_page;
        meta.per_page = response.meta.per_page;
        meta.total = response.meta.total;
        stats.total_in = response.stats.total_in;
        stats.total_out = response.stats.total_out;
        stats.today_count = response.stats.today_count;
        stats.pending_or_failed_count = response.stats.pending_or_failed_count;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const applyFilters = async (): Promise<void> => {
    filters.page = 1;
    await fetchTransactions();
};

const resetFilters = async (): Promise<void> => {
    filters.search = '';
    filters.type = '';
    filters.status = '';
    filters.date_from = '';
    filters.date_to = '';
    filters.per_page = 10;
    filters.page = 1;
    applyRouteUser();
    await fetchTransactions();
};

const goToPage = async (page: number): Promise<void> => {
    if (page < 1 || page > meta.last_page || page === meta.current_page) {
        return;
    }

    filters.page = page;
    await fetchTransactions();
};

watch(
    () => route.params.user_id,
    async () => {
        applyRouteUser();
        filters.page = 1;
        await fetchTransactions();
    },
);

onMounted(async () => {
    applyRouteUser();
    await fetchTransactions();
});
</script>

<template>
    <div class="space-y-5">
        <section
            class="overflow-hidden rounded-[10px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.12),_transparent_28%),linear-gradient(180deg,_rgba(255,255,255,0.98)_0%,_rgba(255,255,255,0.96)_100%)] px-5 py-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)]"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <RouterLink
                        v-if="routeUserId"
                        :to="{ name: 'admin.users.show', params: { user_id: routeUserId } }"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-[#465fff]"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Quay lại chi tiết người dùng
                    </RouterLink>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.3em] text-[#465fff]">Finance workspace</p>
                    <h1 class="mt-2 text-[28px] font-black tracking-tight text-slate-950">{{ pageTitle }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Theo dõi biến động số dư, kiểm tra các giao dịch cộng trừ và đối soát lịch sử ví của toàn hệ thống.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-[8px] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm"
                    @click="fetchTransactions"
                >
                    <RefreshCcw class="h-4 w-4" />
                    Làm mới
                </button>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[10px] border border-slate-200 bg-white px-4 py-4 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
                <p class="text-sm font-semibold text-slate-500">Tổng tiền vào</p>
                <p class="mt-2 text-2xl font-black tracking-tight text-emerald-600">{{ formatCurrency(stats.total_in) }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white px-4 py-4 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
                <p class="text-sm font-semibold text-slate-500">Tổng tiền ra</p>
                <p class="mt-2 text-2xl font-black tracking-tight text-orange-600">{{ formatCurrency(stats.total_out) }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white px-4 py-4 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
                <p class="text-sm font-semibold text-slate-500">Giao dịch hôm nay</p>
                <p class="mt-2 text-2xl font-black tracking-tight text-slate-950">{{ formatNumber(stats.today_count) }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white px-4 py-4 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
                <p class="text-sm font-semibold text-slate-500">Pending / Failed</p>
                <p class="mt-2 text-2xl font-black tracking-tight text-slate-950">{{ formatNumber(stats.pending_or_failed_count) }}</p>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_12px_32px_rgba(15,23,42,0.05)]">
            <div class="grid gap-3 xl:grid-cols-[1.5fr_repeat(4,_minmax(0,_0.9fr))_auto]">
                <label class="flex items-center gap-3 rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                    <Search class="h-4 w-4 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Tìm mã giao dịch, email, số điện thoại..."
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 outline-none placeholder:text-slate-400"
                        @keyup.enter="applyFilters"
                    />
                </label>

                <input
                    v-model="filters.user_id"
                    :disabled="routeUserId !== null"
                    type="number"
                    min="1"
                    placeholder="User ID"
                    class="rounded-[8px] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-600 outline-none disabled:bg-slate-100"
                />

                <select v-model="filters.type" class="rounded-[8px] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-600 outline-none">
                    <option value="">Loại giao dịch</option>
                    <option value="credit">Credit</option>
                    <option value="debit">Debit</option>
                    <option value="refund">Refund</option>
                    <option value="hold">Hold</option>
                    <option value="release">Release</option>
                    <option value="adjustment">Adjustment</option>
                </select>

                <select
                    v-model="filters.status"
                    class="rounded-[8px] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-600 outline-none"
                >
                    <option value="">Trạng thái</option>
                    <option value="success">Success</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <label class="flex items-center gap-3 rounded-[8px] border border-slate-200 bg-white px-3 py-2.5">
                    <CalendarRange class="h-4 w-4 text-slate-400" />
                    <input v-model="filters.date_from" type="date" class="w-full border-0 bg-transparent p-0 text-sm text-slate-600 outline-none" />
                </label>

                <div class="flex gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-[8px] border border-[#465fff] px-4 py-2.5 text-sm font-semibold text-[#465fff] transition hover:bg-[#eef2ff]"
                        @click="applyFilters"
                    >
                        <Filter class="h-4 w-4" />
                        Lọc
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-[8px] bg-[#465fff] px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_20px_rgba(70,95,255,0.22)]"
                        @click="resetFilters"
                    >
                        Đặt lại
                    </button>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-[0_16px_40px_rgba(15,23,42,0.06)]">
            <div class="border-b border-slate-200 px-4 py-4">
                <h2 class="text-xl font-black tracking-tight text-slate-950">Danh sách giao dịch ví</h2>
                <p class="mt-1 text-sm text-slate-500">Hiển thị {{ formatNumber(meta.total) }} giao dịch phù hợp với bộ lọc hiện tại.</p>
            </div>

            <div v-if="loading" class="flex items-center justify-center gap-3 px-6 py-16 text-sm text-slate-500">
                <LoaderCircle class="h-5 w-5 animate-spin" />
                Đang tải giao dịch ví...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50 text-left text-sm font-semibold text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Mã</th>
                            <th class="px-4 py-3">Người dùng</th>
                            <th class="px-4 py-3">Loại</th>
                            <th class="px-4 py-3">Số tiền</th>
                            <th class="px-4 py-3">Số dư</th>
                            <th class="px-4 py-3">Nội dung</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="transactions.length === 0">
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">Không có giao dịch nào.</td>
                        </tr>
                        <tr v-for="item in transactions" :key="item.id" class="border-t border-slate-100 text-sm">
                            <td class="px-4 py-3 font-semibold text-slate-700">{{ item.code }}</td>
                            <td class="px-4 py-3">
                                <RouterLink
                                    v-if="item.user"
                                    :to="{ name: 'admin.users.show', params: { user_id: item.user.id } }"
                                    class="font-semibold text-slate-900 hover:text-[#465fff]"
                                >
                                    {{ item.user.name }}
                                </RouterLink>
                                <p class="text-slate-500">{{ item.user?.email || '--' }}</p>
                            </td>
                            <td class="px-4 py-3 capitalize text-slate-600">{{ item.type }}</td>
                            <td class="px-4 py-3 font-semibold" :class="item.amount >= 0 ? 'text-emerald-600' : 'text-orange-600'">
                                {{ formatCurrency(item.amount) }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ formatCurrency(item.balance_before) }} → {{ formatCurrency(item.balance_after) }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ item.content || '--' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-[8px] px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        item.status === 'success'
                                            ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : item.status === 'pending'
                                              ? 'border border-amber-200 bg-amber-50 text-amber-700'
                                              : 'border border-slate-200 bg-slate-100 text-slate-600'
                                    "
                                >
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(item.created_at, true) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">
                    Hiển thị
                    {{ meta.total === 0 ? 0 : (meta.current_page - 1) * meta.per_page + 1 }}-{{
                        Math.min(meta.current_page * meta.per_page, meta.total)
                    }}
                    trong tổng số {{ formatNumber(meta.total) }} giao dịch
                </p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-500 disabled:opacity-40"
                        :disabled="meta.current_page === 1"
                        @click="goToPage(meta.current_page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <span class="px-2 text-sm font-semibold text-slate-600">{{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-500 disabled:opacity-40"
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
