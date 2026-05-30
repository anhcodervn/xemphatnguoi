export interface AdminWebhookUser {
    id: number;
    name: string;
    email: string | null;
}

export interface AdminWebhookItem {
    id: number;
    user: AdminWebhookUser | null;
    url: string;
    events: string[];
    status: string;
    last_called_at: string | null;
    success_count: number;
    failed_count: number;
    created_at: string | null;
}

export interface AdminWebhookLogItem {
    id: number;
    event: string | null;
    payload_preview: string | null;
    http_status: number | null;
    response_time: number | null;
    status: 'success' | 'failed';
    retry_count: number;
    error_message: string | null;
    created_at: string | null;
}

export interface AdminWebhookListResponse {
    data: AdminWebhookItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total_webhooks: number;
        enabled_webhooks: number;
        failed_today: number;
        success_rate: number;
    };
}

export interface AdminWebhookDetailResponse {
    webhook: AdminWebhookItem;
    recent_logs: AdminWebhookLogItem[];
}

export interface AdminWebhookLogsResponse {
    data: AdminWebhookLogItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}
