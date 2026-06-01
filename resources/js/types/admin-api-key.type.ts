export type AdminApiKeyItem = {
    id: number;
    name: string;
    api_key: string;
    permissions: string[];
    ip_whitelist: string[];
    status: string;
    last_used_at: string | null;
    expired_at: string | null;
    created_at: string | null;
    logs_count: number;
    user: {
        id: number;
        username: string;
        email: string | null;
        full_name: string | null;
    } | null;
};

export type AdminApiKeyListResponse = {
    api_keys: {
        data: AdminApiKeyItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
    summary: {
        total: number;
        active: number;
        inactive: number;
        revoked: number;
    };
};
