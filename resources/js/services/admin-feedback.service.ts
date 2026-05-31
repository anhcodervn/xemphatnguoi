import api from '@/config/axios';
import type { AdminFeedbackItem, AdminFeedbackListResponse } from '@/types/admin-feedback.type';

export const adminFeedbackService = {
    async list(params: Record<string, unknown> = {}): Promise<AdminFeedbackListResponse> {
        const response = await api.get('/api/admin-api/feedbacks', { params });

        return response.data.data as AdminFeedbackListResponse;
    },

    async updateStatus(id: number | string, status: 'new' | 'in_progress' | 'done'): Promise<AdminFeedbackItem> {
        const response = await api.patch(`/api/admin-api/feedbacks/${id}/status`, { status });

        return response.data.data.feedback as AdminFeedbackItem;
    },
};
