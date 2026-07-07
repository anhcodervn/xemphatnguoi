import api from "@/config/axios";
import type { ClientApiKeyType } from "@/types/api-key.type";
import type { CurrentUserSubscriptionType, PackageLimitsType } from "@/types/user-subscription.type";

export type ClientPackageItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    duration_days: number;
    features: string[] | null;
    package_limits: PackageLimitsType;
    status: string;
};

export type ClientPackageOrder = {
    id: number;
    order_code: string;
    package_id: number;
    final_amount: string;
    payment_status: string;
    status: string;
    expires_at: string | null;
    package?: {
        id: number;
        name: string;
        slug: string;
    } | null;
};

export type ClientPackageQuote = {
    quote_type: string;
    price: number;
    discount_amount: number;
    credit_amount: number;
    final_amount: number;
    expires_at: string;
    package: {
        id: number;
        name: string;
        slug: string;
        duration_days: number;
    };
    source_subscription: {
        id: number;
        package_id: number;
        package_name: string;
        package_price: string;
        starts_at: string | null;
        expires_at: string | null;
    } | null;
};

export type ClientPackageIndexResponse = {
    packages: ClientPackageItem[];
    current_subscription: CurrentUserSubscriptionType | null;
    active_subscriptions: CurrentUserSubscriptionType[];
    active_subscription_package_ids: number[];
    summary: {
        active_subscription_count: number;
        latest_order_count: number;
        wallet_balance: string;
    };
    latest_orders: ClientPackageOrder[];
};

export const clientPackageService = {
    async index(): Promise<ClientPackageIndexResponse> {
        const response = await api.get("/api/package");
        return response.data.data as ClientPackageIndexResponse;
    },

    async quote(payload: { package_id: number; coupon_code?: string }): Promise<ClientPackageQuote> {
        const response = await api.post("/api/package/quote", payload);
        return response.data.data as ClientPackageQuote;
    },

    async createOrder(payload: { package_id: number; coupon_code?: string; payment_method?: string; auto_renew_enabled?: boolean }) {
        const response = await api.post("/api/package/orders", payload);
        return response.data.data as ClientPackageOrder;
    },

    async payOrder(orderId: number | string, payload: { payment_method?: "wallet" } = {}) {
        const response = await api.post(`/api/package/orders/${orderId}/pay`, payload);
        return response.data.data as {
            order: ClientPackageOrder;
            subscription: CurrentUserSubscriptionType | null;
            active_subscriptions: CurrentUserSubscriptionType[];
            package_api_key: {
                api_key: ClientApiKeyType;
                api_secret: string | null;
                is_new: boolean;
            } | null;
            wallet: {
                balance: string;
                hold_balance: string;
                total_recharge: string;
                total_spent: string;
            };
        };
    },

    async updateAutoRenew(subscriptionId: number | string, autoRenewEnabled: boolean) {
        const response = await api.patch(`/api/package/subscriptions/${subscriptionId}/auto-renew`, {
            auto_renew_enabled: autoRenewEnabled,
        });

        return response.data.data as {
            subscription: CurrentUserSubscriptionType | null;
            active_subscriptions: CurrentUserSubscriptionType[];
        };
    },
};
