<script setup lang="ts">
import { adminCronJobService } from '@/services/admin-cron-job.service';
import type { CronJobItem } from '@/services/client-cron-job.service';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const rows = ref<CronJobItem[]>([]);
const availableGroups = ref<string[]>([]);
const meta = reactive({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const summary = reactive({
    total_jobs: 0,
    active_jobs: 0,
    paused_jobs: 0,
    disabled_jobs: 0,
    runs_today: 0,
    failed_today: 0,
});
const filters = reactive({
    search: '',
    group_name: '',
    status: '',
});

const formatDate = (value: string | null): string => {
    if (!value) {
        return '--';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const loadJobs = async (page = 1): Promise<void> => {
    loading.value = true;

    try {
        const response = await adminCronJobService.list({
            page,
            per_page: meta.per_page,
            search: filters.search || undefined,
            group_name: filters.group_name || undefined,
            status: filters.status || undefined,
        });

        rows.value = response.data;
        availableGroups.value = response.filters.groups;
        Object.assign(meta, response.meta);
        Object.assign(summary, response.summary);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (job: CronJobItem, status: 'active' | 'paused' | 'disabled'): Promise<void> => {
    try {
        const response = await adminCronJobService.updateStatus(job.id, status);
        handleSuccessResponse(response, 'Đã cập nhật trạng thái cron job.');
        await loadJobs(meta.current_page);
    } catch (error) {
        handleErrorResponse(error);
    }
};

const removeJob = async (job: CronJobItem): Promise<void> => {
    try {
        const response = await adminCronJobService.delete(job.id);
        handleSuccessResponse(response, 'Đã xóa cron job.');
        await loadJobs(meta.current_page);
    } catch (error) {
        handleErrorResponse(error);
    }
};

onMounted(async () => {
    await loadJobs();
});
</script>

<template>
    <div class="space-y-5">
        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <h1 class="text-3xl font-black tracking-[-0.04em] text-slate-950">Cron Jobs Management</h1>
            <p class="mt-2 text-sm leading-7 text-slate-600">Lọc theo trạng thái, nhóm, tìm nhanh và khóa các task đang có dấu hiệu bất thường.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs uppercase tracking-[0.16em] text-slate-400">Total</p><p class="mt-2 text-2xl font-black text-slate-950">{{ summary.total_jobs }}</p></article>
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs uppercase tracking-[0.16em] text-slate-400">Active</p><p class="mt-2 text-2xl font-black text-emerald-600">{{ summary.active_jobs }}</p></article>
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs uppercase tracking-[0.16em] text-slate-400">Paused</p><p class="mt-2 text-2xl font-black text-amber-600">{{ summary.paused_jobs }}</p></article>
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs uppercase tracking-[0.16em] text-slate-400">Disabled</p><p class="mt-2 text-2xl font-black text-rose-600">{{ summary.disabled_jobs }}</p></article>
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs uppercase tracking-[0.16em] text-slate-400">Runs hôm nay</p><p class="mt-2 text-2xl font-black text-slate-950">{{ summary.runs_today }}</p></article>
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs uppercase tracking-[0.16em] text-slate-400">Failed hôm nay</p><p class="mt-2 text-2xl font-black text-amber-600">{{ summary.failed_today }}</p></article>
            </div>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <form class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_220px_220px_120px]" @submit.prevent="loadJobs(1)">
                <input v-model="filters.search" type="search" placeholder="Tìm theo tên, nhóm hoặc URL..." class="h-11 rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                <select v-model="filters.group_name" class="h-11 rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500">
                    <option value="">Tất cả nhóm</option>
                    <option v-for="group in availableGroups" :key="group" :value="group">{{ group }}</option>
                </select>
                <select v-model="filters.status" class="h-11 rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Active</option>
                    <option value="paused">Paused</option>
                    <option value="disabled">Disabled</option>
                </select>
                <button type="submit" class="rounded-[10px] border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Lọc</button>
            </form>

            <div class="mt-5 overflow-hidden rounded-[10px] border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Job</th>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Last status</th>
                                <th class="px-4 py-3">Last run</th>
                                <th class="px-4 py-3">Next run</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading"><td colspan="6" class="px-4 py-10 text-center text-slate-500">Đang tải cron jobs...</td></tr>
                            <tr v-else-if="rows.length === 0"><td colspan="6" class="px-4 py-10 text-center text-slate-500">Chưa có cron job nào.</td></tr>
                            <tr v-for="job in rows" :key="job.id" class="border-t border-slate-100">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ job.name }}</p>
                                    <p v-if="job.group_name" class="mt-1 text-xs font-medium text-sky-700">{{ job.group_name }}</p>
                                    <p class="mt-1 max-w-[320px] truncate text-xs text-slate-500">{{ job.method }} • {{ job.url }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ job.user?.name || job.user?.email || `#${job.user_id}` }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ job.last_status || '--' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ formatDate(job.last_run_at) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ formatDate(job.next_run_at) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="rounded-[8px] border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50" @click="updateStatus(job, 'active')">Active</button>
                                        <button type="button" class="rounded-[8px] border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50" @click="updateStatus(job, 'paused')">Pause</button>
                                        <button type="button" class="rounded-[8px] border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="updateStatus(job, 'disabled')">Disable</button>
                                        <button type="button" class="rounded-[8px] border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" @click="removeJob(job)">Xóa</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</template>
