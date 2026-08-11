import api from '@/config/axios';
import type {
    SupportConversationListResponse,
    SupportSendResponse,
    SupportStats,
    SupportThreadResponse,
    SupportUserSearchItem,
} from '@/types/support.type';

export const supportService = {
    async clientThread(cursor?: string | null): Promise<SupportThreadResponse> {
        const response = await api.get('/api/client/support', { params: cursor ? { cursor } : {} });
        return response.data.data as SupportThreadResponse;
    },

    async clientSend(message: string): Promise<SupportSendResponse> {
        const response = await api.post('/api/client/support/messages', { message });
        return response.data.data as SupportSendResponse;
    },

    async clientMarkRead(): Promise<{ updated: number; message_ids: number[]; stats: SupportStats }> {
        const response = await api.post('/api/client/support/read');
        return response.data.data;
    },

    async clientUnread(): Promise<SupportStats> {
        const response = await api.get('/api/client/support/unread');
        return response.data.data.stats as SupportStats;
    },

    async adminConversations(params: Record<string, unknown> = {}): Promise<SupportConversationListResponse> {
        const response = await api.get('/api/admin-api/support/conversations', { params });
        return response.data.data as SupportConversationListResponse;
    },

    async adminThread(conversationId: number, cursor?: string | null): Promise<SupportThreadResponse> {
        const response = await api.get(`/api/admin-api/support/conversations/${conversationId}`, { params: cursor ? { cursor } : {} });
        return response.data.data as SupportThreadResponse;
    },

    async adminReply(conversationId: number, message: string): Promise<SupportSendResponse> {
        const response = await api.post(`/api/admin-api/support/conversations/${conversationId}/messages`, { message });
        return response.data.data as SupportSendResponse;
    },

    async adminStart(userId: number, message: string): Promise<SupportSendResponse> {
        const response = await api.post('/api/admin-api/support/conversations', { user_id: userId, message });
        return response.data.data as SupportSendResponse;
    },

    async adminMarkRead(conversationId: number): Promise<{ updated: number; message_ids: number[]; stats: SupportStats }> {
        const response = await api.post(`/api/admin-api/support/conversations/${conversationId}/read`);
        return response.data.data;
    },

    async adminUsers(search: string): Promise<SupportUserSearchItem[]> {
        const response = await api.get('/api/admin-api/support/users', { params: { search } });
        return response.data.data.users as SupportUserSearchItem[];
    },

    async adminUnread(): Promise<SupportStats> {
        const response = await api.get('/api/admin-api/support/unread');
        return response.data.data.stats as SupportStats;
    },
};
