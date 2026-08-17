import api from '@/config/axios';
import type { AdminApiLogListResponse } from '@/types/admin-api-log.type';

export const adminApiLogService = {
    async list(params: Record<string, unknown> = {}): Promise<AdminApiLogListResponse> {
        const response = await api.get('/api/admin-api/api-logs', { params });

        return response.data.data as AdminApiLogListResponse;
    },
};
