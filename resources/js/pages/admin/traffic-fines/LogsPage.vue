<script setup lang="ts">
import { adminTrafficFineService, type AdminLookupLog } from '@/services/admin-traffic-fine.service';
import { onMounted, ref } from 'vue';
const items = ref<AdminLookupLog[]>([]);
const search = ref('');
const loading = ref(false);
const load = async (): Promise<void> => {
    loading.value = true;
    try {
        items.value = (await adminTrafficFineService.logs(search.value)).data;
    } finally {
        loading.value = false;
    }
};
onMounted(load);
</script>
<template>
    <div class="grid gap-6">
        <header>
            <p class="text-sm font-bold text-sky-700">Audit</p>
            <h1 class="mt-1 text-3xl font-black text-slate-950">Lookup Logs</h1>
            <p class="mt-2 text-sm text-slate-500">Không lưu raw response của provider.</p>
        </header>
        <form class="flex gap-3" @submit.prevent="load">
            <label class="sr-only" for="log-search">Tìm biển số</label
            ><input
                id="log-search"
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
                            <th class="px-4 py-4">Thời gian</th>
                            <th class="px-4 py-4">Biển số</th>
                            <th class="px-4 py-4">Nguồn</th>
                            <th class="px-4 py-4">Cache</th>
                            <th class="px-4 py-4">Trạng thái</th>
                            <th class="px-4 py-4">Latency</th>
                            <th class="px-4 py-4">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in items" :key="item.id">
                            <td class="px-4 py-4">{{ new Date(item.created_at).toLocaleString('vi-VN') }}</td>
                            <td class="px-4 py-4 font-bold">{{ item.plate }}</td>
                            <td class="px-4 py-4">{{ item.source }}</td>
                            <td class="px-4 py-4">{{ item.cache_hit ? 'Hit' : 'Miss' }}</td>
                            <td class="px-4 py-4">{{ item.status }}</td>
                            <td class="px-4 py-4">{{ item.provider_latency_ms === null ? '-' : `${item.provider_latency_ms} ms` }}</td>
                            <td class="px-4 py-4 font-mono text-xs">{{ item.ip ?? '-' }}</td>
                        </tr>
                        <tr v-if="!items.length">
                            <td colspan="7" class="p-8 text-center text-slate-500">Chưa có log.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
