export interface ApiKeyPermissionType {
    key: string;
    group: string;
    version: string;
    label: string;
    description: string;
    endpoints: string[];
    self_service: boolean;
}

export interface ClientApiKeyType {
    id: number;
    name: string;
    api_key: string;
    key_type: 'wallet' | string;
    permissions: string[];
    permission_details: ApiKeyPermissionType[];
    ip_whitelist: string[];
    status: 'active' | 'inactive' | 'expired' | 'revoked';
    last_used_at: string | null;
    expired_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    logs_count?: number;
}

export interface ClientApiKeyListMetaType {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface ClientApiKeyListType {
    data: ClientApiKeyType[];
    meta: ClientApiKeyListMetaType;
    permissions: ApiKeyPermissionType[];
}

export interface CreateClientApiKeyPayload {
    name: string;
    permissions: string[];
    ip_whitelist?: string[];
    expired_at?: string | null;
}

export interface UpdateClientApiKeyPayload {
    name?: string;
    permissions?: string[];
    ip_whitelist?: string[];
    expired_at?: string | null;
    status?: 'active' | 'inactive' | 'revoked';
}

export interface CreateClientApiKeyResultType {
    api_key: ClientApiKeyType;
    api_secret: string;
    permission_catalog: ApiKeyPermissionType[];
}

export interface RotateClientApiKeyResultType {
    api_key: ClientApiKeyType;
    api_secret: string;
}

export interface UpdateClientApiKeyResultType {
    api_key: ClientApiKeyType;
}
