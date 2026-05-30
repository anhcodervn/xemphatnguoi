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
    status: string;
}

export interface CurrentUserSubscriptionType {
    id: number;
    user_id: number;
    package_id: number;
    order_id: number | null;
    package_name: string;
    package_price: string;
    base_account_limit: number;
    extra_account_limit: number;
    used_account: number;
    starts_at: string | null;
    expires_at: string | null;
    status: string;
    package: UserSubscriptionPackageType;
}
