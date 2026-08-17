<script setup lang="ts">
import { adminTrafficFineService, type AdminTrafficFineResult } from '@/services/admin-traffic-fine.service';
import { onMounted, ref } from 'vue';
const items = ref<AdminTrafficFineResult[]>([]);
const search = ref('');
const loading = ref(false);
const load = async (): Promise<void> => {
    loading.value = true;
    try {
        items.value = (await adminTrafficFineService.results(search.value)).data;
    } finally {
        loading.value = false;
    }
};
onMounted(load);
</script>
<template>
    <div class="grid gap-6">
        <header>
            <p class="text-sm font-bold text-sky-700">Traffic Fine</p>
            <h1 class="mt-1 text-3xl font-black text-slate-950">Kết quả đã lưu</h1>
        </header>
        <form class="flex gap-3" @submit.prevent="load">
            <label class="sr-only" for="result-search">Tìm biển số</label
            ><input
                id="result-search"
                v-model="search"
                placeholder="Tìm biển số"
                class="app-focus h-11 min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-4"
            /><button class="app-focus rounded-lg bg-slate-950 px-5 text-sm font-bold text-white">Tìm</button>
        </form>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Đang tải...</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Biển số</th>
                            <th class="px-5 py-4">Loại</th>
                            <th class="px-5 py-4">Số lỗi</th>
                            <th class="px-5 py-4">Provider</th>
                            <th class="px-5 py-4">Kiểm tra</th>
                            <th class="px-5 py-4">Hết hạn</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in items" :key="item.id">
                            <td class="px-5 py-4 font-bold">{{ item.plate }}</td>
                            <td class="px-5 py-4">{{ item.vehicle_type }}</td>
                            <td class="px-5 py-4">{{ item.violation_count }}</td>
                            <td class="px-5 py-4">{{ item.provider }}</td>
                            <td class="px-5 py-4">{{ new Date(item.checked_at).toLocaleString('vi-VN') }}</td>
                            <td class="px-5 py-4">{{ new Date(item.expires_at).toLocaleString('vi-VN') }}</td>
                        </tr>
                        <tr v-if="!items.length">
                            <td colspan="6" class="p-8 text-center text-slate-500">Chưa có dữ liệu.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
