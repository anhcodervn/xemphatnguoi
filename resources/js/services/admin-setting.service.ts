import api from '@/config/axios';
import type {
    BrandingSettingType,
    ContactSettingType,
    ContentPageSettingsType,
    GeneralSettingType,
    HomeCategorySettingType,
    MonitoringSettingType,
    OptionSettingType,
    SeoSettingType,
    SettingApiResponse,
    SliderImageSettingType,
    SystemSettingType,
    TurnstileSettingType,
} from '@/types/setting.type';

const getTab = async <T>(tab: string): Promise<SettingApiResponse<T>> => {
    const res = await api.get(`/api/admin-api/settings/${tab}`);
    return res.data.data as SettingApiResponse<T>;
};

const updateTab = async <T>(tab: string, payload: T): Promise<SettingApiResponse<T>> => {
    const res = await api.patch(`/api/admin-api/settings/${tab}`, payload);
    return res.data.data as SettingApiResponse<T>;
};

export const adminSettingService = {
    getSystem() {
        return getTab<SystemSettingType>('system');
    },
    updateSystem(payload: SystemSettingType) {
        return updateTab<SystemSettingType>('system', payload);
    },
    getGeneral() {
        return getTab<GeneralSettingType>('general');
    },
    updateGeneral(payload: GeneralSettingType) {
        return updateTab<GeneralSettingType>('general', payload);
    },
    getBranding() {
        return getTab<BrandingSettingType>('branding');
    },
    updateBranding(payload: BrandingSettingType) {
        return updateTab<BrandingSettingType>('branding', payload);
    },
    getHomeCategory() {
        return getTab<HomeCategorySettingType>('home-category');
    },
    updateHomeCategory(payload: HomeCategorySettingType) {
        return updateTab<HomeCategorySettingType>('home-category', payload);
    },
    getFeaturedSliders() {
        return getTab<SliderImageSettingType>('slider-images');
    },
    updateFeaturedSliders(payload: SliderImageSettingType) {
        return updateTab<SliderImageSettingType>('slider-images', payload);
    },
    getContact() {
        return getTab<ContactSettingType>('contact');
    },
    updateContact(payload: ContactSettingType) {
        return updateTab<ContactSettingType>('contact', payload);
    },
    getSeo() {
        return getTab<SeoSettingType>('seo');
    },
    updateSeo(payload: SeoSettingType) {
        return updateTab<SeoSettingType>('seo', payload);
    },
    getMonitoring() {
        return getTab<MonitoringSettingType>('monitoring');
    },
    updateMonitoring(payload: MonitoringSettingType) {
        return updateTab<MonitoringSettingType>('monitoring', payload);
    },
    getTurnstile() {
        return getTab<TurnstileSettingType>('turnstile');
    },
    updateTurnstile(payload: Pick<TurnstileSettingType, 'enabled' | 'site_key'> & { secret_key?: string }) {
        return updateTab<TurnstileSettingType>('turnstile', payload);
    },
    getOptions() {
        return getTab<OptionSettingType>('options');
    },
    updateOptions(payload: OptionSettingType) {
        return updateTab<OptionSettingType>('options', payload);
    },
    getContentPages() {
        return getTab<ContentPageSettingsType>('content-pages');
    },
    updateContentPages(payload: ContentPageSettingsType) {
        return updateTab<ContentPageSettingsType>('content-pages', payload);
    },
};
