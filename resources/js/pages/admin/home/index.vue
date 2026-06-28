<script setup lang="ts">
import { adminCronJobService } from '@/services/admin-cron-job.service';
import { adminUserService } from '@/services/admin-user.service';
import { handleErrorResponse } from '@/utils/response';
import { AlertTriangle, Clock3, PlayCircle, Users, Zap } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const loading = ref(true);
const metrics = ref([
    { key: 'users', label: 'Tổng users', value: '--', note: 'Đang tải', icon: Users, tone: 'bg-sky-50 text-sky-700' },
    { key: 'cron-jobs', label: 'Cron jobs', value: '--', note: 'Đang tải', icon: Clock3, tone: 'bg-emerald-50 text-emerald-700' },
    { key: 'runs-today', label: 'Runs hôm nay', value: '--', note: 'Đang tải', icon: PlayCircle, tone: 'bg-violet-50 text-violet-700' },
    { key: 'failed-today', label: 'Fail hôm nay', value: '--', note: 'Đang tải', icon: AlertTriangle, tone: 'bg-amber-50 text-amber-700' },
]);
const recentFailedLogs = ref<Array<{ id: number; status: string; status_code: number | null; url: string; error_message: string | null; started_at: string | null }>>([]);

const formatDate = (value: string | null): string => {
    if (!value) {
        return '--';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const loadDashboard = async (): Promise<void> => {
    loading.value = true;

    try {
        const [users, cronJobs, failedLogs] = await Promise.all([
            adminUserService.list({ per_page: 1 }),
            adminCronJobService.list({ per_page: 1 }),
            adminCronJobService.globalLogs({ per_page: 5, status: 'failed' }),
        ]);

        metrics.value = metrics.value.map((metric) => {
            if (metric.key === 'users') {
                return {
                    ...metric,
                    value: String(users.stats.total_users),
                    note: `${users.stats.active_users} active / ${users.stats.blocked_users} blocked`,
                };
            }

            if (metric.key === 'cron-jobs') {
                return {
                    ...metric,
                    value: String(cronJobs.summary.total_jobs),
                    note: `${cronJobs.summary.active_jobs} active / ${cronJobs.summary.paused_jobs} paused`,
                };
            }

            if (metric.key === 'runs-today') {
                return {
                    ...metric,
                    value: String(cronJobs.summary.runs_today),
                    note: `${cronJobs.summary.disabled_jobs} jobs disabled`,
                };
            }

            return {
                ...metric,
                value: String(cronJobs.summary.failed_today),
                note: cronJobs.summary.failed_today > 0 ? 'Nên review system logs ngay' : 'Không có lỗi mới',
            };
        });

        recentFailedLogs.value = failedLogs.data;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await loadDashboard();
});
</script>

<template>
    <div class="space-y-5">
        <section
            class="overflow-hidden rounded-[10px] border border-slate-200 bg-[radial-gradient(circle_at_top_right,rgba(96,165,250,0.24),transparent_28%),linear-gradient(135deg,#0f172a_0%,#162456_45%,#1d4ed8_100%)] px-4 py-6 text-white shadow-[0_28px_80px_rgba(15,23,42,0.18)] sm:px-6"
        >
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.8fr)] xl:items-end">
                <div class="min-w-0 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-blue-100/80">Admin workspace</p>
                    <h1 class="max-w-3xl text-3xl font-black tracking-[-0.04em] text-white md:text-4xl">Điều phối AutoCron SaaS theo user, queue và failed runs</h1>
                    <p class="max-w-2xl text-sm leading-7 text-blue-50/80">
                        Dashboard này gom các chỉ số cần nhất cho vận hành HTTP cron: số lượng job đang chạy, runs hôm nay, lỗi cần review và đường tắt vào module quản trị.
                    </p>
                </div>

                <div class="grid gap-3">
                    <RouterLink to="/admin/cron-jobs" class="rounded-[10px] border border-white/15 bg-white/10 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-white/15">
                        Mở quản lý cron jobs
                    </RouterLink>
                    <RouterLink to="/admin/system-logs" class="rounded-[10px] border border-white/15 bg-white/10 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-white/15">
                        Xem failed logs
                    </RouterLink>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article v-for="metric in metrics" :key="metric.key" class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-500">{{ metric.label }}</p>
                        <p class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">{{ metric.value }}</p>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px]" :class="metric.tone">
                        <component :is="metric.icon" class="h-5 w-5" />
                    </div>
                </div>
                <p class="mt-4 text-sm text-slate-500">{{ metric.note }}</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(280px,0.9fr)]">
            <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Failed runs gần đây</h2>
                        <p class="mt-1 text-sm text-slate-500">Ưu tiên review lỗi timeout, blocked và response mismatch trước.</p>
                    </div>
                    <RouterLink to="/admin/system-logs" class="text-sm font-semibold text-sky-700">Xem tất cả</RouterLink>
                </div>

                <div class="mt-4 grid gap-3">
                    <article v-for="log in recentFailedLogs" :key="log.id" class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-all text-sm font-semibold text-slate-900">{{ log.url }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ log.status }} • {{ log.status_code ?? '--' }} • {{ formatDate(log.started_at) }}</p>
                            </div>
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-amber-100 text-amber-700">
                                <Zap class="h-4 w-4" />
                            </div>
                        </div>
                        <p class="mt-3 line-clamp-3 break-words text-sm text-slate-600">{{ log.error_message || 'Không có message chi tiết.' }}</p>
                    </article>
                    <div v-if="!loading && recentFailedLogs.length === 0" class="rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                        Chưa có failed log nào gần đây.
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Lối tắt quản trị</h2>
                <div class="mt-4 grid gap-3">
                    <RouterLink to="/admin/cron-jobs" class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 transition hover:bg-white">
                        Quản lý cron jobs toàn hệ thống
                    </RouterLink>
                    <RouterLink to="/admin/packages" class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 transition hover:bg-white">
                        Cấu hình package limits
                    </RouterLink>
                    <RouterLink to="/admin/users" class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 transition hover:bg-white">
                        Kiểm tra usage theo user
                    </RouterLink>
                    <RouterLink to="/admin/queues" class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 transition hover:bg-white">
                        Review queue và failed jobs
                    </RouterLink>
                </div>
            </article>
        </section>
    </div>
</template>
