<script setup lang="ts">
import { adminCouponService } from '@/services/admin-coupon.service';
import type { CouponLogModel } from '@/types/coupon.type';
import { handleErrorResponse } from '@/utils/response';
import { History, Search, Ticket } from 'lucide-vue-next';
import { onMounted, reactive, ref, watch } from 'vue';

const loading = ref(false);
const logs = ref<CouponLogModel[]>([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

const filters = reactive({
    search: '',
    status: '',
    action: '',
    per_page: 10,
    page: 1,
});

const loadLogs = async (): Promise<void> => {
    try {
        loading.value = true;
        const response = await adminCouponService.logs(filters);
        logs.value = response.logs.data;
        meta.value = response.logs.meta;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const statusClass = (status: string): string => {
    if (status === 'success') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (status === 'failed') {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-slate-100 text-slate-600';
};

const changePage = async (page: number): Promise<void> => {
    if (page < 1 || page > meta.value.last_page || page === filters.page) {
        return;
    }

    filters.page = page;
    await loadLogs();
};

watch(
    () => [filters.status, filters.action, filters.per_page],
    async () => {
        filters.page = 1;
        await loadLogs();
    },
);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(
    () => filters.search,
    (value) => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(
            async () => {
                filters.page = 1;
                await loadLogs();
            },
            value.trim() === '' ? 0 : 300,
        );
    },
);

onMounted(loadLogs);
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-[10px] bg-indigo-50 text-indigo-600">
                    <History class="h-5 w-5" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">Lịch sử coupon</h1>
                    <p class="mt-1 text-sm text-slate-500">Theo dõi thao tác tạo, cập nhật, xóa và log áp dụng coupon.</p>
                </div>
            </div>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_180px_180px_120px]">
                <label class="relative block">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Tìm theo coupon, user, order..."
                        class="w-full rounded-[10px] border border-slate-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-indigo-400"
                    />
                </label>

                <select
                    v-model="filters.status"
                    class="rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                >
                    <option value="">Tất cả trạng thái</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                    <option value="info">Info</option>
                </select>

                <input
                    v-model="filters.action"
                    type="text"
                    placeholder="Action: created, updated..."
                    class="rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                />

                <select
                    v-model="filters.per_page"
                    class="rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                >
                    <option :value="10">10 / trang</option>
                    <option :value="20">20 / trang</option>
                    <option :value="50">50 / trang</option>
                </select>
            </div>
        </section>

        <section class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-base font-semibold text-slate-950">Coupon logs</h2>
                <p class="text-sm text-slate-500">{{ meta.total }} bản ghi</p>
            </div>

            <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">Đang tải log coupon...</div>

            <div v-else-if="logs.length === 0" class="px-4 py-12 text-center">
                <Ticket class="mx-auto h-10 w-10 text-slate-300" />
                <p class="mt-3 text-sm font-semibold text-slate-900">Chưa có log coupon</p>
                <p class="mt-1 text-xs text-slate-500">Các thao tác coupon sẽ xuất hiện tại đây.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Hành động</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Coupon</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">User/Admin</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Chi tiết</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(log.status)">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm">
                                <p class="font-semibold text-slate-950">{{ log.coupon?.code || '-' }}</p>
                                <p class="mt-1 text-slate-500">{{ log.coupon?.name || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-slate-600">
                                <p>User: {{ log.user?.email || '-' }}</p>
                                <p class="mt-1">Admin: {{ log.admin?.email || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-slate-600">
                                <p>{{ log.note || '-' }}</p>
                                <p class="mt-1">Order: {{ log.package_order?.order_code || '-' }}</p>
                                <p class="mt-1">Discount: {{ log.discount_amount || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-slate-500">{{ log.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm">
                <p class="text-slate-500">Trang {{ meta.current_page }} / {{ meta.last_page }}</p>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-[10px] border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="meta.current_page <= 1"
                        @click="changePage(meta.current_page - 1)"
                    >
                        Trước
                    </button>
                    <button
                        type="button"
                        class="rounded-[10px] border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="changePage(meta.current_page + 1)"
                    >
                        Sau
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
