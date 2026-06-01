<script setup lang="ts">
import { adminRechargeMethodService, type RechargeMethodPayload } from '@/services/admin-recharge-method.service';
import type { VietQrBank, VietQrBankListResponse } from '@/types/vietqr.type';
import { handleErrorResponse } from '@/utils/response';
import { ArrowLeft, Check, ChevronDown, CreditCard, Landmark, Link as LinkIcon, Search, Settings2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const defaultQrTemplate =
    'https://img.vietqr.io/image/{METHOD_CODE_UPPER}-{ACCOUNT_NUMBER}-compact.png?addInfo={ORDER_CODE}&amount={AMOUNT}';

const inputClass =
    'block h-10 w-full rounded-[8px] border border-slate-300 px-3 text-sm text-slate-900 outline-none transition focus:border-[#465fff]';
const textareaClass =
    'block w-full rounded-[8px] border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#465fff]';

const processing = ref(false);
const isLoadingBanks = ref(false);
const isBankDropdownOpen = ref(false);
const bankSearchQuery = ref('');
const metadataInput = ref('{}');
const qrTemplateInput = ref(defaultQrTemplate);
const bankSearchWrapper = ref<HTMLElement | null>(null);
const listBankSelect = ref<VietQrBank[]>([]);

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

const rechargeMethodId = computed(() => route.params.recharge_method_id as string | undefined);
const isEditing = computed(() => Boolean(rechargeMethodId.value));
const filteredBankSelect = computed(() => {
    const keyword = bankSearchQuery.value.trim().toLowerCase();

    if (!keyword) {
        return listBankSelect.value;
    }

    return listBankSelect.value.filter((item) => {
        return (
            item.shortName.toLowerCase().includes(keyword) ||
            item.short_name.toLowerCase().includes(keyword) ||
            item.bin.toLowerCase().includes(keyword) ||
            item.code.toLowerCase().includes(keyword) ||
            item.name.toLowerCase().includes(keyword)
        );
    });
});

const qrTemplatePreview = computed(() => {
    const template = qrTemplateInput.value.trim() || defaultQrTemplate;
    const methodCode = (form.value.code || 'vcb').trim();
    const orderCode = 'DEP240601ABC123';
    const amount = Number.isFinite(Number(form.value.min_amount)) ? String(Math.max(0, Number(form.value.min_amount))) : '100000';

    return template
        .replaceAll('{METHOD_CODE}', methodCode.toLowerCase())
        .replaceAll('{METHOD_CODE_UPPER}', methodCode.toUpperCase())
        .replaceAll('{ACCOUNT_NUMBER}', form.value.account_number?.trim() || '0123456789')
        .replaceAll('{ORDER_CODE}', encodeURIComponent(orderCode))
        .replaceAll('{TRANSFER_CONTENT}', encodeURIComponent(orderCode))
        .replaceAll('{AMOUNT}', amount);
});

const syncMetadataInput = (): void => {
    const metadata = { ...(form.value.metadata ?? {}) } as Record<string, unknown>;
    const qrTemplate = typeof metadata.qr_template === 'string' && metadata.qr_template.trim() !== '' ? metadata.qr_template.trim() : '';

    qrTemplateInput.value = qrTemplate || defaultQrTemplate;
    delete metadata.qr_template;
    metadataInput.value = JSON.stringify(metadata, null, 2);
};

const applyMetadataPayload = (): Record<string, unknown> => {
    const metadata = metadataInput.value.trim() ? (JSON.parse(metadataInput.value) as Record<string, unknown>) : {};
    const qrTemplate = qrTemplateInput.value.trim();

    if (qrTemplate !== '') {
        metadata.qr_template = qrTemplate;
    }

    return metadata;
};

const selectBank = (item: VietQrBank): void => {
    const bankDisplayName = item.shortName || item.short_name || item.name;

    form.value.code = item.code;
    form.value.name = bankDisplayName;
    form.value.bank_name = bankDisplayName;
    form.value.metadata = {
        ...(form.value.metadata ?? {}),
        vietqr_id: item.id,
        vietqr_code: item.code,
        vietqr_bin: item.bin,
        vietqr_name: item.name,
        transfer_supported: item.transferSupported,
        lookup_supported: item.lookupSupported,
        swift_code: item.swift_code,
    };

    bankSearchQuery.value = `${bankDisplayName} (${item.bin})`;
    isBankDropdownOpen.value = false;
    syncMetadataInput();
};

const openBankDropdown = async (): Promise<void> => {
    isBankDropdownOpen.value = true;
    await nextTick();
};

const closeBankDropdown = (): void => {
    isBankDropdownOpen.value = false;
};

const handleClickOutsideBankDropdown = (event: MouseEvent): void => {
    if (!bankSearchWrapper.value) {
        return;
    }

    const target = event.target;

    if (target instanceof Node && !bankSearchWrapper.value.contains(target)) {
        closeBankDropdown();
    }
};

const loadRechargeMethod = async (): Promise<void> => {
    if (!rechargeMethodId.value) {
        syncMetadataInput();
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

        bankSearchQuery.value = form.value.bank_name || form.value.name;
        syncMetadataInput();
    } catch (error) {
        handleErrorResponse(error);
        await router.push('/admin/recharge-methods');
    }
};

const submitForm = async (): Promise<void> => {
    try {
        form.value.metadata = applyMetadataPayload();
    } catch {
        await Swal.fire('Metadata không hợp lệ', 'Vui lòng nhập JSON hợp lệ cho metadata nâng cao.', 'warning');
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

const fetchListBank = async (): Promise<void> => {
    try {
        isLoadingBanks.value = true;

        const response = await fetch('https://api.vietqr.io/v2/banks');
        const payload = (await response.json()) as VietQrBankListResponse;

        if (!response.ok) {
            throw new Error(payload.desc || 'Không thể tải danh sách ngân hàng.');
        }

        listBankSelect.value = Array.isArray(payload.data) ? payload.data : [];

        if (form.value.bank_name) {
            bankSearchQuery.value = form.value.bank_name;
        }
    } catch (error) {
        console.error('Không thể tải danh sách ngân hàng VietQR.', error);
        await Swal.fire('Không tải được danh sách ngân hàng', 'Vui lòng thử lại sau.', 'warning');
    } finally {
        isLoadingBanks.value = false;
    }
};

onMounted(async () => {
    document.addEventListener('click', handleClickOutsideBankDropdown);
    await Promise.all([fetchListBank(), loadRechargeMethod()]);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutsideBankDropdown);
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
                        Cấu hình phương thức nạp, tài khoản nhận tiền và mẫu QR VietQR để frontend tự tạo mã thanh toán cho từng đơn nạp.
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
                                <input id="method-code" v-model="form.code" type="text" :class="inputClass" placeholder="vcb" />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-name">Tên hiển thị</label>
                                <input id="method-name" v-model="form.name" type="text" :class="inputClass" placeholder="Vietcombank" />
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
                                    Khi chọn ngân hàng, mã phương thức và tên hiển thị sẽ tự cập nhật theo dữ liệu VietQR.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div ref="bankSearchWrapper" class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-bank-name">Ngân hàng</label>
                                <div class="relative">
                                    <div class="relative">
                                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                        <input
                                            id="method-bank-name"
                                            v-model="bankSearchQuery"
                                            type="text"
                                            :class="`${inputClass} pl-9 pr-9`"
                                            :placeholder="isLoadingBanks ? 'Đang tải danh sách ngân hàng...' : 'Tìm theo tên, mã hoặc BIN'"
                                            :disabled="isLoadingBanks"
                                            autocomplete="off"
                                            @focus="openBankDropdown"
                                            @input="isBankDropdownOpen = true"
                                        />
                                        <button
                                            type="button"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                                            @click="isBankDropdownOpen = !isBankDropdownOpen"
                                        >
                                            <ChevronDown class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <div
                                        v-if="isBankDropdownOpen"
                                        class="absolute z-20 mt-2 max-h-72 w-full overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.12)]"
                                    >
                                        <div v-if="isLoadingBanks" class="px-3 py-3 text-sm text-slate-500">Đang tải danh sách ngân hàng...</div>
                                        <div v-else-if="filteredBankSelect.length === 0" class="px-3 py-3 text-sm text-slate-500">
                                            Không tìm thấy ngân hàng phù hợp.
                                        </div>
                                        <ul v-else class="max-h-72 overflow-y-auto py-1">
                                            <li v-for="item in filteredBankSelect" :key="item.bin">
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                                    @click="selectBank(item)"
                                                >
                                                    <div class="min-w-0">
                                                        <p class="truncate font-semibold text-slate-900">{{ item.shortName }} ({{ item.bin }})</p>
                                                        <p class="truncate text-xs text-slate-500">{{ item.name }}</p>
                                                    </div>
                                                    <Check v-if="form.code === item.code" class="h-4 w-4 shrink-0 text-[#465fff]" />
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
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

                    <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.045)]">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-cyan-50 text-cyan-600">
                                <LinkIcon class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h2 class="text-base font-black tracking-tight text-slate-950">QR VietQR theo template</h2>
                                <p class="text-[13px] text-slate-500">
                                    Frontend sẽ lấy template này để sinh QR động theo đơn nạp. Nếu để trống, hệ thống dùng mẫu mặc định.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700" for="method-qr-template">QR template URL</label>
                                <textarea
                                    id="method-qr-template"
                                    v-model="qrTemplateInput"
                                    rows="3"
                                    :class="textareaClass"
                                    :placeholder="defaultQrTemplate"
                                />
                            </div>

                            <div class="rounded-[8px] border border-slate-200 bg-slate-50 p-3.5 text-[13px] leading-6 text-slate-600">
                                <p class="font-semibold text-slate-900">Placeholder có thể dùng</p>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    <p><code>{METHOD_CODE}</code>: mã phương thức viết thường</p>
                                    <p><code>{METHOD_CODE_UPPER}</code>: mã phương thức viết hoa</p>
                                    <p><code>{ACCOUNT_NUMBER}</code>: số tài khoản nhận tiền</p>
                                    <p><code>{ORDER_CODE}</code>: mã đơn nạp</p>
                                    <p><code>{TRANSFER_CONTENT}</code>: nội dung chuyển khoản</p>
                                    <p><code>{AMOUNT}</code>: số tiền nạp</p>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700">Preview URL</label>
                                <div class="overflow-x-auto rounded-[8px] border border-slate-200 bg-slate-950 px-3 py-2.5 font-mono text-xs leading-6 text-slate-200">
                                    {{ qrTemplatePreview }}
                                </div>
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
                            Dữ liệu tài khoản nhận tiền được lưu trực tiếp trên phương thức nạp. Không lấy từ bank account của người dùng.
                        </p>

                        <div class="mt-4 rounded-[8px] border border-slate-200 bg-slate-50 p-3.5 text-[13px] leading-6 text-slate-600">
                            <p>- Chọn ngân hàng để tự điền mã phương thức và tên hiển thị.</p>
                            <p>- Có thể thay QR template mà không cần sửa code frontend.</p>
                            <p>- Mỗi phương thức nạp là một cấu hình nhận tiền độc lập.</p>
                        </div>
                    </article>

                    <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.045)]">
                        <h2 class="text-base font-black tracking-tight text-slate-950">Metadata nâng cao</h2>
                        <p class="mt-1 text-[13px] text-slate-500">Dùng cho các cờ cấu hình hoặc dữ liệu phụ ngoài QR template.</p>
                        <div class="mt-4">
                            <textarea id="method-metadata" v-model="metadataInput" rows="8" :class="textareaClass" placeholder='{"channel":"manual"}' />
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
