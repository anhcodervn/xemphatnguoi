<template>
    <Card custom-class="rounded-[10px] border border-slate-200/80 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
        <template #body>
            <div class="p-3.5">
                <div class="flex flex-col gap-2.5 border-b border-slate-100 pb-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-900">Ngân hàng hỗ trợ</h2>
                        <p class="mt-1 text-xs text-slate-500">Bảo mật tuyệt đối - Kết nối an toàn</p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition hover:text-blue-700"
                        @click="goBankManager"
                    >
                        Quản lý ngân hàng
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>

                <div v-if="loading" class="mt-4 text-xs text-slate-500">Đang tải danh sách ngân hàng...</div>
                <div v-else-if="banks.length === 0" class="mt-4 text-xs text-slate-500">Chưa có ngân hàng khả dụng.</div>

                <div v-else class="mt-4 grid gap-3 xl:grid-cols-3">
                    <button
                        v-for="bank in banks"
                        :key="bank.code"
                        type="button"
                        class="group flex items-center justify-between gap-3 rounded-[8px] border border-slate-200 bg-white px-3 py-2.5 text-left transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-50"
                        @click="goBankManager"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
                                :style="{ backgroundColor: bank.bg_color || '#2563eb' }"
                            >
                                <img v-if="bank.logo" :src="bank.logo" :alt="bank.short_name || bank.name" class="h-full w-full object-cover" />
                                <span v-else>{{ getBankShort(bank) }}</span>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ bank.short_name || bank.name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">Liên kết {{ linkedCount(bank.code) }} thẻ</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2.5">
                            <span :class="linkedCount(bank.code) > 0 ? activeClass : inactiveClass">
                                {{ linkedCount(bank.code) > 0 ? "Đang dùng" : "Trống" }}
                            </span>
                            <ChevronRight class="h-4 w-4 text-slate-300 transition group-hover:text-slate-500" />
                        </div>
                    </button>
                </div>
            </div>
        </template>
    </Card>
</template>

<script setup lang="ts">
import Card from "@/components/Card/index.vue";
import { clientBankService } from "@/services/client-bank.service";
import type { BankAccountType, BankType } from "@/types/bank.type";
import { ChevronRight } from "lucide-vue-next";
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

const activeClass = "rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700";
const inactiveClass = "rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500";

const banks = ref<BankType[]>([]);
const accounts = ref<BankAccountType[]>([]);
const loading = ref(false);

const getBankShort = (bank: BankType): string => {
    const short = bank.short_name?.trim();
    if (short) {
        return short.slice(0, 3).toUpperCase();
    }

    return bank.name
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? "")
        .join("");
};

const linkedCount = (bankCode: string): number => {
    return accounts.value.filter((item) => String(item.bank_code).toLowerCase() === String(bankCode).toLowerCase()).length;
};

const loadData = async (): Promise<void> => {
    try {
        loading.value = true;
        const [bankList, accountList] = await Promise.all([clientBankService.list(), clientBankService.listAccounts()]);
        banks.value = bankList;
        accounts.value = accountList;
    } finally {
        loading.value = false;
    }
};

const goBankManager = async (): Promise<void> => {
    await router.push({ name: "client.bank-manager" });
};

onMounted(loadData);
</script>
