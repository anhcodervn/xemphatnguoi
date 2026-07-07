# DISCORD_WEBHOOKS

## Mục tiêu

Project đã có sẵn hệ thống gửi Discord webhook cho các nhóm vận hành khác nhau.

Bạn chỉ cần:

1. Tạo channel trong Discord.
2. Tạo webhook cho từng channel.
3. Gắn URL webhook và thông tin server vào `.env`.

## Biến `.env`

### Webhook URL

- `DISCORD_WEBHOOK_QUEUE`
- `DISCORD_WEBHOOK_INFO`
- `DISCORD_WEBHOOK_OPS`
- `DISCORD_WEBHOOK_SECURITY`
- `DISCORD_WEBHOOK_ALERTS`
- `DISCORD_WEBHOOK_RECOVERED`
- `DISCORD_WEBHOOK_STAGING`

### Thông tin bot

- `DISCORD_BOT_NAME`
- `DISCORD_BOT_AVATAR_URL`

### Thông tin server gắn vào mọi message

- `DISCORD_SERVER_NAME`
- `DISCORD_SERVER_IP`
- `DISCORD_SERVER_ROLE`
- `DISCORD_SERVER_REGION`

Ngoài ra webhook cũng tự lấy thêm:

- `APP_NAME`
- `APP_ENV`
- `APP_URL`

## Channel gợi ý

- `#giapcaptcha-queue`
- `#giapcaptcha-info`
- `#giapcaptcha-ops`
- `#giapcaptcha-security`
- `#giapcaptcha-alerts-prod`
- `#giapcaptcha-recovered`
- `#giapcaptcha-staging`

## Mapping trong code

`app/Utils/SendMessage.php` hỗ trợ:

- `SendMessage::sendQueueReport(...)`
- `SendMessage::sendInfoReport(...)`
- `SendMessage::sendOpsReport(...)`
- `SendMessage::sendSecurityReport(...)`
- `SendMessage::sendAlertReport(...)`
- `SendMessage::sendRecoveredReport(...)`
- `SendMessage::sendStagingReport(...)`

`app/Service/DiscordWebhookNotifier.php` hỗ trợ webhook cấu hình trong admin settings và cũng tự gắn context server từ `.env`.

## Heartbeat theo dõi server

Đã có command:

```bash
php artisan monitor:discord-heartbeat
```

Ví dụ:

```bash
php artisan monitor:discord-heartbeat --channel=ops --title="Heartbeat production"
php artisan monitor:discord-heartbeat --channel=alerts --title="Server degraded" --note="CPU tăng cao, cần kiểm tra"
```

Command này sẽ gửi:

- app env
- app url
- server name
- server ip
- server role
- server region
- php version
- queue driver
- cache store
- db connection
- database ping
- disk free

## Ví dụ `.env`

```env
DISCORD_WEBHOOK_QUEUE=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_INFO=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_OPS=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_SECURITY=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_ALERTS=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_RECOVERED=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_STAGING=https://discord.com/api/webhooks/...

DISCORD_BOT_NAME="GiaiCaptcha Monitor"
DISCORD_BOT_AVATAR_URL=
DISCORD_SERVER_NAME="prod-app-01"
DISCORD_SERVER_IP="10.10.1.15"
DISCORD_SERVER_ROLE="app"
DISCORD_SERVER_REGION="sgp-1"
```
