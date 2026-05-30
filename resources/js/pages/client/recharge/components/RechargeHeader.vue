<script setup lang="ts">
const props = defineProps<{
    balance: string;
    bonusPercentage: number;
}>();

const formatCurrency = (value: string): string => {
    return `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))}đ`;
};
</script>

<template>
    <section
        class="flex flex-col gap-3 rounded-[10px] border border-slate-200/70 bg-white px-4 py-4 shadow-sm lg:flex-row lg:items-center lg:justify-between"
    >
        <div>
            <h1 class="text-2xl font-bold tracking-[-0.03em] text-slate-950">Nạp tiền</h1>
            <p class="mt-1 text-sm text-slate-500">Nạp tiền và theo dõi giao dịch.</p>
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
            <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Số dư</p>
                <p class="mt-1 text-lg font-bold text-slate-950">{{ formatCurrency(props.balance) }}</p>
            </div>
            <div class="rounded-[10px] border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-600">Bonus</p>
                <p class="mt-1 text-lg font-bold text-emerald-600">+{{ props.bonusPercentage }}%</p>
            </div>
        </div>
    </section>
</template>
