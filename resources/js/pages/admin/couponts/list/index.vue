<script setup lang="ts">
import { adminCouponService } from '@/services/admin-coupon.service';
import type { CouponAvailability, CouponListResponse, CouponTypeModel } from '@/types/coupon.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { richTextToPlainText } from '@/utils/rich-text';
import { CalendarClock, Clock3, Plus, Search, Ticket, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const response = ref<CouponListResponse | null>(null);

const filters = reactive({
    search: '',
    type: '',
    availability: 'all' as CouponAvailability,
    per_page: 10,
    page: 1,
});

const coupons = computed(() => response.value?.coupons.data ?? []);
const meta = computed(() => response.value?.coupons.meta ?? { current_page: 1, last_page: 1, per_page: 10, total: 0 });
const summary = computed(() => response.value?.summary ?? { total: 0, active: 0, scheduled: 0, expired: 0, total_used: 0 });

const formatCurrency = (value: number | string | null): string => {
    return `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))}đ`;
};

const couponValueLabel = (coupon: CouponTypeModel): string => {
    if (coupon.type === 'percent') {
        return `${coupon.value}%`;
    }

    return formatCurrency(coupon.value);
};

const statusLabel = (coupon: CouponTypeModel): string => {
    if (!coupon.is_active) {
        return 'Tạm tắt';
    }

    if (coupon.starts_at && new Date(coupon.starts_at).getTime() > Date.now()) {
        return 'Chờ áp dụng';
    }

    if (coupon.expired_at && new Date(coupon.expired_at).getTime() <= Date.now()) {
        return 'Hết hạn';
    }

    return 'Đang hoạt động';
};

const statusClass = (coupon: CouponTypeModel): string => {
    if (!coupon.is_active) {
        return 'bg-slate-100 text-slate-600';
    }

    if (coupon.starts_at && new Date(coupon.starts_at).getTime() > Date.now()) {
        return 'bg-amber-100 text-amber-700';
    }

    if (coupon.expired_at && new Date(coupon.expired_at).getTime() <= Date.now()) {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-emerald-100 text-emerald-700';
};

const loadCoupons = async (): Promise<void> => {
    try {
        loading.value = true;
        response.value = await adminCouponService.list(filters);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const changePage = async (page: number): Promise<void> => {
    if (page < 1 || page > meta.value.last_page || page === filters.page) {
        return;
    }

    filters.page = page;
    await loadCoupons();
};

const removeCoupon = async (coupon: CouponTypeModel): Promise<void> => {
    if (!window.confirm(`Xóa coupon ${coupon.code}?`)) {
        return;
    }

    try {
        await adminCouponService.remove(coupon.id);
        handleSuccessResponse({ data: { status: true, message: 'Xóa coupon thành công' } });

        if (coupons.value.length === 1 && filters.page > 1) {
            filters.page -= 1;
        }

        await loadCoupons();
    } catch (error) {
        handleErrorResponse(error);
    }
};

watch(
    () => [filters.type, filters.availability, filters.per_page],
    async () => {
        filters.page = 1;
        await loadCoupons();
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
                if (value !== filters.search) {
                    return;
                }

                await loadCoupons();
            },
            value.trim() === '' ? 0 : 300,
        );
    },
);

onMounted(loadCoupons);
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-500">Admin Workspace</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-950">Quản lý coupon</h1>
                    <p class="mt-1 text-sm text-slate-500">Theo dõi mã giảm giá, điều kiện sử dụng và lịch sử thao tác.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <RouterLink
                        :to="{ name: 'admin.couponts.history' }"
                        class="inline-flex items-center gap-2 rounded-[10px] border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <Clock3 class="h-4 w-4" />
                        Lịch sử
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'admin.couponts.create' }"
                        class="inline-flex items-center gap-2 rounded-[10px] bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        <Plus class="h-4 w-4" />
                        Tạo coupon
                    </RouterLink>
                </div>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Tổng coupon</p>
                <p class="mt-2 text-2xl font-bold text-slate-950">{{ summary.total }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Đang hoạt động</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">{{ summary.active }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Chờ áp dụng</p>
                <p class="mt-2 text-2xl font-bold text-amber-600">{{ summary.scheduled }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Hết hạn</p>
                <p class="mt-2 text-2xl font-bold text-rose-600">{{ summary.expired }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Lượt đã dùng</p>
                <p class="mt-2 text-2xl font-bold text-indigo-600">{{ summary.total_used }}</p>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_180px_220px_120px]">
                <label class="relative block">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Tìm theo code, tên coupon..."
                        class="w-full rounded-[10px] border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-indigo-400"
                    />
                </label>

                <select
                    v-model="filters.type"
                    class="rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-400"
                >
                    <option value="">Tất cả loại</option>
                    <option value="fixed">Giảm tiền</option>
                    <option value="percent">Giảm %</option>
                </select>

                <select
                    v-model="filters.availability"
                    class="rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-400"
                >
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active">Đang hoạt động</option>
                    <option value="scheduled">Chờ áp dụng</option>
                    <option value="expired">Hết hạn</option>
                    <option value="inactive">Tạm tắt</option>
                </select>

                <select
                    v-model="filters.per_page"
                    class="rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-400"
                >
                    <option :value="10">10 / trang</option>
                    <option :value="20">20 / trang</option>
                    <option :value="50">50 / trang</option>
                </select>
            </div>
        </section>

        <section class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Danh sách coupon</h2>
                    <p class="text-sm text-slate-500">{{ meta.total }} coupon</p>
                </div>
                <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                    Trang {{ meta.current_page }}/{{ meta.last_page }}
                </div>
            </div>

            <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">Đang tải dữ liệu coupon...</div>

            <div v-else-if="coupons.length === 0" class="px-4 py-12 text-center">
                <Ticket class="mx-auto h-10 w-10 text-slate-300" />
                <p class="mt-3 text-sm font-semibold text-slate-900">Chưa có coupon</p>
                <p class="mt-1 text-xs text-slate-500">Tạo coupon đầu tiên để bắt đầu quản lý.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Coupon</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Giảm giá</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Điều kiện</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Sử dụng</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Lịch</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Trạng thái</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="coupon in coupons" :key="coupon.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 align-top">
                                <p class="text-sm font-semibold text-slate-950">{{ coupon.code }}</p>
                                <p class="mt-1 text-sm text-slate-700">{{ coupon.name }}</p>
                                <p class="mt-1 line-clamp-2 text-xs text-slate-500">
                                    {{ richTextToPlainText(coupon.description, 'Không có mô tả') }}
                                </p>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <p class="text-sm font-semibold text-indigo-600">{{ couponValueLabel(coupon) }}</p>
                                <p class="mt-1 text-xs text-slate-500">Đơn tối thiểu: {{ formatCurrency(coupon.min_order_amount) }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Giảm tối đa: {{ coupon.max_discount_amount ? formatCurrency(coupon.max_discount_amount) : 'Không giới hạn' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-slate-600">
                                <p>Dùng tối đa/user: {{ coupon.max_usage_per_user ?? 'Không giới hạn' }}</p>
                                <p class="mt-1">Chỉ đơn đầu: {{ coupon.first_order_only ? 'Có' : 'Không' }}</p>
                                <p class="mt-1"></p>
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-slate-600">
                                <p>{{ coupon.used_count }} / {{ coupon.max_usage ?? '∞' }}</p>
                                <p class="mt-1">Log: {{ coupon.logs_count ?? 0 }}</p>
                                <p v-if="coupon.usage_percent !== null && coupon.usage_percent !== undefined" class="mt-1">
                                    {{ coupon.usage_percent }}%
                                </p>
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-slate-600">
                                <p class="inline-flex items-center gap-1">
                                    <CalendarClock class="h-3.5 w-3.5" />
                                    {{ coupon.starts_at || 'Áp dụng ngay' }}
                                </p>
                                <p class="mt-1">Hết hạn: {{ coupon.expired_at || 'Không giới hạn' }}</p>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(coupon)">
                                    {{ statusLabel(coupon) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-[10px] border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        @click="router.push({ name: 'admin.couponts.edit', params: { coupont_id: coupon.id } })"
                                    >
                                        Sửa
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-[10px] border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                                        @click="removeCoupon(coupon)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm">
                <p class="text-slate-500">Hiển thị trang {{ meta.current_page }} / {{ meta.last_page }}</p>
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
