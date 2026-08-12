<script setup lang="ts">
import { clientProxyService, type ProxyCheckBatch, type ProxyCheckResult } from '@/services/client-proxy.service';
import { useUserStore } from '@/stores/user.store';
import { handleErrorResponse } from '@/utils/response';
import { echo } from '@laravel/echo-vue';
import { CheckCircle2, Clipboard, Clock3, Globe2, LoaderCircle, MapPin, Network, Play, RefreshCcw, RotateCcw, XCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type ProxyCheckProgressEvent = {
    batch_id: string;
    status: ProxyCheckBatch['status'];
    total: number;
    processed: number;
    live: number;
    die: number;
    progress: number;
    item: ProxyCheckResult;
};

const storageKey = 'dailyproxy.active-proxy-country-check';
const userStore = useUserStore();
const proxyInput = ref('');
const checking = ref(false);
const refreshing = ref(false);
const submittedLines = ref<string[]>([]);
const batch = ref<ProxyCheckBatch | null>(null);
const failedFlagImages = ref<Set<string>>(new Set());

const proxyLines = computed(() =>
    proxyInput.value
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean),
);
const isRunning = computed(() => batch.value !== null && batch.value.status !== 'completed');
const results = computed(() => batch.value?.results ?? []);
const locatedCount = computed(() => results.value.filter((item) => item.country_code).length);
const countries = computed(() => new Set(results.value.map((item) => item.country_code).filter(Boolean)).size);

const displayProxy = (item: ProxyCheckResult): string => submittedLines.value[item.position] ?? item.endpoint;

const countryFlag = (countryCode: string | null): string => {
    if (!countryCode || !/^[A-Z]{2}$/i.test(countryCode)) return '🌐';

    return [...countryCode.toUpperCase()].map((character) => String.fromCodePoint(127397 + character.charCodeAt(0))).join('');
};

const countryFlagUrl = (countryCode: string): string => `https://flagcdn.com/w80/${countryCode.toLowerCase()}.png`;

const countryDisplayName = (item: ProxyCheckResult): string => {
    if (!item.country_code) return item.country_name || 'Chưa rõ tên quốc gia';

    try {
        return new Intl.DisplayNames(['vi'], { type: 'region' }).of(item.country_code) || item.country_name || item.country_code;
    } catch {
        return item.country_name || item.country_code;
    }
};

const markFlagImageFailed = (countryCode: string): void => {
    failedFlagImages.value.add(countryCode.toUpperCase());
};

const locationLabel = (item: ProxyCheckResult): string => [item.city_name, item.region_name].filter(Boolean).join(', ') || '--';

const persistBatch = (): void => {
    if (batch.value === null || batch.value.status === 'completed') {
        sessionStorage.removeItem(storageKey);
        return;
    }

    sessionStorage.setItem(storageKey, batch.value.id);
};

const applyBatch = (value: ProxyCheckBatch): void => {
    batch.value = value;
    persistBatch();
};

const checkCountries = async (): Promise<void> => {
    if (proxyLines.value.length === 0) {
        await Swal.fire('Chưa có proxy', 'Nhập ít nhất một proxy theo định dạng IP:PORT:USER:PASS.', 'warning');
        return;
    }

    checking.value = true;
    submittedLines.value = [...proxyLines.value];

    try {
        const data = await clientProxyService.checkProxyCountries(submittedLines.value);
        applyBatch(data.batch);
    } catch (error) {
        submittedLines.value = [];
        handleErrorResponse(error as never);
    } finally {
        checking.value = false;
    }
};

const refreshBatch = async (): Promise<void> => {
    if (batch.value === null || refreshing.value) return;

    refreshing.value = true;
    try {
        const data = await clientProxyService.proxyCountryCheckStatus(batch.value.id);
        applyBatch(data.batch);
    } catch (error) {
        handleErrorResponse(error as never);
    } finally {
        refreshing.value = false;
    }
};

const clearAll = (): void => {
    if (isRunning.value) return;

    proxyInput.value = '';
    submittedLines.value = [];
    batch.value = null;
    sessionStorage.removeItem(storageKey);
};

const copyText = async (value: string): Promise<void> => {
    try {
        await navigator.clipboard.writeText(value);
        await Swal.fire({ icon: 'success', title: 'Đã sao chép', showConfirmButton: false, timer: 900 });
    } catch {
        await Swal.fire('Không thể sao chép', 'Trình duyệt không cho phép truy cập clipboard.', 'error');
    }
};

const applyProgress = (event: ProxyCheckProgressEvent): void => {
    if (batch.value === null || event.batch_id !== batch.value.id) return;

    batch.value.status = event.status;
    batch.value.total = event.total;
    batch.value.processed = event.processed;
    batch.value.live = event.live;
    batch.value.die = event.die;
    batch.value.progress = event.progress;

    const index = batch.value.results.findIndex((item) => item.id === event.item.id);
    if (index >= 0) {
        batch.value.results[index] = event.item;
    } else {
        batch.value.results.push(event.item);
        batch.value.results.sort((left, right) => left.position - right.position);
    }

    persistBatch();
};

let subscribedUserId: number | null = null;

const leaveChannel = (): void => {
    if (subscribedUserId === null) return;

    echo().leave(`users.${subscribedUserId}.proxy-checks`);
    subscribedUserId = null;
};

watch(
    () => userStore.user?.id ?? null,
    (userId) => {
        if (userId === subscribedUserId) return;

        leaveChannel();
        if (userId === null) return;

        subscribedUserId = userId;
        echo()
            .private(`users.${userId}.proxy-checks`)
            .listen('.proxy.check.progressed', (event: ProxyCheckProgressEvent) => applyProgress(event));
    },
    { immediate: true },
);

onMounted(async () => {
    const batchId = sessionStorage.getItem(storageKey);
    if (!batchId) return;

    refreshing.value = true;
    try {
        const data = await clientProxyService.proxyCountryCheckStatus(batchId);
        applyBatch(data.batch);
    } catch {
        sessionStorage.removeItem(storageKey);
    } finally {
        refreshing.value = false;
    }
});

onBeforeUnmount(leaveChannel);
</script>

<template>
    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl border border-indigo-100 bg-white shadow-sm">
            <div class="bg-gradient-to-r from-indigo-700 via-blue-600 to-cyan-500 px-6 py-7 text-white sm:px-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                        <Globe2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Check quốc gia proxy</h1>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-blue-50">
                            Xác định IP đầu ra, quốc gia, khu vực, thành phố, múi giờ và nhà mạng của từng proxy bằng hàng đợi nền.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <label for="country-proxy-list" class="text-sm font-semibold text-slate-800">Danh sách proxy</label>
                        <span class="text-xs font-medium" :class="proxyLines.length > 20 ? 'text-rose-600' : 'text-slate-500'">
                            {{ proxyLines.length }}/20 proxy
                        </span>
                    </div>
                    <textarea
                        id="country-proxy-list"
                        v-model="proxyInput"
                        rows="11"
                        spellcheck="false"
                        :disabled="isRunning"
                        class="mt-2 w-full resize-y rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm leading-7 text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:opacity-60"
                        placeholder="113.22.201.111:32582:username:password&#10;118.70.171.107:21113:username:password"
                    />

                    <div class="mt-4 flex flex-wrap gap-3">
                        <button
                            type="button"
                            :disabled="checking || isRunning || proxyLines.length === 0 || proxyLines.length > 20"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="checkCountries"
                        >
                            <LoaderCircle v-if="checking" class="h-4 w-4 animate-spin" />
                            <Play v-else class="h-4 w-4" />
                            {{ checking ? 'Đang đưa vào hàng đợi...' : 'Bắt đầu xác định' }}
                        </button>
                        <button
                            v-if="batch"
                            type="button"
                            :disabled="refreshing"
                            class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 disabled:opacity-50"
                            @click="refreshBatch"
                        >
                            <RefreshCcw class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" />
                            Đồng bộ tiến độ
                        </button>
                        <button
                            type="button"
                            :disabled="checking || isRunning || (!proxyInput && batch === null)"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="clearAll"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Xóa dữ liệu
                        </button>
                    </div>
                </div>

                <aside class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-5">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <MapPin class="h-4 w-4 text-indigo-600" />
                        Dữ liệu nhận được
                    </h2>
                    <ul class="mt-4 grid gap-3 text-sm leading-6 text-slate-600">
                        <li class="flex gap-2"><Globe2 class="mt-1 h-4 w-4 shrink-0 text-indigo-500" /> Quốc gia và mã ISO hai ký tự.</li>
                        <li class="flex gap-2"><MapPin class="mt-1 h-4 w-4 shrink-0 text-indigo-500" /> Khu vực, thành phố và IP đầu ra.</li>
                        <li class="flex gap-2"><Clock3 class="mt-1 h-4 w-4 shrink-0 text-indigo-500" /> Múi giờ và độ trễ kết nối.</li>
                        <li class="flex gap-2"><Network class="mt-1 h-4 w-4 shrink-0 text-indigo-500" /> ISP hoặc nhà mạng của proxy.</li>
                    </ul>
                    <p class="mt-4 rounded-lg bg-white/80 px-3 py-2 text-xs leading-5 text-slate-500">
                        Mỗi proxy chạy trong một job riêng. Thông tin đăng nhập được mã hóa khi chờ và xóa ngay sau khi xử lý.
                    </p>
                </aside>
            </div>
        </section>

        <section v-if="batch" class="space-y-4">
            <div class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Tiến độ xác định quốc gia</p>
                        <p class="mt-1 font-mono text-xs text-slate-500">{{ batch.id }}</p>
                    </div>
                    <span class="text-sm font-bold text-indigo-700">{{ batch.processed }}/{{ batch.total }} · {{ batch.progress }}%</span>
                </div>
                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-indigo-600 to-cyan-400 transition-all duration-500"
                        :style="{ width: `${batch.progress}%` }"
                    />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Đã xử lý</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ batch.processed }}/{{ batch.total }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm text-emerald-700">Đã xác định</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">{{ locatedCount }}</p>
                </div>
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5">
                    <p class="text-sm text-indigo-700">Số quốc gia</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-700">{{ countries }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-5">
                    <p class="text-sm text-rose-700">Không xác định</p>
                    <p class="mt-1 text-2xl font-bold text-rose-700">{{ batch.die }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Kết quả vị trí realtime</h2>
                    <p class="mt-1 text-sm text-slate-500">Các dòng được giữ đúng thứ tự danh sách đã nhập.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Proxy / IP đầu ra</th>
                                <th class="px-5 py-3">Quốc gia</th>
                                <th class="px-5 py-3">Khu vực</th>
                                <th class="px-5 py-3">Múi giờ / ISP</th>
                                <th class="px-5 py-3">Trạng thái</th>
                                <th class="px-5 py-3 text-right">Sao chép</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in results" :key="item.id" class="align-top hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="whitespace-nowrap font-mono text-xs font-medium text-slate-800">{{ displayProxy(item) }}</p>
                                    <p class="mt-1 font-mono text-xs text-slate-500">IP: {{ item.exit_ip || '--' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div v-if="item.country_code" class="flex items-center gap-2">
                                        <div
                                            class="flex h-9 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-slate-50 shadow-sm"
                                        >
                                            <img
                                                v-if="!failedFlagImages.has(item.country_code)"
                                                :src="countryFlagUrl(item.country_code)"
                                                :alt="`Quốc kỳ ${item.country_name || item.country_code}`"
                                                class="h-full w-full object-contain"
                                                loading="lazy"
                                                @error="markFlagImageFailed(item.country_code)"
                                            />
                                            <span v-else class="text-xl leading-none">{{ countryFlag(item.country_code) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ countryDisplayName(item) }}</p>
                                            <p class="text-xs font-medium text-indigo-600">{{ item.country_code }}</p>
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400">--</span>
                                </td>
                                <td class="min-w-48 px-5 py-4">
                                    <p class="font-medium text-slate-800">{{ locationLabel(item) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Độ trễ: {{ item.latency_ms === null ? '--' : `${item.latency_ms} ms` }}</p>
                                </td>
                                <td class="min-w-52 px-5 py-4">
                                    <p class="text-slate-700">{{ item.timezone || '--' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ item.isp || 'Chưa có thông tin ISP' }}</p>
                                </td>
                                <td class="min-w-48 px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"
                                        :class="{
                                            'border-emerald-200 bg-emerald-50 text-emerald-700': item.status === 'live',
                                            'border-rose-200 bg-rose-50 text-rose-700': item.status === 'die',
                                            'border-indigo-200 bg-indigo-50 text-indigo-700': item.status === 'processing',
                                            'border-slate-200 bg-slate-50 text-slate-600': item.status === 'pending',
                                        }"
                                    >
                                        <CheckCircle2 v-if="item.status === 'live'" class="h-3.5 w-3.5" />
                                        <XCircle v-else-if="item.status === 'die'" class="h-3.5 w-3.5" />
                                        <LoaderCircle v-else-if="item.status === 'processing'" class="h-3.5 w-3.5 animate-spin" />
                                        {{
                                            item.status === 'pending'
                                                ? 'ĐANG CHỜ'
                                                : item.status === 'processing'
                                                  ? 'ĐANG CHECK'
                                                  : item.status === 'live'
                                                    ? 'ĐÃ XÁC ĐỊNH'
                                                    : 'THẤT BẠI'
                                        }}
                                    </span>
                                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ item.message || 'Đang chờ xử lý...' }}</p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-slate-400 transition hover:bg-indigo-50 hover:text-indigo-600"
                                        title="Sao chép proxy"
                                        @click="copyText(displayProxy(item))"
                                    >
                                        <Clipboard class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</template>
