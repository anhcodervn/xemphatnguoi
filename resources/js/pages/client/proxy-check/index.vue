<script setup lang="ts">
import { clientProxyService, type ProxyCheckBatch, type ProxyCheckResult } from '@/services/client-proxy.service';
import { useUserStore } from '@/stores/user.store';
import { handleErrorResponse } from '@/utils/response';
import { echo } from '@laravel/echo-vue';
import { Activity, CheckCircle2, Clipboard, LoaderCircle, Play, RefreshCcw, RotateCcw, ShieldCheck, XCircle } from 'lucide-vue-next';
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

const storageKey = 'dailyproxy.active-proxy-check';
const userStore = useUserStore();
const proxyInput = ref('');
const checking = ref(false);
const refreshing = ref(false);
const submittedLines = ref<string[]>([]);
const batch = ref<ProxyCheckBatch | null>(null);

const proxyLines = computed(() =>
    proxyInput.value
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean),
);

const isRunning = computed(() => batch.value !== null && batch.value.status !== 'completed');
const results = computed(() => batch.value?.results ?? []);

const displayProxy = (item: ProxyCheckResult): string => submittedLines.value[item.position] ?? item.endpoint;

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

const checkProxies = async (): Promise<void> => {
    if (proxyLines.value.length === 0) {
        await Swal.fire('Chưa có proxy', 'Nhập ít nhất một proxy theo định dạng IP:PORT:USER:PASS.', 'warning');
        return;
    }

    checking.value = true;
    submittedLines.value = [...proxyLines.value];

    try {
        const data = await clientProxyService.checkProxies(submittedLines.value);
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
        const data = await clientProxyService.proxyCheckStatus(batch.value.id);
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
        const data = await clientProxyService.proxyCheckStatus(batchId);
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
        <section class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
            <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-cyan-500 px-6 py-7 text-white sm:px-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                        <ShieldCheck class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Check Proxy</h1>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-blue-50">
                            Danh sách được chia thành các tác vụ nền và cập nhật tiến độ trực tiếp theo từng proxy.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_280px]">
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <label for="proxy-list" class="text-sm font-semibold text-slate-800">Danh sách proxy</label>
                        <span class="text-xs font-medium" :class="proxyLines.length > 20 ? 'text-rose-600' : 'text-slate-500'">
                            {{ proxyLines.length }}/20 proxy
                        </span>
                    </div>
                    <textarea
                        id="proxy-list"
                        v-model="proxyInput"
                        rows="11"
                        spellcheck="false"
                        :disabled="isRunning"
                        class="mt-2 w-full resize-y rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm leading-7 text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                        placeholder="113.22.201.111:32582:username:password&#10;118.70.171.107:21113:username:password"
                    />

                    <div class="mt-4 flex flex-wrap gap-3">
                        <button
                            type="button"
                            :disabled="checking || isRunning || proxyLines.length === 0 || proxyLines.length > 20"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="checkProxies"
                        >
                            <LoaderCircle v-if="checking" class="h-4 w-4 animate-spin" />
                            <Play v-else class="h-4 w-4" />
                            {{ checking ? 'Đang tạo tác vụ...' : 'Bắt đầu kiểm tra' }}
                        </button>
                        <button
                            v-if="batch"
                            type="button"
                            :disabled="refreshing"
                            class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-5 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 disabled:opacity-50"
                            @click="refreshBatch"
                        >
                            <RefreshCcw class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" />
                            Đồng bộ tiến độ
                        </button>
                        <button
                            type="button"
                            :disabled="checking || isRunning || (!proxyInput && batch === null)"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="clearAll"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Xóa dữ liệu
                        </button>
                    </div>
                </div>

                <aside class="rounded-xl border border-blue-100 bg-blue-50/60 p-5">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <Activity class="h-4 w-4 text-blue-600" />
                        Định dạng hỗ trợ
                    </h2>
                    <code class="mt-3 block rounded-lg bg-slate-900 px-3 py-2 text-xs text-cyan-300">IP:PORT:USER:PASS</code>
                    <ul class="mt-4 space-y-2 text-sm leading-6 text-slate-600">
                        <li>Mỗi proxy nằm trên một dòng riêng.</li>
                        <li>Chỉ hỗ trợ IPv4 public có xác thực.</li>
                        <li>Credential được mã hóa khi chờ queue và xóa sau khi kiểm tra.</li>
                        <li>Nếu socket gián đoạn, dùng nút “Đồng bộ tiến độ”.</li>
                    </ul>
                </aside>
            </div>
        </section>

        <section v-if="batch" class="space-y-4">
            <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Tiến độ batch</p>
                        <p class="mt-1 font-mono text-xs text-slate-500">{{ batch.id }}</p>
                    </div>
                    <span class="text-sm font-bold text-blue-700">{{ batch.processed }}/{{ batch.total }} · {{ batch.progress }}%</span>
                </div>
                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-blue-600 to-cyan-400 transition-all duration-500"
                        :style="{ width: `${batch.progress}%` }"
                    />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Đã xử lý</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ batch.processed }}/{{ batch.total }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm text-emerald-700">Proxy live</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">{{ batch.live }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-5">
                    <p class="text-sm text-rose-700">Proxy die</p>
                    <p class="mt-1 text-2xl font-bold text-rose-700">{{ batch.die }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Kết quả kiểm tra realtime</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Proxy</th>
                                <th class="px-5 py-3">Trạng thái</th>
                                <th class="px-5 py-3">IP đầu ra</th>
                                <th class="px-5 py-3">Độ trễ</th>
                                <th class="px-5 py-3">Ghi chú</th>
                                <th class="px-5 py-3 text-right">Sao chép</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in results" :key="item.id" class="hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-medium text-slate-800">
                                    {{ displayProxy(item) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"
                                        :class="{
                                            'border-emerald-200 bg-emerald-50 text-emerald-700': item.status === 'live',
                                            'border-rose-200 bg-rose-50 text-rose-700': item.status === 'die',
                                            'border-blue-200 bg-blue-50 text-blue-700': item.status === 'processing',
                                            'border-slate-200 bg-slate-50 text-slate-600': item.status === 'pending',
                                        }"
                                    >
                                        <CheckCircle2 v-if="item.status === 'live'" class="h-3.5 w-3.5" />
                                        <XCircle v-else-if="item.status === 'die'" class="h-3.5 w-3.5" />
                                        <LoaderCircle v-else-if="item.status === 'processing'" class="h-3.5 w-3.5 animate-spin" />
                                        {{ item.status === 'pending' ? 'ĐANG CHỜ' : item.status === 'processing' ? 'ĐANG CHECK' : item.status.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ item.exit_ip || '--' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700">
                                    {{ item.latency_ms === null ? '--' : `${item.latency_ms} ms` }}
                                </td>
                                <td class="min-w-52 px-5 py-4 text-slate-500">{{ item.message || 'Đang chờ xử lý...' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-slate-400 transition hover:bg-blue-50 hover:text-blue-600"
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
