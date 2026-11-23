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
    is_active: boolean;
    auto_renew: boolean;
    renewal_count: number;
    started_at: string;
    expires_at: string;
    last_renewed_at: string | null;
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

export type AdminMonitoringSubscription = {
    id: number;
    user: {
        id: number;
        username: string;
        email: string | null;
        full_name: string | null;
    } | null;
    monitoring_plan_id: number | null;
    plan_name: string;
    vehicle_limit: number;
    unit_price: string;
    total_price: string;
    duration_days: number;
    status: 'active' | 'renewed' | 'upgraded';
    is_active: boolean;
    auto_renew: boolean;
    renewal_count: number;
    started_at: string;
    expires_at: string;
    last_renewed_at: string | null;
};

export type MonitoringSubscriptionList = {
    subscriptions: AdminMonitoringSubscription[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
};

export type AdminMonitoringVehicle = {
    id: number;
    vehicle: { id: number; name: string | null; plate: string; vehicle_type: string };
    user: NonNullable<AdminMonitoringSubscription['user']>;
    subscription: { id: number; plan_name: string; expires_at: string } | null;
    enabled: boolean;
    email_notifications: boolean;
    last_checked_at: string | null;
    last_violation_count: number | null;
};

export type MonitoringVehicleList = {
    vehicles: AdminMonitoringVehicle[];
    meta: MonitoringSubscriptionList['meta'];
};
