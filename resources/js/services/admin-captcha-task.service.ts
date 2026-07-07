import api from '@/config/axios';

export const adminCaptchaTaskService = {
    async list(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/admin-api/captcha-tasks', { params });
        return response.data.data;
    },
};
