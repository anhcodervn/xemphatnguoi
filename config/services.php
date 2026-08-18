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
        'bot_name' => env('DISCORD_BOT_NAME', 'XemPhatNguoi Monitor'),
        'bot_avatar_url' => env('DISCORD_BOT_AVATAR_URL'),
        'channels' => [
            'ops' => env('DISCORD_WEBHOOK_OPS')
                ?: env('DISCORD_WEBHOOK_PROVIDER')
                ?: env('DISCORD_WEBHOOK_ALERTS')
                ?: env('DISCORD_WEBHOOK_QUEUE')
                ?: env('DISCORD_WEBHOOK_SECURITY')
                ?: env('DISCORD_WEBHOOK_RECOVERED')
                ?: env('DISCORD_WEBHOOK_INFO'),
            'activity' => env('DISCORD_WEBHOOK_ACTIVITY') ?: env('DISCORD_WEBHOOK_INFO'),
            'sales' => env('DISCORD_WEBHOOK_SALES') ?: env('DISCORD_WEBHOOK_OPS'),
            'support' => env('DISCORD_WEBHOOK_SUPPORT')
                ?: env('DISCORD_WEBHOOK_FEEDBACK')
                ?: env('DISCORD_WEBHOOK_INFO'),
            'staging' => env('DISCORD_WEBHOOK_STAGING'),
        ],
        'rooms' => [
            'ops' => [
                'name' => '#xpn-ops',
                'env' => 'DISCORD_WEBHOOK_OPS',
                'receives' => 'Heartbeat production, queue thất bại, lỗi nguồn tra cứu, cảnh báo bảo mật và phục hồi hệ thống.',
            ],
            'activity' => [
                'name' => '#xpn-activity',
                'env' => 'DISCORD_WEBHOOK_ACTIVITY',
                'receives' => 'Đăng ký tài khoản mới và các thay đổi vòng đời tài khoản.',
            ],
            'sales' => [
                'name' => '#xpn-sales',
                'env' => 'DISCORD_WEBHOOK_SALES',
                'receives' => 'Nạp tiền thành công, giao dịch ví và sự kiện doanh thu.',
            ],
            'support' => [
                'name' => '#xpn-support',
                'env' => 'DISCORD_WEBHOOK_SUPPORT',
                'receives' => 'Tin nhắn hỗ trợ mới và góp ý từ biểu mẫu liên hệ.',
            ],
            'staging' => [
                'name' => '#xpn-staging',
                'env' => 'DISCORD_WEBHOOK_STAGING',
                'receives' => 'Toàn bộ báo cáo từ local, testing và staging để không lẫn với production.',
            ],
        ],
        'context' => [
            'app_name' => env('APP_NAME', 'XemPhatNguoi.vn'),
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

    'api' => [
        'log_retention_days' => (int) env('API_LOG_RETENTION_DAYS', 30),
    ],

    'n8n_content' => [
        'enabled' => filter_var(env('N8N_CONTENT_API_ENABLED', false), FILTER_VALIDATE_BOOL),
        'key' => (string) env('N8N_CONTENT_API_KEY', ''),
        'rate_limit_per_minute' => (int) env('N8N_CONTENT_API_RATE_LIMIT_PER_MINUTE', 30),
    ],

    'turnstile' => [
        'enabled' => filter_var(env('TURNSTILE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'site_key' => env('TURNSTILE_SITE_KEY', ''),
        'secret_key' => env('TURNSTILE_SECRET_KEY', ''),
        'hostname' => env('TURNSTILE_ALLOWED_HOSTNAME') ?: (parse_url((string) env('APP_URL'), PHP_URL_HOST) ?: ''),
        'action' => 'traffic_fine_lookup',
        'connect_timeout' => 2,
        'timeout' => 5,
        'grant_ttl' => 300,
    ],

];
