import api from '@/config/axios';
import type {
    AdminQueueFailedJobsResponse,
    AdminQueueLogsResponse,
    AdminQueueOverviewResponse,
} from '@/types/admin-queue.type';

export const adminQueueService = {
    async overview(): Promise<AdminQueueOverviewResponse> {
        const response = await api.get('/api/admin-api/queues/overview');
        return response.data.data as AdminQueueOverviewResponse;
    },

    async logs(params: Record<string, unknown> = {}): Promise<AdminQueueLogsResponse> {
        const response = await api.get('/api/admin-api/queues/logs', { params });
        return response.data.data as AdminQueueLogsResponse;
    },

    async failedJobs(params: Record<string, unknown> = {}): Promise<AdminQueueFailedJobsResponse> {
        const response = await api.get('/api/admin-api/queues/failed-jobs', { params });
        return response.data.data as AdminQueueFailedJobsResponse;
    },

    async retryFailedJob(id: number): Promise<void> {
        await api.post(`/api/admin-api/queues/failed-jobs/${id}/retry`);
    },

    async deleteFailedJob(id: number): Promise<void> {
        await api.delete(`/api/admin-api/queues/failed-jobs/${id}`);
    },
};
