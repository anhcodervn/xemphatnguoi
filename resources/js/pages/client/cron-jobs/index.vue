<script setup lang="ts">
import { clientCronJobService, type CronJobItem } from '@/services/client-cron-job.service';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { PauseCircle, PlayCircle, Plus, Rocket, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const rows = ref<CronJobItem[]>([]);
const availableGroups = ref<string[]>([]);
const meta = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const summary = reactive({
    total_jobs: 0,
    active_jobs: 0,
    runs_today: 0,
    runs_month: 0,
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

const shortUrl = (value: string): string => (value.length > 52 ? `${value.slice(0, 52)}...` : value);

const scheduleLabel = (job: CronJobItem): string => {
    if (job.cron_expression) {
        return job.cron_expression;
    }

    if (!job.interval_seconds) {
        return '--';
    }

    if (job.interval_seconds % 3600 === 0) {
        return `${job.interval_seconds / 3600} giờ`;
    }

    if (job.interval_seconds % 60 === 0) {
        return `${job.interval_seconds / 60} phút`;
    }

    return `${job.interval_seconds} giây`;
};

const loadJobs = async (page = 1): Promise<void> => {
    loading.value = true;

    try {
        const response = await clientCronJobService.list({
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

const toggleJobStatus = async (job: CronJobItem): Promise<void> => {
    try {
        const response = job.status === 'active' ? await clientCronJobService.pause(job.id) : await clientCronJobService.resume(job.id);

        handleSuccessResponse(response, job.status === 'active' ? 'Đã tạm dừng cron job.' : 'Đã kích hoạt lại cron job.');
        await loadJobs(meta.current_page);
    } catch (error) {
        handleErrorResponse(error);
    }
};

const runNow = async (job: CronJobItem): Promise<void> => {
    try {
        const response = await clientCronJobService.runNow(job.id);
        handleSuccessResponse(response, 'Cron job đã được đưa vào hàng đợi chạy ngay.');
    } catch (error) {
        handleErrorResponse(error);
    }
};

const deleteJob = async (job: CronJobItem): Promise<void> => {
    const confirmed = await Swal.fire({
        icon: 'warning',
        title: 'Xóa cron job?',
        text: 'Lịch chạy và cấu hình sẽ bị xóa mềm khỏi hệ thống.',
        showCancelButton: true,
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
    });

    if (!confirmed.isConfirmed) {
        return;
    }

    try {
        const response = await clientCronJobService.delete(job.id);
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
    <div class="space-y-5 pb-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">HTTP Tasks</p>
                    <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950">Cron Jobs</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">Tạo, tạm dừng, chạy ngay và theo dõi toàn bộ lịch HTTP của bạn trong một danh sách rõ ràng.</p>
                </div>

                <button
                    type="button"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-[10px] bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 sm:w-auto"
                    @click="router.push('/cron-jobs/create')"
                >
                    <Plus class="h-4 w-4" />
                    Tạo cron job mới
                </button>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Tổng jobs</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">{{ summary.total_jobs }}</p>
                </article>
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Active</p>
                    <p class="mt-2 text-2xl font-black text-emerald-600">{{ summary.active_jobs }}</p>
                </article>
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Runs hôm nay</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">{{ summary.runs_today }}</p>
                </article>
                <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Fail hôm nay</p>
                    <p class="mt-2 text-2xl font-black text-amber-600">{{ summary.failed_today }}</p>
                </article>
            </div>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <form class="grid gap-3 md:grid-cols-3 xl:grid-cols-[minmax(0,1fr)_220px_220px_120px]" @submit.prevent="loadJobs(1)">
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Tìm theo tên, nhóm hoặc URL..."
                    class="h-11 rounded-[10px] border border-slate-200 px-3 text-sm outline-none transition focus:border-sky-500 md:col-span-3 xl:col-span-1"
                />
                <select v-model="filters.group_name" class="h-11 rounded-[10px] border border-slate-200 px-3 text-sm outline-none transition focus:border-sky-500">
                    <option value="">Tất cả nhóm</option>
                    <option v-for="group in availableGroups" :key="group" :value="group">{{ group }}</option>
                </select>
                <select v-model="filters.status" class="h-11 rounded-[10px] border border-slate-200 px-3 text-sm outline-none transition focus:border-sky-500">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Active</option>
                    <option value="paused">Paused</option>
                    <option value="disabled">Disabled</option>
                </select>
                <button type="submit" class="h-11 rounded-[10px] border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Lọc
                </button>
            </form>

            <div v-if="loading" class="mt-5 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                Đang tải cron jobs...
            </div>

            <div v-else-if="rows.length === 0" class="mt-5 rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                Chưa có cron job nào khớp bộ lọc.
            </div>

            <div v-else class="mt-5 space-y-3">
                <article v-for="job in rows" :key="job.id" class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <button type="button" class="text-left" @click="router.push(`/cron-jobs/${job.id}`)">
                                <p class="text-base font-bold text-slate-950">{{ job.name }}</p>
                                <p v-if="job.group_name" class="mt-1 text-xs font-medium uppercase tracking-[0.12em] text-sky-700">{{ job.group_name }}</p>
                                <p class="mt-2 break-all text-sm text-slate-600">{{ job.method }} • {{ shortUrl(job.url) }}</p>
                            </button>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Schedule</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ scheduleLabel(job) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Last status</p>
                                    <span
                                        class="mt-1 inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="
                                            job.last_status === 'success'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : job.last_status
                                                    ? 'bg-amber-100 text-amber-700'
                                                    : 'bg-slate-100 text-slate-600'
                                        "
                                    >
                                        {{ job.last_status || '--' }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Last run</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(job.last_run_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Next run</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(job.next_run_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="w-full lg:w-[280px]">
                            <div class="rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Kết quả</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ job.total_success }} success / {{ job.total_failed }} failed</p>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="rounded-[8px] border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="router.push(`/cron-jobs/${job.id}`)">
                                    Xem
                                </button>
                                <button type="button" class="rounded-[8px] border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="router.push(`/cron-jobs/${job.id}/edit`)">
                                    Sửa
                                </button>
                                <button type="button" class="rounded-[8px] border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50" @click="runNow(job)">
                                    <span class="inline-flex items-center gap-1">
                                        <Rocket class="h-3.5 w-3.5" />
                                        Run now
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-[8px] border px-3 py-2 text-xs font-semibold"
                                    :class="job.status === 'active' ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-sky-200 text-sky-700 hover:bg-sky-50'"
                                    @click="toggleJobStatus(job)"
                                >
                                    <span class="inline-flex items-center gap-1">
                                        <component :is="job.status === 'active' ? PauseCircle : PlayCircle" class="h-3.5 w-3.5" />
                                        {{ job.status === 'active' ? 'Pause' : 'Resume' }}
                                    </span>
                                </button>
                                <button type="button" class="rounded-[8px] border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50" @click="deleteJob(job)">
                                    <span class="inline-flex items-center gap-1">
                                        <Trash2 class="h-3.5 w-3.5" />
                                        Xóa
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
