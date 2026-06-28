<script setup lang="ts">
import { AlertTriangle, CheckCircle2, Copy, Landmark, ReceiptText, RefreshCw, WalletCards } from 'lucide-vue-next';
import { computed } from 'vue';

type DepositMethodItem = {
    id: number;
    title: string;
    subtitle: string;
    providerLabel: string;
    accountName: string;
    accountNumber: string;
    isActive: boolean;
    isSelected: boolean;
};

const props = defineProps<{
    hasConfig: boolean;
    amountDisplay: string;
    amountError: string;
    transferInfo: {
        bankName: string;
        accountNumber: string;
        accountName: string;
        content: string;
        qrUrl: string | null;
    };
    copiedField: string | null;
    canSubmit: boolean;
    hasPendingRequest: boolean;
    methods: DepositMethodItem[];
    selectedMethodId: number | null;
}>();

const emit = defineEmits<{
    'update:amount': [value: string];
    'select-preset': [amount: number];
    'select-method': [configId: number];
    copy: [field: 'accountNumber' | 'content' | 'bankName' | 'accountName'];
    refresh: [];
    submit: [];
    confirm: [];
}>();

const presets = [100000, 200000, 500000, 1000000];

const amountLabel = computed(() => (props.amountDisplay ? `${props.amountDisplay}đ` : '0đ'));
const selectedMethod = computed(() => props.methods.find((item) => item.id === props.selectedMethodId) ?? null);
const methodLabel = computed(() => selectedMethod.value?.title || props.transferInfo.bankName || 'Phương thức hệ thống');
</script>

<template>
    <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1fr)_280px]">
        <article class="min-w-0 rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div v-if="!props.hasConfig && props.methods.length === 0" class="rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                <p class="text-base font-bold text-slate-900">Chưa có cấu hình nhận tiền</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Hệ thống hiện chưa bật cấu hình nạp tiền. Vui lòng quay lại sau hoặc liên hệ quản trị viên.</p>
            </div>

            <template v-else>
                <div class="flex min-w-0 flex-col gap-4">
                    <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h2 class="text-[22px] font-black tracking-[-0.04em] text-slate-950 sm:text-[24px]">Tạo yêu cầu nạp</h2>
                            <p class="mt-1 break-words text-sm leading-6 text-slate-500">Chọn bank đang hoạt động của hệ thống rồi nhập số tiền cần nạp.</p>
                        </div>
                        <span class="inline-flex w-fit shrink-0 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">1-3 phút</span>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Phương thức nạp</p>
                        <div class="grid gap-3 lg:grid-cols-2">
                            <button
                                v-for="method in props.methods"
                                :key="method.id"
                                type="button"
                                class="w-full min-w-0 rounded-[10px] border px-4 py-4 text-left transition"
                                :class="method.isSelected ? 'border-indigo-300 bg-indigo-50/70 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'"
                                @click="emit('select-method', method.id)"
                            >
                                <div class="flex min-w-0 items-start justify-between gap-3">
                                    <div class="flex min-w-0 flex-1 items-start gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px]" :class="method.isActive ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500'">
                                            <WalletCards class="h-4.5 w-4.5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                                <p class="min-w-0 break-words font-semibold text-slate-950">{{ method.title }}</p>
                                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="method.providerLabel === 'API' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'">
                                                    {{ method.providerLabel }}
                                                </span>
                                                <span v-if="!method.isActive" class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">Tạm tắt</span>
                                            </div>
                                            <p class="mt-1 break-words text-sm text-slate-500">{{ method.subtitle }}</p>
                                            <p class="mt-1 break-words text-sm text-slate-400">{{ method.accountNumber }} · {{ method.accountName }}</p>
                                        </div>
                                    </div>
                                    <CheckCircle2 v-if="method.isSelected" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-600" />
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="rounded-[10px] border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Số tiền nạp</p>
                            <span class="text-sm font-semibold text-emerald-600">Bonus: 0đ</span>
                        </div>

                        <div class="mt-3 flex min-w-0 items-center rounded-[10px] border border-slate-200 bg-white px-4 py-4">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-indigo-50 text-indigo-600">
                                <Landmark class="h-4.5 w-4.5" />
                            </div>
                            <input
                                :value="props.amountDisplay"
                                inputmode="numeric"
                                type="text"
                                class="ml-3 min-w-0 flex-1 bg-transparent text-[20px] font-black tracking-[-0.04em] text-slate-950 outline-none sm:text-[32px]"
                                placeholder="0"
                                @input="emit('update:amount', ($event.target as HTMLInputElement).value)"
                            />
                            <span class="shrink-0 text-sm font-semibold text-slate-400 sm:text-base">VND</span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2 lg:grid-cols-4">
                            <button
                                v-for="preset in presets"
                                :key="preset"
                                type="button"
                                class="rounded-[10px] border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-600"
                                :class="props.amountDisplay === new Intl.NumberFormat('vi-VN').format(preset) ? 'border-indigo-300 bg-indigo-50 text-indigo-600' : ''"
                                @click="emit('select-preset', preset)"
                            >
                                {{ new Intl.NumberFormat('vi-VN').format(preset) }}đ
                            </button>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                            <span>Tối thiểu: 10.000đ</span>
                            <span>Tối đa: 50.000.000đ</span>
                        </div>

                        <div v-if="props.amountError" class="mt-3 rounded-[10px] border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                            {{ props.amountError }}
                        </div>
                    </div>

                    <div class="rounded-[10px] border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-bold text-slate-950">Thông tin chuyển khoản</h3>
                            <span class="text-xs font-semibold text-indigo-500">Từ dữ liệu hệ thống</span>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                                <div class="flex min-w-0 items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Ngân hàng</p>
                                        <p class="mt-2 break-words text-base font-semibold text-slate-950">{{ props.transferInfo.bankName }}</p>
                                    </div>
                                    <button type="button" class="shrink-0 rounded-[10px] border border-slate-200 bg-white p-2 text-slate-400 transition hover:text-indigo-600" @click="emit('copy', 'bankName')">
                                        <CheckCircle2 v-if="props.copiedField === 'bankName'" class="h-4 w-4" />
                                        <Copy v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                                <div class="flex min-w-0 items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Số tài khoản</p>
                                        <p class="mt-2 break-all text-base font-semibold text-slate-950">{{ props.transferInfo.accountNumber }}</p>
                                    </div>
                                    <button type="button" class="shrink-0 rounded-[10px] border border-slate-200 bg-white p-2 text-slate-400 transition hover:text-indigo-600" @click="emit('copy', 'accountNumber')">
                                        <CheckCircle2 v-if="props.copiedField === 'accountNumber'" class="h-4 w-4" />
                                        <Copy v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                                <div class="flex min-w-0 items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Chủ tài khoản</p>
                                        <p class="mt-2 break-words text-base font-semibold text-slate-950">{{ props.transferInfo.accountName }}</p>
                                    </div>
                                    <button type="button" class="shrink-0 rounded-[10px] border border-slate-200 bg-white p-2 text-slate-400 transition hover:text-indigo-600" @click="emit('copy', 'accountName')">
                                        <CheckCircle2 v-if="props.copiedField === 'accountName'" class="h-4 w-4" />
                                        <Copy v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                                <div class="flex min-w-0 items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Nội dung</p>
                                        <p class="mt-2 break-all text-base font-black tracking-[-0.02em] text-indigo-600">{{ props.transferInfo.content }}</p>
                                        <p class="mt-2 text-xs leading-5 text-slate-400">Nội dung sẽ được tạo sau khi tạo lệnh nạp.</p>
                                    </div>
                                    <button type="button" class="shrink-0 rounded-[10px] border border-slate-200 bg-white p-2 text-slate-400 transition hover:text-indigo-600" @click="emit('copy', 'content')">
                                        <CheckCircle2 v-if="props.copiedField === 'content'" class="h-4 w-4" />
                                        <Copy v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="mt-4 text-sm leading-6 text-slate-500">Mã giao dịch sẽ được tạo tự động sau khi bạn tạo yêu cầu nạp. Hãy chuyển đúng số tiền và giữ nguyên nội dung để hệ thống đối soát nhanh.</p>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:flex-wrap">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-[10px] bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300 sm:w-auto"
                            :disabled="!props.canSubmit"
                            @click="emit('submit')"
                        >
                            Tạo yêu cầu nạp
                        </button>
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-[10px] border border-indigo-200 bg-indigo-50 px-5 py-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                            :disabled="!props.hasPendingRequest"
                            @click="emit('confirm')"
                        >
                            Tiếp tục thanh toán
                        </button>
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-[10px] border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto"
                            @click="emit('refresh')"
                        >
                            <RefreshCw class="h-4 w-4" />
                            Tải lại trạng thái
                        </button>
                    </div>
                </div>
            </template>
        </article>

        <aside class="min-w-0 space-y-4">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <ReceiptText class="h-4.5 w-4.5 text-indigo-600" />
                    <h3 class="text-lg font-bold text-slate-950">Tóm tắt</h3>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">Phương thức</dt>
                        <dd class="min-w-0 break-words text-right font-semibold text-slate-950">{{ methodLabel }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Số tiền</dt>
                        <dd class="font-semibold text-slate-950">{{ amountLabel }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Bonus</dt>
                        <dd class="font-semibold text-emerald-600">+0đ</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                        <dt class="text-slate-500">Tổng nhận</dt>
                        <dd class="text-base font-black text-emerald-600">{{ amountLabel }}</dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-lg font-bold text-slate-950">Hướng dẫn</h3>
                <ol class="mt-4 space-y-2 text-sm leading-6 text-slate-600">
                    <li>1. Chọn bank nạp đang hoạt động.</li>
                    <li>2. Nhập số tiền trong hạn mức cho phép.</li>
                    <li>3. Chuyển khoản đúng thông tin hệ thống cung cấp.</li>
                    <li>4. Theo dõi trạng thái ở tab lịch sử nạp tiền.</li>
                </ol>
            </article>

            <article class="rounded-[10px] border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <AlertTriangle class="mt-0.5 h-4.5 w-4.5 shrink-0 text-amber-600" />
                    <div>
                        <h3 class="text-base font-bold text-slate-950">Lưu ý</h3>
                        <p class="mt-2 text-sm leading-6 text-amber-900">Nếu nạp quá lâu chưa ghi nhận, hãy kiểm tra lại nội dung chuyển khoản hoặc liên hệ hỗ trợ.</p>
                    </div>
                </div>
            </article>
        </aside>
    </div>
</template>
