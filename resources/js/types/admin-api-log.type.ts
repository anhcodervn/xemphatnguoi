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

export type AdminProxyResponseItem = {
    id: number;
    proxy_order_id: number;
    operation: string;
    http_method: string;
    endpoint: string;
    status: 'pending' | 'completed' | 'failed';
    request_data: Record<string, unknown> | unknown[] | null;
    response_data: Record<string, unknown> | unknown[] | null;
    http_status: number | null;
    duration_ms: string | null;
    exception_class: string | null;
    error_message: string | null;
    created_at: string | null;
    order: {
        id: number;
        order_code: string;
        status: string;
        provider: {
            name: string;
            code: string;
        } | null;
    } | null;
};

export type AdminProxyResponseListResponse = {
    proxy_responses: {
        data: AdminProxyResponseItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
    summary: {
        total: number;
        completed: number;
        failed: number;
        pending: number;
    };
};
