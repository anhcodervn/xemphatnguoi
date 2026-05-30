import api from '@/config/axios';
import type { ClientNotificationListResponse } from '@/types/client-notification.type';

export const clientNotificationService = {
    async list(params: Record<string, unknown> = {}): Promise<ClientNotificationListResponse> {
        const response = await api.get('/api/notifications', { params });

        return response.data.data as ClientNotificationListResponse;
    },

    async markRead(notificationId: number | string): Promise<void> {
        await api.post(`/api/notifications/${notificationId}/read`);
    },
};
