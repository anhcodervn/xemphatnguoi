<script setup lang="ts">
import { adminCaptchaTaskService } from '@/services/admin-captcha-task.service';
import { handleErrorResponse } from '@/utils/response';
import { onMounted, ref } from 'vue';

const rows = ref<Array<Record<string, any>>>([]);
const loading = ref(true);

const loadTasks = async (): Promise<void> => {
    try {
        loading.value = true;
        const response = await adminCaptchaTaskService.list();
        rows.value = response.tasks?.data ?? [];
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const extractCaptchaResult = (row: Record<string, any>): string => {
    const payload = row.result_payload;

    if (!payload || typeof payload !== 'object') {
        return '-';
    }

    const keys = ['text', 'token', 'answer', 'solution', 'code', 'captcha'];

    for (const key of keys) {
        const value = payload[key];

        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }
    }

    return '-';
};

const truncateResult = (value: string): string => {
    if (value.length <= 10) {
        return value;
    }

    return `${value.slice(0, 10)}...`;
};

onMounted(async () => {
    await loadTasks();
});
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[16px] border border-teal-100 bg-white/95 p-5 shadow-[0_18px_40px_-28px_rgba(8,145,178,0.25)]">
            <h1 class="text-2xl font-black tracking-[-0.04em] text-slate-950">Yêu cầu captcha toàn hệ thống</h1>
            <p class="mt-2 text-sm text-slate-500">Theo dõi trạng thái request solve captcha, người dùng sử dụng dịch vụ và hiệu quả nguồn giải.</p>
        </section>

        <section class="overflow-hidden rounded-[16px] border border-teal-100 bg-white/95 shadow-[0_18px_40px_-28px_rgba(8,145,178,0.25)]">
            <table class="w-full min-w-[1180px]">
                <thead class="bg-teal-50 text-left text-sm font-semibold text-teal-700">
                    <tr>
                        <th class="px-4 py-3">Task code</th>
                        <th class="px-4 py-3">Người dùng</th>
                        <th class="px-4 py-3">Dịch vụ</th>
                        <th class="px-4 py-3">Nguồn</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Giá bán</th>
                        <th class="px-4 py-3">Kết quả</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-teal-100 text-sm text-slate-700">
                    <tr v-if="loading">
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">Đang tải yêu cầu...</td>
                    </tr>

                    <tr v-else-if="rows.length === 0">
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">Chưa có yêu cầu captcha nào.</td>
                    </tr>

                    <tr v-for="row in rows" :key="String(row.id)">
                        <td class="px-4 py-3 font-mono text-xs">{{ row.task_code }}</td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900">{{ row.user?.full_name || row.user?.username || `User #${row.user?.id || '--'}` }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ row.user?.username || '--' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ row.user?.email || '--' }}</p>
                        </td>
                        <td class="px-4 py-3">{{ row.service?.name || row.service_code }}</td>
                        <td class="px-4 py-3">{{ row.source?.name || '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-teal-600 px-3 py-1 text-xs font-semibold text-white">{{ row.status }}</span>
                        </td>
                        <td class="px-4 py-3">{{ row.selling_price }}</td>
                        <td class="px-4 py-3">{{ truncateResult(extractCaptchaResult(row)) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

