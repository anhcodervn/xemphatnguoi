# TASK_WORKFLOW

## Bug Fix
1. Reproduce the issue from page, route, or test.
2. Trace the matching feature module in `app/Features`.
3. Check route registration in `routes/api.php`, `routes/web.php`, and feature `routes.php`.
4. Add or update a focused Pest test if the behavior is backend-visible.
5. Run only the checks needed for the touched area.

## Feature Work
1. Confirm whether the feature belongs under `Admin`, `Auth`, or `Client`.
2. Prefer custom `feature:*` artisan commands for backend scaffolding.
3. Add request validation, service/action logic, routes, and resource output as needed.
4. Update matching frontend page/service if the feature is user-facing.
5. Verify with focused tests and frontend build.

## Refactor
1. Preserve route names and existing payload shapes unless the task explicitly changes them.
2. Keep refactors module-local when possible.
3. Re-run tests for the touched module.

## Suggested Verification
- Backend only:
  - `vendor/bin/pint --dirty --format agent`
  - `php artisan test --compact tests/Feature/...`
- Frontend only:
  - `npm run format:check`
  - `npx eslint .`
  - `npm run build`
- Mixed change:
  - run both relevant backend and frontend checks

## Caution
- `npm run lint` modifies files because it runs `eslint . --fix`.
- `scripts/check.sh` is non-mutating for frontend lint and safer for review passes.

