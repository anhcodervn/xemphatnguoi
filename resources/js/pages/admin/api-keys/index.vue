<script setup lang="ts">
import { adminApiKeyService } from '@/services/admin-api-key.service';
import type { AdminApiKeyItem } from '@/types/admin-api-key.type';
import { handleErrorResponse } from '@/utils/response';
import { KeyRound, LoaderCircle, Search, ShieldCheck, ShieldOff } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const rows = ref<AdminApiKeyItem[]>([]);

const filters = reactive({
    search: '',
    status: '',
    page: 1,
});

const meta = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});

const summary = reactive({
    total: 0,
    active: 0,
    inactive: 0,
    revoked: 0,
});

const tableRange = computed(() => {
    if (meta.total === 0) {
        return '0-0';
    }

    const from = (meta.current_page - 1) * 10 + 1;
    const to = Math.min(meta.current_page * 10, meta.total);

    return `${from}-${to}`;
});

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('vi-VN');
};

const fetchApiKeys = async (page = 1): Promise<void> => {
    try {
        loading.value = true;

        const response = await adminApiKeyService.list({
            page,
            search: filters.search || undefined,
            status: filters.status || undefined,
        });

        rows.value = response.api_keys.data;
        meta.current_page = response.api_keys.current_page;
        meta.last_page = response.api_keys.last_page;
        meta.total = response.api_keys.total;
        Object.assign(summary, response.summary);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const applyFilters = async (): Promise<void> => {
    filters.page = 1;
    await fetchApiKeys(1);
};

const goToPage = async (page: number): Promise<void> => {
    if (page < 1 || page > meta.last_page || page === meta.current_page) {
        return;
    }

    filters.page = page;
    await fetchApiKeys(page);
};

onMounted(async () => {
    await fetchApiKeys();
});
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <h1 class="text-xl font-bold text-slate-950">Quản lý API key</h1>
            <p class="mt-1 text-sm text-slate-500">Theo dõi API key của khách hàng, trạng thái hoạt động, quyền sử dụng và tần suất gọi API.</p>
        </section>

        <section class="grid gap-3 md:grid-cols-4">
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tổng key</p>
                <p class="mt-1 text-2xl font-bold text-slate-950">{{ summary.total }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Đang hoạt động</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ summary.active }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tạm dừng</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ summary.inactive }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Đã thu hồi</p>
                <p class="mt-1 text-2xl font-bold text-rose-600">{{ summary.revoked }}</p>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-3.5 shadow-sm">
            <div class="grid gap-2.5 lg:grid-cols-[1.6fr_220px_auto]">
                <label class="flex items-center gap-2.5 rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2">
                    <Search class="h-4 w-4 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 outline-none placeholder:text-slate-400"
                        placeholder="Tìm theo tên key, api_key, user..."
                        @keyup.enter="applyFilters"
                    />
                </label>

                <select v-model="filters.status" class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">active</option>
                    <option value="inactive">inactive</option>
                    <option value="revoked">revoked</option>
                    <option value="expired">expired</option>
                </select>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-[8px] border border-[#465fff] px-3.5 py-2 text-sm font-semibold text-[#465fff] transition hover:bg-[#eef2ff]"
                    @click="applyFilters"
                >
                    Lọc
                </button>
            </div>
        </section>

        <section class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm">
            <div v-if="loading" class="flex items-center justify-center gap-2 px-6 py-16 text-sm text-slate-500">
                <LoaderCircle class="h-5 w-5 animate-spin" />
                Đang tải API key...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[1100px]">
                    <thead class="bg-slate-50 text-left text-[13px] font-semibold text-slate-500">
                        <tr>
                            <th class="px-4 py-3">API key</th>
                            <th class="px-4 py-3">Khách hàng</th>
                            <th class="px-4 py-3">Quyền</th>
                            <th class="px-4 py-3">IP</th>
                            <th class="px-4 py-3">Log</th>
                            <th class="px-4 py-3">Lần dùng cuối</th>
                            <th class="px-4 py-3">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">Chưa có API key nào.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="flex items-start gap-2">
                                    <KeyRound class="mt-0.5 h-4 w-4 text-[#465fff]" />
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ row.name }}</p>
                                        <p class="font-mono text-xs text-slate-500">{{ row.api_key }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Tạo lúc {{ formatDateTime(row.created_at) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p class="font-semibold text-slate-900">{{ row.user?.full_name || row.user?.username || '-' }}</p>
                                <p>{{ row.user?.email || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p class="line-clamp-2">{{ row.permissions.join(', ') || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p class="line-clamp-2">{{ row.ip_whitelist.join(', ') || 'Tất cả' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ row.logs_count }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ formatDateTime(row.last_used_at) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        row.status === 'active'
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border-slate-200 bg-slate-100 text-slate-700'
                                    "
                                >
                                    <ShieldCheck v-if="row.status === 'active'" class="h-3.5 w-3.5" />
                                    <ShieldOff v-else class="h-3.5 w-3.5" />
                                    {{ row.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm text-slate-500">
                <p>Đang hiển thị {{ tableRange }} / {{ meta.total }}</p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-[8px] border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="meta.current_page <= 1"
                        @click="goToPage(meta.current_page - 1)"
                    >
                        Trước
                    </button>
                    <span>Trang {{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button
                        type="button"
                        class="rounded-[8px] border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                    >
                        Sau
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
