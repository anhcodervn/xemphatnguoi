import api from '@/config/axios';
import type {
    AdminWebhookDetailResponse,
    AdminWebhookListResponse,
    AdminWebhookLogsResponse,
} from '@/types/admin-webhook.type';

export const adminWebhookService = {
    async list(params: Record<string, unknown> = {}): Promise<AdminWebhookListResponse> {
        const response = await api.get('/api/admin-api/webhooks', { params });
        return response.data.data as AdminWebhookListResponse;
    },

    async detail(id: number | string): Promise<AdminWebhookDetailResponse> {
        const response = await api.get(`/api/admin-api/webhooks/${id}`);
        return response.data.data as AdminWebhookDetailResponse;
    },

    async toggle(id: number | string): Promise<{ id: number; status: string }> {
        const response = await api.post(`/api/admin-api/webhooks/${id}/toggle`);
        return response.data.data as { id: number; status: string };
    },

    async test(id: number | string): Promise<Record<string, unknown>> {
        const response = await api.post(`/api/admin-api/webhooks/${id}/test`);
        return response.data.data as Record<string, unknown>;
    },

    async logs(id: number | string, params: Record<string, unknown> = {}): Promise<AdminWebhookLogsResponse> {
        const response = await api.get(`/api/admin-api/webhooks/${id}/logs`, { params });
        return response.data.data as AdminWebhookLogsResponse;
    },
};
