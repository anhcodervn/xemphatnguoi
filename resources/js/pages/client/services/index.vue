<script setup lang="ts">
import Breadcrumb from '@/components/MasterLayouts/Breadcrumb/index.vue';
import {
    clientCaptchaService,
    type ClientCaptchaServiceItem,
} from '@/services/client-captcha.service';
import { handleErrorResponse } from '@/utils/response';
import { Bot, Check, Clock3 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const loading = ref(true);
const services = ref<ClientCaptchaServiceItem[]>([]);

const sortedServices = computed(() =>
    [...services.value].sort((left, right) => {
        const orderDiff = Number(left.sort_order ?? 0) - Number(right.sort_order ?? 0);

        if (orderDiff !== 0) {
            return orderDiff;
        }

        return Number(left.id) - Number(right.id);
    }),
);

const loadServices = async (): Promise<void> => {
    try {
        loading.value = true;
        const response = await clientCaptchaService.services();
        services.value = response.services ?? [];
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const formatDescription = (service: ClientCaptchaServiceItem): string => {
    if (service.description && service.description.trim().length > 0) {
        return service.description.trim();
    }

    return `Dịch vụ ${service.name} tốc độ ${service.stats.processing_time_label}, tỉ lệ thành công ${service.stats.success_rate}%.`;
};

onMounted(async () => {
    await loadServices();
});
</script>

<template>
    <div class="space-y-5">
        <Breadcrumb
            title="Dịch vụ captcha"
            description="Danh sách đầy đủ các loại captcha đang mở bán với giá, tốc độ xử lý và tỉ lệ thành công thực tế."
        >
            <template #actions>
                <div class="flex flex-wrap items-center gap-3">
                    <RouterLink
                        to="/api-docs"
                        class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-500"
                    >
                        Tài liệu API
                    </RouterLink>
                    <RouterLink
                        to="/wallet"
                        class="inline-flex items-center rounded-xl border border-teal-100 bg-white px-4 py-2.5 text-sm font-semibold text-teal-700 transition hover:bg-teal-50"
                    >
                        Nạp tiền ví
                    </RouterLink>
                </div>
            </template>
        </Breadcrumb>

        <section
            class="overflow-hidden rounded-[16px] border border-teal-100 bg-white shadow-[0_22px_60px_-32px_rgba(8,145,178,0.32)]"
        >
            <div
                class="grid min-w-[760px] grid-cols-[minmax(320px,1.9fr)_minmax(140px,0.8fr)_minmax(120px,0.7fr)_minmax(150px,0.8fr)] bg-[linear-gradient(135deg,_#0f766e_0%,_#0891b2_100%)] text-sm font-bold text-white"
            >
                <div class="px-6 py-4">Loại captcha</div>
                <div class="px-5 py-4">Giá /1 lần giải</div>
                <div class="px-5 py-4">Tốc độ</div>
                <div class="px-5 py-4">Tỷ lệ thành công</div>
            </div>

            <div v-if="loading" class="min-w-[760px] px-6 py-12 text-center text-sm text-slate-500">
                Đang tải danh sách dịch vụ...
            </div>

            <div v-else-if="sortedServices.length === 0" class="min-w-[760px] px-6 py-12 text-center text-sm text-slate-500">
                Chưa có dịch vụ captcha nào đang được mở bán.
            </div>

            <div v-else class="min-w-[760px] divide-y divide-slate-100">
                <div
                    v-for="service in sortedServices"
                    :key="service.id"
                    class="grid grid-cols-[minmax(320px,1.9fr)_minmax(140px,0.8fr)_minmax(120px,0.7fr)_minmax(150px,0.8fr)] items-center bg-white transition hover:bg-teal-50/50"
                >
                    <div class="px-6 py-5">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-teal-100 bg-teal-50 text-teal-700"
                            >
                                <img
                                    v-if="service.settings?.icon_url"
                                    :src="service.settings.icon_url"
                                    :alt="`${service.name} icon`"
                                    class="h-full w-full object-cover"
                                />
                                <Bot v-else class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <span class="inline-flex rounded-full bg-teal-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-teal-700">
                                    {{ service.code }}
                                </span>
                                <p class="text-[15px] font-bold leading-6 text-slate-900">
                                    {{ service.name }}
                                </p>
                                <p class="mt-1 max-w-[280px] whitespace-pre-line text-sm leading-6 text-slate-600">
                                    {{ formatDescription(service) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-5 text-[15px] font-bold text-slate-700">
                        {{ service.selling_price }} đ
                    </div>

                    <div class="px-5 py-5">
                        <span class="inline-flex items-center gap-2 text-[15px] font-bold text-slate-700">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-teal-500 text-white">
                                <Clock3 class="h-3.5 w-3.5" />
                            </span>
                            {{ service.stats.processing_time_label }}
                        </span>
                    </div>

                    <div class="px-5 py-5">
                        <span class="inline-flex items-center gap-2 text-[15px] font-bold text-slate-700">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-teal-500 text-white">
                                <Check class="h-3.5 w-3.5 stroke-[3]" />
                            </span>
                            {{ service.stats.success_rate }}%
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
