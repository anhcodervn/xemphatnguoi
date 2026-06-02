import api from '@/config/axios';
import type { BankAccountType, BankTransactionType, BankType } from '@/types/bank.type';

export const clientBankService = {
    async list(): Promise<BankType[]> {
        const res = await api.get('/api/bank');
        return Array.isArray(res.data?.data) ? res.data.data : [];
    },

    async listAccounts(): Promise<BankAccountType[]> {
        const res = await api.get('/api/bank/accounts');
        return Array.isArray(res.data?.data) ? res.data.data : [];
    },

    async getAccount(id: string | number): Promise<BankAccountType> {
        const res = await api.get(`/api/bank/accounts/${id}`);
        return res.data.data;
    },

    async listTransactions(id: string | number, limit = 20, forceRefresh = false): Promise<BankTransactionType[]> {
        const res = await api.get(`/api/bank/transaction/${id}`, {
            params: { limit, force_refresh: forceRefresh ? 1 : 0 },
        });
        return Array.isArray(res.data?.data) ? res.data.data : [];
    },

    async updateAccount(id: string | number, payload: Record<string, unknown>) {
        return api.put(`/api/bank/accounts/${id}`, payload);
    },

    async updateAccountStatus(id: string | number, status: 'active' | 'inactive') {
        return api.patch(`/api/bank/accounts/${id}/status`, { status });
    },

    async deleteAccount(id: string | number) {
        return api.delete(`/api/bank/accounts/${id}`);
    },
};
