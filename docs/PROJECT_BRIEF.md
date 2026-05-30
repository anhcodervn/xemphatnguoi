# PROJECT_BRIEF

## Confirmed Basics
- Framework: Laravel 12 (`composer.json`)
- Frontend: Vue 3 + Vite (`package.json`)
- Auth packages present:
  - `laravel/sanctum`
  - `tymon/jwt-auth`
- Test framework: Pest (`composer.json`, `tests/Pest.php`)

## Repository Shape
- Backend code is primarily under `app/Features`, `app/Models`, and `app/Http`.
- Frontend code is under `resources/js`.
- Routes are split between `routes/api.php` and `routes/web.php`.
- CI is present in `.github/workflows`.

## Confirmed Feature Areas From Code
- Auth
- Bank
- Package
- Profile
- Recharge
- Subscription
- User
- Wallet
- Webhook
- Admin modules for package, recharge, coupon, setting, user, deposit, wallet transaction, package order, webhook

## Quick Start Commands Present In Repo
1. `composer install`
2. `npm install`
3. `php artisan key:generate`
4. `php artisan migrate`
5. `composer run dev`

## Script Notes
- `scripts/check.sh` and `scripts/test.sh` are POSIX shell scripts.
- On Windows, they may require Git Bash, WSL, or equivalent shell support.

## Unknown / Needs Confirmation
- README content: no root `README.md` was present at inspection time.
- Intended production deployment process.
- Whether JWT routes are actively used; `routes/api.php` contains an empty `v1` group.
