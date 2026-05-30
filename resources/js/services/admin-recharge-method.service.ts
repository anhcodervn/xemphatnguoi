import api from '@/config/axios';

export type RechargeMethodBankAccount = {
    id: number;
    bank_name: string;
    account_name: string;
    account_number: string;
    status: 'active' | 'inactive' | 'error';
};

export type RechargeMethodPayload = {
    code: string;
    name: string;
    description: string | null;
    badge_label: string | null;
    badge_type: 'auto' | 'manual';
    bank_name: string | null;
    account_number: string | null;
    account_name: string | null;
    min_amount: number;
    max_amount: number;
    bonus_percentage: number;
    sort_order: number;
    is_active: boolean;
    bank_account_ids: number[];
    metadata: Record<string, unknown>;
};

export type RechargeMethodItem = RechargeMethodPayload & {
    id: number;
    bankAccounts: RechargeMethodBankAccount[];
    bank_accounts?: RechargeMethodBankAccount[];
    created_at: string | null;
    updated_at: string | null;
};

type RechargeMethodListResponse = {
    methods: {
        data: RechargeMethodItem[];
        current_page: number;
        last_page: number;
    };
    summary: {
        total: number;
        active: number;
        inactive: number;
    };
};

const normalizeRechargeMethod = (item: RechargeMethodItem): RechargeMethodItem => {
    return {
        ...item,
        bankAccounts: item.bankAccounts ?? item.bank_accounts ?? [],
    };
};

export const adminRechargeMethodService = {
    async list(params: Record<string, unknown> = {}): Promise<RechargeMethodListResponse> {
        const res = await api.get('/admin-api/recharge-methods', { params });
        const payload = res.data.data as RechargeMethodListResponse;

        return {
            ...payload,
            methods: {
                ...payload.methods,
                data: payload.methods.data.map((item) => normalizeRechargeMethod(item)),
            },
        };
    },
    async get(id: number | string): Promise<RechargeMethodItem> {
        const res = await api.get(`/admin-api/recharge-methods/${id}`);

        return normalizeRechargeMethod(res.data.data as RechargeMethodItem);
    },
    async create(payload: RechargeMethodPayload) {
        return api.post('/admin-api/recharge-methods', payload);
    },
    async update(id: number | string, payload: RechargeMethodPayload) {
        return api.patch(`/admin-api/recharge-methods/${id}`, payload);
    },
    async delete(id: number | string) {
        return api.delete(`/admin-api/recharge-methods/${id}`);
    },
};
