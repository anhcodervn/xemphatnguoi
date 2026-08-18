<script setup lang="ts">
import Breadcrumb from '@/components/MasterLayouts/Breadcrumb/index.vue';
import Editor from '@/components/shared/Editor/index.vue';
import { adminSeoService } from '@/services/admin-seo.service';
import type { AdminSeoPostItem, AdminSeoPostPayload, SeoRobotsValue } from '@/types/admin-seo.type';
import { uploadEditorImages } from '@/utils/editor-image-upload';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { CheckCircle2, ExternalLink, Save, Send, XCircle } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const saving = ref(false);
const actioning = ref(false);
const post = ref<AdminSeoPostItem | null>(null);
const categories = ref<Array<{ id: number; name: string }>>([]);
const tagsInput = ref('');
const rejectionReason = ref('');
const editingId = computed(() => (route.params.seo_post_id ? Number(route.params.seo_post_id) : null));

const form = reactive<AdminSeoPostPayload>({
    title: '',
    slug: '',
    seo_category_id: null,
    excerpt: '',
    thumbnail: '',
    content: [],
    faq: [],
    seo_title: '',
    seo_description: '',
    canonical_url: '',
    og_image: '',
    robots: 'index,follow',
    index_status: 'index',
    focus_keyword: '',
    tags: [],
    cover_alt: '',
    article_schema: true,
    breadcrumb_schema: true,
    status: 'draft',
    published_at: null,
    scheduled_at: null,
});

const pageTitle = computed(() => (editingId.value ? 'Review bài viết' : 'Tạo bài viết thủ công'));
const canApprove = computed(() => ['draft', 'pending_review', 'rejected'].includes(post.value?.status ?? ''));
const canReject = computed(() => post.value !== null && post.value.status !== 'published' && post.value.status !== 'rejected');
const canPublish = computed(() => post.value?.status === 'approved');
const canSaveDraft = computed(() => post.value !== null && post.value.status !== 'published');

const applyPost = (item: AdminSeoPostItem): void => {
    post.value = item;
    form.title = item.title;
    form.slug = item.slug;
    form.seo_category_id = item.seo_category_id;
    form.excerpt = item.excerpt ?? '';
    form.thumbnail = item.thumbnail ?? '';
    form.content = Array.isArray(item.content) ? item.content : [];
    form.faq = Array.isArray(item.faq) ? item.faq : [];
    form.seo_title = item.seo_title ?? '';
    form.seo_description = item.seo_description ?? '';
    form.canonical_url = item.canonical_url ?? '';
    form.og_image = item.og_image ?? '';
    form.robots = item.robots as SeoRobotsValue;
    form.index_status = item.index_status ?? 'index';
    form.focus_keyword = item.focus_keyword ?? '';
    form.tags = Array.isArray(item.tags) ? item.tags : [];
    tagsInput.value = item.seo_tags?.map((tag) => tag.name).join(', ') || form.tags.join(', ');
    form.cover_alt = item.cover_alt ?? '';
    form.article_schema = item.article_schema;
    form.breadcrumb_schema = item.breadcrumb_schema;
    form.status = item.status;
    form.published_at = item.published_at;
    form.scheduled_at = item.scheduled_at;
    rejectionReason.value = item.rejection_reason ?? '';
};

const fetchPage = async (): Promise<void> => {
    const categoryRows = await adminSeoService.listCategories();
    categories.value = categoryRows.map((category) => ({ id: category.id, name: category.name }));

    if (editingId.value) {
        applyPost(await adminSeoService.getPost(editingId.value));
    }
};

const payload = async (): Promise<AdminSeoPostPayload> => {
    form.content = await uploadEditorImages(form.content ?? []);

    return {
        ...form,
        content: form.content ?? [],
        faq: (form.faq ?? []).filter((item) => item.question.trim() && item.answer.trim()),
        tags: tagsInput.value
            .split(',')
            .map((tag) => tag.trim())
            .filter((tag, index, tags) => tag && tags.findIndex((item) => item.toLocaleLowerCase('vi') === tag.toLocaleLowerCase('vi')) === index),
    };
};

const saveContent = async (showMessage = true): Promise<number> => {
    const response = editingId.value
        ? await adminSeoService.updatePost(editingId.value, await payload())
        : await adminSeoService.createPost(await payload());

    if (showMessage) handleSuccessResponse(response);

    return Number(response.data.data.id);
};

const handleSave = async (): Promise<void> => {
    try {
        saving.value = true;
        const id = await saveContent();
        if (!editingId.value) await router.replace(`/admin/articles/${id}`);
        else applyPost(await adminSeoService.getPost(id));
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};

const runAction = async (action: 'draft' | 'approve' | 'reject' | 'publish'): Promise<void> => {
    if (!editingId.value) return;

    try {
        actioning.value = true;
        await saveContent(false);
        const response =
            action === 'draft'
                ? await adminSeoService.saveDraft(editingId.value)
                : action === 'approve'
                  ? await adminSeoService.approvePost(editingId.value)
                  : action === 'reject'
                    ? await adminSeoService.rejectPost(editingId.value, rejectionReason.value)
                    : await adminSeoService.publishPost(editingId.value);
        handleSuccessResponse(response);
        applyPost(await adminSeoService.getPost(editingId.value));
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        actioning.value = false;
    }
};

const formatDate = (value: string | null | undefined): string => (value ? new Date(value).toLocaleString('vi-VN') : '—');

onMounted(async () => {
    try {
        loading.value = true;
        await fetchPage();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="space-y-4">
        <Breadcrumb :title="pageTitle" description="Đối chiếu nguồn, chỉnh sửa nội dung và thực hiện workflow duyệt trước khi public.">
            <template #actions>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex min-h-11 items-center gap-2 rounded-[10px] border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                        :disabled="saving || actioning || loading"
                        @click="handleSave"
                    >
                        <Save class="h-4 w-4" />
                        {{ saving ? 'Đang lưu...' : 'Lưu nội dung' }}
                    </button>
                    <button
                        v-if="canApprove"
                        type="button"
                        class="inline-flex min-h-11 items-center gap-2 rounded-[10px] bg-sky-600 px-4 text-sm font-semibold text-white hover:bg-sky-500 disabled:opacity-50"
                        :disabled="actioning"
                        @click="runAction('approve')"
                    >
                        <CheckCircle2 class="h-4 w-4" /> Approve
                    </button>
                    <button
                        v-if="canPublish"
                        type="button"
                        class="inline-flex min-h-11 items-center gap-2 rounded-[10px] bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                        :disabled="actioning"
                        @click="runAction('publish')"
                    >
                        <Send class="h-4 w-4" /> Publish
                    </button>
                </div>
            </template>
        </Breadcrumb>

        <div v-if="loading" class="rounded-[14px] border border-slate-200 bg-white p-12 text-center text-sm text-slate-500">Đang tải bài viết...</div>

        <div v-else class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <main class="space-y-4">
                <section class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="md:col-span-2"
                            ><span class="text-sm font-medium text-slate-700">Tiêu đề</span
                            ><input v-model="form.title" required type="text" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm"
                        /></label>
                        <label
                            ><span class="text-sm font-medium text-slate-700">Slug</span
                            ><input
                                v-model="form.slug"
                                required
                                type="text"
                                class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 font-mono text-sm"
                        /></label>
                        <label
                            ><span class="text-sm font-medium text-slate-700">Danh mục chính</span
                            ><select v-model="form.seo_category_id" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm">
                                <option :value="null">Không gắn danh mục</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select></label
                        >
                        <label class="md:col-span-2"
                            ><span class="text-sm font-medium text-slate-700">Excerpt</span
                            ><textarea v-model="form.excerpt" rows="3" class="mt-2 w-full rounded-[10px] border-slate-200 text-sm" />
                        </label>
                        <label class="md:col-span-2"
                            ><span class="text-sm font-medium text-slate-700">Thumbnail URL</span
                            ><input v-model="form.thumbnail" type="url" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm"
                        /></label>
                        <img
                            v-if="form.thumbnail"
                            :src="form.thumbnail"
                            :alt="form.cover_alt || form.title"
                            class="aspect-[16/7] w-full rounded-xl border border-slate-200 object-cover md:col-span-2"
                        />
                    </div>
                </section>

                <section class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-lg font-semibold text-slate-950">Nội dung đầy đủ</h2>
                    <div class="mt-4"><Editor v-model="form.content" :height="620" /></div>
                </section>

                <section class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-lg font-semibold text-slate-950">SEO metadata</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label
                            ><span class="text-sm font-medium text-slate-700">Primary keyword</span
                            ><input v-model="form.focus_keyword" type="text" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm"
                        /></label>
                        <label
                            ><span class="text-sm font-medium text-slate-700">Tags, cách nhau bằng dấu phẩy</span
                            ><input v-model="tagsInput" type="text" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm"
                        /></label>
                        <label class="md:col-span-2"
                            ><span class="text-sm font-medium text-slate-700">Meta title</span
                            ><input v-model="form.seo_title" type="text" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm"
                        /></label>
                        <label class="md:col-span-2"
                            ><span class="text-sm font-medium text-slate-700">Meta description</span
                            ><textarea v-model="form.seo_description" rows="3" class="mt-2 w-full rounded-[10px] border-slate-200 text-sm" />
                        </label>
                        <label class="md:col-span-2"
                            ><span class="text-sm font-medium text-slate-700">Canonical URL</span
                            ><input v-model="form.canonical_url" type="url" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm"
                        /></label>
                        <label
                            ><span class="text-sm font-medium text-slate-700">Index status</span
                            ><select v-model="form.index_status" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm">
                                <option value="index">index</option>
                                <option value="noindex">noindex</option>
                            </select></label
                        >
                        <label
                            ><span class="text-sm font-medium text-slate-700">Robots</span
                            ><select v-model="form.robots" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm">
                                <option value="index,follow">index,follow</option>
                                <option value="noindex,follow">noindex,follow</option>
                            </select></label
                        >
                        <label class="md:col-span-2"
                            ><span class="text-sm font-medium text-slate-700">Alt text ảnh đại diện</span
                            ><input v-model="form.cover_alt" type="text" class="mt-2 min-h-11 w-full rounded-[10px] border-slate-200 text-sm"
                        /></label>
                    </div>
                </section>
            </main>

            <aside class="space-y-4">
                <section class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-slate-950">Trạng thái review</h2>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800">{{ post?.status ?? 'draft' }}</span>
                    </div>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tạo bởi</dt>
                            <dd class="mt-1 font-medium text-slate-800">{{ post?.created_by_type === 'n8n' ? 'N8N' : 'MANUAL' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">N8N gửi lúc</dt>
                            <dd class="mt-1 text-slate-700">{{ formatDate(post?.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Duyệt lúc</dt>
                            <dd class="mt-1 text-slate-700">{{ formatDate(post?.reviewed_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Xuất bản lúc</dt>
                            <dd class="mt-1 text-slate-700">{{ formatDate(post?.published_at) }}</dd>
                        </div>
                    </dl>
                    <div v-if="editingId" class="mt-5 grid gap-2">
                        <button
                            v-if="canSaveDraft"
                            type="button"
                            class="min-h-11 rounded-[10px] border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            :disabled="actioning"
                            @click="runAction('draft')"
                        >
                            Save Draft
                        </button>
                        <label v-if="canReject"
                            ><span class="text-sm font-medium text-slate-700">Lý do từ chối</span
                            ><textarea
                                v-model="rejectionReason"
                                rows="3"
                                class="mt-2 w-full rounded-[10px] border-slate-200 text-sm"
                                placeholder="Phản hồi để n8n có thể sửa bài"
                            />
                        </label>
                        <button
                            v-if="canReject"
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-[10px] border border-rose-200 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                            :disabled="actioning || !rejectionReason.trim()"
                            @click="runAction('reject')"
                        >
                            <XCircle class="h-4 w-4" /> Reject
                        </button>
                    </div>
                </section>

                <section class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold text-slate-950">Nguồn đối chiếu</h2>
                    <a
                        v-if="post?.source_url"
                        :href="post.source_url"
                        target="_blank"
                        rel="noreferrer"
                        class="mt-3 inline-flex items-center gap-2 break-all text-sm font-semibold text-violet-600 hover:underline"
                        ><ExternalLink class="h-4 w-4 shrink-0" />{{ post.source_title || post.source_domain || post.source_url }}</a
                    >
                    <p v-else class="mt-3 text-sm text-slate-500">Bài không khai báo nguồn chính.</p>
                    <div v-if="post?.sources?.length" class="mt-4 grid gap-2 border-t border-slate-100 pt-4">
                        <a
                            v-for="source in post.sources"
                            :key="source.id"
                            :href="source.url"
                            target="_blank"
                            rel="noreferrer"
                            class="rounded-lg bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100"
                            >{{ source.title || source.domain || source.url }}</a
                        >
                    </div>
                </section>

                <section v-if="post?.activity_logs?.length" class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold text-slate-950">Activity log</h2>
                    <ol class="mt-4 grid gap-3">
                        <li v-for="log in post.activity_logs" :key="log.id" class="border-l-2 border-violet-200 pl-3 text-xs">
                            <p class="font-semibold text-slate-800">{{ log.action }}</p>
                            <p class="mt-1 text-slate-500">
                                {{ log.old_status || '—' }} → {{ log.new_status || '—' }} · {{ formatDate(log.created_at) }}
                            </p>
                        </li>
                    </ol>
                </section>
            </aside>
        </div>
    </div>
</template>
