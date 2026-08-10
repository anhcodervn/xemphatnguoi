<script setup lang="ts">
import Editor from '@/components/shared/Editor/index.vue';
import { adminCouponService } from '@/services/admin-coupon.service';
import type { CouponPayload } from '@/types/coupon.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { ArrowLeft, Save, Ticket } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const saving = ref(false);

const couponId = computed(() => route.params.coupont_id as string | undefined);
const isEditMode = computed(() => Boolean(couponId.value));

const form = reactive<CouponPayload>({
    code: '',
    name: '',
    description: null,
    type: 'percent',
    value: 10,
    min_order_amount: 0,
    max_discount_amount: null,
    max_usage: null,
    max_usage_per_user: 1,
    starts_at: null,
    expired_at: null,
    first_order_only: false,
    is_active: true,
    requirements: {
        note: '',
    },
});

const discountPreview = computed(() => {
    if (form.type === 'percent') {
        return `${form.value}%`;
    }

    return `${new Intl.NumberFormat('vi-VN').format(form.value)}đ`;
});

const loadCoupon = async (): Promise<void> => {
    if (!couponId.value) {
        return;
    }

    const coupon = await adminCouponService.get(couponId.value);

    form.code = coupon.code;
    form.name = coupon.name;
    form.description = coupon.description;
    form.type = coupon.type;
    form.value = Number(coupon.value);
    form.min_order_amount = Number(coupon.min_order_amount);
    form.max_discount_amount = coupon.max_discount_amount !== null ? Number(coupon.max_discount_amount) : null;
    form.max_usage = coupon.max_usage;
    form.max_usage_per_user = coupon.max_usage_per_user;
    form.starts_at = coupon.starts_at ? coupon.starts_at.slice(0, 16) : null;
    form.expired_at = coupon.expired_at ? coupon.expired_at.slice(0, 16) : null;
    form.first_order_only = coupon.first_order_only;
    form.is_active = coupon.is_active;
    form.requirements = {
        note: coupon.requirements?.note ?? '',
    };
};

const submit = async (): Promise<void> => {
    try {
        saving.value = true;

        const payload: CouponPayload = {
            ...form,
            requirements: {
                note: form.requirements.note?.trim() || null,
            },
            description: form.description?.trim() || null,
            starts_at: form.starts_at || null,
            expired_at: form.expired_at || null,
            max_discount_amount: form.max_discount_amount ? Number(form.max_discount_amount) : null,
            max_usage: form.max_usage ? Number(form.max_usage) : null,
            max_usage_per_user: form.max_usage_per_user ? Number(form.max_usage_per_user) : null,
        };

        if (isEditMode.value && couponId.value) {
            await adminCouponService.update(couponId.value, payload);
            handleSuccessResponse({ data: { status: true, message: 'Cập nhật coupon thành công' } });
        } else {
            await adminCouponService.create(payload);
            handleSuccessResponse({ data: { status: true, message: 'Tạo coupon thành công' } });
        }

        await router.push({ name: 'admin.couponts.index' });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    try {
        loading.value = true;
        await loadCoupon();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <RouterLink
                        :to="{ name: 'admin.couponts.index' }"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Quay lại danh sách
                    </RouterLink>
                    <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ isEditMode ? 'Cập nhật coupon' : 'Tạo coupon mới' }}</h1>
                    <p class="mt-1 text-sm text-slate-500">Thiết lập giảm giá, điều kiện sử dụng và phạm vi áp dụng.</p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-[10px] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                    :disabled="saving || loading"
                    @click="submit"
                >
                    <Save class="h-4 w-4" />
                    {{ saving ? 'Đang lưu...' : isEditMode ? 'Lưu cập nhật' : 'Tạo coupon' }}
                </button>
            </div>
        </section>

        <div v-if="loading" class="rounded-[10px] border border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm">
            Đang tải dữ liệu coupon...
        </div>

        <div v-else class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="space-y-4">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Thông tin cơ bản</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Code coupon</span>
                            <input
                                v-model="form.code"
                                type="text"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Tên coupon</span>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                        <div class="space-y-2 md:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Mô tả</span>
                            <Editor v-model:value="form.description" format="html" :height="280" :debounce="0" />
                        </div>
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Cấu hình giảm giá</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Loại giảm</span>
                            <select
                                v-model="form.type"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            >
                                <option value="percent">Giảm theo %</option>
                                <option value="fixed">Giảm theo tiền</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Giá trị</span>
                            <input
                                v-model.number="form.value"
                                type="number"
                                min="0"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Đơn tối thiểu</span>
                            <input
                                v-model.number="form.min_order_amount"
                                type="number"
                                min="0"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Giảm tối đa</span>
                            <input
                                v-model.number="form.max_discount_amount"
                                type="number"
                                min="0"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Điều kiện sử dụng</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Tổng lượt dùng tối đa</span>
                            <input
                                v-model.number="form.max_usage"
                                type="number"
                                min="1"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Lượt tối đa mỗi user</span>
                            <input
                                v-model.number="form.max_usage_per_user"
                                type="number"
                                min="1"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                        <label class="flex items-center gap-3 rounded-[10px] border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700">
                            <input v-model="form.first_order_only" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                            Chỉ áp dụng cho đơn đầu tiên
                        </label>
                        <label class="flex items-center gap-3 rounded-[10px] border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700">
                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                            Kích hoạt coupon
                        </label>
                        <label class="space-y-2 md:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Ghi chú điều kiện</span>
                            <textarea
                                v-model="form.requirements.note"
                                rows="3"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Thời gian áp dụng</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Bắt đầu</span>
                            <input
                                v-model="form.starts_at"
                                type="datetime-local"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Hết hạn</span>
                            <input
                                v-model="form.expired_at"
                                type="datetime-local"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            />
                        </label>
                    </div>
                </article>
            </section>

            <aside class="space-y-4">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <Ticket class="h-4 w-4 text-indigo-600" />
                        <h2 class="text-base font-semibold text-slate-950">Xem nhanh</h2>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Code</span>
                            <span class="font-semibold text-slate-950">{{ form.code || 'CPN...' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Giảm giá</span>
                            <span class="font-semibold text-indigo-600">{{ discountPreview }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Đơn tối thiểu</span>
                            <span class="font-semibold text-slate-950">{{ new Intl.NumberFormat('vi-VN').format(form.min_order_amount) }}đ</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Trạng thái</span>
                            <span class="font-semibold" :class="form.is_active ? 'text-emerald-600' : 'text-slate-500'">
                                {{ form.is_active ? 'Đang bật' : 'Tạm tắt' }}
                            </span>
                        </div>
                    </div>
                </article>
            </aside>
        </div>
    </div>
</template>
