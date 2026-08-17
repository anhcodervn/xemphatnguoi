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

export type AdminTrafficFineReportDaily = {
    date: string;
    label: string;
    total: number;
    completed: number;
    provider_errors: number;
    cache_hits: number;
    negative_cache_hits: number;
};

export type AdminTrafficFineReportBreakdown = {
    key: string;
    label: string;
    total: number;
    percentage: number;
};

export type AdminTrafficFineReport = {
    period: {
        days: number;
        from: string;
        to: string;
    };
    summary: {
        total_lookups: number;
        unique_plates: number;
        completed_lookups: number;
        violation_lookups: number;
        no_violation_lookups: number;
        provider_errors: number;
        cache_hits: number;
        negative_cache_hits: number;
        cache_misses: number;
        cache_hit_rate: number;
        completion_rate: number;
        provider_requests: number;
        average_provider_latency_ms: number | null;
    };
    daily: AdminTrafficFineReportDaily[];
    vehicle_types: AdminTrafficFineReportBreakdown[];
    sources: AdminTrafficFineReportBreakdown[];
    recent_errors: Array<{
        id: number;
        plate: string;
        vehicle_type: string;
        provider: string | null;
        source: string;
        provider_latency_ms: number | null;
        created_at: string;
    }>;
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

export type AdminCachedPlate = {
    id: number;
    plate: string;
    vehicle_type: string;
    status: string;
    violation_count: number;
    provider: string;
    checked_at: string;
    expires_at: string;
    remaining_seconds: number;
    cache_duration_seconds: number;
    cache_state: 'active' | 'expiring' | 'expired';
    lookup_count: number;
    positive_cache_hits: number;
    provider_requests: number;
    provider_errors: number;
    cache_hit_rate: number;
    last_lookup_at: string | null;
};

export type AdminCachedPlateFilters = {
    days: 7 | 30 | 90;
    search: string;
    state: 'all' | 'active' | 'expiring' | 'expired';
    vehicle_type: '' | 'car' | 'motorbike' | 'electric_motorbike';
    status: '' | 'success' | 'no_violation';
    sort: 'lookup_count' | 'last_lookup_at' | 'expires_at' | 'checked_at' | 'plate';
    direction: 'asc' | 'desc';
    per_page: number;
    page: number;
};

export type AdminCachedPlateResponse = {
    server_time: string;
    period: { days: number; from: string; to: string };
    cache: { store: string; configured_ttl_seconds: number };
    items: AdminCachedPlate[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total_entries: number;
        active_entries: number;
        expiring_entries: number;
        expired_entries: number;
        violation_entries: number;
        period_lookups: number;
        period_positive_cache_hits: number;
        period_provider_requests: number;
        positive_cache_hit_rate: number;
    };
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
    async report(days = 30): Promise<AdminTrafficFineReport> {
        const response = await api.get('/api/admin-api/traffic-fines/report', { params: { days } });
        return response.data.data as AdminTrafficFineReport;
    },
    async results(params: Partial<AdminCachedPlateFilters> = {}): Promise<AdminCachedPlateResponse> {
        const response = await api.get('/api/admin-api/traffic-fines/results', { params });
        return response.data.data as AdminCachedPlateResponse;
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
