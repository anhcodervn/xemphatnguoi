<script setup lang="ts">
import {
    adminTrafficFineService,
    type AdminCachedPlate,
    type AdminCachedPlateFilters,
    type AdminCachedPlateResponse,
} from '@/services/admin-traffic-fine.service';
import { Activity, AlertTriangle, CheckCircle2, Clock3, Database, RefreshCw, Search, Server, TimerReset, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

const periodOptions = [7, 30, 90] as const;
const vehicleLabels: Record<string, string> = {
    car: 'Ô tô',
    motorbike: 'Xe máy',
    electric_motorbike: 'Xe máy điện',
};

const createDefaultFilters = (): AdminCachedPlateFilters => ({
    days: 30,
    search: '',
    state: 'all',
    vehicle_type: '',
    status: '',
    sort: 'lookup_count',
    direction: 'desc',
    per_page: 25,
    page: 1,
});

const filters = reactive<AdminCachedPlateFilters>(createDefaultFilters());
const result = ref<AdminCachedPlateResponse | null>(null);
const loading = ref(true);
const errorMessage = ref('');
const clock = ref(Date.now());
const serverOffsetMilliseconds = ref(0);
let clockTimer: ReturnType<typeof setInterval> | undefined;

const items = computed(() => result.value?.items ?? []);
const meta = computed(() => result.value?.meta ?? null);
const periodLabel = computed(() => {
    if (!result.value) {
        return '';
    }

    return `${formatDate(result.value.period.from)} – ${formatDate(result.value.period.to)}`;
});
const summaryCards = computed(() => {
    if (!result.value) {
        return [];
    }

    const summary = result.value.summary;

    return [
        {
            label: 'Biển số đã cache',
            value: formatNumber(summary.total_entries),
            note: `${formatNumber(summary.violation_entries)} biển có vi phạm`,
            icon: Database,
            tone: 'bg-sky-50 text-sky-700',
        },
        {
            label: 'Cache còn hạn',
            value: formatNumber(summary.active_entries),
            note: `${formatNumber(summary.expiring_entries)} sắp hết trong 1 giờ`,
            icon: CheckCircle2,
            tone: 'bg-emerald-50 text-emerald-700',
        },
        {
            label: 'Cache hết hạn',
            value: formatNumber(summary.expired_entries),
            note: 'Không được dùng cho lần tra cứu mới',
            icon: TimerReset,
            tone: 'bg-amber-50 text-amber-700',
        },
        {
            label: `Tra cứu ${result.value.period.days} ngày`,
            value: formatNumber(summary.period_lookups),
            note: `${formatNumber(summary.period_provider_requests)} lượt gọi provider`,
            icon: Activity,
            tone: 'bg-indigo-50 text-indigo-700',
        },
        {
            label: 'Cache hit kết quả',
            value: `${formatNumber(summary.positive_cache_hit_rate)}%`,
            note: `${formatNumber(summary.period_positive_cache_hits)} lượt từ Redis/Database`,
            icon: Server,
            tone: 'bg-violet-50 text-violet-700',
        },
        {
            label: 'TTL cấu hình',
            value: formatDuration(result.value.cache.configured_ttl_seconds),
            note: `Store: ${result.value.cache.store}`,
            icon: Clock3,
            tone: 'bg-slate-100 text-slate-700',
        },
    ];
});

const formatNumber = (value: number): string => value.toLocaleString('vi-VN', { maximumFractionDigits: 2 });
const formatDate = (value: string): string => new Date(`${value}T00:00:00`).toLocaleDateString('vi-VN');
const formatDateTime = (value: string | null): string => (value ? new Date(value).toLocaleString('vi-VN') : 'Chưa có lượt tra cứu');

const formatDuration = (seconds: number): string => {
    if (seconds <= 0) {
        return 'Đã hết hạn';
    }

    if (seconds < 60) {
        return '< 1 phút';
    }

    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (days > 0) {
        return `${days} ngày${hours > 0 ? ` ${hours} giờ` : ''}`;
    }

    if (hours > 0) {
        return `${hours} giờ${minutes > 0 ? ` ${minutes} phút` : ''}`;
    }

    return `${minutes} phút`;
};

const remainingSeconds = (item: AdminCachedPlate): number => {
    const serverNow = clock.value + serverOffsetMilliseconds.value;

    return Math.max(0, Math.floor((new Date(item.expires_at).getTime() - serverNow) / 1000));
};

const liveCacheState = (item: AdminCachedPlate): AdminCachedPlate['cache_state'] => {
    const remaining = remainingSeconds(item);

    if (remaining === 0) {
        return 'expired';
    }

    return remaining <= 3600 ? 'expiring' : 'active';
};

const cacheStateLabel = (item: AdminCachedPlate): string => {
    return {
        active: 'Còn hạn',
        expiring: 'Sắp hết hạn',
        expired: 'Hết hạn',
    }[liveCacheState(item)];
};

const cacheStateClass = (item: AdminCachedPlate): string => {
    return {
        active: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        expiring: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        expired: 'bg-slate-100 text-slate-600 ring-slate-500/20',
    }[liveCacheState(item)];
};

const vehicleLabel = (vehicleType: string): string => {
    return vehicleLabels[vehicleType] ?? vehicleType;
};

const statusLabel = (status: string): string => (status === 'success' ? 'Có vi phạm' : 'Không vi phạm');

const load = async (page = 1): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';
    filters.page = page;

    try {
        const response = await adminTrafficFineService.results({ ...filters });
        result.value = response;
        serverOffsetMilliseconds.value = new Date(response.server_time).getTime() - Date.now();
        clock.value = Date.now();
    } catch {
        errorMessage.value = 'Không thể tải dữ liệu cache biển số. Vui lòng thử lại.';
    } finally {
        loading.value = false;
    }
};

const selectPeriod = (days: 7 | 30 | 90): void => {
    if (filters.days === days && result.value) {
        return;
    }

    filters.days = days;
    void load();
};

const resetFilters = (): void => {
    Object.assign(filters, createDefaultFilters(), { days: filters.days });
    void load();
};

onMounted(() => {
    void load();
    clockTimer = setInterval(() => {
        clock.value = Date.now();
    }, 30_000);
});

onBeforeUnmount(() => {
    if (clockTimer) {
        clearInterval(clockTimer);
    }
});
</script>

<template>
    <div class="grid gap-5">
        <header class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <div class="flex items-center gap-2 text-sm font-bold text-sky-700">
                        <Database class="h-4 w-4" aria-hidden="true" />
                        Cache tra cứu
                    </div>
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Quản lý cache biển số</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Theo dõi thời gian còn lại và mức độ tra cứu của từng biển số. Số lượt bên dưới chỉ tính trong khoảng thời gian đã chọn.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div
                        role="group"
                        aria-label="Chọn khoảng thống kê tra cứu"
                        class="inline-flex min-h-11 rounded-lg border border-slate-200 bg-slate-50 p-1"
                    >
                        <button
                            v-for="days in periodOptions"
                            :key="days"
                            type="button"
                            :aria-pressed="filters.days === days"
                            class="app-focus min-h-9 rounded-md px-3 text-sm font-bold transition-colors"
                            :class="filters.days === days ? 'bg-white text-sky-700 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                            @click="selectPeriod(days)"
                        >
                            {{ days }} ngày
                        </button>
                    </div>
                    <button
                        type="button"
                        :disabled="loading"
                        class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-slate-950 px-4 text-sm font-bold text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="load(meta?.current_page ?? 1)"
                    >
                        <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin motion-reduce:animate-none' : ''" aria-hidden="true" />
                        Làm mới
                    </button>
                </div>
            </div>

            <div v-if="result && !loading" class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                <span>{{ periodLabel }}</span>
                <span>TTL cập nhật theo giờ máy chủ</span>
            </div>
        </header>

        <aside class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900">
            <strong>Phạm vi dữ liệu:</strong> danh sách lấy từ cache bền trong database. Redis là tầng truy cập nhanh và có thể được nạp lại từ bản
            ghi còn hạn; trang này không quét trực tiếp key Redis. Negative cache lỗi cũng không được tính là cache hit kết quả.
        </aside>

        <div
            v-if="errorMessage"
            role="alert"
            class="flex flex-col gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-start gap-3 text-sm text-rose-800">
                <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                <span>{{ errorMessage }}</span>
            </div>
            <button type="button" class="app-focus min-h-11 rounded-lg px-4 text-sm font-bold text-rose-800 hover:bg-rose-100" @click="load()">
                Thử lại
            </button>
        </div>

        <template v-if="loading && !result">
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6" aria-label="Đang tải tổng quan cache">
                <div v-for="index in 6" :key="index" class="h-32 animate-pulse rounded-xl bg-slate-200 motion-reduce:animate-none" />
            </section>
            <div class="h-80 animate-pulse rounded-xl bg-slate-200 motion-reduce:animate-none" />
        </template>

        <template v-else-if="result">
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6" aria-label="Tổng quan cache biển số">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-500">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-black tabular-nums text-slate-950">{{ card.value }}</p>
                        </div>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg" :class="card.tone">
                            <component :is="card.icon" class="h-4 w-4" aria-hidden="true" />
                        </span>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-500">{{ card.note }}</p>
                </article>
            </section>

            <form class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" @submit.prevent="load()">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(220px,1.4fr)_repeat(4,minmax(140px,0.75fr))_auto]">
                    <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                        Biển số
                        <span class="relative">
                            <Search class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400" aria-hidden="true" />
                            <input
                                v-model.trim="filters.search"
                                type="search"
                                maxlength="16"
                                placeholder="Ví dụ: 30A-123.45"
                                class="app-focus min-h-11 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-950 placeholder:text-slate-400"
                            />
                        </span>
                    </label>
                    <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                        Trạng thái cache
                        <select
                            v-model="filters.state"
                            class="app-focus min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950"
                        >
                            <option value="all">Tất cả</option>
                            <option value="active">Còn hạn</option>
                            <option value="expiring">Sắp hết hạn</option>
                            <option value="expired">Hết hạn</option>
                        </select>
                    </label>
                    <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                        Loại xe
                        <select
                            v-model="filters.vehicle_type"
                            class="app-focus min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950"
                        >
                            <option value="">Tất cả</option>
                            <option value="car">Ô tô</option>
                            <option value="motorbike">Xe máy</option>
                            <option value="electric_motorbike">Xe máy điện</option>
                        </select>
                    </label>
                    <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                        Kết quả
                        <select
                            v-model="filters.status"
                            class="app-focus min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950"
                        >
                            <option value="">Tất cả</option>
                            <option value="success">Có vi phạm</option>
                            <option value="no_violation">Không vi phạm</option>
                        </select>
                    </label>
                    <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                        Sắp xếp
                        <select
                            v-model="filters.sort"
                            class="app-focus min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950"
                        >
                            <option value="lookup_count">Lượt tra cứu</option>
                            <option value="last_lookup_at">Lần tra cứu cuối</option>
                            <option value="expires_at">Thời gian hết hạn</option>
                            <option value="checked_at">Thời gian kiểm tra</option>
                            <option value="plate">Biển số</option>
                        </select>
                    </label>
                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="app-focus inline-flex min-h-11 flex-1 items-center justify-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white hover:bg-sky-700"
                        >
                            Lọc
                        </button>
                        <button
                            type="button"
                            aria-label="Đặt lại bộ lọc"
                            class="app-focus inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50"
                            @click="resetFilters"
                        >
                            <X class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </form>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" :aria-busy="loading">
                <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-bold text-slate-950">Danh sách cache</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ formatNumber(meta?.total ?? 0) }} biển số · số lượt tính trong {{ result.period.days }} ngày
                        </p>
                    </div>
                    <span v-if="loading" class="inline-flex items-center gap-2 text-xs font-semibold text-sky-700">
                        <RefreshCw class="h-3.5 w-3.5 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                        Đang cập nhật
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <caption class="sr-only">
                            Danh sách biển số đã cache, TTL và mức độ tra cứu
                        </caption>
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">Biển số</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Kết quả</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Mức độ tra cứu</th>
                                <th scope="col" class="px-5 py-3 font-semibold">TTL cache</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Mốc thời gian</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Provider</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="item in items" :key="item.id" class="align-top hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="font-black tracking-wide text-slate-950">{{ item.plate }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ vehicleLabel(item.vehicle_type) }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold ring-1 ring-inset"
                                        :class="
                                            item.status === 'success'
                                                ? 'bg-rose-50 text-rose-700 ring-rose-600/20'
                                                : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                                        "
                                    >
                                        {{ statusLabel(item.status) }}
                                    </span>
                                    <p class="mt-2 text-xs text-slate-500">{{ formatNumber(item.violation_count) }} lỗi</p>
                                </td>
                                <td class="min-w-52 px-5 py-4">
                                    <p class="font-bold tabular-nums text-slate-950">{{ formatNumber(item.lookup_count) }} lượt</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        {{ formatNumber(item.positive_cache_hits) }} cache hit · {{ formatNumber(item.provider_requests) }} provider
                                    </p>
                                    <p class="text-xs text-slate-500">Tỷ lệ cache {{ formatNumber(item.cache_hit_rate) }}%</p>
                                    <p v-if="item.provider_errors > 0" class="mt-1 text-xs font-semibold text-rose-700">
                                        {{ formatNumber(item.provider_errors) }} lượt lỗi/chặn provider
                                    </p>
                                </td>
                                <td class="min-w-44 px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold ring-1 ring-inset"
                                        :class="cacheStateClass(item)"
                                    >
                                        {{ cacheStateLabel(item) }}
                                    </span>
                                    <p class="mt-2 font-bold tabular-nums text-slate-950">{{ formatDuration(remainingSeconds(item)) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Chu kỳ {{ formatDuration(item.cache_duration_seconds) }}</p>
                                </td>
                                <td class="min-w-56 px-5 py-4 text-xs leading-5 text-slate-500">
                                    <p><span class="font-semibold text-slate-700">Kiểm tra:</span> {{ formatDateTime(item.checked_at) }}</p>
                                    <p><span class="font-semibold text-slate-700">Tra cứu cuối:</span> {{ formatDateTime(item.last_lookup_at) }}</p>
                                    <p><span class="font-semibold text-slate-700">Hết hạn:</span> {{ formatDateTime(item.expires_at) }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-600">{{ item.provider }}</td>
                            </tr>
                            <tr v-if="items.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Không có biển số cache phù hợp với bộ lọc.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="meta && meta.last_page > 1"
                    class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-xs text-slate-500">Hiển thị {{ meta.from }}–{{ meta.to }} trên {{ formatNumber(meta.total) }} biển số</p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="loading || meta.current_page <= 1"
                            class="app-focus min-h-10 rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="load(meta.current_page - 1)"
                        >
                            Trước
                        </button>
                        <span class="px-2 text-sm tabular-nums text-slate-500">{{ meta.current_page }} / {{ meta.last_page }}</span>
                        <button
                            type="button"
                            :disabled="loading || meta.current_page >= meta.last_page"
                            class="app-focus min-h-10 rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="load(meta.current_page + 1)"
                        >
                            Sau
                        </button>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>
