export type AdminNotificationItem = {
    id: number;
    scope: 'system' | 'user';
    user_id: number | null;
    user?: {
        id: number;
        name: string | null;
        email: string | null;
        phone: string | null;
    } | null;
    title: string;
    content: string;
    redirect_url: string | null;
    type: string | null;
    reads_count: number;
    created_at: string | null;
    updated_at: string | null;
};

export type AdminNotificationListResponse = {
    data: AdminNotificationItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total: number;
        system: number;
        user: number;
        today: number;
    };
};

export type AdminNotificationPayload = {
    scope: 'system' | 'user';
    user_id?: number | null;
    title: string;
    content: string;
    redirect_url?: string | null;
    type?: string | null;
};
