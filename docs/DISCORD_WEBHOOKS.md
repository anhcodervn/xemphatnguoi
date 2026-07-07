# DISCORD_WEBHOOKS

## Mục tiêu

Project đã có sẵn hệ thống gửi Discord webhook để theo dõi:

- vận hành server
- cảnh báo lỗi
- cảnh báo bảo mật
- queue / job nền
- staging
- thông báo hồi phục sau sự cố

Bạn chỉ cần:

1. Tạo channel trong Discord.
2. Tạo webhook cho từng channel.
3. Gắn URL webhook và thông tin server vào `.env`.

## Gắn link hook vào đâu

Gắn trực tiếp vào file `.env`:

```env
DISCORD_WEBHOOK_QUEUE=
DISCORD_WEBHOOK_INFO=
DISCORD_WEBHOOK_OPS=
DISCORD_WEBHOOK_SECURITY=
DISCORD_WEBHOOK_ALERTS=
DISCORD_WEBHOOK_RECOVERED=
DISCORD_WEBHOOK_STAGING=
```

Sau khi sửa `.env`, chạy:

```bash
php artisan config:clear
```

## Giải thích chức năng từng channel

### `DISCORD_WEBHOOK_QUEUE`

Dùng cho queue worker và job nền.

Nên đẩy vào đây:

- job xử lý chậm
- backlog queue tăng mạnh
- worker restart liên tục
- failed jobs của tác vụ nền

Không nên dùng cho:

- lỗi captcha trực tiếp của người dùng
- heartbeat server

### `DISCORD_WEBHOOK_INFO`

Dùng cho thông tin vận hành nhẹ, không khẩn cấp.

Nên đẩy vào đây:

- thông báo hệ thống chung
- event business không nghiêm trọng
- log test ping nhẹ

### `DISCORD_WEBHOOK_OPS`

Đây là channel quan trọng nhất để theo dõi server/app.

Nên đẩy vào đây:

- heartbeat định kỳ
- database ping
- disk free
- queue driver / cache store / app env
- trạng thái app production

Khuyến nghị:

- nếu bạn chỉ cấu hình 1 webhook trước, hãy cấu hình `OPS` đầu tiên

### `DISCORD_WEBHOOK_SECURITY`

Dùng cho cảnh báo bảo mật và abuse.

Nên đẩy vào đây:

- API key bị dùng bất thường
- IP lạ hoặc spam request
- hành vi nghi ngờ brute force / abuse
- callback hoặc request có dấu hiệu nguy hiểm

### `DISCORD_WEBHOOK_ALERTS`

Dùng cho lỗi production đang xảy ra.

Nên đẩy vào đây:

- provider captcha lỗi hàng loạt
- create task / check task fail tăng cao
- service down
- exception nghiêm trọng
- thanh toán / ví / billing lỗi diện rộng

Đây là channel nên theo dõi sát nhất sau `OPS`.

### `DISCORD_WEBHOOK_RECOVERED`

Dùng cho thông báo hệ thống đã hồi phục.

Nên đẩy vào đây:

- provider captcha hoạt động lại
- queue xử lý ổn định lại
- server hoặc service hết lỗi

Mục đích:

- tách riêng tín hiệu “đã ổn” khỏi channel cảnh báo đỏ

### `DISCORD_WEBHOOK_STAGING`

Dùng riêng cho test/staging.

Nên đẩy vào đây:

- test webhook
- thử cron / queue ở staging
- cảnh báo môi trường test

Không nên trộn vào production.

## Mapping khuyến nghị cho GiaCaptcha.vn

### Tối thiểu nên bật

Nếu muốn setup nhanh gọn, chỉ cần 4 channel này:

```env
DISCORD_WEBHOOK_OPS=
DISCORD_WEBHOOK_ALERTS=
DISCORD_WEBHOOK_SECURITY=
DISCORD_WEBHOOK_INFO=
```

### Mapping thực tế đề xuất

| Channel env | Mục đích | Ví dụ sự kiện |
|---|---|---|
| `DISCORD_WEBHOOK_OPS` | giám sát hệ thống | heartbeat server, DB ping, disk free, app health |
| `DISCORD_WEBHOOK_ALERTS` | lỗi production | provider captcha fail, task fail tăng mạnh, lỗi ví/thanh toán |
| `DISCORD_WEBHOOK_SECURITY` | bảo mật / abuse | API key bất thường, IP lạ, spam request |
| `DISCORD_WEBHOOK_INFO` | thông tin nhẹ | thông báo chung, log sự kiện không khẩn cấp |
| `DISCORD_WEBHOOK_QUEUE` | queue nền | backlog, failed jobs, worker có vấn đề |
| `DISCORD_WEBHOOK_RECOVERED` | hồi phục | service hoạt động lại sau alert |
| `DISCORD_WEBHOOK_STAGING` | test/staging | test heartbeat, test webhook, lỗi staging |

## Mapping theo code hiện tại

### 1. Kênh `.env` dùng qua `SendMessage`

`app/Utils/SendMessage.php` hỗ trợ:

- `SendMessage::sendQueueReport(...)` -> `DISCORD_WEBHOOK_QUEUE`
- `SendMessage::sendInfoReport(...)` -> `DISCORD_WEBHOOK_INFO`
- `SendMessage::sendOpsReport(...)` -> `DISCORD_WEBHOOK_OPS`
- `SendMessage::sendSecurityReport(...)` -> `DISCORD_WEBHOOK_SECURITY`
- `SendMessage::sendAlertReport(...)` -> `DISCORD_WEBHOOK_ALERTS`
- `SendMessage::sendRecoveredReport(...)` -> `DISCORD_WEBHOOK_RECOVERED`
- `SendMessage::sendStagingReport(...)` -> `DISCORD_WEBHOOK_STAGING`

### 2. Webhook cấu hình trong admin

`app/Service/DiscordWebhookNotifier.php` hỗ trợ webhook cấu hình từ admin settings theo event:

- `test_ping`
- `user_registered`
- `recharge_success`
- `captcha_task_failed`

Ý nghĩa:

- phần `.env` là webhook vận hành hệ thống
- phần admin settings là webhook business/event theo nhu cầu quản trị

Bạn có thể dùng song song cả hai.

## Event -> channel khuyến nghị rõ ràng

### Nên gửi vào `OPS`

- heartbeat server mỗi 10 phút
- database ping fail / recover
- disk gần đầy
- queue worker chết nhưng chưa thành incident lớn

### Nên gửi vào `ALERTS`

- nguồn captcha chính chết
- create task lỗi hàng loạt
- check task timeout tăng mạnh
- wallet deduction lỗi
- thanh toán gói lỗi diện rộng

### Nên gửi vào `SECURITY`

- API key gọi sai quá nhiều
- nhiều IP lạ dùng cùng key
- spam request solve
- dấu hiệu lạm dụng callback / abuse endpoint

### Nên gửi vào `INFO`

- user đăng ký mới
- nạp tiền thành công
- thông báo business thông thường

### Nên gửi vào `QUEUE`

- failed jobs
- queue delay cao
- worker crash loop

### Nên gửi vào `RECOVERED`

- provider captcha sống lại
- queue xử lý bình thường lại
- database / server hết cảnh báo

### Nên gửi vào `STAGING`

- tất cả thứ gì thuộc môi trường test

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

Ngoài ra hệ thống còn tự lấy:

- `APP_NAME`
- `APP_ENV`
- `APP_URL`

## Channel gợi ý

- `#giapcaptcha-ops`
- `#giapcaptcha-alerts`
- `#giapcaptcha-security`
- `#giapcaptcha-info`
- `#giapcaptcha-queue`
- `#giapcaptcha-recovered`
- `#giapcaptcha-staging`

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

## Mẫu `.env` production

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

## Setup nhanh cho bạn

Nếu bạn muốn cấu hình gọn trước, dùng tối thiểu:

```env
DISCORD_WEBHOOK_OPS=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_ALERTS=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_SECURITY=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_INFO=https://discord.com/api/webhooks/...

DISCORD_BOT_NAME="GiaiCaptcha Monitor"
DISCORD_SERVER_NAME="prod-app-01"
DISCORD_SERVER_IP="10.10.1.15"
DISCORD_SERVER_ROLE="app"
DISCORD_SERVER_REGION="sgp-1"
```

Sau đó test:

```bash
php artisan config:clear
php artisan monitor:discord-heartbeat --channel=ops --title="Heartbeat production"
```
