import api from "@/config/axios";

export type PackagePayload = {
    name: string;
    slug: string;
    description: string;
    price: number;
    duration_days: number;
    account_limit: number;
    can_buy_extra_account: boolean;
    extra_account_price: number;
    request_limit: number;
    request_per_minute: number;
    concurrent_limit: number;
    features: string[];
    status: "active" | "inactive";
};

export const adminPackageService = {
    async list(params: Record<string, unknown> = {}) {
        const res = await api.get("/admin-api/packages", { params });
        return res.data.data;
    },
    async get(id: number | string) {
        const res = await api.get(`/admin-api/packages/${id}`);
        return res.data.data;
    },
    async create(payload: PackagePayload) {
        return await api.post("/admin-api/packages", payload);
    },
    async update(id: number | string, payload: PackagePayload) {
        return await api.patch(`/admin-api/packages/${id}`, payload);
    },
    async delete(id: number | string) {
        return await api.delete(`/admin-api/packages/${id}`);
    },
};
