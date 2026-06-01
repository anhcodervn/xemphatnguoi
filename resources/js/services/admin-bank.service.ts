import api from '@/config/axios';
import type { AdminBankItem, AdminBankListResponse, AdminBankPayload } from '@/types/admin-bank.type';

export const adminBankService = {
    async list(params: Record<string, unknown> = {}): Promise<AdminBankListResponse> {
        const response = await api.get('/api/admin-api/banks', { params });

        return response.data.data as AdminBankListResponse;
    },
    async get(id: number | string): Promise<AdminBankItem> {
        const response = await api.get(`/api/admin-api/banks/${id}`);

        return response.data.data as AdminBankItem;
    },
    async create(payload: AdminBankPayload) {
        return api.post('/api/admin-api/banks', payload);
    },
    async update(id: number | string, payload: Partial<AdminBankPayload>) {
        return api.patch(`/api/admin-api/banks/${id}`, payload);
    },
    async remove(id: number | string) {
        return api.delete(`/api/admin-api/banks/${id}`);
    },
};
