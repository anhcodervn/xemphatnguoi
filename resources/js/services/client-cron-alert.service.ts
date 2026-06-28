import api from '@/config/axios';
import type { CronAlertChannelItem } from './client-cron-job.service';

export type CronAlertPayload = {
    name: string;
    type: 'discord' | 'telegram' | 'webhook' | 'email';
    target_url?: string | null;
    telegram_bot_token?: string | null;
    telegram_chat_id?: string | null;
    email?: string | null;
    events: string[];
    is_enabled?: boolean;
};

export const clientCronAlertService = {
    async list(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/client/cron-alert-channels', { params });
        return response.data.data as {
            data: CronAlertChannelItem[];
            meta: { current_page: number; last_page: number; per_page: number; total: number };
        };
    },
    async get(id: number | string) {
        const response = await api.get(`/api/client/cron-alert-channels/${id}`);
        return response.data.data as { channel: CronAlertChannelItem };
    },
    async create(payload: CronAlertPayload) {
        return await api.post('/api/client/cron-alert-channels', payload);
    },
    async update(id: number | string, payload: CronAlertPayload) {
        return await api.patch(`/api/client/cron-alert-channels/${id}`, payload);
    },
    async delete(id: number | string) {
        return await api.delete(`/api/client/cron-alert-channels/${id}`);
    },
    async test(id: number | string) {
        return await api.post(`/api/client/cron-alert-channels/${id}/test`);
    },
};
