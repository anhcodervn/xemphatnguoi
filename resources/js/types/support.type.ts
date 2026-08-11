export type SupportSenderRole = 'user' | 'admin';
export type SupportMessageStatus = 'sending' | 'sent' | 'failed';

export type SupportMessage = {
    id: number | string;
    conversation_id: number;
    sender_id: number;
    sender_role: SupportSenderRole;
    message: string;
    read_at: string | null;
    created_at: string | null;
    status?: SupportMessageStatus;
};

export type SupportConversationUser = {
    id: number;
    name: string;
    username: string;
    email?: string | null;
    avatar: string | null;
};

export type SupportConversation = {
    id: number;
    user: SupportConversationUser;
    status: string;
    last_message: SupportMessage | null;
    last_message_at: string | null;
    unread_count: number;
    created_at?: string | null;
};

export type SupportStats = {
    user_unread: number;
    admin_unread: number;
};

export type SupportThreadResponse = {
    conversation: SupportConversation | null;
    messages: SupportMessage[];
    meta: {
        per_page: number;
        next_cursor: string | null;
        has_more: boolean;
    };
    stats: SupportStats;
};

export type SupportConversationListResponse = {
    conversations: SupportConversation[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: SupportStats;
};

export type SupportSendResponse = {
    conversation: SupportConversation;
    message: SupportMessage;
    stats: SupportStats;
};

export type SupportMessageCreatedEvent = {
    message: SupportMessage;
    conversation: SupportConversation;
    stats: SupportStats;
};

export type SupportMessagesReadEvent = {
    conversation_id: number;
    message_ids: number[];
    reader_role: SupportSenderRole;
    read_at: string;
    stats: SupportStats;
};

export type SupportConversationUpdatedEvent = {
    conversation: SupportConversation;
    stats: SupportStats;
};

export type SupportUserSearchItem = SupportConversationUser & {
    email: string;
    conversation_id: number | null;
};
