import api from '@/config/axios';

export const clientPackageService = {
    async list() {
        const res = await api.get('/api/package');
        return res.data.data;
    },
    async quote(payload: { package_id: number; coupon_code?: string | null }) {
        const res = await api.post('/api/package/quote', payload);
        return res.data.data;
    },
    async createOrder(payload: { package_id: number; payment_method?: string | null; coupon_code?: string | null; auto_renew_enabled?: boolean }) {
        const res = await api.post('/api/package/orders', payload);
        return res;
    },
    async payOrder(packageOrderId: number, payload: { payment_method?: string | null } = {}) {
        const res = await api.post(`/api/package/orders/${packageOrderId}/pay`, payload);
        return res;
    },
    async updateSubscriptionAutoRenew(subscriptionId: number, payload: { auto_renew_enabled: boolean }) {
        const res = await api.patch(`/api/package/subscriptions/${subscriptionId}/auto-renew`, payload);
        return res;
    },
    async createExtraAccountOrder(payload: { user_subscription_id: number; quantity: number }) {
        const res = await api.post('/api/client/subscriptions/extra-account-orders', payload);
        return res;
    },
    async payExtraAccountOrder(extraAccountOrderId: number) {
        const res = await api.post(`/api/client/subscriptions/extra-account-orders/${extraAccountOrderId}/pay`);
        return res;
    },
};
