import api from '@/config/axios';
import type {
    ApiKeyPermissionType,
    ClientApiKeyListType,
    CreateClientApiKeyPayload,
    CreateClientApiKeyResultType,
    RotateClientApiKeyResultType,
    UpdateClientApiKeyPayload,
    UpdateClientApiKeyResultType,
} from '@/types/api-key.type';

export const clientApiKeyService = {
    async list(): Promise<ClientApiKeyListType> {
        const response = await api.get('/api/client/api-keys');

        return response.data?.data as ClientApiKeyListType;
    },

    async permissions(): Promise<{ permissions: ApiKeyPermissionType[]; note?: string }> {
        const response = await api.get('/api/client/api-keys/permissions');

        return response.data?.data ?? { permissions: [] };
    },

    async create(payload: CreateClientApiKeyPayload): Promise<CreateClientApiKeyResultType> {
        const response = await api.post('/api/client/api-keys', payload);

        return response.data?.data as CreateClientApiKeyResultType;
    },

    async update(apiKeyId: number, payload: UpdateClientApiKeyPayload): Promise<UpdateClientApiKeyResultType> {
        const response = await api.patch(`/api/client/api-keys/${apiKeyId}`, payload);

        return response.data?.data as UpdateClientApiKeyResultType;
    },

    async rotate(apiKeyId: number): Promise<RotateClientApiKeyResultType> {
        const response = await api.post(`/api/client/api-keys/${apiKeyId}/rotate-secret`);

        return response.data?.data as RotateClientApiKeyResultType;
    },
};
