export type AdminFeedbackItem = {
    id: number;
    user_id: number | null;
    user: {
        id: number;
        username: string | null;
        full_name: string | null;
        email: string | null;
        phone: string | null;
    } | null;
    name: string | null;
    email: string | null;
    phone: string | null;
    subject: string;
    content: string;
    status: 'new' | 'in_progress' | 'done';
    handled_at: string | null;
    handled_by: number | null;
    handler: {
        id: number;
        username: string | null;
        full_name: string | null;
    } | null;
    created_at: string | null;
    updated_at: string | null;
};

export type AdminFeedbackListResponse = {
    data: AdminFeedbackItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total: number;
        new: number;
        in_progress: number;
        done: number;
    };
};
