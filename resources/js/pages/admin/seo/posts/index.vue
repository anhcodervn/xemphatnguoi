<script setup lang="ts">
import Breadcrumb from '@/components/MasterLayouts/Breadcrumb/index.vue';
import { adminSeoService } from '@/services/admin-seo.service';
import type { AdminSeoPostItem, SeoPostStatus } from '@/types/admin-seo.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { FilterX, Plus, Search, Trash2 } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';

const loading = ref(false);
const rows = ref<AdminSeoPostItem[]>([]);
const categories = ref<Array<{ id: number; name: string }>>([]);
const filters = reactive({
    search: '',
    status: 'pending_review',
    category_id: '',
    source: '',
    created_by_type: '',
    date: '',
});

const fetchPosts = async (): Promise<void> => {
    try {
        loading.value = true;
        const response = await adminSeoService.listPosts({
            search: filters.search || undefined,
            status: filters.status || undefined,
            category_id: filters.category_id || undefined,
            source: filters.source || undefined,
            created_by_type: filters.created_by_type || undefined,
            date: filters.date || undefined,
        });
        rows.value = response.posts;
        categories.value = response.categories;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const resetFilters = async (): Promise<void> => {
    filters.search = '';
    filters.status = 'pending_review';
    filters.category_id = '';
    filters.source = '';
    filters.created_by_type = '';
    filters.date = '';
    await fetchPosts();
};

const removePost = async (post: AdminSeoPostItem): Promise<void> => {
    if (!window.confirm(`Xóa bài viết “${post.title}”?`)) {
        return;
    }

    try {
        const response = await adminSeoService.removePost(post.id);
        handleSuccessResponse(response);
        await fetchPosts();
    } catch (error) {
        handleErrorResponse(error);
    }
};

const formatDate = (value: string | null): string => (value ? new Date(value).toLocaleString('vi-VN') : '—');

const statusClass = (status: SeoPostStatus): string => {
    const classes: Record<SeoPostStatus, string> = {
        draft: 'border-slate-200 bg-slate-100 text-slate-700',
        pending_review: 'border-amber-200 bg-amber-50 text-amber-800',
        approved: 'border-sky-200 bg-sky-50 text-sky-700',
        published: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        rejected: 'border-rose-200 bg-rose-50 text-rose-700',
        scheduled: 'border-violet-200 bg-violet-50 text-violet-700',
    };

    return classes[status];
};

onMounted(fetchPosts);
</script>

<template>
    <div class="space-y-4">
        <Breadcrumb title="Quản lý bài viết" description="Đọc, chỉnh sửa và duyệt thủ công nội dung do n8n hoặc admin tạo.">
            <template #actions>
                <RouterLink
                    to="/admin/articles/create"
                    class="inline-flex min-h-11 items-center gap-2 rounded-[10px] bg-violet-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2"
                >
                    <Plus class="h-4 w-4" />
                    Tạo bài thủ công
                </RouterLink>
            </template>
        </Breadcrumb>

        <section class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <label class="md:col-span-2 xl:col-span-2">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Từ khóa</span>
                    <span
                        class="flex min-h-11 items-center gap-2 rounded-[10px] border border-slate-200 bg-slate-50 px-3 text-sm text-slate-500 focus-within:border-violet-300 focus-within:ring-2 focus-within:ring-violet-100"
                    >
                        <Search class="h-4 w-4" />
                        <input
                            v-model="filters.search"
                            type="search"
                            class="w-full border-0 bg-transparent p-0 text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0"
                            placeholder="Tiêu đề, slug, SEO title"
                            @keyup.enter="fetchPosts"
                        />
                    </span>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái</span>
                    <select v-model="filters.status" class="min-h-11 w-full rounded-[10px] border-slate-200 text-sm" @change="fetchPosts">
                        <option value="">Tất cả</option>
                        <option value="pending_review">Chờ duyệt</option>
                        <option value="draft">Bản nháp</option>
                        <option value="approved">Đã duyệt</option>
                        <option value="published">Đã xuất bản</option>
                        <option value="rejected">Bị từ chối</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Danh mục</span>
                    <select v-model="filters.category_id" class="min-h-11 w-full rounded-[10px] border-slate-200 text-sm" @change="fetchPosts">
                        <option value="">Tất cả</option>
                        <option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Nguồn tạo</span>
                    <select v-model="filters.created_by_type" class="min-h-11 w-full rounded-[10px] border-slate-200 text-sm" @change="fetchPosts">
                        <option value="">Tất cả</option>
                        <option value="n8n">N8N</option>
                        <option value="admin">Manual</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Ngày gửi</span>
                    <input v-model="filters.date" type="date" class="min-h-11 w-full rounded-[10px] border-slate-200 text-sm" @change="fetchPosts" />
                </label>
            </div>

            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="flex-1">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Loại hoặc domain nguồn</span>
                    <input
                        v-model="filters.source"
                        type="text"
                        class="min-h-11 w-full rounded-[10px] border-slate-200 text-sm"
                        placeholder="official, csgt.vn..."
                        @keyup.enter="fetchPosts"
                    />
                </label>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="min-h-11 rounded-[10px] bg-slate-950 px-5 text-sm font-semibold text-white hover:bg-slate-800"
                        @click="fetchPosts"
                    >
                        Lọc bài
                    </button>
                    <button
                        type="button"
                        class="inline-flex min-h-11 items-center gap-2 rounded-[10px] border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        @click="resetFilters"
                    >
                        <FilterX class="h-4 w-4" />
                        Đặt lại
                    </button>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[1600px]">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">ID / Bài viết</th>
                            <th class="px-4 py-3">Danh mục / Tags</th>
                            <th class="px-4 py-3">Nguồn</th>
                            <th class="px-4 py-3">Tạo bởi</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Ngày gửi</th>
                            <th class="px-4 py-3">Duyệt lúc</th>
                            <th class="px-4 py-3">Xuất bản lúc</th>
                            <th class="px-4 py-3">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading">
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-500">Đang tải bài viết...</td>
                        </tr>
                        <tr v-else-if="rows.length === 0">
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-500">Không có bài phù hợp với bộ lọc.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="align-top hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                <p class="text-xs font-bold text-slate-400">#{{ row.id }}</p>
                                <p class="mt-1 max-w-sm font-semibold text-slate-950">{{ row.title }}</p>
                                <p class="mt-1 max-w-sm truncate font-mono text-xs text-slate-500">{{ row.slug }}</p>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">
                                <p class="font-medium text-slate-800">{{ row.category?.name || '—' }}</p>
                                <div class="mt-2 flex max-w-xs flex-wrap gap-1">
                                    <span
                                        v-for="tag in row.seo_tags?.slice(0, 3)"
                                        :key="tag.id"
                                        class="rounded-full bg-slate-100 px-2 py-1 text-xs"
                                        >{{ tag.name }}</span
                                    >
                                    <span v-if="(row.seo_tags?.length ?? 0) > 3" class="rounded-full bg-slate-100 px-2 py-1 text-xs"
                                        >+{{ (row.seo_tags?.length ?? 0) - 3 }}</span
                                    >
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">
                                <a
                                    v-if="row.source_url"
                                    :href="row.source_url"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="font-medium text-violet-600 hover:underline"
                                >
                                    {{ row.source_domain || row.source_type || 'Mở nguồn' }}
                                </a>
                                <span v-else>—</span>
                                <p v-if="row.source_type" class="mt-1 text-xs text-slate-400">{{ row.source_type }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="rounded-full border px-2.5 py-1 text-xs font-bold"
                                    :class="
                                        row.created_by_type === 'n8n'
                                            ? 'border-violet-200 bg-violet-50 text-violet-700'
                                            : 'border-slate-200 bg-slate-100 text-slate-700'
                                    "
                                >
                                    {{ row.created_by_type === 'n8n' ? 'N8N' : 'MANUAL' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold" :class="statusClass(row.status)">{{
                                    row.status
                                }}</span>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-600">{{ formatDate(row.created_at) }}</td>
                            <td class="px-4 py-4 text-xs text-slate-600">{{ formatDate(row.reviewed_at) }}</td>
                            <td class="px-4 py-4 text-xs text-slate-600">{{ formatDate(row.published_at) }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <RouterLink
                                        :to="`/admin/articles/${row.id}`"
                                        class="inline-flex min-h-10 items-center rounded-[8px] border border-violet-200 px-3 text-xs font-semibold text-violet-700 hover:bg-violet-50"
                                    >
                                        {{ row.status === 'pending_review' ? 'Review' : 'Xem / sửa' }}
                                    </RouterLink>
                                    <button
                                        type="button"
                                        class="inline-flex min-h-10 items-center gap-1 rounded-[8px] border border-rose-200 px-3 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                        @click="removePost(row)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
