import api from '@/config/axios';
import type { AdminApiKeyListResponse } from '@/types/admin-api-key.type';

export const adminApiKeyService = {
    async list(params: Record<string, unknown> = {}): Promise<AdminApiKeyListResponse> {
        const response = await api.get('/api/admin-api/api-keys', { params });

        return response.data.data as AdminApiKeyListResponse;
    },
};
