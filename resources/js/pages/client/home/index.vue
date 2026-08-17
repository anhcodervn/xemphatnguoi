<script setup lang="ts">
import { trafficFineService, type TrafficFineDashboard } from '@/services/traffic-fine.service';
import formatCash from '@/utils/helpers/formatCash';
import { BarChart3, History, Search, Wallet } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const dashboard = ref<TrafficFineDashboard | null>(null);
const loading = ref(true);
const errorMessage = ref('');
const chartMaximum = computed(() => Math.max(1, ...(dashboard.value?.api_chart.map((item) => item.requests) ?? [1])));

const loadDashboard = async (): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';

    try {
        dashboard.value = await trafficFineService.dashboard();
    } catch {
        errorMessage.value = 'Không thể tải dữ liệu tổng quan. Vui lòng thử lại.';
    } finally {
        loading.value = false;
    }
};

onMounted(loadDashboard);
</script>

<template>
    <div class="grid gap-7">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-sky-700">Dashboard</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Tổng quan tài khoản</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Theo dõi số dư, lượt gọi API và chi phí theo thời gian thực.</p>
            </div>
            <RouterLink
                to="/dashboard/api"
                class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-sky-700 px-5 text-sm font-bold text-white hover:bg-sky-800"
            >
                <Search class="h-4 w-4" /> Tích hợp API
            </RouterLink>
        </header>

        <div v-if="loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="index in 4" :key="index" class="h-32 animate-pulse rounded-xl bg-slate-200/70" />
        </div>
        <div v-else-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <p>{{ errorMessage }}</p>
            <button type="button" class="mt-3 font-bold underline" @click="loadDashboard">Thử lại</button>
        </div>
        <template v-else-if="dashboard">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in [
                        { label: 'Số dư ví', value: `${formatCash(Number(dashboard.wallet.balance))}đ`, icon: Wallet },
                        { label: 'Giá mỗi request', value: `${formatCash(dashboard.api_request_price)}đ`, icon: Search },
                        { label: 'API tháng này', value: dashboard.api_usage.requests_month.toLocaleString('vi-VN'), icon: BarChart3 },
                        { label: 'Chi phí tháng này', value: `${formatCash(Number(dashboard.api_usage.amount_month))}đ`, icon: History },
                    ]"
                    :key="card.label"
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ card.value }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700"
                            ><component :is="card.icon" class="h-5 w-5"
                        /></span>
                    </div>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
                <article class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">Lượt gọi API trong 14 ngày</h2>
                            <p class="mt-1 text-sm text-slate-500">Chỉ tính các request đã trừ tiền thành công.</p>
                        </div>
                        <RouterLink to="/dashboard/api-usage" class="text-sm font-bold text-sky-700">Xem log chi tiết →</RouterLink>
                    </div>
                    <div class="mt-6 flex h-52 items-end gap-2 border-b border-slate-200 px-1">
                        <div
                            v-for="item in dashboard.api_chart"
                            :key="item.date"
                            class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-2"
                        >
                            <span class="text-[10px] font-bold text-slate-500 opacity-0 transition group-hover:opacity-100">{{ item.requests }}</span>
                            <div
                                class="w-full max-w-8 rounded-t bg-sky-600 transition hover:bg-sky-700"
                                :style="{ height: `${Math.max(4, (item.requests / chartMaximum) * 160)}px` }"
                                :title="`${item.label}: ${item.requests} request`"
                            />
                            <span class="hidden text-[10px] text-slate-400 sm:block">{{ item.label }}</span>
                        </div>
                    </div>
                </article>

                <aside class="rounded-xl bg-slate-950 p-6 text-white">
                    <p class="text-sm font-bold text-sky-300">Trả theo lượt dùng</p>
                    <p class="mt-2 text-3xl font-black">
                        {{ formatCash(dashboard.api_request_price) }}đ <span class="text-sm font-semibold text-slate-300">/ request</span>
                    </p>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Không cần mua gói. Website tra cứu miễn phí; chỉ API v1 thành công mới trừ tiền, kể cả kết quả lấy từ cache.
                    </p>
                    <div class="mt-6 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="text-slate-400">Xe của tôi</p>
                            <p class="mt-1 text-xl font-black">{{ dashboard.vehicle_count }}</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="text-slate-400">Đang theo dõi</p>
                            <p class="mt-1 text-xl font-black">{{ dashboard.monitoring_count }}</p>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-slate-950">Tra cứu website gần đây</h2>
                    <RouterLink to="/dashboard/history" class="text-sm font-bold text-sky-700">Xem tất cả</RouterLink>
                </div>
                <div v-if="dashboard.recent_lookups.length" class="mt-5 divide-y divide-slate-200">
                    <div
                        v-for="item in dashboard.recent_lookups"
                        :key="item.id"
                        class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0"
                    >
                        <div>
                            <p class="font-bold text-slate-950">{{ item.plate }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ new Date(item.created_at).toLocaleString('vi-VN') }}</p>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-bold"
                            :class="item.violation_count ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'"
                            >{{ item.violation_count ? `${item.violation_count} lỗi` : 'Chưa ghi nhận lỗi' }}</span
                        >
                    </div>
                </div>
                <p v-else class="mt-5 rounded-lg bg-slate-50 p-5 text-sm text-slate-500">Bạn chưa có lịch sử tra cứu.</p>
            </section>
        </template>
    </div>
</template>
