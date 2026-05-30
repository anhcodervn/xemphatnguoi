export interface SettingApiResponse<T> {
    tab: string;
    settings: T;
}

export interface SystemSettingType {
    site_name: string;
    site_domain: string;
    site_description: string;
    site_active: boolean;
    allow_register: boolean;
    support_email: string;
    hotline: string;
    address: string;
    facebook: string;
    zalo: string;
    youtube: string;
    meta_title: string;
    meta_description: string;
    robots: string;
    gtm_id: string;
    meta_pixel_id: string;
    custom_script: string;
    recharge_syntax: string;
    terms_of_use: unknown[];
    privacy_policy: unknown[];
    refund_policy: unknown[];
    [key: string]: unknown;
}

export interface GeneralSettingType {
    site_name: string;
    site_domain: string;
    site_description: string;
    site_active: boolean;
    allow_register: boolean;
    [key: string]: unknown;
}

export interface BrandingSettingType {
    logo: string;
    favicon: string;
    color_primary: string;
    color_accent: string;
    color_surface: string;
    [key: string]: unknown;
}

export interface HomeCategoryItemType {
    id?: number | string;
    name?: string;
    slug?: string;
    is_active?: boolean;
    [key: string]: unknown;
}

export interface HomeCategorySettingType {
    category_ids?: number[];
    categories?: HomeCategoryItemType[];
    [key: string]: unknown;
}

export interface FeaturedSliderItemType {
    id?: number | string | null;
    title?: string;
    subtitle?: string;
    image?: string;
    link?: string;
    link_redirect?: string;
    is_active?: boolean;
    status?: boolean;
    sort_order?: number;
    [key: string]: unknown;
}

export interface SliderImageSettingType {
    items: FeaturedSliderItemType[];
    [key: string]: unknown;
}

export interface ContactSettingType {
    hotline: string;
    support_email: string;
    address: string;
    facebook: string;
    zalo: string;
    youtube: string;
    [key: string]: unknown;
}

export interface SeoSettingType {
    meta_title: string;
    meta_description: string;
    robots?: string;
    gtm_id?: string;
    meta_pixel_id?: string;
    custom_script?: string;
    meta_keywords?: string;
    og_image?: string;
    [key: string]: unknown;
}

export interface OptionSettingType {
    terms_of_use: unknown[];
    privacy_policy: unknown[];
    refund_policy: unknown[];
    recharge_syntax: string;
    [key: string]: unknown;
}
