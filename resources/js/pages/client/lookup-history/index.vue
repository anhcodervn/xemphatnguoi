<script setup lang="ts">
import { trafficFineService } from '@/services/traffic-fine.service';
import type { LookupHistory, PaginatedResponse, VehicleType } from '@/types/traffic-fine.type';
import { onMounted, ref } from 'vue';

const histories = ref<PaginatedResponse<LookupHistory> | null>(null);
const loading = ref(true);
const errorMessage = ref('');

const label = (type: VehicleType): string => ({ car: 'Ô tô', motorbike: 'Xe máy', electric_motorbike: 'Xe máy điện' })[type];
const load = async (page = 1): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';
    try {
        histories.value = await trafficFineService.histories(page);
    } catch {
        errorMessage.value = 'Không thể tải lịch sử tra cứu.';
    } finally {
        loading.value = false;
    }
};
onMounted(() => load());
</script>

<template>
    <div class="grid gap-7">
        <header>
            <p class="text-sm font-bold text-sky-700">Dữ liệu của bạn</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Lịch sử tra cứu</h1>
            <p class="mt-2 text-sm text-slate-500">Chỉ các lượt tra cứu khi đã đăng nhập mới xuất hiện tại đây.</p>
        </header>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Đang tải lịch sử...</div>
            <div v-else-if="errorMessage" class="p-6 text-sm text-red-700">{{ errorMessage }}</div>
            <div v-else-if="!histories?.data.length" class="p-8 text-center text-sm text-slate-500">Bạn chưa có lịch sử tra cứu.</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Biển số</th>
                            <th class="px-5 py-4">Loại xe</th>
                            <th class="px-5 py-4">Số lỗi</th>
                            <th class="px-5 py-4">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in histories.data" :key="item.id">
                            <td class="px-5 py-4 font-bold text-slate-950">{{ item.plate }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ label(item.vehicle_type) }}</td>
                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold"
                                    :class="item.violation_count ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'"
                                    >{{ item.violation_count }}</span
                                >
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ new Date(item.created_at).toLocaleString('vi-VN') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div v-if="histories && histories.last_page > 1" class="flex justify-center gap-2">
            <button
                v-for="page in histories.last_page"
                :key="page"
                type="button"
                class="app-focus min-h-10 min-w-10 rounded-lg border text-sm font-bold"
                :class="page === histories.current_page ? 'border-sky-700 bg-sky-700 text-white' : 'border-slate-300 bg-white text-slate-700'"
                @click="load(page)"
            >
                {{ page }}
            </button>
        </div>
    </div>
</template>
