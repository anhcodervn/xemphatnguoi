<script setup lang="ts">
import { adminBankService } from '@/services/admin-bank.service';
import type { AdminBankPayload } from '@/types/admin-bank.type';
import type { VietQrBank, VietQrBankListResponse } from '@/types/vietqr.type';
import { handleErrorResponse } from '@/utils/response';
import { ArrowLeft, Check, ChevronDown, Landmark, Save, Search } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const processing = ref(false);
const isLoadingBanks = ref(false);
const isBankDropdownOpen = ref(false);
const bankSearchQuery = ref('');
const bankSearchWrapper = ref<HTMLElement | null>(null);
const vietQrBanks = ref<VietQrBank[]>([]);

const inputClass =
    'block h-10 w-full rounded-[8px] border border-slate-300 px-3 text-sm text-slate-900 outline-none transition focus:border-[#465fff]';
const textareaClass =
    'block w-full rounded-[8px] border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#465fff]';

const form = ref<AdminBankPayload>({
    code: '',
    name: '',
    short_name: '',
    logo: '',
    bg_color: '#FFFFFF',
    is_active: true,
    sort_order: 0,
    limit_request_per_minute: 6,
    metadata: {},
});

const metadataInput = ref('{}');
const bankId = computed(() => route.params.bank_id as string | undefined);
const isEditing = computed(() => Boolean(bankId.value));
const filteredBanks = computed(() => {
    const keyword = bankSearchQuery.value.trim().toLowerCase();

    if (!keyword) {
        return vietQrBanks.value;
    }

    return vietQrBanks.value.filter((item) => {
        return (
            item.shortName.toLowerCase().includes(keyword) ||
            item.bin.toLowerCase().includes(keyword) ||
            item.code.toLowerCase().includes(keyword) ||
            item.name.toLowerCase().includes(keyword)
        );
    });
});

const fetchVietQrBanks = async (): Promise<void> => {
    try {
        isLoadingBanks.value = true;

        const response = await fetch('https://api.vietqr.io/v2/banks');
        const payload = (await response.json()) as VietQrBankListResponse;

        if (!response.ok) {
            throw new Error(payload.desc || 'Không thể tải danh sách ngân hàng.');
        }

        vietQrBanks.value = Array.isArray(payload.data) ? payload.data : [];
    } catch (error) {
        console.error('Không thể tải danh sách ngân hàng VietQR.', error);
        await Swal.fire('Không tải được danh sách ngân hàng', 'Vui lòng thử lại sau.', 'warning');
    } finally {
        isLoadingBanks.value = false;
    }
};

const syncMetadataInput = (): void => {
    metadataInput.value = JSON.stringify(form.value.metadata, null, 2);
};

const selectVietQrBank = (item: VietQrBank): void => {
    form.value.code = item.code.toLowerCase();
    form.value.name = item.name;
    form.value.short_name = item.shortName;
    form.value.logo = item.logo;
    form.value.metadata = {
        ...(form.value.metadata ?? {}),
        vietqr_id: item.id,
        vietqr_code: item.code,
        vietqr_bin: item.bin,
        transfer_supported: item.transferSupported,
        lookup_supported: item.lookupSupported,
        swift_code: item.swift_code,
    };
    bankSearchQuery.value = `${item.shortName} (${item.bin})`;
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

const loadBank = async (): Promise<void> => {
    if (!bankId.value) {
        return;
    }

    try {
        const bank = await adminBankService.get(bankId.value);
        form.value = {
            code: bank.code,
            name: bank.name,
            short_name: bank.short_name ?? '',
            logo: bank.logo ?? '',
            bg_color: bank.bg_color ?? '#FFFFFF',
            is_active: bank.is_active,
            sort_order: bank.sort_order,
            limit_request_per_minute: bank.limit_request_per_minute,
            metadata: bank.metadata ?? {},
        };
        metadataInput.value = JSON.stringify(form.value.metadata, null, 2);

        const label = [form.value.short_name, form.value.metadata?.vietqr_bin]
            .filter((value): value is string => typeof value === 'string' && value.trim() !== '')
            .join(' ');

        bankSearchQuery.value = label || form.value.name;
    } catch (error) {
        handleErrorResponse(error);
        await router.push('/admin/banks');
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
            ? await adminBankService.update(bankId.value!, form.value)
            : await adminBankService.create(form.value);

        await Swal.fire('Thành công', response.data.message, 'success');
        await router.push('/admin/banks');
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        processing.value = false;
    }
};

onMounted(async () => {
    document.addEventListener('click', handleClickOutsideBankDropdown);
    await Promise.all([fetchVietQrBanks(), loadBank()]);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutsideBankDropdown);
});
</script>

<template>
    <div class="mx-auto w-full max-w-5xl space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#465fff]">Admin workspace</p>
                    <h1 class="mt-1.5 text-[24px] font-black tracking-tight text-slate-950">
                        {{ isEditing ? 'Cập nhật bank' : 'Thêm bank mới' }}
                    </h1>
                    <p class="mt-1.5 max-w-2xl text-[13px] leading-5 text-slate-500">
                        Quản lý bảng master data ngân hàng để dùng cho sync giao dịch, hiển thị UI và giới hạn request mỗi phút.
                    </p>
                </div>

                <RouterLink
                    to="/admin/banks"
                    class="inline-flex items-center gap-2 self-start rounded-[8px] border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Quay lại danh sách
                </RouterLink>
            </div>
        </section>

        <form class="space-y-4" @submit.prevent="submitForm">
            <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-[9px] bg-sky-50 text-sky-600">
                        <Landmark class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <h2 class="text-base font-black tracking-tight text-slate-950">Thông tin bank</h2>
                        <p class="text-[13px] text-slate-500">Chọn nhanh từ VietQR hoặc nhập thủ công mã, tên hiển thị và giới hạn request/phút.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div ref="bankSearchWrapper" class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-semibold text-slate-700" for="vietqr-bank-search">Ngân hàng từ VietQR</label>
                        <div class="relative">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    id="vietqr-bank-search"
                                    v-model="bankSearchQuery"
                                    type="text"
                                    :class="`${inputClass} pl-9 pr-9`"
                                    :placeholder="isLoadingBanks ? 'Đang tải danh sách ngân hàng...' : 'Tìm ngân hàng theo tên, mã hoặc BIN'"
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
                                <div v-else-if="filteredBanks.length === 0" class="px-3 py-3 text-sm text-slate-500">
                                    Không tìm thấy ngân hàng phù hợp.
                                </div>
                                <ul v-else class="max-h-72 overflow-y-auto py-1">
                                    <li v-for="item in filteredBanks" :key="item.id">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                            @click="selectVietQrBank(item)"
                                        >
                                            <div class="min-w-0">
                                                <p class="truncate font-semibold text-slate-900">{{ item.shortName }} ({{ item.bin }})</p>
                                                <p class="truncate text-xs text-slate-500">{{ item.name }}</p>
                                            </div>
                                            <Check
                                                v-if="form.code === item.code.toLowerCase()"
                                                class="h-4 w-4 shrink-0 text-[#465fff]"
                                            />
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-slate-700" for="bank-code">Code</label>
                        <input id="bank-code" v-model="form.code" type="text" :class="inputClass" placeholder="mb" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-slate-700" for="bank-name">Tên bank</label>
                        <input id="bank-name" v-model="form.name" type="text" :class="inputClass" placeholder="Ngân hàng TMCP Quân đội" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-slate-700" for="bank-short-name">Short name</label>
                        <input id="bank-short-name" v-model="form.short_name" type="text" :class="inputClass" placeholder="MBBank" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-slate-700" for="bank-logo">Logo URL</label>
                        <input id="bank-logo" v-model="form.logo" type="text" :class="inputClass" placeholder="https://cdn.vietqr.io/img/MB.png" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-slate-700" for="bank-bg-color">Màu nền</label>
                        <input id="bank-bg-color" v-model="form.bg_color" type="text" :class="inputClass" placeholder="#FFFFFF" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-slate-700" for="bank-limit">Limit request/phút</label>
                        <input id="bank-limit" v-model.number="form.limit_request_per_minute" type="number" min="1" max="120" :class="inputClass" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-slate-700" for="bank-sort-order">Thứ tự sắp xếp</label>
                        <input id="bank-sort-order" v-model.number="form.sort_order" type="number" min="0" :class="inputClass" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-slate-700" for="bank-status">Trạng thái</label>
                        <select id="bank-status" v-model="form.is_active" :class="inputClass">
                            <option :value="true">Hoạt động</option>
                            <option :value="false">Tạm dừng</option>
                        </select>
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-semibold text-slate-700" for="bank-metadata">Metadata</label>
                        <textarea
                            id="bank-metadata"
                            v-model="metadataInput"
                            rows="6"
                            :class="textareaClass"
                            placeholder='{"provider":"mb","vietqr_bin":"970422"}'
                        />
                    </div>
                </div>
            </section>

            <section class="flex flex-col-reverse gap-2.5 rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:justify-end">
                <RouterLink
                    to="/admin/banks"
                    class="inline-flex items-center justify-center rounded-[8px] border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                >
                    Hủy
                </RouterLink>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-[8px] bg-[#465fff] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#3c52e0] disabled:cursor-not-allowed disabled:bg-slate-400"
                    :disabled="processing"
                >
                    <Save class="h-4 w-4" />
                    {{ processing ? 'Đang lưu...' : isEditing ? 'Cập nhật bank' : 'Tạo bank' }}
                </button>
            </section>
        </form>
    </div>
</template>
