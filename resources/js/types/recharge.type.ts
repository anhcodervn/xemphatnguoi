import type { WalletType } from '@/types/wallet.type';

export type RechargeStatus = 'pending' | 'processing' | 'paid' | 'failed' | 'cancelled' | 'expired';

export type RechargeMethodBadgeType = 'auto' | 'manual' | null;

export interface RechargeMethodType {
    source: 'database' | 'config';
    key: string;
    active: boolean;
    label: string;
    description: string | null;
    badge_label: string | null;
    badge_type: RechargeMethodBadgeType;
    bank_name: string | null;
    account_number: string | null;
    account_name: string | null;
    minimum_amount: number;
    maximum_amount: number;
    bonus_percentage: number;
    recharge_method_id: number | null;
    bank_account_id: number | null;
    metadata: Record<string, unknown>;
}

export interface RechargeOrderType {
    id: number;
    order_code: string;
    method: string;
    method_label: string;
    amount: string;
    bonus_amount: string;
    total_amount: string;
    bank_name: string | null;
    account_number: string | null;
    account_name: string | null;
    transfer_content: string | null;
    status: RechargeStatus;
    requested_at: string | null;
    paid_at: string | null;
    expires_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    metadata: Record<string, unknown> | null;
}

export interface RechargeHistoryMetaType {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface RechargeStatsType {
    total_recharge: string;
    total_bonus: string;
    total_orders: number;
}

export interface RechargeOverviewHistoryType {
    data: RechargeOrderType[];
    meta: RechargeHistoryMetaType;
}

export interface RechargeOverviewType {
    wallet: WalletType;
    bonus_percentage: number;
    minimum_amount: number;
    maximum_amount: number;
    recharge_syntax: string;
    transfer_content_preview: string;
    methods: RechargeMethodType[];
    stats: RechargeStatsType;
    history: RechargeOverviewHistoryType;
}

export interface RechargeOverviewFilters {
    search?: string;
    status?: 'all' | RechargeStatus;
    per_page?: number;
    page?: number;
}

export interface CreateRechargeOrderPayload {
    method: string;
    amount: number;
}
