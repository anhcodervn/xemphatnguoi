import api from '@/config/axios';
import type { CronJobItem, CronJobLogItem } from './client-cron-job.service';

export const adminCronJobService = {
    async list(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/admin-api/cron-jobs', { params });
        return response.data.data as {
            data: CronJobItem[];
            meta: { current_page: number; last_page: number; per_page: number; total: number };
            filters: {
                groups: string[];
            };
            summary: {
                total_jobs: number;
                active_jobs: number;
                paused_jobs: number;
                disabled_jobs: number;
                runs_today: number;
                failed_today: number;
            };
        };
    },
    async get(id: number | string) {
        const response = await api.get(`/api/admin-api/cron-jobs/${id}`);
        return response.data.data as { cron_job: CronJobItem };
    },
    async updateStatus(id: number | string, status: 'active' | 'paused' | 'disabled') {
        return await api.patch(`/api/admin-api/cron-jobs/${id}/status`, { status });
    },
    async delete(id: number | string) {
        return await api.delete(`/api/admin-api/cron-jobs/${id}`);
    },
    async logs(id: number | string, params: Record<string, unknown> = {}) {
        const response = await api.get(`/api/admin-api/cron-jobs/${id}/logs`, { params });
        return response.data.data as {
            data: CronJobLogItem[];
            meta: { current_page: number; last_page: number; per_page: number; total: number };
        };
    },
    async globalLogs(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/admin-api/cron-jobs/logs', { params });
        return response.data.data as {
            data: CronJobLogItem[];
            meta: { current_page: number; last_page: number; per_page: number; total: number };
        };
    },
};
