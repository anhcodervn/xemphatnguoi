# Supervisor Setup

These Supervisor configs are production-oriented examples for AutoCron.

## Included Programs

- `autocron-worker.conf`
  - runs Laravel queue workers for:
    - `cron-high`
    - `cron-default`
    - `cron-low`
    - `mails`
    - `user-logs`
- `autocron-scheduler.conf`
  - runs `php artisan schedule:work` so the in-app scheduler can dispatch due cron jobs continuously

## Before Enabling

Update these values to match your server:

- `directory=/var/www/autocron`
- `command=/usr/bin/php ...`
- `user=www-data`
- log paths under `/var/log/supervisor/`

## Install

Copy the files into Supervisor's config directory, for example:

```bash
sudo cp deploy/supervisor/autocron-*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

## Useful Commands

```bash
sudo supervisorctl restart autocron-worker:*
sudo supervisorctl restart autocron-scheduler
sudo supervisorctl tail -f autocron-worker
sudo supervisorctl tail -f autocron-scheduler
```

## Notes

- `numprocs=2` is a starting point only. Increase it if `cron-high` and `cron-default` queues back up.
- This repo schedules `cron:dispatch-due` with `schedule:work`, so production should keep the scheduler process alive.
- `queue:work --timeout=180` was chosen to stay above the current `RunHttpCronJob` timeout of `120` seconds and leave room for mail/log jobs.
