import api from '@/config/axios';
import type { CreateRechargeOrderPayload, RechargeOrderType, RechargeOverviewFilters, RechargeOverviewType } from '@/types/recharge.type';

export const clientRechargeService = {
    async overview(filters: RechargeOverviewFilters = {}): Promise<RechargeOverviewType> {
        const response = await api.get('/api/recharge', {
            params: filters,
        });

        return response.data.data as RechargeOverviewType;
    },

    async createOrder(payload: CreateRechargeOrderPayload): Promise<RechargeOrderType> {
        const response = await api.post('/api/recharge/orders', payload);

        return response.data.data as RechargeOrderType;
    },

    async getOrder(id: string | number): Promise<RechargeOrderType> {
        const response = await api.get(`/api/recharge/orders/${id}`);

        return response.data.data as RechargeOrderType;
    },
};
