import api from "@/config/axios";

export const adminDiscountService = {
    async listFlashSales(params: any = {}) {
        const res = await api.get("/api/admin-api/flash-sales", { params });
        return res.data.data;
    },
    async createFlashSale(payload: any) {
        const res = await api.post("/api/admin-api/flash-sales", payload);
        return res.data.data;
    },
    async getFlashSale(id: number | string) {
        const res = await api.get(`/api/admin-api/flash-sales/${id}`);
        return res.data.data;
    },
    async updateFlashSale(id: number | string, payload: any) {
        const res = await api.patch(`/api/admin-api/flash-sales/${id}`, payload);
        return res.data.data;
    },
    async deleteFlashSale(id: number | string) {
        const res = await api.delete(`/api/admin-api/flash-sales/${id}`);
        return res.data.data;
    },
    async getFlashSaleProducts(id: number | string) {
        const res = await api.get(`/api/admin-api/flash-sales/${id}/products`);
        return res.data.data;
    },
    async syncFlashSaleProducts(id: number | string, items: any[]) {
        const res = await api.patch(`/api/admin-api/flash-sales/${id}/products`, { items });
        return res.data.data;
    },

    async listCoupons(params: any = {}) {
        const res = await api.get("/api/admin-api/coupons", { params });
        return res.data.data;
    },
    async createCoupon(payload: any) {
        const res = await api.post("/api/admin-api/coupons", payload);
        return res.data.data;
    },
    async getCoupon(id: number | string) {
        const res = await api.get(`/api/admin-api/coupons/${id}`);
        return res.data.data;
    },
    async updateCoupon(id: number | string, payload: any) {
        const res = await api.patch(`/api/admin-api/coupons/${id}`, payload);
        return res.data.data;
    },
    async deleteCoupon(id: number | string) {
        const res = await api.delete(`/api/admin-api/coupons/${id}`);
        return res.data.data;
    },
    async getCouponProducts(id: number | string) {
        const res = await api.get(`/api/admin-api/coupons/${id}/products`);
        return res.data.data;
    },
    async syncCouponProducts(id: number | string, product_ids: number[]) {
        const res = await api.patch(`/api/admin-api/coupons/${id}/products`, { product_ids });
        return res.data.data;
    },
};
