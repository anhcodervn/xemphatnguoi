export type AdminApiLogItem = {
    id: number;
    endpoint: string;
    method: string;
    ip: string | null;
    request_data: Record<string, unknown> | null;
    service_response_data: Record<string, unknown> | null;
    response_data: Record<string, unknown> | null;
    status_code: number | null;
    response_time_ms: number | null;
    created_at: string | null;
    user: {
        id: number;
        username: string;
        email: string | null;
        full_name: string | null;
    } | null;
    api_key: {
        id: number;
        name: string;
        api_key: string;
        status: string;
    } | null;
};

export type AdminApiLogListResponse = {
    api_logs: {
        data: AdminApiLogItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
    summary: {
        total: number;
        success: number;
        client_error: number;
        server_error: number;
    };
};
