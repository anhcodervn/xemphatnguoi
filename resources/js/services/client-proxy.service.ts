import api from '@/config/axios';
import type { ClientNotificationItem } from '@/types/client-notification.type';

export type ProxyProtocol = 'http' | 'https' | 'socks4' | 'socks5';

export interface ProxyProduct {
    id: number;
    code: string;
    name: string;
    country_code: string | null;
    protocol: ProxyProtocol;
    supported_protocols: ProxyProtocol[];
    description: string | null;
    selling_price: string;
    max_quantity: number;
    proxy_category_id: number;
    category?: ProxyCategory | null;
}

export interface ProxyCategory {
    id: number;
    code: string;
    name: string;
    description: string | null;
    icon: string | null;
    products: ProxyProduct[];
}

export type ProxyOrderStatus = 'pending' | 'processing' | 'fulfilled' | 'failed' | 'refunded';
export type ProxyOrderType = 'purchase' | 'change' | 'renew';
export type ManagedProxyType = 'static' | 'rotating';
export type ManagedProxyStatus = 'pending' | 'active' | 'changing' | 'expired' | 'disabled' | 'error';

export interface ProxyOrder {
    id: number;
    order_code: string;
    type: ProxyOrderType;
    status: ProxyOrderStatus;
    product: { id: number; code: string; name: string };
    target_proxy_id: number | null;
    quantity: number;
    duration_days: number;
    country_code: string | null;
    protocol: ProxyProtocol;
    unit_price: string;
    total_amount: string;
    error_code: string | null;
    error_message: string | null;
    ordered_at: string | null;
    fulfilled_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface ManagedProxy {
    id: number;
    label: string | null;
    status: ManagedProxyStatus;
    product: { id: number; code: string; name: string } | null;
    source_order_code: string | null;
    country_code: string | null;
    protocol: ProxyProtocol;
    proxy_type: ManagedProxyType;
    access_key: string | null;
    connection: {
        host: string;
        port: number | null;
        username: string | null;
        password: string | null;
    } | null;
    error_message: string | null;
    expires_at: string | null;
    last_changed_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface ProxyOperationResponse {
    order: ProxyOrder;
}

export interface FetchedRotatingProxy {
    proxy_id: number;
    proxy: string;
    protocol: 'http' | 'socks5';
    message: string;
}

export interface ProxyCheckResult {
    id: number;
    position: number;
    endpoint: string;
    status: 'pending' | 'processing' | 'live' | 'die';
    exit_ip: string | null;
    latency_ms: number | null;
    message: string | null;
    started_at: string | null;
    completed_at: string | null;
}

export interface ProxyCheckBatch {
    id: string;
    status: 'pending' | 'processing' | 'completed';
    total: number;
    processed: number;
    live: number;
    die: number;
    progress: number;
    created_at: string | null;
    completed_at: string | null;
    results: ProxyCheckResult[];
}

export interface ProxyDashboard {
    summary: {
        balance: string;
        active_proxies: number;
        expiring_proxies: number;
        unread_notifications: number;
    };
    expiring_proxies: Array<{
        id: number;
        label: string | null;
        endpoint: string;
        status: ManagedProxyStatus;
        product: { id: number; code: string; name: string } | null;
        country_code: string | null;
        protocol: ProxyProtocol;
        expires_at: string | null;
    }>;
    notifications: ClientNotificationItem[];
    recent_activities: Array<{
        id: string;
        type: 'order' | 'wallet_credit' | 'wallet';
        title: string;
        description: string;
        amount: string;
        status: string;
        occurred_at: string | null;
        redirect_url: string;
    }>;
}

export interface CreatedProxyOrder {
    id: number;
    order_code: string;
    status: ProxyOrderStatus;
    product_code: string;
    product_name: string;
    quantity: number;
    duration_days: number;
    country_code: string | null;
    protocol: ProxyProtocol;
    unit_price: string;
    total_amount: string;
    external_order_id: string | null;
    ordered_at: string | null;
    fulfilled_at: string | null;
}

export interface CreatedProxy {
    id: number;
    status: ManagedProxyStatus;
    country_code: string | null;
    protocol: ProxyProtocol;
    host: string | null;
    port: number | null;
    username: string | null;
    password: string | null;
    access_key: string | null;
    expires_at: string | null;
}

export interface CreateProxyOrderResponse {
    order: CreatedProxyOrder;
    proxies: CreatedProxy[];
}

const responseData = <T>(payload: unknown, requiredKeys: string[]): T => {
    if (typeof payload !== 'object' || payload === null || !('data' in payload)) {
        throw new Error('Phản hồi từ API proxy không đúng cấu trúc.');
    }

    const data = payload.data;
    if (typeof data !== 'object' || data === null || requiredKeys.some((key) => !(key in data))) {
        throw new Error('Phản hồi từ API proxy đang thiếu dữ liệu cần thiết.');
    }

    return data as T;
};

export const clientProxyService = {
    async checkProxies(proxies: string[]) {
        const response = await api.post('/api/client/proxy/check', { proxies });
        return responseData<{ batch: ProxyCheckBatch }>(response.data, ['batch']);
    },

    async proxyCheckStatus(batchId: string) {
        const response = await api.get(`/api/client/proxy/check/${batchId}`);
        return responseData<{ batch: ProxyCheckBatch }>(response.data, ['batch']);
    },

    async dashboard() {
        const response = await api.get('/api/client/proxy/dashboard');
        return responseData<ProxyDashboard>(response.data, ['summary', 'expiring_proxies', 'notifications', 'recent_activities']);
    },

    async products() {
        const response = await api.get('/api/client/proxy/products');
        return responseData<{ categories: ProxyCategory[]; products: ProxyProduct[] }>(response.data, ['categories', 'products']);
    },

    async orders(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/client/proxy/orders', { params });
        return responseData<{ orders: ProxyOrder[]; meta: PaginationMeta }>(response.data, ['orders', 'meta']);
    },

    async order(id: number) {
        const response = await api.get(`/api/client/proxy/orders/${id}`);
        return responseData<{ order: ProxyOrder }>(response.data, ['order']);
    },

    async proxies(params: Record<string, unknown> = {}) {
        const response = await api.get('/api/client/proxy/proxies', { params });
        return responseData<{ proxies: ManagedProxy[]; meta: PaginationMeta }>(response.data, ['proxies', 'meta']);
    },

    async proxy(id: number) {
        const response = await api.get(`/api/client/proxy/proxies/${id}`);
        return responseData<{ proxy: ManagedProxy }>(response.data, ['proxy']);
    },

    async createOrder(payload: { product_code: string; quantity: number; duration_days: number; protocol: ProxyProtocol }) {
        const response = await api.post('/api/client/proxy/orders', payload);
        return responseData<CreateProxyOrderResponse>(response.data, ['order', 'proxies']);
    },

    async changeProxy(id: number) {
        const response = await api.post(`/api/client/proxy/proxies/${id}/change-proxy`);
        return responseData<ProxyOperationResponse>(response.data, ['order']);
    },

    async fetchRotatingProxy(id: number) {
        const response = await api.post(`/api/client/proxy/proxies/${id}/fetch-rotating`);
        return responseData<FetchedRotatingProxy>(response.data, ['proxy_id', 'proxy', 'protocol', 'message']);
    },

    async renewProxy(id: number, durationDays: number) {
        const response = await api.post(`/api/client/proxy/proxies/${id}/renew`, {
            duration_days: durationDays,
        });
        return responseData<ProxyOperationResponse>(response.data, ['order']);
    },
};
