import api from '@/config/axios';
import type { MonitoringPlan, MonitoringSubscription } from '@/types/monitoring-plan.type';

export type MonitoringPlansDashboard = {
    plans: MonitoringPlan[];
    subscription: MonitoringSubscription | null;
};

export const clientMonitoringPlanService = {
    async index(): Promise<MonitoringPlansDashboard> {
        const response = await api.get('/api/client/monitoring-plans');
        return response.data.data as MonitoringPlansDashboard;
    },

    async subscribe(planId: number, vehicleCount?: number): Promise<MonitoringSubscription> {
        const response = await api.post('/api/client/monitoring-plans/subscribe', {
            plan_id: planId,
            ...(vehicleCount === undefined ? {} : { vehicle_count: vehicleCount }),
        });
        return response.data.data.subscription as MonitoringSubscription;
    },

    async upgrade(planId: number, vehicleCount?: number): Promise<MonitoringSubscription> {
        const response = await api.post('/api/client/monitoring-plans/upgrade', {
            plan_id: planId,
            ...(vehicleCount === undefined ? {} : { vehicle_count: vehicleCount }),
        });
        return response.data.data.subscription as MonitoringSubscription;
    },

    async updateAutoRenew(autoRenew: boolean): Promise<MonitoringSubscription | null> {
        const response = await api.patch('/api/client/monitoring-plans/subscription/auto-renew', {
            auto_renew: autoRenew,
        });
        return response.data.data.subscription as MonitoringSubscription | null;
    },
};
