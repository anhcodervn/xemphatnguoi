import api from '@/config/axios';

const resource = (path: 'proxy-categories') => ({
    async list(params: Record<string, unknown> = {}) {
        const response = await api.get(`/api/admin-api/${path}`, { params });
        return response.data.data;
    },
    async create(payload: Record<string, unknown>) {
        return api.post(`/api/admin-api/${path}`, payload);
    },
    async update(id: number, payload: Record<string, unknown>) {
        return api.patch(`/api/admin-api/${path}/${id}`, payload);
    },
    async delete(id: number) {
        return api.delete(`/api/admin-api/${path}/${id}`);
    },
});

export const adminProxyCategoryService = resource('proxy-categories');
