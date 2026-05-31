import api from '@/config/axios';

export type AdminPackageOrderItem = {
    id: number;
    code: string;
    user: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
    } | null;
    package: {
        id: number;
        name: string;
    } | null;
    price: number;
    duration_days: number | null;
    started_at: string | null;
    expired_at: string | null;
    payment_status: string | null;
    status: string | null;
    is_renewal: boolean;
    created_at: string | null;
};

export type AdminPackageOrderListResponse = {
    data: AdminPackageOrderItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total_orders: number;
        revenue: number;
        today_orders: number;
        active_packages: number;
        expiring_soon: number;
        expired_packages: number;
        renewal_rate: number;
        monthly_revenue: number;
    };
};

export type AdminPackageOrderListParams = {
    search?: string;
    user_id?: number | string;
    package_id?: number | string;
    status?: string;
    date_from?: string;
    date_to?: string;
    per_page?: number;
    page?: number;
};

export const adminPackageOrderService = {
    async list(params: AdminPackageOrderListParams = {}): Promise<AdminPackageOrderListResponse> {
        const response = await api.get('/api/admin-api/package-orders', { params });

        return response.data.data;
    },
};
