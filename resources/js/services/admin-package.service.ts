import api from "@/config/axios";

export type AdminPackageLimits = {
    max_api_keys: number;
    requests_per_minute: number;
    monthly_captcha_quota: number | null;
    max_concurrent_tasks: number;
    max_whitelisted_ips: number;
    supports_callback: boolean;
    supports_priority_queue: boolean;
    supports_manual_review: boolean;
    service_whitelist: string[];
};

export type AdminPackageItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    duration_days: number;
    features: string[] | null;
    package_limits: AdminPackageLimits;
    status: "active" | "inactive";
    user_subscriptions_count?: number;
};

export type AdminPackageListResponse = {
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

export type AdminPackagePayload = {
    name: string;
    slug: string;
    description: string;
    price: number;
    duration_days: number;
    features: string[];
    package_limits: AdminPackageLimits;
    status: "active" | "inactive";
};

export const adminPackageService = {
    async list(params: Record<string, unknown> = {}): Promise<AdminPackageListResponse> {
        const response = await api.get("/api/admin-api/packages", { params });
        return response.data.data as AdminPackageListResponse;
    },

    async show(id: number | string): Promise<AdminPackageItem> {
        const response = await api.get(`/api/admin-api/packages/${id}`);
        return response.data.data as AdminPackageItem;
    },

    async create(payload: AdminPackagePayload): Promise<AdminPackageItem> {
        const response = await api.post("/api/admin-api/packages", payload);
        return response.data.data as AdminPackageItem;
    },

    async update(id: number | string, payload: AdminPackagePayload): Promise<AdminPackageItem> {
        const response = await api.patch(`/api/admin-api/packages/${id}`, payload);
        return response.data.data as AdminPackageItem;
    },

    async delete(id: number | string): Promise<void> {
        await api.delete(`/api/admin-api/packages/${id}`);
    },
};
