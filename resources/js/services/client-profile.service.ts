import api from '@/config/axios';
import type {
    ClientPasswordUpdatePayload,
    ClientProfilePaginatedResponse,
    ClientProfileType,
    ClientProfileUpdatePayload,
    LogoutOtherDevicesPayload,
    UserLogItem,
    WalletTransactionItem,
} from '@/types/client-profile.type';

type ProfileListFilters = {
    page?: number;
    per_page?: number;
    search?: string;
    action?: string;
    type?: string;
};

export const clientProfileService = {
    async getProfile(): Promise<ClientProfileType> {
        const response = await api.get('/api/profile');

        return response.data.data as ClientProfileType;
    },

    async updateProfile(payload: ClientProfileUpdatePayload): Promise<{ data: ClientProfileType; message: string }> {
        const response = await api.patch('/api/profile', payload);

        return {
            data: response.data.data as ClientProfileType,
            message: response.data.message as string,
        };
    },

    async updatePassword(payload: ClientPasswordUpdatePayload): Promise<string> {
        const response = await api.put('/api/profile/password', payload);

        return response.data.message as string;
    },

    async logoutOtherDevices(payload: LogoutOtherDevicesPayload): Promise<string> {
        const response = await api.post('/api/profile/logout-other-devices', payload);

        return response.data.message as string;
    },

    async getUserLogs(filters: ProfileListFilters = {}): Promise<ClientProfilePaginatedResponse<UserLogItem>> {
        const response = await api.get('/api/profile/user-logs', {
            params: filters,
        });

        return response.data.data as ClientProfilePaginatedResponse<UserLogItem>;
    },

    async getWalletTransactions(filters: ProfileListFilters = {}): Promise<ClientProfilePaginatedResponse<WalletTransactionItem>> {
        const response = await api.get('/api/profile/wallet-transactions', {
            params: filters,
        });

        return {
            ...(response.data.data as ClientProfilePaginatedResponse<WalletTransactionItem>),
            data: ((response.data.data?.data ?? []) as WalletTransactionItem[]).map((item) => ({
                ...item,
                amount: Number(item.amount ?? 0),
                balanceAfter: Number((item as WalletTransactionItem & { balance_after?: number | string }).balanceAfter ?? (item as WalletTransactionItem & { balance_after?: number | string }).balance_after ?? 0),
            })),
        };
    },
};
