<script setup lang="ts">
import type { DepositRequestItem } from '@/types/recharge-config.type';
import { CalendarRange, ChevronLeft, ChevronRight, Eye, Landmark, Search } from 'lucide-vue-next';

const props = defineProps<{
    stats: {
        totalDeposited: string;
        depositCount: number;
        totalBonus: string;
    };
    filters: {
        search: string;
        status: string;
        dateLabel: string;
    };
    loading: boolean;
    rows: DepositRequestItem[];
    total: number;
    currentPage: number;
    lastPage: number;
}>();

const emit = defineEmits<{
    'update:search': [value: string];
    'update:status': [value: string];
    'change-page': [page: number];
    'go-create': [];
    confirm: [item: DepositRequestItem];
}>();

const statusOptions: Array<{ label: string; value: string }> = [
    { label: 'Tất cả', value: 'all' },
    { label: 'Chờ xử lý', value: 'pending' },
    { label: 'Đang xử lý', value: 'processing' },
    { label: 'Thành công', value: 'paid' },
    { label: 'Thất bại', value: 'failed' },
    { label: 'Đã hủy', value: 'cancelled' },
    { label: 'Hết hạn', value: 'expired' },
];

const statusClasses: Record<DepositRequestItem['status'], string> = {
    pending: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    processing: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
    paid: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    failed: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
    cancelled: 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
    expired: 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
};

const statusLabels: Record<DepositRequestItem['status'], string> = {
    pending: 'Chờ xử lý',
    processing: 'Đang xử lý',
    paid: 'Thành công',
    failed: 'Thất bại',
    cancelled: 'Đã hủy',
    expired: 'Hết hạn',
};

const formatMoney = (value: number): string =>
    new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '--';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN');
};
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-3 md:grid-cols-3">
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Tổng đã nạp</p>
                <p class="mt-1.5 text-xl font-black tracking-[-0.03em] text-slate-950 sm:mt-2 sm:text-2xl">{{ props.stats.totalDeposited }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Số lần nạp</p>
                <p class="mt-1.5 text-xl font-black tracking-[-0.03em] text-slate-950 sm:mt-2 sm:text-2xl">{{ props.stats.depositCount }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Bonus đã nhận</p>
                <p class="mt-1.5 text-xl font-black tracking-[-0.03em] text-emerald-600 sm:mt-2 sm:text-2xl">{{ props.stats.totalBonus }}</p>
            </article>
        </div>

        <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="grid flex-1 gap-3 lg:grid-cols-[minmax(0,1.4fr)_200px_220px]">
                    <label class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            :value="props.filters.search"
                            type="text"
                            placeholder="Tìm theo mã giao dịch"
                            class="h-11 w-full rounded-[10px] border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white"
                            @input="emit('update:search', ($event.target as HTMLInputElement).value)"
                        />
                    </label>

                    <label class="relative">
                        <CalendarRange class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input :value="props.filters.dateLabel" readonly type="text" class="h-11 w-full rounded-[10px] border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm text-slate-500 outline-none" />
                    </label>

                    <select
                        :value="props.filters.status"
                        class="h-11 rounded-[10px] border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white"
                        @change="emit('update:status', ($event.target as HTMLSelectElement).value)"
                    >
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>

                <button type="button" class="inline-flex h-10 items-center justify-center rounded-[10px] bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-500 xl:h-11 xl:w-auto" @click="emit('go-create')">
                    Tạo yêu cầu
                </button>
            </div>

            <div v-if="props.loading" class="mt-4 rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                Đang tải lịch sử nạp tiền...
            </div>

            <div v-else-if="props.total === 0" class="mt-4 rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-4 py-12 text-center">
                <h3 class="text-lg font-bold text-slate-950">Chưa có giao dịch</h3>
                <p class="mt-2 text-sm text-slate-500">Tạo yêu cầu nạp đầu tiên để bắt đầu theo dõi trạng thái thanh toán tại đây.</p>
                <button type="button" class="mt-4 inline-flex items-center justify-center rounded-[10px] bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500" @click="emit('go-create')">
                    Tạo yêu cầu
                </button>
            </div>

            <template v-else>
                <div class="mt-4 space-y-3 lg:hidden">
                    <article v-for="row in props.rows" :key="row.id" class="rounded-[10px] border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-slate-950">{{ row.code }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ formatDateTime(row.created_at) }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClasses[row.status]">
                                {{ statusLabels[row.status] }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-[10px] bg-gradient-to-br from-indigo-600 to-sky-500 text-white">
                                <Landmark class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ row.method.name }}</p>
                                <p class="text-xs text-slate-500">Bonus {{ formatMoney(row.bonus_amount) }}</p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-2 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Số tiền</span>
                                <span class="font-semibold text-slate-900">{{ formatMoney(row.amount) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Nội dung</span>
                                <span class="font-semibold text-indigo-600">{{ row.content || '--' }}</span>
                            </div>
                        </div>

                        <div v-if="row.can_confirm" class="mt-4 flex justify-end">
                            <button type="button" class="inline-flex items-center gap-2 rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700" @click="emit('confirm', row)">
                                <Eye class="h-4 w-4" />
                                {{ row.status === 'pending' ? 'Tiếp tục thanh toán' : 'Xem chi tiết' }}
                            </button>
                        </div>
                    </article>
                </div>

                <div class="mt-4 hidden overflow-hidden rounded-[10px] border border-slate-200 lg:block">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Mã GD</th>
                                    <th class="px-4 py-3">Thời gian</th>
                                    <th class="px-4 py-3">Phương thức</th>
                                    <th class="px-4 py-3">Số tiền</th>
                                    <th class="px-4 py-3">Bonus</th>
                                    <th class="px-4 py-3">Trạng thái</th>
                                    <th class="px-4 py-3 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="row in props.rows" :key="row.id" class="align-top">
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ row.code }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ formatDateTime(row.created_at) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-[10px] bg-gradient-to-br from-indigo-600 to-sky-500 text-white">
                                                <Landmark class="h-4 w-4" />
                                            </div>
                                            <span class="font-medium text-slate-700">{{ row.method.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ formatMoney(row.amount) }}</td>
                                    <td class="px-4 py-3 font-semibold text-emerald-600">{{ formatMoney(row.bonus_amount) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClasses[row.status]">
                                            {{ statusLabels[row.status] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            v-if="row.can_confirm"
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                            @click="emit('confirm', row)"
                                        >
                                            <Eye class="h-4 w-4" />
                                            {{ row.status === 'pending' ? 'Tiếp tục thanh toán' : 'Xem chi tiết' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>Hiển thị {{ props.rows.length }} / {{ props.total }} giao dịch</p>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-[10px] border border-slate-200 bg-white px-2.5 py-1.5" :class="props.currentPage <= 1 ? 'cursor-not-allowed text-slate-300' : 'text-slate-600'" :disabled="props.currentPage <= 1" @click="emit('change-page', props.currentPage - 1)">
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                        <span class="rounded-[10px] bg-slate-900 px-3 py-1.5 font-semibold text-white">{{ props.currentPage }}</span>
                        <button type="button" class="rounded-[10px] border border-slate-200 bg-white px-2.5 py-1.5" :class="props.currentPage >= props.lastPage ? 'cursor-not-allowed text-slate-300' : 'text-slate-600'" :disabled="props.currentPage >= props.lastPage" @click="emit('change-page', props.currentPage + 1)">
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </template>
        </article>
    </div>
</template>
