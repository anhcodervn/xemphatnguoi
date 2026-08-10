<script setup lang="ts">
import { adminAnalyticsService } from '@/services/admin-analytics.service';
import { adminProxyProductService } from '@/services/admin-proxy-product.service';
import { adminProxyProviderService } from '@/services/admin-proxy-provider.service';
import { handleErrorResponse } from '@/utils/response';
import { BarChart3, Database, KeyRound, Settings2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const metrics = ref([
    { label: 'Nguồn solve', value: '--', icon: Database },
    { label: 'Sản phẩm proxy', value: '--', icon: Settings2 },
    { label: 'Lợi nhuận 7 ngày', value: '--', icon: BarChart3 },
    { label: 'Mô hình SaaS', value: 'B2B API', icon: KeyRound },
]);

const loadDashboard = async (): Promise<void> => {
    try {
        const [providers, products, analytics] = await Promise.all([
            adminProxyProviderService.list(),
            adminProxyProductService.list(),
            adminAnalyticsService.dashboard('7d'),
        ]);

        metrics.value[0].value = String(providers.providers?.total ?? providers.providers?.data?.length ?? 0);
        metrics.value[1].value = String(products.products?.total ?? products.products?.data?.length ?? 0);
        metrics.value[2].value = `${Math.round(analytics.summary.gross_profit).toLocaleString('vi-VN')} đ`;
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
        <section
            class="rounded-[10px] border border-slate-200 bg-[linear-gradient(135deg,#052e16_0%,#065f46_48%,#164e63_100%)] p-6 text-white shadow-sm"
        >
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-100">Admin workspace</p>
            <h1 class="mt-3 text-3xl font-black tracking-[-0.05em]">Điều hành hệ thống bán proxy qua nguồn thứ ba</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-emerald-50/90">
                Tập trung quản lý nguồn solve bên thứ 3, cấu hình giá gốc và giá bán cho từng dịch vụ proxy, đồng thời theo dõi request toàn hệ thống.
            </p>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <RouterLink
                    to="/admin/proxy-providers"
                    class="rounded-[10px] border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white"
                >
                    Quản lý nguồn solve
                </RouterLink>
                <RouterLink
                    to="/admin/proxy-products"
                    class="rounded-[10px] border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white"
                >
                    Quản lý sản phẩm
                </RouterLink>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
