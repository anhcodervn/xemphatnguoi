export type AdminMailRecipientType = 'all' | 'users';

export interface AdminMailUserItem {
    id: number;
    username: string;
    full_name: string | null;
    email: string | null;
    phone: string | null;
    status: string | null;
}

export interface AdminMailUserListResponse {
    data: AdminMailUserItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export interface AdminSendMailPayload {
    recipient_type: AdminMailRecipientType;
    user_ids?: number[];
    subject: string;
    title: string;
    message: string;
    cta_text?: string | null;
    cta_url?: string | null;
}

export interface AdminSendMailResponse {
    queued: number;
    skipped: number;
}
