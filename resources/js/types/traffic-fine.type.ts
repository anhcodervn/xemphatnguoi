export type VehicleType = 'car' | 'motorbike' | 'electric_motorbike';

export type TrafficFineViolation = {
    plate_color: string | null;
    time: string | null;
    location: string | null;
    behavior: string | null;
    status: string | null;
    resolution_status: 'processed' | 'unprocessed' | 'unknown';
    agency: string | null;
    resolution_agency: string | null;
    resolution_address: string | null;
    resolution_phone: string | null;
};

export type TrafficFineResult = {
    plate: string;
    display_plate: string;
    vehicle_type: VehicleType;
    status: 'success' | 'no_violation';
    violation_count: number;
    processed_count: number;
    unprocessed_count: number;
    unknown_status_count: number;
    violations: TrafficFineViolation[];
    checked_at: string;
};

export type TrafficFineLookupResponse = {
    success: true;
    cached: boolean;
    data: TrafficFineResult;
};

export type LookupHistory = {
    id: number;
    plate: string;
    vehicle_type: VehicleType;
    violation_count: number;
    created_at: string;
};

export type UserVehicle = {
    id: number;
    name: string;
    plate: string;
    vehicle_type: VehicleType;
    created_at: string;
    updated_at: string;
    monitoring?: {
        enabled: boolean;
        last_checked_at: string | null;
        last_violation_count: number | null;
    } | null;
};

export type ApiUsageSummary = {
    total_requests: number;
    charged_requests: number;
    failed_requests: number;
    total_amount: string;
    requests_today: number;
    amount_today: string;
    requests_month: number;
    amount_month: string;
};

export type ApiUsageDaily = {
    date: string;
    label: string;
    requests: number;
    amount: string;
};

export type ApiUsageLog = {
    id: number;
    api_key_name: string | null;
    plate: string | null;
    vehicle_type: VehicleType | null;
    method: 'GET';
    status_code: number;
    response_time_ms: number;
    unit_price: string;
    charged_amount: string;
    billing_status: 'charged' | 'not_charged' | 'insufficient_balance' | 'not_billable';
    created_at: string;
};

export type PaginatedResponse<T> = {
    current_page: number;
    data: T[];
    last_page: number;
    per_page: number;
    total: number;
};
