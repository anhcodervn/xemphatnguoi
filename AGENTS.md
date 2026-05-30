# AGENTS.md

## Read First
1. `composer.json`
2. `package.json`
3. `routes/api.php`
4. `routes/web.php`
5. `tests/Pest.php`
6. `docs/PROJECT_BRIEF.md`
7. `docs/ARCHITECTURE.md`

## Default Workflow
1. Inspect the target area before editing:
   - backend feature module in `app/Features/...`
   - matching frontend page/service in `resources/js/...`
   - route includes in `routes/api.php` and `routes/web.php`
2. Reuse existing scaffolding commands when adding backend feature files:
   - `php artisan feature:create`
   - `php artisan feature:controller`
   - `php artisan feature:service`
   - `php artisan feature:action`
   - `php artisan feature:request`
   - `php artisan feature:resource`
3. Keep edits scoped to the touched module.
4. Run focused verification before finishing.

## Commands
- Install:
  - `composer install`
  - `npm install`
- Dev:
  - `composer run dev`
  - `npm run dev`
- Build:
  - `npm run build`
- Format / lint:
  - `vendor/bin/pint --dirty --format agent`
  - `npm run format`
  - `npm run format:check`
  - `npm run lint`
  - `npx eslint .`
- Tests:
  - `php artisan test --compact`
  - `php artisan test --compact tests/Feature/SomeTest.php`

## Repo-Specific Instructions
- `npm run lint` uses `eslint . --fix` and may modify files. Use `npx eslint .` when you only want a non-mutating check.
- Admin routes are not all in one place:
  - some admin API routes are included from `routes/api.php`
  - some `/admin-api/...` routes are included from `routes/web.php`
- Preserve existing feature/module names exactly, including existing typos such as `Couponts`, unless the user explicitly asks to rename them.

## Do Not Change Without Explicit Permission
- Route prefixes and naming conventions.
- Authentication / middleware strategy.
- CI workflows under `.github/workflows`.
- Existing migration history unrelated to the task.

## Risky Areas
- Wallet / package / subscription payment flows in `app/Features/Client/Package`, `app/Features/Client/Subscription`, `app/Features/Client/Wallet`.
- Admin route protection consistency (`auth` vs `auth + admin`) needs care when editing admin modules.
- TypeScript typecheck setup is not confirmed stable. `tsconfig.json` includes custom `types` entries that may need confirmation before relying on `vue-tsc`.

## Final Response
- State what changed.
- List the files you touched.
- List the commands you ran and whether they passed.
- Call out unknowns, risks, or follow-up items.

