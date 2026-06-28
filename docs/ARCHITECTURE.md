# ARCHITECTURE

## Stack
- Laravel 12
- Vue 3 + Vite + TypeScript
- TailwindCSS
- Axios + Sanctum session auth
- Pest

## Folder Model
- `app/Features`
  - `Admin/...`
  - `Client/...`
  - `Cron/...`
- `app/Models`
  - Eloquent models cho package, subscription, wallet và cron domain
- `app/Console/Commands`
  - command scheduler, pruning, quota reset
- `app/Jobs`
  - queue jobs, bao gồm `RunHttpCronJob`
- `resources/js/pages`
  - page theo admin/client
- `resources/js/services`
  - wrapper API typed
- `resources/js/router/modules`
  - router admin/client

## Feature-First Conventions
Mỗi feature ưu tiên giữ cấu trúc:
- `Controllers`
- `Requests`
- `Services`
- `Actions`
- `Resources`
- `routes.php`

## AutoCron Backend Modules

### `app/Features/Cron`
- `Services/CronPlanService`
  - resolve package limits cho user/subscription
- `Services/CronScheduleService`
  - tính `next_run_at` theo interval hoặc cron expression
- `Services/CronUsageService`
  - đếm usage và quota
- `Services/HttpTargetValidator`
  - SSRF protection và URL validation
- `Services/CronRunnerService`
  - chạy HTTP request, ghi log, update stats, retry, alert
- `Services/CronAlertService`
  - gửi alert fail/recovered/test
- `Resources/*`
  - JSON response shape cho cron jobs, logs, channels

### Client Features
- `Client/CronJob`
  - CRUD cron jobs, pause/resume/run-now, logs, stats
- `Client/CronAlert`
  - CRUD alert channels, test alert

### Admin Features
- `Admin/CronJob`
  - quản lý tất cả cron jobs và logs
- `Admin/Package`
  - chỉnh package limits cho AutoCron

## Core Data Flow
1. Vue page gọi service trong `resources/js/services`.
2. Service gọi API Laravel session-authenticated.
3. Controller dùng `FormRequest` validate input.
4. Service/Action xử lý nghiệp vụ.
5. Model ghi dữ liệu và `Resource` trả JSON thống nhất.

## Scheduler And Worker Flow
1. `routes/console.php` schedule:
   - `cron:dispatch-due` every minute
   - `cron:prune-logs` daily
   - `cron:reset-usage-quota` daily
2. `cron:dispatch-due` tìm job `active` có `next_run_at <= now()`.
3. Hệ thống lock theo job trước khi dispatch.
4. Queue được chọn theo `package_limits.queue_name`.
5. `RunHttpCronJob` thực thi request.
6. Kết quả được lưu vào `cron_job_logs`.
7. Counters trên `cron_jobs` và `cron_usage_counters` được cập nhật.
8. Alert được gửi khi fail/recovered nếu package cho phép.

## Package Limits
Package limits được lưu trong `package_limits` và snapshot trên subscription. Các trường quan trọng:
- `max_cron_jobs`
- `min_interval_seconds`
- `max_logs_per_job`
- `max_request_timeout_seconds`
- `max_response_size_kb`
- `max_retries_per_run`
- `allowed_methods`
- `allow_custom_headers`
- `allow_custom_body`
- `allow_cron_expression`
- `allow_run_now`
- `allow_alerts`
- `monthly_run_quota`
- `daily_run_quota`
- `concurrent_runs_limit`
- `priority`
- `queue_name`

## Queue / Supervisor Notes
- Queue names:
  - `cron-low`
  - `cron-default`
  - `cron-high`
- Production nên dùng Redis queue + Supervisor hoặc Horizon.
- Worker cần scale theo priority package và lưu ý `withoutOverlapping` cho dispatch command.

## Security Boundary
- SSRF validation trước khi request.
- Không cho redirect sang private IP.
- Preview response body bị cắt theo package limit.
- Log pruning theo package retention và giới hạn logs/job.
