import api from '@/config/axios';

export type AdminWalletTransactionListItem = {
    id: number;
    code: string;
    user: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
    } | null;
    type: string;
    amount: number;
    balance_before: number;
    balance_after: number;
    content: string | null;
    status: string;
    created_at: string | null;
};

export type AdminWalletTransactionListResponse = {
    data: AdminWalletTransactionListItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total_in: number;
        total_out: number;
        today_count: number;
        pending_or_failed_count: number;
    };
};

export type AdminWalletTransactionListParams = {
    search?: string;
    user_id?: number | string;
    type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    per_page?: number;
    page?: number;
};

export const adminWalletTransactionService = {
    async list(params: AdminWalletTransactionListParams = {}): Promise<AdminWalletTransactionListResponse> {
        const response = await api.get('/api/admin/wallet-transactions', { params });

        return response.data.data;
    },
};
