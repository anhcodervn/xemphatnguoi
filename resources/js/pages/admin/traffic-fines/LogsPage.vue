<script setup lang="ts">
import { adminTrafficFineService, type AdminLookupLog } from '@/services/admin-traffic-fine.service';
import { Search, ServerCrash, UserRound, UsersRound } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

const localDate = (): string => {
    const date = new Date();
    const offset = date.getTimezoneOffset() * 60_000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 10);
};

const items = ref<AdminLookupLog[]>([]);
const loading = ref(false);
const errorMessage = ref('');
const filters = reactive({ search: '', status: '', from: localDate(), to: localDate() });
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
const summary = reactive({
    total: 0,
    completed: 0,
    provider_errors: 0,
    affected_users: 0,
    anonymous_requests: 0,
    affected_anonymous_ips: 0,
    first_failure_at: null as string | null,
    last_failure_at: null as string | null,
});

const failureWindow = computed(() => {
    if (!summary.first_failure_at || !summary.last_failure_at) return 'Không có lỗi trong kỳ';

    return `${new Date(summary.first_failure_at).toLocaleTimeString('vi-VN')} – ${new Date(summary.last_failure_at).toLocaleTimeString('vi-VN')}`;
});

const load = async (page = 1): Promise<void> => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await adminTrafficFineService.logs({
            page,
            per_page: 50,
            search: filters.search || undefined,
            status: filters.status || undefined,
            from: filters.from || undefined,
            to: filters.to || undefined,
        });

        items.value = response.logs.data;
        meta.current_page = response.logs.current_page;
        meta.last_page = response.logs.last_page;
        meta.total = response.logs.total;
        Object.assign(summary, response.summary);
    } catch {
        errorMessage.value = 'Không thể tải nhật ký tra cứu.';
    } finally {
        loading.value = false;
    }
};

const statusLabel = (status: string): string => {
    if (status === 'provider_error') return '503 · Nguồn lỗi';
    if (status === 'success') return 'Thành công';
    if (status === 'no_violation') return 'Không vi phạm';

    return status;
};

const statusClass = (status: string): string => (status === 'provider_error' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700');

onMounted(() => load());
</script>

<template>
    <div class="grid gap-6">
        <header>
            <p class="text-sm font-bold text-sky-700">Tra cứu phạt nguội</p>
            <h1 class="mt-1 text-3xl font-black text-slate-950">Nhật ký request người dùng</h1>
            <p class="mt-2 text-sm text-slate-500">
                Bao gồm lượt tra cứu từ website, tài khoản đăng nhập và API; không lưu raw response của nhà cung cấp.
            </p>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Tổng lượt tra cứu</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ summary.total.toLocaleString('vi-VN') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ summary.completed.toLocaleString('vi-VN') }} hoàn tất</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700"><Search class="h-5 w-5" /></span>
                </div>
            </article>
            <article class="rounded-xl border border-red-200 bg-red-50/40 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-red-700">Lỗi nguồn / 503</p>
                        <p class="mt-2 text-2xl font-black text-red-800">{{ summary.provider_errors.toLocaleString('vi-VN') }}</p>
                        <p class="mt-1 text-xs text-red-600">{{ failureWindow }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 text-red-700"><ServerCrash class="h-5 w-5" /></span>
                </div>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">User bị ảnh hưởng</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ summary.affected_users.toLocaleString('vi-VN') }}</p>
                        <p class="mt-1 text-xs text-slate-500">Tài khoản xác định được</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700"
                        ><UserRound class="h-5 w-5"
                    /></span>
                </div>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Khách ẩn danh bị lỗi</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ summary.anonymous_requests.toLocaleString('vi-VN') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ summary.affected_anonymous_ips.toLocaleString('vi-VN') }} IP khác nhau</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700"
                        ><UsersRound class="h-5 w-5"
                    /></span>
                </div>
            </article>
        </section>

        <form
            class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_190px_160px_160px_auto]"
            @submit.prevent="load(1)"
        >
            <input
                v-model="filters.search"
                type="search"
                placeholder="Biển số, IP, username hoặc email"
                class="app-focus h-11 rounded-lg border border-slate-300 px-4 text-sm"
            />
            <select v-model="filters.status" class="app-focus h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                <option value="">Mọi trạng thái</option>
                <option value="success">Thành công</option>
                <option value="no_violation">Không vi phạm</option>
                <option value="provider_error">503 · Nguồn lỗi</option>
            </select>
            <label class="grid gap-1 text-xs font-semibold text-slate-500"
                ><span>Từ ngày</span
                ><input v-model="filters.from" type="date" class="app-focus h-11 rounded-lg border border-slate-300 px-3 text-sm text-slate-900"
            /></label>
            <label class="grid gap-1 text-xs font-semibold text-slate-500"
                ><span>Đến ngày</span
                ><input v-model="filters.to" type="date" class="app-focus h-11 rounded-lg border border-slate-300 px-3 text-sm text-slate-900"
            /></label>
            <button
                type="submit"
                class="app-focus inline-flex h-11 items-center justify-center gap-2 self-end rounded-lg bg-slate-950 px-5 text-sm font-bold text-white"
            >
                <Search class="h-4 w-4" />Lọc
            </button>
        </form>

        <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">{{ errorMessage }}</div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Đang tải...</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-4">Thời gian</th>
                            <th class="px-4 py-4">Khách</th>
                            <th class="px-4 py-4">Biển số</th>
                            <th class="px-4 py-4">Nguồn</th>
                            <th class="px-4 py-4">Cache</th>
                            <th class="px-4 py-4">Trạng thái</th>
                            <th class="px-4 py-4">Latency</th>
                            <th class="px-4 py-4">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in items" :key="item.id">
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ new Date(item.created_at).toLocaleString('vi-VN') }}</td>
                            <td class="px-4 py-4">
                                <p class="font-medium text-slate-900">{{ item.user?.username ?? 'Khách ẩn danh' }}</p>
                                <p v-if="item.user?.email" class="mt-1 text-xs text-slate-500">{{ item.user.email }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 font-bold">{{ item.plate }}</td>
                            <td class="px-4 py-4">{{ item.source }}</td>
                            <td class="px-4 py-4">{{ item.cache_hit ? 'Hit' : 'Miss' }}</td>
                            <td class="px-4 py-4">
                                <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClass(item.status)">{{
                                    statusLabel(item.status)
                                }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                {{ item.provider_latency_ms === null ? '—' : `${item.provider_latency_ms} ms` }}
                            </td>
                            <td class="px-4 py-4 font-mono text-xs">{{ item.ip ?? '—' }}</td>
                        </tr>
                        <tr v-if="!items.length">
                            <td colspan="8" class="p-8 text-center text-slate-500">Không có request trong khoảng thời gian đã chọn.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="meta.last_page > 1" class="flex flex-wrap justify-center gap-2">
            <button
                v-for="page in meta.last_page"
                :key="page"
                type="button"
                class="app-focus min-h-10 min-w-10 rounded-lg border text-sm font-bold"
                :class="page === meta.current_page ? 'border-sky-700 bg-sky-700 text-white' : 'border-slate-300 bg-white'"
                @click="load(page)"
            >
                {{ page }}
            </button>
        </div>
    </div>
</template>
