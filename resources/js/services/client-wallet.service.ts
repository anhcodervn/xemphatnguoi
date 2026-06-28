import api from '@/config/axios';
import type { DepositRequestItem, DepositRequestListResponse, RechargeConfigType } from '@/types/recharge-config.type';
import type { WalletType } from '@/types/wallet.type';

export const clientWalletService = {
    async overview(params: { amount?: number } = {}): Promise<{ wallet: WalletType; recharge_config: RechargeConfigType | null; recharge_configs: RechargeConfigType[] }> {
        const response = await api.get('/api/client/wallet', { params });

        return response.data.data as { wallet: WalletType; recharge_config: RechargeConfigType | null; recharge_configs: RechargeConfigType[] };
    },

    async listDepositRequests(params: Record<string, unknown> = {}): Promise<DepositRequestListResponse> {
        const response = await api.get('/api/client/wallet/deposit-requests', { params });

        return response.data.data as DepositRequestListResponse;
    },

    async createDepositRequest(payload: { amount: number; config_id?: number | null }): Promise<{ deposit_request: DepositRequestItem }> {
        const response = await api.post('/api/client/wallet/deposit-requests', payload);

        return response.data.data as { deposit_request: DepositRequestItem };
    },

    async confirmDepositRequest(depositRequestId: number | string): Promise<{ deposit_request: DepositRequestItem }> {
        const response = await api.post(`/api/client/wallet/deposit-requests/${depositRequestId}/confirm`);

        return response.data.data as { deposit_request: DepositRequestItem };
    },
};
