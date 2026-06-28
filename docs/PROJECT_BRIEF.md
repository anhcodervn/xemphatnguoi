# PROJECT_BRIEF

## Product
- Product name: `AutoCron`
- Domain: SaaS cho thuê và quản lý `HTTP Cron Jobs`
- Core value:
  - user mua package/subscription
  - tạo cron job HTTP theo giới hạn gói
  - hệ thống tự dispatch và chạy request theo lịch
  - ghi log, tính quota, cảnh báo khi lỗi

## Confirmed Stack
- Backend: Laravel 12
- Frontend: Vue 3 + Vite + TypeScript
- Auth SPA: Sanctum + session/cookie + CSRF refresh
- UI: admin/client SPA layout có sẵn
- Tests: Pest

## Architecture Direction
- Giữ kiến trúc `feature-first` trong `app/Features`
- Tái sử dụng:
  - auth
  - admin/client layout
  - package
  - subscription
  - wallet
- Domain cũ liên quan API Bank được loại khỏi route/UI chính, thay bằng domain AutoCron

## Main Business Domains
- `CronJob`
  - cấu hình URL, method, headers, body, schedule, expected checks, retry, status
- `CronJobLog`
  - lịch sử mỗi lần chạy, request/response preview, error, duration
- `CronAlertChannel`
  - discord, telegram, webhook, email
- `CronUsageCounter`
  - thống kê số lần chạy theo ngày/tháng cho quota
- `Package` / `UserSubscription`
  - package limits cho cron SaaS

## Scheduler And Queue Flow
1. `cron:dispatch-due` chạy mỗi phút.
2. Command tìm `cron_jobs` đến hạn.
3. Kiểm tra subscription, quota, status, SSRF safety.
4. Lock job để tránh dispatch trùng.
5. Đẩy `RunHttpCronJob` vào queue theo priority package:
   - `cron-low`
   - `cron-default`
   - `cron-high`
6. Worker thực hiện HTTP request, ghi log, cập nhật counters và alert.

## Security
- Có SSRF protection cho target URL:
  - chỉ cho `http` / `https`
  - chặn localhost, private IP, metadata IP
  - chặn domain resolve sang private IP
  - chặn dangerous ports
  - chặn body/header/URL quá lớn

## Verification Commands
1. `php artisan route:list`
2. `php artisan test --compact`
3. `npm run build`
4. `npx eslint .`

## Current Refactor Status
- Đã thêm domain backend/frontend AutoCron cơ bản.
- Đã thay route và menu chính sang cron jobs, logs, alerts, packages.
- Vẫn còn một số module cũ trên đĩa để tránh động vào migration/history không liên quan.
