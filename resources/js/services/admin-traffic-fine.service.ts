import api from '@/config/axios';

export type AdminTrafficFineMetrics = {
    lookup_today: number;
    lookup_month: number;
    cache_hits: number;
    cache_misses: number;
    provider_requests: number;
    provider_errors: number;
    average_provider_latency_ms: number | null;
    users: number;
    api_request_price: number;
    api_requests_today: number;
    api_requests_month: number;
    api_revenue_today: string;
    api_revenue_month: string;
    api_revenue_total: string;
    api_chart: AdminApiUsageDaily[];
};

export type AdminApiUsageDaily = {
    date: string;
    label: string;
    requests: number;
    amount: string;
};

export type AdminApiBilling = {
    api_request_price: number;
    summary: {
        total_requests: number;
        charged_requests: number;
        failed_requests: number;
        total_amount: string;
        requests_today: number;
        amount_today: string;
        requests_month: number;
        amount_month: string;
    };
    chart: AdminApiUsageDaily[];
};

export type AdminTrafficFineResult = {
    id: number;
    plate: string;
    vehicle_type: string;
    status: string;
    violation_count: number;
    provider: string;
    checked_at: string;
    expires_at: string;
};

export type AdminLookupLog = {
    id: number;
    user: { username: string; email: string | null } | null;
    plate: string;
    vehicle_type: string;
    source: string;
    cache_hit: boolean;
    provider: string | null;
    provider_latency_ms: number | null;
    status: string;
    ip: string | null;
    created_at: string;
};

export type AdminProviderStatus = {
    name: string;
    enabled: boolean;
    priority: number;
    timeout: number;
    status: string;
    url_configured: boolean;
    credential_configured: boolean;
    last_error: string | null;
};

export type AdminAdSlot = {
    id: number;
    name: string;
    code: string | null;
    enabled: boolean;
    device: 'all' | 'desktop' | 'mobile';
    start_at: string | null;
    end_at: string | null;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; total: number };

export const adminTrafficFineService = {
    async overview(): Promise<AdminTrafficFineMetrics> {
        const response = await api.get('/api/admin-api/traffic-fines/overview');
        return response.data.data.metrics as AdminTrafficFineMetrics;
    },
    async results(search = ''): Promise<Paginated<AdminTrafficFineResult>> {
        const response = await api.get('/api/admin-api/traffic-fines/results', { params: { search, per_page: 50 } });
        return response.data.data as Paginated<AdminTrafficFineResult>;
    },
    async logs(search = ''): Promise<Paginated<AdminLookupLog>> {
        const response = await api.get('/api/admin-api/traffic-fines/logs', { params: { search, per_page: 50 } });
        return response.data.data as Paginated<AdminLookupLog>;
    },
    async provider(): Promise<AdminProviderStatus> {
        const response = await api.get('/api/admin-api/traffic-fines/provider');
        return response.data.data as AdminProviderStatus;
    },
    async billing(): Promise<AdminApiBilling> {
        const response = await api.get('/api/admin-api/traffic-fines/billing');
        return response.data.data as AdminApiBilling;
    },
    async updateBilling(apiRequestPrice: number): Promise<AdminApiBilling> {
        const response = await api.put('/api/admin-api/traffic-fines/billing', { api_request_price: apiRequestPrice });
        return response.data.data as AdminApiBilling;
    },
    async adSlots(): Promise<AdminAdSlot[]> {
        const response = await api.get('/api/admin-api/traffic-fines/ad-slots');
        return response.data.data.slots as AdminAdSlot[];
    },
    async saveAdSlot(payload: Omit<AdminAdSlot, 'id'>, id?: number): Promise<AdminAdSlot> {
        const response = id
            ? await api.patch(`/api/admin-api/traffic-fines/ad-slots/${id}`, payload)
            : await api.post('/api/admin-api/traffic-fines/ad-slots', payload);
        return response.data.data as AdminAdSlot;
    },
    async deleteAdSlot(id: number): Promise<void> {
        await api.delete(`/api/admin-api/traffic-fines/ad-slots/${id}`);
    },
};
