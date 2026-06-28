<script setup lang="ts">
const props = defineProps<{
    balanceLabel: string;
    bonusRate: number;
}>();
</script>

<template>
    <section class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm sm:p-5">
        <div class="flex flex-col gap-3 sm:gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0 max-w-2xl">
                <h1 class="text-[24px] font-black tracking-[-0.05em] text-slate-950 sm:text-[36px] lg:text-[40px]">Nạp tiền</h1>
                <p class="mt-1 break-words text-sm text-slate-500 sm:mt-2 sm:text-base">Nạp tiền vào ví và theo dõi giao dịch của bạn.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:w-[420px] xl:w-[460px]">
                <article class="rounded-[10px] border border-slate-200 bg-white px-3 py-3 shadow-sm sm:px-5 sm:py-4">
                    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-indigo-50 text-indigo-600 sm:h-12 sm:w-12">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current sm:h-6 sm:w-6" stroke-width="1.8">
                                <rect x="3.5" y="5.5" width="17" height="13" rx="2.5" />
                                <path d="M7 10.5h10M7 14.5h6" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-500 sm:text-sm">Số dư</p>
                            <p class="mt-0.5 break-words text-base font-black tracking-[-0.04em] text-slate-950 sm:mt-1 sm:text-[20px]">{{ props.balanceLabel }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white px-3 py-3 shadow-sm sm:px-5 sm:py-4">
                    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-emerald-50 text-emerald-600 sm:h-12 sm:w-12">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current sm:h-6 sm:w-6" stroke-width="1.8">
                                <path d="M12 3v18M4 8h16M6 21h12a2 2 0 0 0 2-2V8H4v11a2 2 0 0 0 2 2Z" />
                                <path d="M8 8V6a2 2 0 1 1 4 0v2M12 8V6a2 2 0 1 1 4 0v2" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-500 sm:text-sm">Bonus</p>
                            <p class="mt-0.5 text-base font-black tracking-[-0.04em] text-emerald-600 sm:mt-1 sm:text-[20px]">+{{ props.bonusRate }}%</p>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>
