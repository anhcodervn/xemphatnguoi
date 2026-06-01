export type AdminBankItem = {
    id: number;
    code: string;
    name: string;
    short_name: string | null;
    logo: string | null;
    bg_color: string | null;
    is_active: boolean;
    sort_order: number;
    limit_request_per_minute: number;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
    updated_at: string | null;
};

export type AdminBankListResponse = {
    banks: {
        data: AdminBankItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
    summary: {
        total: number;
        active: number;
        inactive: number;
    };
};

export type AdminBankPayload = {
    code: string;
    name: string;
    short_name: string | null;
    logo: string | null;
    bg_color: string | null;
    is_active: boolean;
    sort_order: number;
    limit_request_per_minute: number;
    metadata: Record<string, unknown>;
};
