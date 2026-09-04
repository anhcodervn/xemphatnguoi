import api from '@/config/axios';
import type { MonitoringPlan, MonitoringPlanPayload } from '@/types/monitoring-plan.type';

export const adminMonitoringPlanService = {
    async index(): Promise<MonitoringPlan[]> {
        const response = await api.get('/api/admin-api/monitoring/plans');
        return response.data.data.plans as MonitoringPlan[];
    },

    async create(payload: MonitoringPlanPayload): Promise<MonitoringPlan> {
        const response = await api.post('/api/admin-api/monitoring/plans', payload);
        return response.data.data.plan as MonitoringPlan;
    },

    async update(id: number, payload: MonitoringPlanPayload): Promise<MonitoringPlan> {
        const response = await api.patch(`/api/admin-api/monitoring/plans/${id}`, payload);
        return response.data.data.plan as MonitoringPlan;
    },

    async remove(id: number): Promise<void> {
        await api.delete(`/api/admin-api/monitoring/plans/${id}`);
    },
};
