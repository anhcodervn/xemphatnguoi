import api from '@/config/axios';
import type { CouponListResponse, CouponLogListResponse, CouponPayload, CouponTypeModel } from '@/types/coupon.type';

export const adminCouponService = {
    async list(params: Record<string, unknown> = {}): Promise<CouponListResponse> {
        const response = await api.get('/api/admin-api/coupons', { params });

        return response.data.data as CouponListResponse;
    },

    async get(id: number | string): Promise<CouponTypeModel> {
        const response = await api.get(`/api/admin-api/coupons/${id}`);

        return response.data.data as CouponTypeModel;
    },

    async create(payload: CouponPayload): Promise<CouponTypeModel> {
        const response = await api.post('/api/admin-api/coupons', payload);

        return response.data.data as CouponTypeModel;
    },

    async update(id: number | string, payload: CouponPayload): Promise<CouponTypeModel> {
        const response = await api.patch(`/api/admin-api/coupons/${id}`, payload);

        return response.data.data as CouponTypeModel;
    },

    async remove(id: number | string): Promise<void> {
        await api.delete(`/api/admin-api/coupons/${id}`);
    },

    async logs(params: Record<string, unknown> = {}): Promise<CouponLogListResponse> {
        const response = await api.get('/api/admin-api/coupons/logs', { params });

        return response.data.data as CouponLogListResponse;
    },
};
