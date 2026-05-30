import api from '@/config/axios';
import type { SystemSettingType } from '@/types/setting.type';

export const clientSystemSettingService = {
    async get(): Promise<SystemSettingType> {
        const response = await api.get('/api/system-settings');

        return response.data.data.settings as SystemSettingType;
    },
};
