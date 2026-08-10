<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'discord' => [
        'bot_name' => env('DISCORD_BOT_NAME', 'DailyProxy Monitor'),
        'bot_avatar_url' => env('DISCORD_BOT_AVATAR_URL'),
        'channels' => [
            'queue' => env('DISCORD_WEBHOOK_QUEUE'),
            'info' => env('DISCORD_WEBHOOK_INFO'),
            'ops' => env('DISCORD_WEBHOOK_OPS'),
            'security' => env('DISCORD_WEBHOOK_SECURITY'),
            'alerts' => env('DISCORD_WEBHOOK_ALERTS'),
            'recovered' => env('DISCORD_WEBHOOK_RECOVERED'),
            'staging' => env('DISCORD_WEBHOOK_STAGING'),
            'sales' => env('DISCORD_WEBHOOK_SALES') ?: env('DISCORD_WEBHOOK_OPS'),
            'provider' => env('DISCORD_WEBHOOK_PROVIDER') ?: env('DISCORD_WEBHOOK_ALERTS'),
            'feedback' => env('DISCORD_WEBHOOK_FEEDBACK') ?: env('DISCORD_WEBHOOK_INFO'),
            'activity' => env('DISCORD_WEBHOOK_ACTIVITY') ?: env('DISCORD_WEBHOOK_INFO'),
        ],
        'context' => [
            'app_name' => env('APP_NAME', 'DailyProxy.vn'),
            'app_env' => env('APP_ENV', 'production'),
            'app_url' => env('APP_URL'),
            'server_name' => env('DISCORD_SERVER_NAME', gethostname() ?: php_uname('n')),
            'server_ip' => env('DISCORD_SERVER_IP'),
            'server_role' => env('DISCORD_SERVER_ROLE', 'app'),
            'server_region' => env('DISCORD_SERVER_REGION'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'default_chat_id' => env('TELEGRAM_DEFAULT_CHAT_ID'),
    ],

    'internal_cron' => [
        'key' => env('AUTOCRON_INTERNAL_KEY'),
    ],

    'proxy' => [
        'pending_order_ttl_hours' => (int) env('PROXY_PENDING_ORDER_TTL_HOURS', 24),
        'api_log_retention_days' => (int) env('PROXY_API_LOG_RETENTION_DAYS', 30),
        'source_low_balance_threshold' => (float) env('PROXY_SOURCE_LOW_BALANCE_THRESHOLD', 50000),
        'source_low_balance_channel' => env('PROXY_SOURCE_LOW_BALANCE_CHANNEL', 'alerts'),
        'check_url' => env('PROXY_CHECK_URL', 'https://api.ipify.org?format=json'),
        'check_connect_timeout' => (int) env('PROXY_CHECK_CONNECT_TIMEOUT', 4),
        'check_timeout' => (int) env('PROXY_CHECK_TIMEOUT', 8),
    ],

];
