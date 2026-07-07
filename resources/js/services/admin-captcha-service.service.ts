import api from '@/config/axios';

export const adminCaptchaServiceService = {
    async list(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/admin-api/captcha-services', { params });
        return response.data.data;
    },

    async show(id: number | string) {
        const response = await api.get(`/api/admin-api/captcha-services/${id}`);
        return response.data.data;
    },

    async create(payload: Record<string, unknown>) {
        return api.post('/api/admin-api/captcha-services', payload);
    },

    async update(id: number | string, payload: Record<string, unknown>) {
        return api.patch(`/api/admin-api/captcha-services/${id}`, payload);
    },
};
