import api from '@/config/axios';
import type {
    ApiUsageDaily,
    ApiUsageLog,
    ApiUsageSummary,
    LookupHistory,
    PaginatedResponse,
    UserVehicle,
    VehicleType,
} from '@/types/traffic-fine.type';
import type { WalletType } from '@/types/wallet.type';

export type TrafficFineDashboard = {
    wallet: WalletType;
    api_request_price: number;
    api_usage: ApiUsageSummary;
    api_chart: ApiUsageDaily[];
    lookup_count: number;
    monitoring_count: number;
    vehicle_count: number;
    recent_lookups: LookupHistory[];
};

export type ApiUsageDashboard = {
    api_request_price: number;
    summary: ApiUsageSummary;
    chart: ApiUsageDaily[];
    logs: PaginatedResponse<ApiUsageLog>;
};

export const trafficFineService = {
    async dashboard(): Promise<TrafficFineDashboard> {
        const response = await api.get('/api/client/traffic-fines/dashboard');

        return response.data.data as TrafficFineDashboard;
    },

    async histories(page = 1): Promise<PaginatedResponse<LookupHistory>> {
        const response = await api.get('/api/client/traffic-fines/histories', { params: { page } });

        return response.data.data as PaginatedResponse<LookupHistory>;
    },

    async vehicles(): Promise<UserVehicle[]> {
        const response = await api.get('/api/client/traffic-fines/vehicles');

        return response.data.data.vehicles as UserVehicle[];
    },

    async createVehicle(payload: { name: string; plate: string; vehicle_type: VehicleType }): Promise<UserVehicle> {
        const response = await api.post('/api/client/traffic-fines/vehicles', payload);

        return response.data.data as UserVehicle;
    },

    async updateVehicle(id: number, payload: { name: string; plate: string; vehicle_type: VehicleType }): Promise<UserVehicle> {
        const response = await api.patch(`/api/client/traffic-fines/vehicles/${id}`, payload);

        return response.data.data as UserVehicle;
    },

    async deleteVehicle(id: number): Promise<void> {
        await api.delete(`/api/client/traffic-fines/vehicles/${id}`);
    },

    async apiUsage(page = 1): Promise<ApiUsageDashboard> {
        const response = await api.get('/api/client/traffic-fines/api-usage', { params: { page } });

        return response.data.data as ApiUsageDashboard;
    },
};
