<script setup lang="ts">
import Breadcrumb from "@/components/MasterLayouts/Breadcrumb/index.vue";
import { adminSeoService } from "@/services/admin-seo.service";
import { handleErrorResponse } from "@/utils/response";
import {
    seoReferences,
    seoTechnicalChecklist,
    seoToneClasses,
    seoToneIconClasses,
} from "../data";
import type { AdminSeoOverviewSummary } from "@/types/admin-seo.type";
import { ArrowUpRight, CheckCircle2, Search } from "lucide-vue-next";
import { computed, onMounted, ref } from "vue";

const loading = ref(false);
const summary = ref<AdminSeoOverviewSummary>({
    total_categories: 0,
    indexed_categories: 0,
    total_posts: 0,
    published_posts: 0,
    sitemap_files: 0,
    technical_score: 0,
});

const metricCards = computed(() => [
    {
        label: "Danh mục index",
        value: String(summary.value.indexed_categories),
        description: "Các nhóm nội dung đang được cấu hình title, robots và canonical.",
        tone: "blue" as const,
    },
    {
        label: "Bài viết SEO",
        value: String(summary.value.published_posts),
        description: "Bài viết có slug sạch, meta description và cấu trúc heading rõ ràng.",
        tone: "emerald" as const,
    },
    {
        label: "Sitemap khả dụng",
        value: String(summary.value.sitemap_files),
        description: "Sitemap index, bài viết, danh mục và landing đều có thể submit Search Console.",
        tone: "violet" as const,
    },
    {
        label: "Checklist kỹ thuật",
        value: `${summary.value.technical_score}/100`,
        description: "Đang ưu tiên canonical, robots meta, breadcrumb và schema bài viết.",
        tone: "amber" as const,
    },
]);

const fetchOverview = async (): Promise<void> => {
    try {
        loading.value = true;
        const response = await adminSeoService.overview();
        summary.value = response.summary;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await fetchOverview();
});
</script>

<template>
    <div class="space-y-4">
        <Breadcrumb
            title="Quản trị SEO"
            description="Theo dõi cấu trúc nội dung, sitemap, canonical và checklist SEO kỹ thuật theo Search Central."
        >
            <template #actions>
                <a
                    href="https://developers.google.com/search/docs/fundamentals/seo-starter-guide"
                    target="_blank"
                    rel="noreferrer"
                    class="inline-flex items-center gap-2 rounded-[10px] border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <Search class="h-4 w-4" />
                    Tài liệu SEO chuẩn
                </a>
            </template>
        </Breadcrumb>

        <section class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_360px]">
                <div>
                    <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-violet-700">
                        Search Central
                    </span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-950">
                        Bộ quản trị nội dung và tín hiệu SEO cho landing, blog và trang pháp lý
                    </h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                        Module này gom các phần quan trọng nhất theo tài liệu Google Search Central:
                        title/meta riêng cho từng URL, sitemap rõ cấu trúc, canonical chống trùng lặp,
                        breadcrumb và schema bài viết cho các page cần hiển thị mạnh trên tìm kiếm.
                    </p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <article
                            v-for="metric in metricCards"
                            :key="metric.label"
                            class="rounded-[14px] border p-4"
                            :class="seoToneClasses[metric.tone]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em]">
                                        {{ metric.label }}
                                    </p>
                                    <p class="mt-2 text-3xl font-bold">
                                        {{ loading ? "..." : metric.value }}
                                    </p>
                                    <p class="mt-2 text-sm leading-6 opacity-90">
                                        {{ metric.description }}
                                    </p>
                                </div>
                                <div
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-sm"
                                    :class="seoToneIconClasses[metric.tone]"
                                >
                                    <span class="text-sm font-bold">
                                        {{ metric.label.slice(0, 2).toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <aside class="rounded-[14px] border border-slate-200 bg-slate-50/90 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                        Trụ cột cần ưu tiên
                    </p>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-[12px] border border-slate-200 bg-white p-4">
                            <p class="font-semibold text-slate-900">Content quality</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Mỗi bài phải trả lời đúng intent, có heading rõ ràng và không trùng lặp title/description.
                            </p>
                        </div>
                        <div class="rounded-[12px] border border-slate-200 bg-white p-4">
                            <p class="font-semibold text-slate-900">Technical signals</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Canonical, robots, sitemap và breadcrumb phải khớp URL public đang muốn index.
                            </p>
                        </div>
                        <div class="rounded-[12px] border border-slate-200 bg-white p-4">
                            <p class="font-semibold text-slate-900">Rich results</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Bài viết nên có Article schema, ảnh đại diện và metadata author/date đầy đủ.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_420px]">
            <article class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Checklist SEO kỹ thuật</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Danh sách chốt theo chuẩn index, structured data và trải nghiệm crawl.
                        </p>
                    </div>
                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        Ưu tiên thực thi
                    </span>
                </div>

                <div class="mt-5 grid gap-3">
                    <div
                        v-for="item in seoTechnicalChecklist"
                        :key="item"
                        class="flex gap-3 rounded-[12px] border border-slate-200 bg-slate-50/70 px-4 py-3"
                    >
                        <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                        <p class="text-sm leading-6 text-slate-600">{{ item }}</p>
                    </div>
                </div>
            </article>

            <article class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Nguồn tham chiếu chính thức</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Các page quản trị SEO này được tổ chức theo guideline chính thức từ Google Search Central.
                </p>

                <div class="mt-5 space-y-3">
                    <a
                        v-for="reference in seoReferences"
                        :key="reference.url"
                        :href="reference.url"
                        target="_blank"
                        rel="noreferrer"
                        class="block rounded-[12px] border border-slate-200 bg-slate-50/80 p-4 transition hover:border-violet-200 hover:bg-violet-50/60"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">{{ reference.label }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    {{ reference.summary }}
                                </p>
                            </div>
                            <ArrowUpRight class="h-4 w-4 shrink-0 text-slate-400" />
                        </div>
                    </a>
                </div>
            </article>
        </section>
    </div>
</template>
