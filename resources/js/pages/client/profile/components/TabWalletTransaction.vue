<script setup lang="ts">
import type { ClientProfilePaginationMeta, WalletTransactionItem } from '@/types/client-profile.type';
import { formatTime } from '@/utils/helpers/format';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    filters: {
        search: string;
        type: string;
    };
    transactions: WalletTransactionItem[];
    loading: boolean;
    meta: ClientProfilePaginationMeta;
}>();

const emit = defineEmits<{
    'change-page': [page: number];
    'update:search': [value: string];
    'update:type': [value: string];
}>();

const typeOptions = [
    { label: 'Tất cả giao dịch', value: 'all' },
    { label: 'Nạp tiền', value: 'recharge' },
    { label: 'Trừ tiền', value: 'deduct' },
    { label: 'Hoàn tiền', value: 'refund' },
    { label: 'Thưởng bonus', value: 'bonus' },
];

const statusClasses: Record<string, string> = {
    success: 'bg-teal-50 text-teal-700 ring-1 ring-teal-200',
    processing: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    failed: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
};

const statusLabels: Record<string, string> = {
    success: 'Thành công',
    processing: 'Đang xử lý',
    failed: 'Lỗi',
};

const transactionThemes: Record<string, { amount: string; badge: string; label: string; row: string }> = {
    recharge: {
        amount: 'text-sky-700',
        badge: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
        label: 'Nạp tiền',
        row: 'hover:bg-sky-50/40',
    },
    bonus: {
        amount: 'text-emerald-700',
        badge: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        label: 'Thưởng',
        row: 'hover:bg-emerald-50/40',
    },
    deduct: {
        amount: 'text-rose-700',
        badge: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        label: 'Trừ tiền',
        row: 'hover:bg-rose-50/40',
    },
    refund: {
        amount: 'text-amber-700',
        badge: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        label: 'Hoàn tiền',
        row: 'hover:bg-amber-50/40',
    },
};

const getStatusClass = (status: string): string => statusClasses[status] ?? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
const getStatusLabel = (status: string): string => statusLabels[status] ?? 'Không rõ';
const getTransactionTheme = (type: string) =>
    transactionThemes[type] ?? {
        amount: 'text-slate-700',
        badge: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        label: 'Giao dịch',
        row: 'hover:bg-slate-50',
    };

const formatCurrency = (amount: number): string => `${amount >= 0 ? '+' : '-'}${Math.abs(amount).toLocaleString('vi-VN')} đ`;
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-col gap-2 rounded-[10px] bg-teal-50/70 p-3 md:flex-row md:items-center">
            <input
                :value="props.filters.search"
                type="text"
                class="w-full rounded-[8px] border border-teal-100 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-teal-300 focus:ring-2 focus:ring-teal-100"
                placeholder="Tìm theo mã giao dịch hoặc nội dung..."
                @input="emit('update:search', ($event.target as HTMLInputElement).value)"
            />

            <select
                :value="props.filters.type"
                class="rounded-[8px] border border-teal-100 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-teal-300 focus:ring-2 focus:ring-teal-100 md:w-[220px]"
                @change="emit('update:type', ($event.target as HTMLSelectElement).value)"
            >
                <option v-for="option in typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-[10px] border border-teal-100">
            <div v-if="props.loading" class="bg-teal-50/70 px-4 py-10 text-center text-sm text-slate-500">Đang tải lịch sử dòng tiền...</div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-teal-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-teal-700">
                        <tr>
                            <th class="px-4 py-3">Mã giao dịch</th>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Nội dung</th>
                            <th class="px-4 py-3">Số tiền</th>
                            <th class="px-4 py-3">Số dư sau GD</th>
                            <th class="px-4 py-3">Trạng thái</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="item in props.transactions" :key="item.id" class="align-top transition-colors" :class="getTransactionTheme(item.type).row">
                            <td class="px-4 py-3">
                                <div class="space-y-1">
                                    <p class="font-semibold text-slate-900">{{ item.code }}</p>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="getTransactionTheme(item.type).badge">
                                        {{ getTransactionTheme(item.type).label }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ formatTime(item.time, 'H:i:s d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ item.content || '--' }}</td>
                            <td class="px-4 py-3 font-bold" :class="getTransactionTheme(item.type).amount">{{ formatCurrency(item.amount) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ item.balanceAfter.toLocaleString('vi-VN') }} đ</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="getStatusClass(item.status)">
                                    {{ getStatusLabel(item.status) }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="props.transactions.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Không có giao dịch nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>Hiển thị {{ props.transactions.length }} / {{ props.meta.total }} giao dịch</p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-[10px] border border-slate-200 bg-white px-2.5 py-1.5"
                    :class="props.meta.current_page <= 1 ? 'cursor-not-allowed text-slate-300' : 'text-slate-600'"
                    :disabled="props.meta.current_page <= 1"
                    @click="emit('change-page', props.meta.current_page - 1)"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <span class="rounded-[10px] bg-teal-600 px-3 py-1.5 font-semibold text-white">{{ props.meta.current_page }} / {{ props.meta.last_page }}</span>
                <button
                    type="button"
                    class="rounded-[10px] border border-slate-200 bg-white px-2.5 py-1.5"
                    :class="props.meta.current_page >= props.meta.last_page ? 'cursor-not-allowed text-slate-300' : 'text-slate-600'"
                    :disabled="props.meta.current_page >= props.meta.last_page"
                    @click="emit('change-page', props.meta.current_page + 1)"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
