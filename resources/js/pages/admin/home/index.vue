<script setup lang="ts">
import { adminCaptchaServiceService } from '@/services/admin-captcha-service.service';
import { adminCaptchaSourceService } from '@/services/admin-captcha-source.service';
import { adminCaptchaTaskService } from '@/services/admin-captcha-task.service';
import { adminAnalyticsService } from '@/services/admin-analytics.service';
import { handleErrorResponse } from '@/utils/response';
import { BarChart3, Database, KeyRound, ListChecks, Settings2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const metrics = ref([
    { label: 'Nguồn solve', value: '--', icon: Database },
    { label: 'Dịch vụ captcha', value: '--', icon: Settings2 },
    { label: 'Yêu cầu gần đây', value: '--', icon: ListChecks },
    { label: 'Lợi nhuận 7 ngày', value: '--', icon: BarChart3 },
    { label: 'Mô hình SaaS', value: 'B2B API', icon: KeyRound },
]);

const loadDashboard = async (): Promise<void> => {
    try {
        const [sources, services, tasks, analytics] = await Promise.all([
            adminCaptchaSourceService.list(),
            adminCaptchaServiceService.list(),
            adminCaptchaTaskService.list(),
            adminAnalyticsService.dashboard('7d'),
        ]);

        metrics.value[0].value = String(sources.sources?.total ?? sources.sources?.data?.length ?? 0);
        metrics.value[1].value = String(services.services?.total ?? services.services?.data?.length ?? 0);
        metrics.value[2].value = String(tasks.tasks?.total ?? tasks.tasks?.data?.length ?? 0);
        metrics.value[3].value = `${Math.round(analytics.summary.gross_profit).toLocaleString('vi-VN')} đ`;
    } catch (error) {
        handleErrorResponse(error);
    }
};

onMounted(async () => {
    await loadDashboard();
});
</script>

<template>
    <div class="space-y-5">
        <section class="rounded-[10px] border border-slate-200 bg-[linear-gradient(135deg,#052e16_0%,#065f46_48%,#164e63_100%)] p-6 text-white shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-100">Admin workspace</p>
            <h1 class="mt-3 text-3xl font-black tracking-[-0.05em]">Điều hành SaaS giải captcha qua API</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-emerald-50/90">
                Tập trung quản lý nguồn solve bên thứ 3, cấu hình giá gốc và giá bán cho từng dịch vụ captcha, đồng thời theo dõi request toàn hệ thống.
            </p>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <RouterLink to="/admin/captcha-sources" class="rounded-[10px] border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white">
                    Quản lý nguồn solve
                </RouterLink>
                <RouterLink to="/admin/captcha-services" class="rounded-[10px] border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white">
                    Quản lý dịch vụ
                </RouterLink>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article v-for="metric in metrics" :key="metric.label" class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ metric.label }}</p>
                        <p class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950">{{ metric.value }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-emerald-50 text-emerald-700">
                        <component :is="metric.icon" class="h-5 w-5" />
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            <article class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Báo cáo vận hành</h2>
                        <p class="text-sm text-slate-500">Xem doanh thu, chi phí, lời lỗ và webhook giám sát theo thời gian thực.</p>
                    </div>

                    <RouterLink
                        to="/admin/analytics"
                        class="inline-flex items-center rounded-[10px] bg-teal-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700"
                    >
                        Mở trang thống kê
                    </RouterLink>
                </div>
            </article>
        </section>
    </div>
</template>
