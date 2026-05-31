<script setup lang="ts">
import { adminRechargeMethodService, type RechargeMethodPayload } from '@/services/admin-recharge-method.service';
import { handleErrorResponse } from '@/utils/response';
import { ArrowLeft, CreditCard, Landmark, Settings2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const inputClass =
    'block h-10 w-full rounded-[8px] border border-slate-300 px-3 text-sm text-slate-900 outline-none transition focus:border-[#465fff]';
const textareaClass =
    'block w-full rounded-[8px] border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#465fff]';

const processing = ref(false);

const form = ref<RechargeMethodPayload>({
    code: '',
    name: '',
    description: '',
    badge_label: '',
    badge_type: 'manual',
    bank_name: '',
    account_number: '',
    account_name: '',
    min_amount: 10000,
    max_amount: 50000000,
    bonus_percentage: 0,
    sort_order: 0,
    is_active: true,
    bank_account_ids: [],
    metadata: {},
});

const metadataInput = ref('{}');

const rechargeMethodId = computed(() => route.params.recharge_method_id as string | undefined);
const isEditing = computed(() => Boolean(rechargeMethodId.value));

const loadRechargeMethod = async (): Promise<void> => {
    if (!rechargeMethodId.value) {
        return;
    }

    try {
        const data = await adminRechargeMethodService.get(rechargeMethodId.value);

        form.value = {
            code: data.code,
            name: data.name,
            description: data.description ?? '',
            badge_label: data.badge_label ?? '',
            badge_type: data.badge_type,
            bank_name: data.bank_name ?? '',
            account_number: data.account_number ?? '',
            account_name: data.account_name ?? '',
            min_amount: Number(data.min_amount),
            max_amount: Number(data.max_amount),
            bonus_percentage: data.bonus_percentage,
            sort_order: data.sort_order,
            is_active: data.is_active,
            bank_account_ids: data.bankAccounts.map((bankAccount) => bankAccount.id),
            metadata: data.metadata ?? {},
        };

        metadataInput.value = JSON.stringify(form.value.metadata, null, 2);
    } catch (error) {
        handleErrorResponse(error);
        await router.push('/admin/recharge-methods');
    }
};

const submitForm = async (): Promise<void> => {
    try {
        form.value.metadata = metadataInput.value.trim() ? JSON.parse(metadataInput.value) : {};
    } catch {
        await Swal.fire('Metadata không hợp lệ', 'Vui lòng nhập JSON hợp lệ cho metadata.', 'warning');
        return;
    }

    try {
        processing.value = true;

        const response = isEditing.value
            ? await adminRechargeMethodService.update(rechargeMethodId.value!, form.value)
            : await adminRechargeMethodService.create(form.value);

        await Swal.fire('Thành công', response.data.message, 'success');
        await router.push('/admin/recharge-methods');
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        processing.value = false;
    }
};

onMounted(async () => {
    await loadRechargeMethod();
});
</script>

<template>
    <div class="mx-auto w-full max-w-6xl space-y-4">
        <section
            class="overflow-hidden rounded-[10px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.08),_transparent_28%),linear-gradient(180deg,_rgba(255,255,255,0.98)_0%,_rgba(255,255,255,0.96)_100%)] px-4 py-4 shadow-[0_12px_30px_rgba(15,23,42,0.05)]"
        >
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#465fff]">Admin workspace</p>
                    <h1 class="mt-1.5 text-[24px] font-black tracking-tight text-slate-950">
                        {{ isEditing ? 'Cập nhật thẻ nhận tiền' : 'Tạo thẻ nhận tiền' }}
                    </h1>
                    <p class="mt-1.5 max-w-2xl text-[13px] leading-5 text-slate-500">
                        Màn này chỉ cấu hình thông tin tài khoản nhận tiền của hệ thống. Không có dữ liệu thẻ gắn riêng cho từng người dùng.
                    </p>
                </div>

                <RouterLink
                    to="/admin/recharge-methods"
                    class="inline-flex items-center gap-2 self-start rounded-[8px] border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Quay lại danh sách
                </RouterLink>
            </div>
        </section>

        <form class="space-y-4" @submit.prevent="submitForm">
            <section class="grid gap-4 xl:grid-cols-[1.25fr_0.95fr]">
                <div class="space-y-4">
                    <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.045)]">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-sky-50 text-sky-600">
                                <CreditCard class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h2 class="text-base font-black tracking-tight text-slate-950">Thông tin hiển thị</h2>
                                <p class="text-[13px] text-slate-500">Tên phương thức, mã định danh và mô tả hiển thị ở frontend.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-code">Mã phương thức</label>
                                <input id="method-code" v-model="form.code" type="text" :class="inputClass" placeholder="vietcombank-manual" />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-name">Tên hiển thị</label>
                                <input id="method-name" v-model="form.name" type="text" :class="inputClass" placeholder="Chuyển khoản Vietcombank" />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-type">Kiểu xử lý</label>
                                <select id="method-type" v-model="form.badge_type" :class="inputClass">
                                    <option value="manual">Thủ công</option>
                                    <option value="auto">Tự động</option>
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-badge">Nhãn hiển thị</label>
                                <input id="method-badge" v-model="form.badge_label" type="text" :class="inputClass" placeholder="Khuyến nghị" />
                            </div>

                            <div class="space-y-1.5 md:col-span-2">
                                <label class="text-sm font-semibold text-slate-700" for="method-description">Mô tả</label>
                                <textarea
                                    id="method-description"
                                    v-model="form.description"
                                    rows="3"
                                    :class="textareaClass"
                                    placeholder="Hiển thị hướng dẫn ngắn cho người dùng."
                                />
                            </div>
                        </div>
                    </article>

                    <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.045)]">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-emerald-50 text-emerald-600">
                                <Landmark class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h2 class="text-base font-black tracking-tight text-slate-950">Thông tin tài khoản nhận tiền</h2>
                                <p class="text-[13px] text-slate-500">
                                    Đây là phần chính của màn quản trị: ngân hàng, số tài khoản và chủ tài khoản.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-bank-name">Ngân hàng</label>
                                <input id="method-bank-name" v-model="form.bank_name" type="text" :class="inputClass" placeholder="Vietcombank" />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-account-number">Số tài khoản</label>
                                <input
                                    id="method-account-number"
                                    v-model="form.account_number"
                                    type="text"
                                    :class="inputClass"
                                    placeholder="0123456789"
                                />
                            </div>

                            <div class="space-y-1.5 md:col-span-2">
                                <label class="text-sm font-semibold text-slate-700" for="method-account-name">Chủ tài khoản</label>
                                <input
                                    id="method-account-name"
                                    v-model="form.account_name"
                                    type="text"
                                    :class="inputClass"
                                    placeholder="NGUYEN VAN A"
                                />
                            </div>
                        </div>
                    </article>
                </div>

                <div class="space-y-4">
                    <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.045)]">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-violet-50 text-violet-600">
                                <Settings2 class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h2 class="text-base font-black tracking-tight text-slate-950">Cấu hình nạp tiền</h2>
                                <p class="text-[13px] text-slate-500">Thiết lập hạn mức, thưởng và trạng thái hoạt động.</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label class="text-sm font-semibold text-slate-700" for="method-min-amount">Nạp tối thiểu</label>
                                    <input id="method-min-amount" v-model.number="form.min_amount" type="number" min="0" :class="inputClass" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-semibold text-slate-700" for="method-max-amount">Nạp tối đa</label>
                                    <input id="method-max-amount" v-model.number="form.max_amount" type="number" min="0" :class="inputClass" />
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label class="text-sm font-semibold text-slate-700" for="method-bonus">Phần trăm thưởng</label>
                                    <input
                                        id="method-bonus"
                                        v-model.number="form.bonus_percentage"
                                        type="number"
                                        min="0"
                                        max="100"
                                        :class="inputClass"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-semibold text-slate-700" for="method-sort-order">Thứ tự sắp xếp</label>
                                    <input id="method-sort-order" v-model.number="form.sort_order" type="number" min="0" :class="inputClass" />
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-status">Trạng thái</label>
                                <select id="method-status" v-model="form.is_active" :class="inputClass">
                                    <option :value="true">Hoạt động</option>
                                    <option :value="false">Tạm dừng</option>
                                </select>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.045)]">
                        <h2 class="text-base font-black tracking-tight text-slate-950">Ghi chú cấu hình</h2>
                        <p class="mt-1 text-[13px] text-slate-500">
                            Màn này lưu trực tiếp thông tin tài khoản nhận tiền của hệ thống vào phương thức nạp. Không lấy dữ liệu từ danh sách
                            bank account của người dùng.
                        </p>

                        <div class="mt-4 rounded-[8px] border border-slate-200 bg-slate-50 p-3.5 text-[13px] leading-6 text-slate-600">
                            <p>- Điền trực tiếp ngân hàng, số tài khoản và chủ tài khoản ở phần bên trên.</p>
                            <p>- Mỗi phương thức nạp là một cấu hình nhận tiền độc lập.</p>
                            <p>- Nếu cần thay đổi tài khoản nhận tiền, chỉ cần cập nhật lại phương thức nạp này.</p>
                        </div>
                    </article>

                    <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.045)]">
                        <h2 class="text-base font-black tracking-tight text-slate-950">Metadata nâng cao</h2>
                        <p class="mt-1 text-[13px] text-slate-500">Chỉ dùng khi backend cần thêm cờ cấu hình hoặc dữ liệu phụ.</p>
                        <div class="mt-4">
                            <textarea
                                id="method-metadata"
                                v-model="metadataInput"
                                rows="7"
                                :class="textareaClass"
                                placeholder='{"channel":"manual"}'
                            />
                        </div>
                    </article>
                </div>
            </section>

            <section
                class="flex flex-col-reverse gap-2.5 rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.045)] sm:flex-row sm:justify-end"
            >
                <RouterLink
                    to="/admin/recharge-methods"
                    class="inline-flex items-center justify-center rounded-[8px] border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                >
                    Hủy
                </RouterLink>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-[8px] bg-[#465fff] px-4 py-2 text-sm font-semibold text-white shadow-[0_8px_18px_rgba(70,95,255,0.2)] transition hover:bg-[#3c52e0] disabled:cursor-not-allowed disabled:bg-slate-400"
                    :disabled="processing"
                >
                    {{ processing ? 'Đang lưu...' : isEditing ? 'Cập nhật phương thức' : 'Tạo phương thức' }}
                </button>
            </section>
        </form>
    </div>
</template>
