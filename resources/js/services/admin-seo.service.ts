import api from '@/config/axios';
import type {
    AdminSeoCategoryItem,
    AdminSeoCategoryPayload,
    AdminSeoOverviewSummary,
    AdminSeoPostItem,
    AdminSeoPostPayload,
    AdminSeoSitemapEntry,
    AdminSeoTagItem,
    AdminSeoTagPayload,
} from '@/types/admin-seo.type';

export const adminSeoService = {
    async overview(): Promise<{ summary: AdminSeoOverviewSummary; sitemaps: AdminSeoSitemapEntry[] }> {
        const response = await api.get('/api/admin-api/seo/overview');

        return response.data.data as { summary: AdminSeoOverviewSummary; sitemaps: AdminSeoSitemapEntry[] };
    },

    async listCategories(params: Record<string, unknown> = {}): Promise<AdminSeoCategoryItem[]> {
        const response = await api.get('/api/admin-api/seo/categories', { params });

        return response.data.data.categories as AdminSeoCategoryItem[];
    },

    async createCategory(payload: AdminSeoCategoryPayload) {
        return api.post('/api/admin-api/seo/categories', payload);
    },

    async updateCategory(id: number | string, payload: Partial<AdminSeoCategoryPayload>) {
        return api.patch(`/api/admin-api/seo/categories/${id}`, payload);
    },

    async removeCategory(id: number | string) {
        return api.delete(`/api/admin-api/seo/categories/${id}`);
    },

    async mergeCategory(id: number | string, targetId: number) {
        return api.post(`/api/admin-api/seo/categories/${id}/merge`, { target_id: targetId });
    },

    async listTags(params: Record<string, unknown> = {}): Promise<AdminSeoTagItem[]> {
        const response = await api.get('/api/admin-api/seo/tags', { params });

        return response.data.data.tags as AdminSeoTagItem[];
    },

    async createTag(payload: AdminSeoTagPayload) {
        return api.post('/api/admin-api/seo/tags', payload);
    },

    async updateTag(id: number | string, payload: Partial<AdminSeoTagPayload>) {
        return api.patch(`/api/admin-api/seo/tags/${id}`, payload);
    },

    async removeTag(id: number | string) {
        return api.delete(`/api/admin-api/seo/tags/${id}`);
    },

    async mergeTag(id: number | string, targetId: number) {
        return api.post(`/api/admin-api/seo/tags/${id}/merge`, { target_id: targetId });
    },

    async listPosts(params: Record<string, unknown> = {}): Promise<{
        posts: AdminSeoPostItem[];
        categories: Array<{ id: number; name: string }>;
    }> {
        const response = await api.get('/api/admin-api/seo/posts', { params });

        return response.data.data as {
            posts: AdminSeoPostItem[];
            categories: Array<{ id: number; name: string }>;
        };
    },

    async getPost(id: number | string): Promise<AdminSeoPostItem> {
        const response = await api.get(`/api/admin-api/seo/posts/${id}`);

        return response.data.data as AdminSeoPostItem;
    },

    async createPost(payload: AdminSeoPostPayload) {
        return api.post('/api/admin-api/seo/posts', payload);
    },

    async updatePost(id: number | string, payload: Partial<AdminSeoPostPayload>) {
        return api.patch(`/api/admin-api/seo/posts/${id}`, payload);
    },

    async removePost(id: number | string) {
        return api.delete(`/api/admin-api/seo/posts/${id}`);
    },

    async saveDraft(id: number | string) {
        return api.post(`/api/admin-api/seo/posts/${id}/save-draft`);
    },

    async approvePost(id: number | string) {
        return api.post(`/api/admin-api/seo/posts/${id}/approve`);
    },

    async rejectPost(id: number | string, rejectionReason: string) {
        return api.post(`/api/admin-api/seo/posts/${id}/reject`, { rejection_reason: rejectionReason });
    },

    async publishPost(id: number | string) {
        return api.post(`/api/admin-api/seo/posts/${id}/publish`);
    },

    async sitemaps(): Promise<AdminSeoSitemapEntry[]> {
        const response = await api.get('/api/admin-api/seo/sitemaps');

        return response.data.data.entries as AdminSeoSitemapEntry[];
    },
};
