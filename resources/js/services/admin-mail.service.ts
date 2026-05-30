import api from '@/config/axios';
import type { AdminMailUserListResponse, AdminSendMailPayload, AdminSendMailResponse } from '@/types/admin-mail.type';

export const adminMailService = {
    async users(params: Record<string, unknown> = {}): Promise<AdminMailUserListResponse> {
        const response = await api.get('/api/admin/mail/users', { params });
        return response.data.data as AdminMailUserListResponse;
    },

    async send(payload: AdminSendMailPayload): Promise<AdminSendMailResponse> {
        const response = await api.post('/api/admin/mail/send', payload);
        return response.data.data as AdminSendMailResponse;
    },
};
