export type CouponType = 'fixed' | 'percent';
export type CouponAvailability = 'all' | 'active' | 'scheduled' | 'expired' | 'inactive';
export type CouponLogStatus = 'success' | 'failed' | 'info';

export interface CouponRequirementType {
    note?: string | null;
}

export interface CouponTypeModel {
    id: number;
    code: string;
    name: string;
    description: string | null;
    type: CouponType;
    value: string | number;
    min_order_amount: string | number;
    max_discount_amount: string | number | null;
    max_usage: number | null;
    max_usage_per_user: number | null;
    used_count: number;
    starts_at: string | null;
    expired_at: string | null;
    first_order_only: boolean;
    is_active: boolean;
    is_available: boolean;
    requirements: CouponRequirementType;
    logs_count?: number;
    usage_percent?: number | null;
    created_at: string | null;
    recent_logs?: CouponLogModel[];
}

export interface CouponLogModel {
    id: number;
    action: string;
    status: CouponLogStatus;
    coupon?: {
        id: number;
        code: string;
        name: string;
    } | null;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
    admin?: {
        id: number;
        name: string;
        email: string;
    } | null;
    order_amount: string | number | null;
    discount_amount: string | number | null;
    note: string | null;
    payload: Record<string, unknown> | null;
    created_at: string | null;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface CouponListResponse {
    coupons: {
        data: CouponTypeModel[];
        meta: PaginationMeta;
    };
    summary: {
        total: number;
        active: number;
        scheduled: number;
        expired: number;
        total_used: number;
    };
}

export interface CouponLogListResponse {
    logs: {
        data: CouponLogModel[];
        meta: PaginationMeta;
    };
}

export interface CouponPayload {
    code: string;
    name: string;
    description: string | null;
    type: CouponType;
    value: number;
    min_order_amount: number;
    max_discount_amount: number | null;
    max_usage: number | null;
    max_usage_per_user: number | null;
    starts_at: string | null;
    expired_at: string | null;
    first_order_only: boolean;
    is_active: boolean;
    requirements: CouponRequirementType;
}
