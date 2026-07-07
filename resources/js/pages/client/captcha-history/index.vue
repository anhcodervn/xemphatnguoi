<script setup lang="ts">
import Breadcrumb from '@/components/MasterLayouts/Breadcrumb/index.vue';
import {
    clientCaptchaService,
    type ClientCaptchaTaskItem,
} from '@/services/client-captcha.service';
import { handleErrorResponse } from '@/utils/response';
import {
    CheckCheck,
    ChevronLeft,
    ChevronRight,
    Clock3,
    LoaderCircle,
    Search,
    ShieldX,
    WalletCards,
} from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';

type OverviewSummary = {
    total_tasks: number;
    pending_tasks: number;
    solved_tasks: number;
    failed_tasks: number;
    spent: number;
};

const loading = ref(true);
const loadingOverview = ref(true);
const tasks = ref<ClientCaptchaTaskItem[]>([]);
const summary = ref<OverviewSummary>({
    total_tasks: 0,
    pending_tasks: 0,
    solved_tasks: 0,
    failed_tasks: 0,
    spent: 0,
});

const meta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
});

const filters = reactive({
    status: '',
    service_code: '',
    task_code: '',
});

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const statusOptions = [
    { label: 'Tất cả trạng thái', value: '' },
    { label: 'Đang chờ', value: 'pending' },
    { label: 'Đang xử lý', value: 'processing' },
    { label: 'Đã giải xong', value: 'solved' },
    { label: 'Thất bại', value: 'failed' },
];

const metricCards = computed(() => [
    {
        label: 'Tổng yêu cầu',
        value: summary.value.total_tasks,
        icon: WalletCards,
        tone: 'bg-teal-50 text-teal-700',
    },
    {
        label: 'Đã giải xong',
        value: summary.value.solved_tasks,
        icon: CheckCheck,
        tone: 'bg-teal-50 text-teal-700',
    },
    {
        label: 'Đang chờ xử lý',
        value: summary.value.pending_tasks,
        icon: Clock3,
        tone: 'bg-lime-50 text-lime-700',
    },
    {
        label: 'Đã thất bại',
        value: summary.value.failed_tasks,
        icon: ShieldX,
        tone: 'bg-rose-50 text-rose-700',
    },
]);

const formatDateTime = (value: string | null): string => {
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

const formatMoney = (value: string | number): string => {
    const amount = Number(value);

    if (Number.isNaN(amount)) {
        return `${value} đ`;
    }

    return `${amount.toLocaleString('vi-VN')} đ`;
};

const formatProcessingTime = (task: ClientCaptchaTaskItem): string => {
    if (task.processing_time_label && task.processing_time_label.trim().length > 0) {
        return task.processing_time_label;
    }

    if (typeof task.processing_seconds === 'number' && task.processing_seconds > 0) {
        return `${task.processing_seconds}s`;
    }

    return '--';
};

const statusBadgeClass = (status: string): string => {
    const classes: Record<string, string> = {
        pending: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        processing: 'bg-teal-50 text-teal-700 ring-1 ring-teal-200',
        solved: 'bg-teal-50 text-teal-700 ring-1 ring-teal-200',
        failed: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
    };

    return classes[status] ?? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
};

const statusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        pending: 'Đang chờ',
        processing: 'Đang xử lý',
        solved: 'Đã giải',
        failed: 'Thất bại',
    };

    return labels[status] ?? status;
};

const extractSolutionText = (task: ClientCaptchaTaskItem): string => {
    if (!task.result_payload) {
        return '--';
    }

    const candidateKeys = ['text', 'token', 'answer', 'solution', 'code'];

    for (const key of candidateKeys) {
        const value = task.result_payload[key];

        if (typeof value === 'string' && value.trim().length > 0) {
            return value;
        }
    }

    return JSON.stringify(task.result_payload);
};

const loadOverview = async (): Promise<void> => {
    try {
        loadingOverview.value = true;
        const response = await clientCaptchaService.overview();
        summary.value = response.summary;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingOverview.value = false;
    }
};

const loadTasks = async (page = 1): Promise<void> => {
    try {
        loading.value = true;

        const response = await clientCaptchaService.tasks({
            page,
            per_page: meta.per_page,
            status: filters.status || undefined,
            service_code: filters.service_code.trim() || undefined,
            task_code: filters.task_code.trim() || undefined,
        });

        tasks.value = response.tasks.data ?? [];
        meta.current_page = response.tasks.current_page;
        meta.last_page = response.tasks.last_page;
        meta.per_page = response.tasks.per_page;
        meta.total = response.tasks.total;
        meta.from = response.tasks.from ?? 0;
        meta.to = response.tasks.to ?? 0;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const reloadAll = async (): Promise<void> => {
    await Promise.all([loadOverview(), loadTasks(1)]);
};

watch(
    () => filters.status,
    async () => {
        await loadTasks(1);
    },
);

watch(
    () => [filters.service_code, filters.task_code],
    () => {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        searchTimer = setTimeout(() => {
            void loadTasks(1);
        }, 350);
    },
);

onMounted(async () => {
    await reloadAll();
});
</script>

<template>
    <div class="space-y-5">
        <Breadcrumb
            title="Lịch sử giải captcha"
            description="Theo dõi toàn bộ yêu cầu captcha đã gửi, trạng thái xử lý, thời gian hoàn tất và chi phí thực tế của tài khoản."
        >
            <template #actions>
                <div class="flex flex-wrap items-center gap-3">
                    <RouterLink
                        to="/api-docs"
                        class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-500"
                    >
                        Tài liệu API
                    </RouterLink>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-xl border border-teal-100 bg-white px-4 py-2.5 text-sm font-semibold text-teal-700 transition hover:bg-teal-50"
                        @click="reloadAll"
                    >
                        Làm mới dữ liệu
                    </button>
                </div>
            </template>
        </Breadcrumb>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="card in metricCards"
                :key="card.label"
                class="rounded-[14px] border border-teal-100 bg-white/95 p-4 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ card.label }}</p>
                        <p class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950">
                            {{ loadingOverview ? '--' : card.value }}
                        </p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl" :class="card.tone">
                        <component :is="card.icon" class="h-5 w-5" />
                    </div>
                </div>
            </article>
        </section>

        <section class="rounded-[14px] border border-teal-100 bg-white/95 p-4 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)] sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-950">Lọc lịch sử solve</h2>
                    <p class="mt-1 text-sm text-slate-500">Tìm nhanh theo mã task, mã dịch vụ hoặc trạng thái xử lý.</p>
                </div>

                <div class="rounded-xl bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-700">
                    Tổng chi phí: {{ formatMoney(summary.spent) }}
                </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_220px]">
                <label class="flex items-center gap-3 rounded-xl border border-teal-100 bg-teal-50/70 px-3 py-3">
                    <Search class="h-4 w-4 text-teal-500" />
                    <input
                        v-model="filters.task_code"
                        type="text"
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 outline-none ring-0 placeholder:text-slate-400"
                        placeholder="Tìm theo mã task code"
                    />
                </label>

                <input
                    v-model="filters.service_code"
                    type="text"
                    class="rounded-xl border border-teal-100 bg-teal-50/70 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-teal-300 focus:ring-2 focus:ring-teal-100"
                    placeholder="Lọc theo mã dịch vụ"
                />

                <select
                    v-model="filters.status"
                    class="rounded-xl border border-teal-100 bg-teal-50/70 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-teal-300 focus:ring-2 focus:ring-teal-100"
                >
                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
        </section>

        <section class="overflow-hidden rounded-[14px] border border-teal-100 bg-white/95 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)]">
            <div v-if="loading" class="px-6 py-12 text-center text-sm text-slate-500">
                <div class="inline-flex items-center gap-2">
                    <LoaderCircle class="h-4 w-4 animate-spin" />
                    Đang tải lịch sử solve captcha...
                </div>
            </div>

            <div v-else-if="tasks.length === 0" class="px-6 py-12 text-center text-sm text-slate-500">
                Chưa có yêu cầu captcha nào khớp bộ lọc hiện tại.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-[1260px] w-full">
                    <thead class="bg-teal-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-teal-700">
                        <tr>
                            <th class="px-5 py-4">Task</th>
                            <th class="px-4 py-4">Dịch vụ</th>
                            <th class="px-4 py-4">Trạng thái</th>
                            <th class="px-4 py-4">Tốc độ giải</th>
                            <th class="px-4 py-4">Kết quả</th>
                            <th class="px-4 py-4">Chi phí</th>
                            <th class="px-4 py-4">Thời gian tạo</th>
                            <th class="px-4 py-4">Hoàn tất</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <tr v-for="task in tasks" :key="task.id" class="align-top hover:bg-teal-50/50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ task.task_code }}</p>
                                <p class="mt-1 text-xs text-slate-500">API: {{ task.external_task_id || '--' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-900">{{ task.service?.name || task.service_code }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ task.service_code }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(task.status)">
                                    {{ statusLabel(task.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 font-semibold text-teal-700">{{ formatProcessingTime(task) }}</td>
                            <td class="px-4 py-4">
                                <p class="max-w-[280px] truncate font-medium text-slate-700">
                                    {{ extractSolutionText(task) }}
                                </p>
                                <p v-if="task.error_message" class="mt-1 max-w-[280px] truncate text-xs text-rose-600">
                                    {{ task.error_message }}
                                </p>
                            </td>
                            <td class="px-4 py-4 font-semibold text-slate-900">{{ formatMoney(task.selling_price) }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ formatDateTime(task.requested_at || task.created_at) }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ formatDateTime(task.solved_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>Hiển thị {{ meta.from || 0 }}-{{ meta.to || tasks.length }} trên tổng {{ meta.total }} yêu cầu</p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-teal-100 bg-white px-3 py-2 text-teal-700 transition hover:bg-teal-50 disabled:cursor-not-allowed disabled:text-slate-300"
                        :disabled="meta.current_page <= 1 || loading"
                        @click="loadTasks(meta.current_page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <span class="rounded-xl bg-teal-600 px-3 py-2 text-xs font-semibold text-white">
                        {{ meta.current_page }} / {{ meta.last_page }}
                    </span>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-teal-100 bg-white px-3 py-2 text-teal-700 transition hover:bg-teal-50 disabled:cursor-not-allowed disabled:text-slate-300"
                        :disabled="meta.current_page >= meta.last_page || loading"
                        @click="loadTasks(meta.current_page + 1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
