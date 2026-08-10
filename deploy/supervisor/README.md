# Supervisor Setup

These Supervisor configs are production-oriented examples for DailyProxy.vn.

## Included Programs

- `dailyproxy-worker.conf`
  - runs Laravel queue workers for:
    - `default`
    - `mails`
    - `user-logs`
- `dailyproxy-scheduler.conf`
  - runs `php artisan schedule:work` so Laravel can execute:
    - Discord heartbeat
    - pending package order pruning
    - api log pruning

## Before Enabling

Update these values to match your server:

- `directory=/var/www/dailyproxy.vn/laravel-app`
- `command=/usr/bin/php ...`
- `user=www-data`
- log paths under `/var/log/supervisor/`

## Install

Copy the files into Supervisor's config directory:

```bash
sudo cp deploy/supervisor/dailyproxy-*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

## Useful Commands

```bash
sudo supervisorctl restart dailyproxy-worker:*
sudo supervisorctl restart dailyproxy-scheduler
sudo supervisorctl tail -f dailyproxy-worker
sudo supervisorctl tail -f dailyproxy-scheduler
```

## Cron Alternative

If you prefer classic cron for scheduling, keep only `dailyproxy-worker.conf`
and use:

```bash
* * * * * cd /var/www/dailyproxy.vn/laravel-app && php artisan schedule:run >> /dev/null 2>&1
```

In that case, do not run `dailyproxy-scheduler.conf`.

## Notes

- `numprocs=2` is a safe starting point. Increase if queue starts backing up.
- `queue:work --timeout=120` is enough for current mail, user log, and app jobs.
- If you deploy to another path, update `directory` and log file names before enabling Supervisor.
