<template>
    <div class="space-y-3 pb-3">
        <section class="rounded-[10px] border border-white/70 bg-white/75 px-4 py-3 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.16)] backdrop-blur">
            <RouterLink
                :to="{ name: 'client.bank-manager' }"
                class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition hover:text-blue-700"
            >
                <ArrowLeft class="h-4 w-4" />
                Quay lại quản lý thẻ
            </RouterLink>

            <h1 class="mt-2.5 text-[1.75rem] font-black tracking-[-0.04em] text-slate-900">
                {{ pageTitle }}
            </h1>
            <p class="mt-0.5 text-xs text-slate-500">
                {{ pageDescription }}
            </p>
        </section>

        <section class="grid gap-3 xl:grid-cols-[1.12fr_0.88fr]">
            <form
                class="space-y-3 rounded-[10px] border border-slate-200/80 bg-white p-3.5 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]"
                @submit.prevent="submitForm"
            >
                <div v-if="isLoadingAccount" class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500">
                    Đang tải thông tin tài khoản ngân hàng...
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div class="space-y-1.5 md:col-span-2">
                        <span class="text-xs font-semibold text-slate-600">Chọn ngân hàng</span>
                        <Listbox
                            :model-value="form.bank_code"
                            :disabled="isLoadingBanks || processing || isLoadingAccount"
                            @update:model-value="(value) => (form.bank_code = value)"
                        >
                            <div class="relative">
                                <ListboxButton
                                    class="relative w-full rounded-[8px] border border-slate-200 bg-white py-2 pl-3 pr-10 text-left outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-50"
                                >
                                    <div v-if="selectedBank" class="flex min-w-0 items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-700"
                                            :style="{ backgroundColor: selectedBank.bg_color || '#EFF6FF' }"
                                        >
                                            <img
                                                v-if="selectedBank.logo"
                                                :src="selectedBank.logo"
                                                :alt="selectedBank.short_name || selectedBank.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <span v-else class="text-white">{{ selectedBankInitials }}</span>
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">
                                                {{ selectedBank.name }}
                                            </p>
                                            <p class="truncate text-xs text-slate-500">
                                                {{ selectedBank.short_name || selectedBank.code.toUpperCase() }}
                                            </p>
                                        </div>
                                    </div>

                                    <span v-else class="block truncate pr-2 text-sm text-slate-500">
                                        {{ isLoadingBanks ? 'Đang tải danh sách ngân hàng...' : 'Chọn ngân hàng cần liên kết' }}
                                    </span>

                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                        <ChevronsUpDown class="h-4 w-4" />
                                    </span>
                                </ListboxButton>

                                <transition
                                    leave-active-class="transition duration-100 ease-in"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <ListboxOptions
                                        class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-[10px] border border-slate-200 bg-white p-1.5 shadow-[0_20px_40px_-24px_rgba(15,23,42,0.28)] outline-none"
                                    >
                                        <div v-if="!banks.length && isLoadingBanks" class="px-3 py-2 text-sm text-slate-500">
                                            Đang tải danh sách ngân hàng...
                                        </div>
                                        <div v-else-if="!banks.length" class="px-3 py-2 text-sm text-slate-500">Chưa có ngân hàng để chọn.</div>
                                        <ListboxOption
                                            v-for="bank in banks"
                                            v-else
                                            :key="bank.id"
                                            v-slot="{ active, selected }"
                                            :value="bank.code"
                                            as="template"
                                        >
                                            <li
                                                :class="[
                                                    'relative flex cursor-pointer items-center gap-3 rounded-[8px] px-3 py-2.5 pr-9 transition',
                                                    active ? 'bg-blue-50 text-blue-700' : 'text-slate-700',
                                                ]"
                                            >
                                                <div
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-50 text-[11px] font-bold"
                                                    :style="{ backgroundColor: bank.bg_color || '#EFF6FF' }"
                                                >
                                                    <img
                                                        v-if="bank.logo"
                                                        :src="bank.logo"
                                                        :alt="bank.short_name || bank.name"
                                                        class="h-full w-full object-cover"
                                                    />
                                                    <span v-else class="text-white">{{ getBankInitials(bank) }}</span>
                                                </div>

                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold">{{ bank.name }}</p>
                                                    <p :class="['truncate text-xs', active ? 'text-blue-500' : 'text-slate-500']">
                                                        {{ bank.short_name || bank.code.toUpperCase() }}
                                                    </p>
                                                </div>

                                                <span v-if="selected" class="absolute inset-y-0 right-3 flex items-center text-blue-600">
                                                    <Check class="h-4 w-4" />
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>

                    <label class="space-y-1.5">
                        <span class="text-xs font-semibold text-slate-600">Tên hiển thị</span>
                        <input
                            v-model="form.display_name"
                            type="text"
                            class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                            placeholder="Ví dụ: Tài khoản chính"
                        />
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-xs font-semibold text-slate-600">Số tài khoản ngân hàng</span>
                        <input
                            v-model="form.account_number"
                            type="text"
                            inputmode="numeric"
                            class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                            placeholder="Nhập số tài khoản"
                        />
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-xs font-semibold text-slate-600">Username đăng nhập ngân hàng</span>
                        <input
                            v-model="form.username"
                            type="text"
                            autocomplete="username"
                            class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                            placeholder="Tên đăng nhập internet banking"
                        />
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-xs font-semibold text-slate-600">
                            {{ isEditMode ? 'Mật khẩu đăng nhập ngân hàng mới' : 'Mật khẩu đăng nhập ngân hàng' }}
                        </span>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                            :placeholder="isEditMode ? 'Để trống nếu không đổi mật khẩu' : 'Nhập mật khẩu đăng nhập'"
                        />
                    </label>
                </div>

                <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Lưu ý xác thực</p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{
                            isEditMode
                                ? 'Khi cập nhật tài khoản ngân hàng, hệ thống sẽ giữ nguyên mật khẩu cũ nếu bạn không nhập lại.'
                                : 'Sau khi thêm thẻ, hệ thống có thể thực hiện xác thực đăng nhập theo từng ngân hàng tương ứng.'
                        }}
                    </p>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-3">
                    <RouterLink
                        :to="{ name: 'client.bank-manager' }"
                        class="inline-flex items-center justify-center rounded-[8px] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                    >
                        Hủy
                    </RouterLink>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-[8px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        :disabled="!canSubmit || processing || isLoadingAccount"
                    >
                        <Save class="h-4 w-4" />
                        {{ processing ? 'Đang lưu...' : submitLabel }}
                    </button>
                </div>
            </form>

            <aside class="space-y-3 rounded-[10px] border border-slate-200/80 bg-white p-3.5 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
                <div>
                    <h2 class="text-base font-bold tracking-[-0.03em] text-slate-900">Thông tin liên kết</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Kiểm tra nhanh cấu hình trước khi lưu thay đổi.</p>
                </div>

                <div class="rounded-[10px] border border-slate-200 px-4 py-4 text-white" :style="{ background: previewBackground }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-[0.22em] text-white/70">
                                {{ selectedBank?.short_name || 'Ngân hàng' }}
                            </p>
                            <p class="mt-2 truncate text-lg font-bold">
                                {{ form.display_name || 'Tên hiển thị' }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10">
                            <img
                                v-if="selectedBank?.logo"
                                :src="selectedBank.logo"
                                :alt="selectedBank.short_name || selectedBank.name"
                                class="h-7 w-7 rounded-full object-cover"
                            />
                            <Landmark v-else class="h-5 w-5 text-white" />
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 text-sm text-white/90">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.16em] text-white/60">Số tài khoản</p>
                            <p class="mt-1 font-semibold text-white">{{ previewAccountNumber }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.16em] text-white/60">Username đăng nhập</p>
                            <p class="mt-1 font-semibold text-white">{{ previewUsername }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-3">
                    <p class="text-xs font-medium text-slate-400">Ngân hàng đã chọn</p>
                    <div class="mt-2 flex items-center gap-2">
                        <span
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold text-white"
                            :style="{ backgroundColor: selectedBank?.bg_color || '#2563EB' }"
                        >
                            {{ selectedBankInitials }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ selectedBank?.name || 'Chưa chọn ngân hàng' }}</p>
                            <p class="text-[11px] text-slate-500">Mã: {{ selectedBank?.code || '--' }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 rounded-[8px] border border-slate-200 bg-white px-3 py-3">
                    <div class="flex items-start gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-[8px] bg-emerald-100 text-emerald-600">
                            <ShieldCheck class="h-3.5 w-3.5" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-900">Lưu trữ bảo mật</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">
                                Thông tin đăng nhập sẽ được lưu để phục vụ kết nối và đồng bộ với ngân hàng.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-[8px] bg-blue-100 text-blue-600">
                            <KeyRound class="h-3.5 w-3.5" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-900">Cập nhật linh hoạt</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">
                                Bạn có thể thay đổi tên hiển thị, username, mật khẩu hoặc số tài khoản bất cứ lúc nào.
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</template>

<script setup lang="ts">
import api from '@/config/axios';
import { clientBankService } from '@/services/client-bank.service';
import type { BankAccountType, BankType } from '@/types/bank.type';
import { handleErrorResponse } from '@/utils/response';
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue';
import { ArrowLeft, Check, ChevronsUpDown, KeyRound, Landmark, Save, ShieldCheck } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

type BankFormShape = {
    bank_code: string;
    display_name: string;
    username: string;
    password: string;
    account_number: string;
};

const route = useRoute();
const router = useRouter();

const isEditMode = computed(() => typeof route.params.bank_id === 'string' && route.params.bank_id !== '');
const currentBankId = computed(() => String(route.params.bank_id ?? ''));

const defaultForm: BankFormShape = {
    bank_code: '',
    display_name: '',
    username: '',
    password: '',
    account_number: '',
};

const form = reactive<BankFormShape>({
    ...defaultForm,
});

const banks = ref<BankType[]>([]);
const isLoadingBanks = ref(false);
const isLoadingAccount = ref(false);
const processing = ref(false);

const pageTitle = computed(() => (isEditMode.value ? 'Chỉnh sửa thông tin bank' : 'Thêm thông tin bank'));
const pageDescription = computed(() =>
    isEditMode.value
        ? 'Cập nhật lại thông tin liên kết và thông tin đăng nhập ngân hàng.'
        : 'Liên kết tài khoản ngân hàng để đồng bộ giao dịch và dùng cho các bước nạp tiền, xác thực.',
);
const submitLabel = computed(() => (isEditMode.value ? 'Lưu thay đổi' : 'Thêm thẻ'));

const availableBanks = computed<BankType[]>(() => (Array.isArray(banks.value) ? banks.value : []));
const selectedBank = computed(() => availableBanks.value.find((bank) => bank.code === form.bank_code) ?? null);

const getBankInitials = (bank: BankType | null): string => {
    const label = bank?.short_name || bank?.name || 'BK';

    return label
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
};

const selectedBankInitials = computed(() => getBankInitials(selectedBank.value));

const previewBackground = computed(() => {
    const bankColor = selectedBank.value?.bg_color || '#1E3A8A';

    return `linear-gradient(135deg, ${bankColor}, #0f172a)`;
});

const previewAccountNumber = computed(() => {
    if (!form.account_number.trim()) {
        return 'Chưa nhập số tài khoản';
    }

    return form.account_number;
});

const previewUsername = computed(() => {
    if (!form.username.trim()) {
        return 'Chưa nhập username';
    }

    return form.username;
});

const canSubmit = computed(() => {
    const requiredFields = [form.bank_code, form.display_name.trim(), form.username.trim(), form.account_number.trim()];

    if (!isEditMode.value) {
        requiredFields.push(form.password.trim());
    }

    return requiredFields.every(Boolean);
});

const loadBanks = async (): Promise<void> => {
    isLoadingBanks.value = true;

    try {
        banks.value = await clientBankService.list();
    } catch (error) {
        console.error('load banks failed', error);
    } finally {
        isLoadingBanks.value = false;
    }
};

const applyAccountToForm = (account: BankAccountType): void => {
    form.bank_code = account.bank_code;
    form.display_name = account.account_name;
    form.username = account.username ?? '';
    form.password = '';
    form.account_number = account.account_number;
};

const loadAccount = async (): Promise<void> => {
    if (!isEditMode.value || currentBankId.value === '') {
        return;
    }

    isLoadingAccount.value = true;

    try {
        const account = await clientBankService.getAccount(currentBankId.value);
        applyAccountToForm(account);
    } catch (error) {
        handleErrorResponse(error);
        await router.push({ name: 'client.bank-manager' });
    } finally {
        isLoadingAccount.value = false;
    }
};

const submitForm = async (): Promise<void> => {
    if (!canSubmit.value) {
        return;
    }

    try {
        processing.value = true;

        if (isEditMode.value) {
            const payload: Record<string, unknown> = {
                ...form,
            };

            if (!form.password.trim()) {
                delete payload.password;
            }

            const response = await clientBankService.updateAccount(currentBankId.value, payload);

            await Swal.fire('Thành công', response.data.message || 'Cập nhật tài khoản ngân hàng thành công.', 'success');
            await router.push({ name: 'client.bank-manager' });

            return;
        }

        const response = await api.post('/api/bank/save-bank', {
            ...form,
        });

        await Swal.fire('Thành công', response.data.message || 'Liên kết tài khoản ngân hàng thành công.', 'success');
        await router.push({ name: 'client.bank-manager' });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        processing.value = false;
    }
};

onMounted(async () => {
    await loadBanks();
    await loadAccount();
});
</script>
