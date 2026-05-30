import api from '@/config/axios';
import type { ContactFeedbackPayload, ContactInfoResponse } from '@/types/contact-feedback.type';

export const clientContactService = {
    async getInfo(): Promise<ContactInfoResponse> {
        const response = await api.get('/api/contact/info');

        return response.data.data as ContactInfoResponse;
    },

    async submitFeedback(payload: ContactFeedbackPayload): Promise<void> {
        await api.post('/api/contact/feedback', payload);
    },
};
