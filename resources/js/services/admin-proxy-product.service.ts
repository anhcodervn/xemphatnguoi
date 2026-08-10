import api from '@/config/axios';

export const adminProxyProductService = {
    async list(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/admin-api/proxy-products', { params });
        return response.data.data;
    },

    async show(id: number | string) {
        const response = await api.get(`/api/admin-api/proxy-products/${id}`);
        return response.data.data;
    },

    async create(payload: Record<string, unknown>) {
        return api.post('/api/admin-api/proxy-products', payload);
    },

    async update(id: number | string, payload: Record<string, unknown>) {
        return api.patch(`/api/admin-api/proxy-products/${id}`, payload);
    },
};
