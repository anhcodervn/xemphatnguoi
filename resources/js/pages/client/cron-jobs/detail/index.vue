<script setup lang="ts">
import { clientCronJobService, type CronJobItem, type CronJobLogItem } from '@/services/client-cron-job.service';
import { handleErrorResponse } from '@/utils/response';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const cronJob = ref<CronJobItem | null>(null);
const recentLogs = ref<CronJobLogItem[]>([]);
const stats = ref<{
    total_runs: number;
    total_success: number;
    total_failed: number;
    success_rate: number;
    consecutive_failures: number;
    last_status: string | null;
    last_run_at: string | null;
    next_run_at: string | null;
} | null>(null);

const cronJobId = computed(() => route.params.cron_job_id as string);

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

const scheduleLabel = computed(() => {
    if (!cronJob.value) {
        return '--';
    }

    if (cronJob.value.cron_expression) {
        return cronJob.value.cron_expression;
    }

    return `${cronJob.value.interval_seconds ?? '--'} giây`;
});

const loadDetail = async (): Promise<void> => {
    loading.value = true;

    try {
        const [jobResponse, statsResponse] = await Promise.all([
            clientCronJobService.get(cronJobId.value),
            clientCronJobService.stats(cronJobId.value),
        ]);

        cronJob.value = jobResponse.cron_job;
        stats.value = statsResponse.summary;
        recentLogs.value = statsResponse.recent_logs;
    } catch (error) {
        handleErrorResponse(error);
        await router.push('/cron-jobs');
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await loadDetail();
});
</script>

<template>
    <div class="space-y-5 pb-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <div v-if="loading" class="text-sm text-slate-500">Đang tải cron job...</div>
            <div v-else-if="cronJob" class="space-y-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">Cron Job Detail</p>
                        <h1 class="mt-2 break-words text-3xl font-black tracking-[-0.04em] text-slate-950">{{ cronJob.name }}</h1>
                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ cronJob.description || 'Không có mô tả.' }}</p>
                    </div>
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap">
                        <button type="button" class="rounded-[10px] border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="router.push(`/cron-jobs/${cronJob.id}/edit`)">
                            Chỉnh sửa
                        </button>
                        <button type="button" class="rounded-[10px] bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" @click="router.push('/logs')">
                            Xem tất cả logs
                        </button>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Success rate</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ stats?.success_rate ?? 0 }}%</p>
                    </article>
                    <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Total runs</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ stats?.total_runs ?? 0 }}</p>
                    </article>
                    <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Last run</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ formatDate(stats?.last_run_at ?? null) }}</p>
                    </article>
                    <article class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Next run</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ formatDate(stats?.next_run_at ?? null) }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="cronJob" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]">
            <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">Recent Logs</h2>
                <div class="mt-4 grid gap-3">
                    <article v-for="log in recentLogs" :key="log.id" class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ log.status }} • {{ log.status_code ?? '--' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ formatDate(log.started_at) }} • {{ log.duration_ms ?? '--' }} ms</p>
                            </div>
                            <span class="text-xs text-slate-500">Attempt {{ log.attempt }}</span>
                        </div>
                        <p class="mt-3 break-words whitespace-pre-wrap text-sm text-slate-600">{{ log.error_message || log.response_body_preview || 'Không có preview.' }}</p>
                    </article>
                    <div v-if="recentLogs.length === 0" class="rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                        Chưa có log nào cho cron job này.
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">Configuration</h2>
                <dl class="mt-4 grid gap-3">
                    <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="text-xs uppercase tracking-[0.16em] text-slate-400">Request</dt>
                        <dd class="mt-2 break-all text-sm font-semibold text-slate-900">{{ cronJob.method }} {{ cronJob.url }}</dd>
                    </div>
                    <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="text-xs uppercase tracking-[0.16em] text-slate-400">Schedule</dt>
                        <dd class="mt-2 break-all text-sm font-semibold text-slate-900">{{ scheduleLabel }}</dd>
                    </div>
                    <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="text-xs uppercase tracking-[0.16em] text-slate-400">Retry / Timeout</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ cronJob.retry_count }} retries • {{ cronJob.timeout_seconds }}s timeout</dd>
                    </div>
                    <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="text-xs uppercase tracking-[0.16em] text-slate-400">Alerts</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ cronJob.alert_channels?.length ?? 0 }} channels</dd>
                    </div>
                </dl>
            </article>
        </section>
    </div>
</template>
