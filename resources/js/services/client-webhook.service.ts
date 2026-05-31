import api from '@/config/axios';
import type { WebhookLogType, WebhookType } from '@/types/bank.type';

export const clientWebhookService = {
    async listByBank(bankAccountId: string | number): Promise<WebhookType[]> {
        const res = await api.get(`/api/webhook/bank/${bankAccountId}`);
        return Array.isArray(res.data?.data) ? res.data.data : [];
    },

    async create(bankAccountId: string | number, payload: Record<string, unknown>) {
        return api.post(`/api/webhook/bank/${bankAccountId}`, payload);
    },

    async dispatch(bankAccountId: string | number, payload: Record<string, unknown>) {
        return api.post(`/api/webhook/bank/${bankAccountId}/dispatch`, payload);
    },

    async dispatchTransaction(bankAccountId: string | number, transactionId: string | number) {
        return api.post(`/api/webhook/bank/${bankAccountId}/transactions/${transactionId}/dispatch`);
    },

    async update(id: string | number, payload: Record<string, unknown>) {
        return api.put(`/api/webhook/${id}`, payload);
    },

    async logs(id: string | number): Promise<WebhookLogType[]> {
        const res = await api.get(`/api/webhook/${id}/logs`);
        return Array.isArray(res.data?.data) ? res.data.data : [];
    },

    async delete(id: string | number) {
        return api.delete(`/api/webhook/${id}`);
    },
};
