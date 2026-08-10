export interface AdminQueueSummary {
    total_pending_jobs: number;
    total_failed_jobs: number;
    total_processing_logs: number;
    total_failed_logs: number;
}

export interface AdminQueueItem {
    queue: string;
    pending_jobs: number;
    failed_jobs: number;
    processing_logs: number;
    success_logs: number;
    failed_logs: number;
}

export interface AdminQueueOverviewResponse {
    summary: AdminQueueSummary;
    queues: AdminQueueItem[];
}

export interface AdminQueueLogItem {
    id: number;
    job_uuid: string | null;
    connection: string | null;
    queue: string | null;
    job_name: string | null;
    status: 'processing' | 'success' | 'failed';
    attempts: number;
    payload: Record<string, unknown> | null;
    error_message: string | null;
    processing_at: string | null;
    processed_at: string | null;
    failed_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    can_replay: boolean;
}

export interface AdminQueueFailedJobItem {
    id: number;
    uuid: string;
    queue: string;
    connection: string;
    failed_at: string;
    exception: string;
}

export interface AdminQueuePaginateMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface AdminQueueLogsResponse {
    data: AdminQueueLogItem[];
    meta: AdminQueuePaginateMeta;
}

export interface AdminQueueFailedJobsResponse {
    data: AdminQueueFailedJobItem[];
    meta: AdminQueuePaginateMeta;
}
