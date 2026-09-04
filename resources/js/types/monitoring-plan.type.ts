export type MonitoringPlan = {
    id: number;
    name: string;
    description: string | null;
    is_custom: boolean;
    vehicle_limit: number | null;
    price: string | null;
    unit_price: string | null;
    min_vehicle_count: number;
    minimum_price: string | null;
    duration_days: number;
    is_active?: boolean;
    sort_order?: number;
};

export type MonitoringSubscription = {
    id: number;
    plan_name: string;
    vehicle_limit: number;
    enabled_vehicle_count: number;
    remaining_vehicle_count: number;
    total_price: string;
    started_at: string;
    expires_at: string;
};

export type MonitoringPlanPayload = {
    name: string;
    description: string;
    is_custom: boolean;
    vehicle_limit: number | null;
    price: number | null;
    unit_price: number | null;
    min_vehicle_count: number;
    duration_days: number;
    is_active: boolean;
    sort_order: number;
};
