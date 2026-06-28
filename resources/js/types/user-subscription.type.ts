export interface PackageLimitsType {
    max_cron_jobs: number;
    min_interval_seconds: number;
    max_logs_per_job: number;
    max_request_timeout_seconds: number;
    max_response_size_kb: number;
    max_retries_per_run: number;
    max_headers_count: number;
    max_body_size_kb: number;
    allowed_methods: string[];
    allow_custom_headers: boolean;
    allow_custom_body: boolean;
    allow_cron_expression: boolean;
    allow_run_now: boolean;
    allow_alerts: boolean;
    max_alert_channels: number;
    monthly_run_quota: number | null;
    daily_run_quota: number | null;
    concurrent_runs_limit: number;
    priority: string;
    queue_name: string;
    allow_expected_body_check: boolean;
    allow_webhook_alert: boolean;
    allow_discord_alert: boolean;
    allow_telegram_alert: boolean;
    allow_email_alert?: boolean;
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
    auto_renew_enabled: boolean;
    starts_at: string | null;
    expires_at: string | null;
    status: string;
    package: UserSubscriptionPackageType;
}
