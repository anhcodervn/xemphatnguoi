import api from '@/config/axios';
import type { AdminNotificationItem, AdminNotificationListResponse, AdminNotificationPayload } from '@/types/notification.type';

export const adminNotificationService = {
    async list(params: Record<string, unknown> = {}): Promise<AdminNotificationListResponse> {
        const response = await api.get('/api/admin-api/notifications', { params });

        return response.data.data as AdminNotificationListResponse;
    },

    async get(id: number | string): Promise<AdminNotificationItem> {
        const response = await api.get(`/api/admin-api/notifications/${id}`);

        return response.data.data as AdminNotificationItem;
    },

    async create(payload: AdminNotificationPayload): Promise<AdminNotificationItem> {
        const response = await api.post('/api/admin-api/notifications', payload);

        return response.data.data as AdminNotificationItem;
    },

    async update(id: number | string, payload: Partial<AdminNotificationPayload>): Promise<AdminNotificationItem> {
        const response = await api.patch(`/api/admin-api/notifications/${id}`, payload);

        return response.data.data as AdminNotificationItem;
    },

    async remove(id: number | string): Promise<void> {
        await api.delete(`/api/admin-api/notifications/${id}`);
    },
};
