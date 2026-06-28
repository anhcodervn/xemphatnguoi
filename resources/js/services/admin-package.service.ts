import api from '@/config/axios';
import type { PackageLimitsType } from '@/types/user-subscription.type';

export type PackagePayload = {
    name: string;
    slug: string;
    description: string;
    price: number;
    duration_days: number;
    account_limit?: number | null;
    can_buy_extra_account?: boolean;
    extra_account_price?: number | null;
    request_limit?: number | null;
    request_per_minute?: number | null;
    concurrent_limit?: number | null;
    features: string[];
    package_limits: PackageLimitsType;
    status: 'active' | 'inactive';
};

export type AdminPackageItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string | number;
    duration_days: number;
    account_limit: number;
    can_buy_extra_account: boolean;
    extra_account_price: string | number;
    request_limit: number | null;
    request_per_minute: number | null;
    concurrent_limit: number;
    features: string[];
    package_limits?: PackageLimitsType;
    status: 'active' | 'inactive';
    user_subscriptions_count?: number;
};

type PackageListParams = {
    page?: number;
    per_page?: number;
    search?: string;
    status?: string;
};

type PackageListResponse = {
    packages: {
        data: AdminPackageItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    summary: {
        total: number;
        active: number;
        inactive: number;
    };
    filters: {
        search: string;
        status: string;
    };
};

const normalizePackage = (item: AdminPackageItem): AdminPackageItem => ({
    ...item,
    price: Number(item.price ?? 0),
    extra_account_price: Number(item.extra_account_price ?? 0),
    features: Array.isArray(item.features) ? item.features : [],
});

export const adminPackageService = {
    async list(params: PackageListParams = {}): Promise<PackageListResponse> {
        const response = await api.get('/api/admin-api/packages', { params });
        const data = response.data.data as PackageListResponse;

        return {
            ...data,
            packages: {
                ...data.packages,
                data: (data.packages?.data ?? []).map((item) => normalizePackage(item)),
            },
        };
    },

    async get(packageId: string | number): Promise<AdminPackageItem> {
        const response = await api.get(`/api/admin-api/packages/${packageId}`);

        return normalizePackage(response.data.data as AdminPackageItem);
    },

    async create(payload: PackagePayload) {
        return api.post('/api/admin-api/packages', payload);
    },

    async update(packageId: string | number, payload: PackagePayload) {
        return api.patch(`/api/admin-api/packages/${packageId}`, payload);
    },

    async delete(packageId: string | number) {
        return api.delete(`/api/admin-api/packages/${packageId}`);
    },
};
