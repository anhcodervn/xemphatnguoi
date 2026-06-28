import api from '@/config/axios';

export type CronJobMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
export type CronJobStatus = 'active' | 'paused' | 'disabled';
export type CronJobResultStatus = 'success' | 'failed' | 'timeout' | 'error' | 'blocked';

export type CronJobItem = {
    id: number;
    user_id: number;
    name: string;
    group_name: string | null;
    description: string | null;
    url: string;
    method: CronJobMethod;
    headers: Record<string, string>;
    body_type: 'none' | 'json' | 'form' | 'raw';
    body: string | null;
    query_params: Record<string, string>;
    cron_expression: string | null;
    interval_seconds: number | null;
    timezone: string;
    timeout_seconds: number;
    connect_timeout_seconds: number;
    retry_count: number;
    retry_delay_seconds: number;
    max_response_size_kb: number;
    expected_status_codes: number[] | null;
    expected_body_contains: string | null;
    expected_body_not_contains: string | null;
    follow_redirects: boolean;
    verify_ssl: boolean;
    status: CronJobStatus;
    last_run_at: string | null;
    next_run_at: string | null;
    last_status: CronJobResultStatus | null;
    consecutive_failures: number;
    total_runs: number;
    total_success: number;
    total_failed: number;
    created_at: string | null;
    updated_at: string | null;
    alert_channels?: CronAlertChannelItem[];
};

export type CronJobLogItem = {
    id: number;
    cron_job_id: number;
    user_id: number;
    run_uuid: string;
    attempt: number;
    status: CronJobResultStatus;
    method: string;
    url: string;
    status_code: number | null;
    duration_ms: number | null;
    request_headers: Record<string, string>;
    request_body_preview: string | null;
    response_headers: Record<string, string[] | string>;
    response_body_preview: string | null;
    response_size_bytes: number | null;
    error_message: string | null;
    ip_resolved: string | null;
    started_at: string | null;
    finished_at: string | null;
    created_at: string | null;
};

export type CronAlertChannelItem = {
    id: number;
    user_id: number;
    cron_job_id: number | null;
    name: string;
    type: 'discord' | 'telegram' | 'webhook' | 'email';
    target_url: string | null;
    telegram_chat_id: string | null;
    email: string | null;
    events: string[];
    is_enabled: boolean;
    created_at: string | null;
    updated_at: string | null;
};

export type CronJobPayload = {
    name: string;
    group_name?: string | null;
    description?: string | null;
    url: string;
    method: CronJobMethod;
    headers?: Array<{ key: string; value: string }>;
    body_type: 'none' | 'json' | 'form' | 'raw';
    body?: string | null;
    query_params?: Array<{ key: string; value: string }>;
    cron_expression?: string | null;
    interval_seconds?: number | null;
    timezone?: string;
    timeout_seconds?: number;
    connect_timeout_seconds?: number;
    retry_count?: number;
    retry_delay_seconds?: number;
    max_response_size_kb?: number;
    expected_status_codes?: number[] | null;
    expected_body_contains?: string | null;
    expected_body_not_contains?: string | null;
    follow_redirects?: boolean;
    verify_ssl?: boolean;
    alert_channel_ids?: number[];
};

export const clientCronJobService = {
    async list(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/client/cron-jobs', { params });
        return response.data.data as {
            data: CronJobItem[];
            meta: { current_page: number; last_page: number; per_page: number; total: number };
            filters: {
                groups: string[];
            };
            summary: {
                total_jobs: number;
                active_jobs: number;
                runs_today: number;
                runs_month: number;
                failed_today: number;
                quota: { daily: number | null; monthly: number | null };
            };
        };
    },
    async get(id: number | string) {
        const response = await api.get(`/api/client/cron-jobs/${id}`);
        return response.data.data as { cron_job: CronJobItem };
    },
    async create(payload: CronJobPayload) {
        return await api.post('/api/client/cron-jobs', payload);
    },
    async update(id: number | string, payload: CronJobPayload) {
        return await api.patch(`/api/client/cron-jobs/${id}`, payload);
    },
    async delete(id: number | string) {
        return await api.delete(`/api/client/cron-jobs/${id}`);
    },
    async pause(id: number | string) {
        return await api.post(`/api/client/cron-jobs/${id}/pause`);
    },
    async resume(id: number | string) {
        return await api.post(`/api/client/cron-jobs/${id}/resume`);
    },
    async runNow(id: number | string) {
        return await api.post(`/api/client/cron-jobs/${id}/run-now`);
    },
    async logs(id: number | string, params: Record<string, unknown> = {}) {
        const response = await api.get(`/api/client/cron-jobs/${id}/logs`, { params });
        return response.data.data as {
            data: CronJobLogItem[];
            meta: { current_page: number; last_page: number; per_page: number; total: number };
        };
    },
    async globalLogs(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/client/cron-jobs/logs', { params });
        return response.data.data as {
            data: CronJobLogItem[];
            meta: { current_page: number; last_page: number; per_page: number; total: number };
        };
    },
    async stats(id: number | string) {
        const response = await api.get(`/api/client/cron-jobs/${id}/stats`);
        return response.data.data as {
            summary: {
                total_runs: number;
                total_success: number;
                total_failed: number;
                success_rate: number;
                consecutive_failures: number;
                last_status: CronJobResultStatus | null;
                last_run_at: string | null;
                next_run_at: string | null;
            };
            recent_logs: CronJobLogItem[];
        };
    },
};
