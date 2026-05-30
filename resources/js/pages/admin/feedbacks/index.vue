<script setup lang="ts">
import { adminFeedbackService } from '@/services/admin-feedback.service';
import type { AdminFeedbackItem, AdminFeedbackListResponse } from '@/types/admin-feedback.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { Eye, MessageSquareMore, Search, X } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const loading = ref(false);
const statusLoading = ref(false);
const response = ref<AdminFeedbackListResponse | null>(null);
const selectedItem = ref<AdminFeedbackItem | null>(null);
const detailStatus = ref<'new' | 'in_progress' | 'done'>('new');

const filters = reactive({
    search: '',
    status: '' as '' | 'new' | 'in_progress' | 'done',
    per_page: 10,
    page: 1,
});

const items = computed(() => response.value?.data ?? []);
const meta = computed(() => response.value?.meta ?? { current_page: 1, last_page: 1, per_page: 10, total: 0 });
const stats = computed(() => response.value?.stats ?? { total: 0, new: 0, in_progress: 0, done: 0 });

const loadItems = async (): Promise<void> => {
    try {
        loading.value = true;
        response.value = await adminFeedbackService.list({ ...filters });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (item: AdminFeedbackItem, status: 'new' | 'in_progress' | 'done'): Promise<void> => {
    try {
        statusLoading.value = true;
        await adminFeedbackService.updateStatus(item.id, status);
        handleSuccessResponse({ data: { status: true, message: 'Cập nhật trạng thái thành công.' } });
        await loadItems();
        if (selectedItem.value?.id === item.id) {
            const refreshedItem = response.value?.data.find((feedback) => feedback.id === item.id) ?? null;
            selectedItem.value = refreshedItem;
            if (refreshedItem) {
                detailStatus.value = refreshedItem.status;
            }
        }
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        statusLoading.value = false;
    }
};

const openDetail = (item: AdminFeedbackItem): void => {
    selectedItem.value = item;
    detailStatus.value = item.status;
};

const closeDetail = (): void => {
    selectedItem.value = null;
};

const submitDetailStatus = async (): Promise<void> => {
    if (!selectedItem.value) {
        return;
    }

    await updateStatus(selectedItem.value, detailStatus.value);
};

const changePage = async (page: number): Promise<void> => {
    if (page < 1 || page > meta.value.last_page || page === filters.page) {
        return;
    }

    filters.page = page;
    await loadItems();
};

watch(
    () => [filters.status, filters.per_page],
    async () => {
        filters.page = 1;
        await loadItems();
    },
);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(
    () => filters.search,
    (value) => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(
            async () => {
                filters.page = 1;
                if (value !== filters.search) {
                    return;
                }

                await loadItems();
            },
            value.trim() === '' ? 0 : 300,
        );
    },
);

onMounted(loadItems);
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <h1 class="text-xl font-bold text-slate-950">Liên hệ & góp ý</h1>
            <p class="text-sm text-slate-500">Danh sách phản hồi từ client gửi về admin.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Tổng góp ý</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ stats.total }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Mới</p>
                <p class="mt-1 text-2xl font-bold text-rose-600">{{ stats.new }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Đang xử lý</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ stats.in_progress }}</p>
            </article>
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Hoàn tất</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.done }}</p>
            </article>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_200px_130px]">
                <label class="relative block">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        class="w-full rounded-[10px] border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-indigo-400"
                        placeholder="Tìm theo tiêu đề, email, nội dung..."
                    />
                </label>
                <select v-model="filters.status" class="rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400">
                    <option value="">Tất cả trạng thái</option>
                    <option value="new">Mới</option>
                    <option value="in_progress">Đang xử lý</option>
                    <option value="done">Hoàn tất</option>
                </select>
                <select v-model="filters.per_page" class="rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400">
                    <option :value="10">10 / trang</option>
                    <option :value="20">20 / trang</option>
                    <option :value="50">50 / trang</option>
                </select>
            </div>
        </section>

        <section class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm">
            <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">Đang tải dữ liệu...</div>
            <div v-else-if="items.length === 0" class="px-4 py-12 text-center">
                <MessageSquareMore class="mx-auto h-10 w-10 text-slate-300" />
                <p class="mt-3 text-sm font-semibold text-slate-900">Chưa có góp ý</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Người gửi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tiêu đề</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Thời gian</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">#{{ item.id }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                <p class="text-sm font-semibold text-slate-900">{{ item.name || item.user?.full_name || item.user?.username || '-' }}</p>
                                <p>{{ item.email || item.user?.email || '-' }}</p>
                                <p>{{ item.phone || item.user?.phone || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ item.subject }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="item.status === 'new' ? 'bg-rose-100 text-rose-700' : item.status === 'in_progress' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                                >
                                    {{ item.status === 'new' ? 'Mới' : item.status === 'in_progress' ? 'Đang xử lý' : 'Hoàn tất' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                <p>Tạo: {{ item.created_at || '-' }}</p>
                                <p>Xử lý: {{ item.handled_at || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-[8px] border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    @click="openDetail(item)"
                                >
                                    <Eye class="h-3.5 w-3.5" />
                                    Xem
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm">
                <p class="text-slate-500">Trang {{ meta.current_page }}/{{ meta.last_page }}</p>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-[10px] border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                        :disabled="meta.current_page <= 1"
                        @click="changePage(meta.current_page - 1)"
                    >
                        Trước
                    </button>
                    <button
                        type="button"
                        class="rounded-[10px] border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="changePage(meta.current_page + 1)"
                    >
                        Sau
                    </button>
                </div>
            </div>
        </section>

        <Teleport to="body">
            <div v-if="selectedItem" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-4">
                <div class="w-full max-w-2xl rounded-[10px] border border-slate-200 bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                        <div>
                            <p class="text-sm font-bold text-slate-900">Chi tiết góp ý #{{ selectedItem.id }}</p>
                            <p class="text-xs text-slate-500">{{ selectedItem.created_at || '-' }}</p>
                        </div>
                        <button type="button" class="rounded-[8px] p-1.5 text-slate-500 hover:bg-slate-100" @click="closeDetail">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="space-y-3 px-4 py-4 text-sm">
                        <div class="grid gap-2 md:grid-cols-2">
                            <div>
                                <p class="text-xs text-slate-500">Người gửi</p>
                                <p class="font-semibold text-slate-900">
                                    {{ selectedItem.name || selectedItem.user?.full_name || selectedItem.user?.username || '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Liên hệ</p>
                                <p class="text-slate-700">{{ selectedItem.email || selectedItem.user?.email || '-' }}</p>
                                <p class="text-slate-700">{{ selectedItem.phone || selectedItem.user?.phone || '-' }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Tiêu đề</p>
                            <p class="font-semibold text-slate-900">{{ selectedItem.subject }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Nội dung góp ý</p>
                            <div class="mt-1 rounded-[8px] border border-slate-200 bg-slate-50 p-3 text-slate-700 whitespace-pre-wrap">
                                {{ selectedItem.content }}
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-semibold text-slate-600">Trạng thái</label>
                                <select
                                    v-model="detailStatus"
                                    class="rounded-[8px] border border-slate-200 px-2 py-1.5 text-xs outline-none focus:border-indigo-400"
                                    :disabled="statusLoading"
                                >
                                    <option value="new">Mới</option>
                                    <option value="in_progress">Đang xử lý</option>
                                    <option value="done">Hoàn tất</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-[8px] border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    @click="closeDetail"
                                >
                                    Đóng
                                </button>
                                <button
                                    type="button"
                                    class="rounded-[8px] bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                    :disabled="statusLoading"
                                    @click="submitDetailStatus"
                                >
                                    {{ statusLoading ? 'Đang lưu...' : 'Lưu trạng thái' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
