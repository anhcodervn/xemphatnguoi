<script setup lang="ts">
import Breadcrumb from '@/components/MasterLayouts/Breadcrumb/index.vue';
import { adminSeoService } from '@/services/admin-seo.service';
import type { AdminSeoTagItem, AdminSeoTagPayload } from '@/types/admin-seo.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { Merge, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const saving = ref(false);
const tags = ref<AdminSeoTagItem[]>([]);
const editingId = ref<number | null>(null);
const search = ref('');
const mergeTargets = reactive<Record<number, string>>({});
const form = reactive<AdminSeoTagPayload>({ name: '', slug: '', is_active: true });

const slugify = (value: string): string =>
    value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');

const fetchTags = async (): Promise<void> => {
    try {
        loading.value = true;
        tags.value = await adminSeoService.listTags({ search: search.value || undefined });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const resetForm = (): void => {
    editingId.value = null;
    form.name = '';
    form.slug = '';
    form.is_active = true;
};

const edit = (tag: AdminSeoTagItem): void => {
    editingId.value = tag.id;
    form.name = tag.name;
    form.slug = tag.slug;
    form.is_active = tag.is_active;
};

const submit = async (): Promise<void> => {
    try {
        saving.value = true;
        const payload = { ...form, slug: form.slug || slugify(form.name) };
        const response = editingId.value ? await adminSeoService.updateTag(editingId.value, payload) : await adminSeoService.createTag(payload);
        handleSuccessResponse(response);
        resetForm();
        await fetchTags();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};

const remove = async (tag: AdminSeoTagItem): Promise<void> => {
    if (!window.confirm(`Xóa tag “${tag.name}”? Chỉ tag chưa dùng mới có thể xóa.`)) return;

    try {
        const response = await adminSeoService.removeTag(tag.id);
        handleSuccessResponse(response);
        await fetchTags();
    } catch (error) {
        handleErrorResponse(error);
    }
};

const merge = async (tag: AdminSeoTagItem): Promise<void> => {
    const targetId = Number(mergeTargets[tag.id]);
    if (!targetId || !window.confirm(`Gộp tag “${tag.name}” vào tag đã chọn?`)) return;

    try {
        const response = await adminSeoService.mergeTag(tag.id, targetId);
        handleSuccessResponse(response);
        await fetchTags();
    } catch (error) {
        handleErrorResponse(error);
    }
};

onMounted(fetchTags);
</script>

<template>
    <div class="space-y-4">
        <Breadcrumb title="Tags" description="Chuẩn hóa tag do n8n hoặc admin tạo, gộp trùng và vô hiệu hóa taxonomy không cần thiết." />

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <label
                    class="flex min-h-11 items-center gap-2 rounded-[10px] border border-slate-200 bg-slate-50 px-3 focus-within:border-violet-300"
                >
                    <Search class="h-4 w-4 text-slate-400" />
                    <span class="sr-only">Tìm tag</span>
                    <input
                        v-model="search"
                        type="search"
                        class="w-full border-0 bg-transparent p-0 text-sm focus:ring-0"
                        placeholder="Tìm tên hoặc slug"
                        @keyup.enter="fetchTags"
                    />
                </label>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[900px]">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Tag</th>
                                <th class="px-4 py-3">Nguồn</th>
                                <th class="px-4 py-3">Bài viết</th>
                                <th class="px-4 py-3">Gộp vào</th>
                                <th class="px-4 py-3">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="loading">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Đang tải tags...</td>
                            </tr>
                            <tr v-else-if="tags.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Chưa có tag.</td>
                            </tr>
                            <tr v-for="tag in tags" :key="tag.id" class="align-top hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ tag.name }}</p>
                                    <p class="mt-1 font-mono text-xs text-slate-500">{{ tag.slug }}</p>
                                    <span
                                        class="mt-2 inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="tag.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                        >{{ tag.is_active ? 'Đang dùng' : 'Đã tắt' }}</span
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full border px-2 py-1 text-xs font-bold"
                                        :class="
                                            tag.created_by_type === 'n8n'
                                                ? 'border-violet-200 bg-violet-50 text-violet-700'
                                                : 'border-slate-200 bg-slate-100 text-slate-700'
                                        "
                                        >{{ tag.created_by_type === 'n8n' ? 'N8N' : 'MANUAL' }}</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ tag.posts_count ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <select
                                            v-model="mergeTargets[tag.id]"
                                            :aria-label="`Tag đích cho ${tag.name}`"
                                            class="min-h-10 min-w-44 rounded-[8px] border-slate-200 text-xs"
                                        >
                                            <option value="">Chọn tag đích</option>
                                            <option
                                                v-for="target in tags.filter((item) => item.id !== tag.id)"
                                                :key="target.id"
                                                :value="String(target.id)"
                                            >
                                                {{ target.name }}
                                            </option>
                                        </select>
                                        <button
                                            type="button"
                                            class="inline-flex min-h-10 items-center gap-1 rounded-[8px] border border-sky-200 px-3 text-xs font-semibold text-sky-700 hover:bg-sky-50"
                                            @click="merge(tag)"
                                        >
                                            <Merge class="h-3.5 w-3.5" /> Gộp
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex min-h-10 items-center gap-1 rounded-[8px] border border-slate-200 px-3 text-xs font-semibold"
                                            @click="edit(tag)"
                                        >
                                            <Pencil class="h-3.5 w-3.5" /> Sửa</button
                                        ><button
                                            type="button"
                                            class="inline-flex min-h-10 items-center gap-1 rounded-[8px] border border-rose-200 px-3 text-xs font-semibold text-rose-700"
                                            @click="remove(tag)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" /> Xóa
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600"
                        ><Plus class="h-4 w-4"
                    /></span>
                    <div>
                        <h2 class="font-semibold text-slate-950">{{ editingId ? 'Cập nhật tag' : 'Tạo tag' }}</h2>
                        <p class="text-sm text-slate-500">Slug là khóa chống trùng logic.</p>
                    </div>
                </div>
                <form class="mt-5 grid gap-4" @submit.prevent="submit">
                    <label
                        ><span class="text-sm font-medium text-slate-700">Tên tag</span
                        ><input v-model="form.name" required type="text" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm"
                    /></label>
                    <label
                        ><span class="text-sm font-medium text-slate-700">Slug</span
                        ><input
                            v-model="form.slug"
                            type="text"
                            class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 font-mono text-sm"
                            placeholder="Tự sinh nếu để trống"
                    /></label>
                    <label class="flex min-h-11 items-center gap-3 rounded-[10px] border border-slate-200 px-3"
                        ><input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-violet-600" /><span
                            class="text-sm text-slate-700"
                            >Taxonomy đang hoạt động</span
                        ></label
                    >
                    <div class="flex gap-2">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="min-h-11 flex-1 rounded-[10px] bg-violet-600 px-4 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            {{ saving ? 'Đang lưu...' : editingId ? 'Lưu thay đổi' : 'Thêm tag' }}</button
                        ><button
                            v-if="editingId"
                            type="button"
                            class="min-h-11 rounded-[10px] border border-slate-200 px-4 text-sm font-semibold"
                            @click="resetForm"
                        >
                            Hủy
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </div>
</template>
