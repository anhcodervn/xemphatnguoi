# DISCORD_WEBHOOKS

## Mục tiêu

Project đã được tách sẵn các kênh Discord theo từng nhóm vận hành trong `app/Utils/SendMessage.php`.

Bạn chỉ cần:

1. Tạo các channel trong Discord.
2. Tạo webhook cho từng channel.
3. Dán URL webhook vào `.env`.

## Danh sách biến `.env`

- `DISCORD_WEBHOOK_QUEUE`
  - dùng cho log queue thành công/thất bại
- `DISCORD_WEBHOOK_INFO`
  - dùng cho sự kiện nghiệp vụ chung như đăng ký, mua gói
- `DISCORD_WEBHOOK_OPS`
  - dùng cho vận hành hệ thống nội bộ
- `DISCORD_WEBHOOK_SECURITY`
  - dùng cho cảnh báo bảo mật, SSRF blocked, abuse
- `DISCORD_WEBHOOK_ALERTS`
  - dùng cho cảnh báo cron job production
- `DISCORD_WEBHOOK_RECOVERED`
  - dùng cho cảnh báo cron job đã hồi phục
- `DISCORD_WEBHOOK_STAGING`
  - dùng cho staging, test, dev

## Gợi ý tạo channel trong Discord

- `#autocron-queue`
- `#autocron-info`
- `#autocron-ops`
- `#autocron-security`
- `#autocron-alerts-prod`
- `#autocron-recovered`
- `#autocron-staging`

## Mapping trong code

`app/Utils/SendMessage.php` đang hỗ trợ các method:

- `SendMessage::sendQueueReport(...)`
- `SendMessage::sendInfoReport(...)`
- `SendMessage::sendOpsReport(...)`
- `SendMessage::sendSecurityReport(...)`
- `SendMessage::sendAlertReport(...)`
- `SendMessage::sendRecoveredReport(...)`
- `SendMessage::sendStagingReport(...)`

## Chỗ đang dùng sẵn

- Queue notifications đang dùng `SendMessage::sendQueueReport(...)`
- Các sự kiện user/package đang dùng `SendMessage::sendInfoReport(...)`

## Ví dụ `.env`

```env
DISCORD_WEBHOOK_QUEUE=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_INFO=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_OPS=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_SECURITY=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_ALERTS=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_RECOVERED=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_STAGING=https://discord.com/api/webhooks/...
```
