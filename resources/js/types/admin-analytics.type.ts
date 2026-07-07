export type AnalyticsRange = "today" | "7d" | "30d" | "all";

export type AnalyticsSummary = {
    users_total: number;
    users_active: number;
    users_new: number;
    users_new_today: number;
    wallet_balance_total: number;
    wallet_recharge_total: number;
    wallet_spent_total: number;
    deposit_success_amount: number;
    captcha_revenue: number;
    provider_cost: number;
    gross_profit: number;
    gross_margin: number;
    tasks_total: number;
    tasks_pending: number;
    tasks_solved: number;
    tasks_failed: number;
    task_success_rate: number;
    avg_processing_seconds: number;
    api_requests: number;
    api_avg_response_ms: number;
    active_webhooks: number;
    configured_webhooks: number;
};

export type AnalyticsTopService = {
    service_code: string;
    total_tasks: number;
    revenue: number;
    cost: number;
    profit: number;
};

export type AnalyticsDailyItem = {
    label: string;
    users: number;
    deposits: number;
    revenue: number;
    cost: number;
    profit: number;
    tasks_solved: number;
};

export type AnalyticsTaskFailure = {
    task_code: string;
    service_code: string;
    user: string | null;
    error_message: string | null;
    created_at: string | null;
};

export type AnalyticsRechargeItem = {
    transaction_code: string;
    amount: number;
    user: string | null;
    created_at: string | null;
};

export type AnalyticsDiscordWebhook = {
    name: string;
    url: string;
    is_active: boolean;
    events: string[];
};

export type AnalyticsEventOption = {
    label: string;
    value: string;
};

export type AdminAnalyticsResponse = {
    range: AnalyticsRange;
    filters: {
        ranges: Array<{ label: string; value: AnalyticsRange }>;
    };
    summary: AnalyticsSummary;
    top_services: AnalyticsTopService[];
    daily_overview: AnalyticsDailyItem[];
    recent_failed_tasks: AnalyticsTaskFailure[];
    recent_recharges: AnalyticsRechargeItem[];
    discord: {
        events: AnalyticsEventOption[];
        webhooks: AnalyticsDiscordWebhook[];
    };
};

