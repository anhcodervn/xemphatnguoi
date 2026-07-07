import api from '@/config/axios';

export interface ClientCaptchaServiceStats {
    success_rate: number;
    processing_time_label: string;
    avg_processing_seconds?: number | null;
}

export interface ClientCaptchaTaskItem {
    id: number;
    task_code: string;
    external_task_id: string | null;
    service_code: string;
    status: string;
    request_payload: Record<string, unknown> | null;
    result_payload: Record<string, unknown> | null;
    provider_cost: string;
    selling_price: string;
    error_message: string | null;
    processing_seconds: number | null;
    processing_time_label: string | null;
    requested_at: string | null;
    solved_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    service?: {
        id: number;
        code: string;
        name: string;
        category: string;
        description: string | null;
        selling_price: string;
        estimated_seconds: number | null;
        is_active: boolean;
        settings?: {
            icon_url?: string | null;
        };
        stats?: ClientCaptchaServiceStats;
    } | null;
}

export interface ClientCaptchaTaskListResponse {
    current_page: number;
    data: ClientCaptchaTaskItem[];
    first_page_url: string | null;
    from: number | null;
    last_page: number;
    last_page_url: string | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export interface ClientCaptchaServiceItem {
    id: number;
    code: string;
    name: string;
    category: string;
    description: string | null;
    sort_order: number;
    selling_price: string;
    estimated_seconds: number | null;
    is_active: boolean;
    settings: {
        icon_url?: string | null;
        request_example_body?: string | null;
    };
    stats: ClientCaptchaServiceStats;
}

export const clientCaptchaService = {
    async overview() {
        const response = await api.get('/api/client/captcha/overview');
        return response.data.data as {
            summary: {
                total_tasks: number;
                pending_tasks: number;
                solved_tasks: number;
                failed_tasks: number;
                spent: number;
            };
            recent_tasks: Array<Record<string, unknown>>;
        };
    },

    async services() {
        const response = await api.get('/api/client/captcha/services');
        return response.data.data as {
            services: ClientCaptchaServiceItem[];
        };
    },

    async tasks(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/client/captcha/tasks', { params });
        return response.data.data as {
            tasks: ClientCaptchaTaskListResponse;
        };
    },
};
