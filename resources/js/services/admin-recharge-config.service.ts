import api from '@/config/axios';
import type { ApiBankVnBankAccountOption, RechargeConfigType } from '@/types/recharge-config.type';

type RechargeConfigPayload = {
    provider: 'manual' | 'apibankvn_api';
    bank_name: string;
    account_name: string;
    account_number: string;
    qr_template: string;
    transfer_prefix: string;
    api_base_url: string | null;
    api_key: string | null;
    api_secret: string | null;
    api_bank_id: number | null;
    is_active: boolean;
};

export const adminRechargeConfigService = {
    async get(): Promise<RechargeConfigType[]> {
        const response = await api.get('/api/admin-api/recharge-config');

        return (response.data.data?.configs ?? []) as RechargeConfigType[];
    },

    async create(payload: RechargeConfigPayload): Promise<{ config: RechargeConfigType }> {
        const response = await api.post('/api/admin-api/recharge-config', payload);

        return response.data.data as { config: RechargeConfigType };
    },

    async update(id: number, payload: RechargeConfigPayload): Promise<{ config: RechargeConfigType }> {
        const response = await api.patch(`/api/admin-api/recharge-config/${id}`, payload);

        return response.data.data as { config: RechargeConfigType };
    },

    async toggle(id: number): Promise<{ config: RechargeConfigType }> {
        const response = await api.patch(`/api/admin-api/recharge-config/${id}/toggle`);

        return response.data.data as { config: RechargeConfigType };
    },

    async remove(id: number): Promise<void> {
        await api.delete(`/api/admin-api/recharge-config/${id}`);
    },

    async verifyCredentials(payload: { api_key: string; api_secret: string }): Promise<{
        user: Record<string, unknown>;
        permissions: unknown[];
        endpoints: string[];
        bank_accounts: ApiBankVnBankAccountOption[];
    }> {
        const response = await api.post('/api/admin-api/recharge-config/verify-credentials', payload);

        return response.data.data as {
            user: Record<string, unknown>;
            permissions: unknown[];
            endpoints: string[];
            bank_accounts: ApiBankVnBankAccountOption[];
        };
    },
};
