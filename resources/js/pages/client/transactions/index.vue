<script setup lang="ts">
import { clientProfileService } from '@/services/client-profile.service';
import type { WalletTransactionItem } from '@/types/client-profile.type';
import formatCash from '@/utils/helpers/formatCash';
import { onMounted, ref } from 'vue';

const transactions = ref<WalletTransactionItem[]>([]);
const loading = ref(true);
const errorMessage = ref('');
onMounted(async () => {
    try {
        transactions.value = (await clientProfileService.getWalletTransactions({ per_page: 50 })).data;
    } catch {
        errorMessage.value = 'Không thể tải giao dịch.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="grid gap-7">
        <header>
            <p class="text-sm font-bold text-sky-700">Tài chính</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Giao dịch</h1>
        </header>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Đang tải giao dịch...</div>
            <div v-else-if="errorMessage" class="p-6 text-sm text-red-700">{{ errorMessage }}</div>
            <div v-else-if="!transactions.length" class="p-8 text-center text-sm text-slate-500">Chưa có giao dịch.</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Nội dung</th>
                            <th class="px-5 py-4">Loại</th>
                            <th class="px-5 py-4">Số tiền</th>
                            <th class="px-5 py-4">Số dư sau</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in transactions" :key="item.id">
                            <td class="px-5 py-4 font-medium text-slate-950">{{ item.content ?? item.code }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ item.type }}</td>
                            <td class="px-5 py-4 font-bold" :class="item.type === 'deduct' ? 'text-red-700' : 'text-emerald-700'">
                                {{ formatCash(Number(item.amount)) }}đ
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ formatCash(Number(item.balanceAfter)) }}đ</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
