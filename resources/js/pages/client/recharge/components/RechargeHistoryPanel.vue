<script setup lang="ts">
import type { RechargeHistoryMetaType, RechargeOrderType, RechargeStatsType, RechargeStatus } from '@/types/recharge.type';
import { CalendarRange, ChevronLeft, ChevronRight, CreditCard, Eye, Landmark, Plus, QrCode, Search, Smartphone } from 'lucide-vue-next';

defineProps<{
    orders: RechargeOrderType[];
    meta: RechargeHistoryMetaType;
    stats: RechargeStatsType;
    search: string;
    status: string;
    loading: boolean;
}>();

const emit = defineEmits<{
    (event: 'update:search', value: string): void;
    (event: 'update:status', value: string): void;
    (event: 'go-create'): void;
    (event: 'view-order', order: RechargeOrderType): void;
}>();

const iconMap: Record<string, unknown> = {
    banking: Landmark,
    momo: Smartphone,
    vnpay: QrCode,
    card: CreditCard,
};

const backgroundMap: Record<string, string> = {
    banking: 'linear-gradient(135deg,#34d399,#10b981)',
    momo: 'linear-gradient(135deg,#ec4899,#db2777)',
    vnpay: 'linear-gradient(135deg,#fb923c,#f97316)',
    card: 'linear-gradient(135deg,#3b82f6,#2563eb)',
};

const formatCurrency = (value: number | string): string => {
    return `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))}đ`;
};

const statusLabel = (status: RechargeStatus): string => {
    const labels: Record<RechargeStatus, string> = {
        pending: 'Chờ xử lý',
        processing: 'Đang xử lý',
        paid: 'Thành công',
        failed: 'Thất bại',
        cancelled: 'Đã hủy',
        expired: 'Hết hạn',
    };

    return labels[status];
};

const statusClass = (status: RechargeStatus): string => {
    const classes: Record<RechargeStatus, string> = {
        pending: 'bg-amber-100 text-amber-700',
        processing: 'bg-sky-100 text-sky-700',
        paid: 'bg-emerald-100 text-emerald-700',
        failed: 'bg-rose-100 text-rose-700',
        cancelled: 'bg-slate-200 text-slate-700',
        expired: 'bg-slate-200 text-slate-700',
    };

    return `inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${classes[status]}`;
};

const canContinuePayment = (status: RechargeStatus): boolean => {
    return ['pending', 'processing'].includes(status);
};
</script>

<template>
    <section class="space-y-3">
        <div class="grid gap-2 sm:grid-cols-3">
            <article class="rounded-[10px] border border-slate-200/70 bg-white px-3 py-3 shadow-sm">
                <p class="text-xs text-slate-500">Tổng đã nạp</p>
                <p class="mt-1 text-lg font-bold text-emerald-600">{{ formatCurrency(stats.total_recharge) }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200/70 bg-white px-3 py-3 shadow-sm">
                <p class="text-xs text-slate-500">Số lần nạp</p>
                <p class="mt-1 text-lg font-bold text-indigo-600">{{ stats.total_orders }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200/70 bg-white px-3 py-3 shadow-sm">
                <p class="text-xs text-slate-500">Bonus đã nhận</p>
                <p class="mt-1 text-lg font-bold text-blue-600">{{ formatCurrency(stats.total_bonus) }}</p>
            </article>
        </div>

        <article class="rounded-[10px] border border-slate-200/70 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
                <div class="grid gap-2 sm:grid-cols-2 xl:flex xl:flex-1 xl:flex-wrap">
                    <label class="relative block sm:col-span-2 xl:min-w-[220px] xl:flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            :value="search"
                            type="text"
                            placeholder="Tìm mã giao dịch..."
                            class="w-full rounded-[10px] border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-50"
                            @input="emit('update:search', ($event.target as HTMLInputElement).value)"
                        />
                    </label>

                    <label class="relative block xl:min-w-[200px]">
                        <CalendarRange class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            type="text"
                            value="Lọc theo ngày"
                            readonly
                            class="w-full rounded-[10px] border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-400 outline-none"
                        />
                    </label>

                    <select
                        :value="status"
                        class="rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-50 xl:min-w-[180px]"
                        @change="emit('update:status', ($event.target as HTMLSelectElement).value)"
                    >
                        <option value="all">Tất cả trạng thái</option>
                        <option value="pending">Chờ xử lý</option>
                        <option value="processing">Đang xử lý</option>
                        <option value="paid">Thành công</option>
                        <option value="failed">Thất bại</option>
                        <option value="cancelled">Đã hủy</option>
                        <option value="expired">Hết hạn</option>
                    </select>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-[10px] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    @click="emit('go-create')"
                >
                    <Plus class="h-4 w-4" />
                    Tạo yêu cầu
                </button>
            </div>

            <div v-if="loading" class="mt-3 rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                Đang tải lịch sử nạp tiền...
            </div>

            <div v-else-if="orders.length === 0" class="mt-3 rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                <p class="text-sm font-semibold text-slate-900">Chưa có giao dịch</p>
                <p class="mt-1 text-xs text-slate-500">Tạo yêu cầu nạp đầu tiên để bắt đầu.</p>
                <button
                    type="button"
                    class="mt-3 inline-flex items-center gap-2 rounded-[10px] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    @click="emit('go-create')"
                >
                    <Plus class="h-4 w-4" />
                    Tạo yêu cầu
                </button>
            </div>

            <div v-else class="mt-3 space-y-3">
                <div class="grid gap-2 md:hidden">
                    <article v-for="order in orders" :key="order.id" class="rounded-[10px] border border-slate-200 bg-white p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-950">{{ order.order_code }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ order.created_at }}</p>
                            </div>
                            <span :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span>
                        </div>

                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-[10px] text-white"
                                    :style="{ background: backgroundMap[order.method] ?? backgroundMap.banking }"
                                >
                                    <component :is="iconMap[order.method] ?? Landmark" class="h-3.5 w-3.5" />
                                </div>
                                <span class="text-slate-700">{{ order.method_label }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Số tiền</span>
                                <span class="font-semibold text-slate-950">{{ formatCurrency(order.amount) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Bonus</span>
                                <span class="font-semibold text-emerald-600">+{{ formatCurrency(order.bonus_amount) }}</span>
                            </div>
                            <button
                                v-if="canContinuePayment(order.status)"
                                type="button"
                                class="inline-flex items-center justify-center rounded-[10px] border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50"
                                @click="emit('view-order', order)"
                            >
                                Tiếp tục thanh toán
                            </button>
                        </div>
                    </article>
                </div>

                <div class="hidden overflow-hidden rounded-[10px] border border-slate-200 md:block">
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Mã GD</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                        Thời gian
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                        Phương thức
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                        Số tiền
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Bonus</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                        Trạng thái
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Xem</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="order in orders" :key="order.id" class="hover:bg-slate-50">
                                    <td class="px-3 py-3 text-sm font-semibold text-slate-950">{{ order.order_code }}</td>
                                    <td class="px-3 py-3 text-sm text-slate-500">{{ order.created_at }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-[10px] text-white"
                                                :style="{ background: backgroundMap[order.method] ?? backgroundMap.banking }"
                                            >
                                                <component :is="iconMap[order.method] ?? Landmark" class="h-3.5 w-3.5" />
                                            </div>
                                            <span class="text-sm text-slate-700">{{ order.method_label }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-sm font-semibold text-slate-950">{{ formatCurrency(order.amount) }}</td>
                                    <td class="px-3 py-3 text-sm font-semibold text-emerald-600">+{{ formatCurrency(order.bonus_amount) }}</td>
                                    <td class="px-3 py-3">
                                        <span :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 hover:text-indigo-600"
                                            :disabled="!canContinuePayment(order.status)"
                                            :class="canContinuePayment(order.status) ? '' : 'cursor-not-allowed opacity-40'"
                                            @click="emit('view-order', order)"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>Hiển thị {{ orders.length }} / {{ meta.total }} giao dịch</p>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-[10px] border border-slate-200 bg-white px-2.5 py-1.5 text-slate-400">
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                        <button type="button" class="rounded-[10px] bg-indigo-600 px-3 py-1.5 font-semibold text-white">
                            {{ meta.current_page }}
                        </button>
                        <button type="button" class="rounded-[10px] border border-slate-200 bg-white px-2.5 py-1.5 text-slate-600">
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </article>
    </section>
</template>
