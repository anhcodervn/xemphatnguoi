import api from '@/config/axios';

export const adminProxyProviderService = {
    async list(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/admin-api/proxy-providers', { params });
        return response.data.data;
    },

    async show(id: number | string) {
        const response = await api.get(`/api/admin-api/proxy-providers/${id}`);
        return response.data.data;
    },

    async create(payload: Record<string, unknown>) {
        return api.post('/api/admin-api/proxy-providers', payload);
    },

    async update(id: number | string, payload: Record<string, unknown>) {
        return api.patch(`/api/admin-api/proxy-providers/${id}`, payload);
    },

    async delete(id: number | string) {
        return api.delete(`/api/admin-api/proxy-providers/${id}`);
    },
};
