# DECISIONS

## 1. Backend code is organized by feature modules
- Evidence:
  - `app/Features/Admin/*`
  - `app/Features/Auth/*`
  - `app/Features/Client/*`
  - custom commands in `app/Console/Commands` for `feature:create`, `feature:service`, `feature:controller`, `feature:action`, `feature:request`, `feature:resource`
- Notes:
  - This is confirmed from repository structure.
  - The exact reason for this choice is not documented in the repo.

## 2. Laravel 12 bootstrap-style middleware registration is in use
- Evidence:
  - `bootstrap/app.php` registers middleware aliases and route configuration.
- Notes:
  - This is a framework-structure decision visible in code.

## 3. Route definitions are split across central route files and feature route files
- Evidence:
  - `routes/api.php` and `routes/web.php` both `require` feature `routes.php` files.
- Notes:
  - Admin routes are not centralized in a single route file.

## 4. Pest is the active test runner
- Evidence:
  - `composer.json` requires Pest packages.
  - `tests/Pest.php` configures Pest.
  - `.github/workflows/tests.yml` runs `./vendor/bin/pest`.

## 5. Frontend lint script is configured as an auto-fixing command
- Evidence:
  - `package.json` defines `lint` as `eslint . --fix`.
- Notes:
  - This affects how review/check scripts should be written.

