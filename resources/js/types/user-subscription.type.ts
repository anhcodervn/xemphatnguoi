export interface PackageLimitsType {
    max_api_keys: number;
    requests_per_minute: number;
    monthly_captcha_quota: number | null;
    max_concurrent_tasks: number;
    max_whitelisted_ips: number;
    supports_callback: boolean;
    supports_priority_queue: boolean;
    supports_manual_review: boolean;
    service_whitelist: string[];
}

export interface UserSubscriptionPackageType {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    duration_days: number;
    account_limit: number;
    can_buy_extra_account: boolean;
    extra_account_price: string;
    request_limit: number;
    request_per_minute: number;
    concurrent_limit: number;
    features: unknown[] | Record<string, unknown> | null;
    package_limits?: PackageLimitsType;
    status: string;
}

export interface CurrentUserSubscriptionType {
    id: number;
    user_id: number;
    package_id: number;
    order_id: number | null;
    package_name: string;
    package_price: string;
    package_limits?: PackageLimitsType;
    base_account_limit: number;
    extra_account_limit: number;
    used_account: number;
    used_captcha_quota: number;
    remaining_captcha_quota: number | null;
    auto_renew_enabled: boolean;
    starts_at: string | null;
    expires_at: string | null;
    status: string;
    package_api_keys?: Array<{
        id: number;
        name: string;
        api_key: string;
        api_secret: string | null;
        status: string;
        expired_at: string | null;
        created_at: string | null;
    }>;
    package: UserSubscriptionPackageType;
}
