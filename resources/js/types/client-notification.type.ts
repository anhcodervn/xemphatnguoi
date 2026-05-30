export type ClientNotificationItem = {
    id: number;
    scope: 'system' | 'user';
    title: string;
    content: string;
    redirect_url: string | null;
    type: string | null;
    is_read: boolean;
    created_at: string | null;
};

export type ClientNotificationListResponse = {
    data: ClientNotificationItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total: number;
        unread: number;
    };
};
