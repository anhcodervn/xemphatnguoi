# CODING_STANDARD

## Formatting
- PHP formatting uses Pint:
  - `vendor/bin/pint`
- Frontend formatting uses Prettier:
  - `npm run format`
  - `npm run format:check`
- Frontend lint command present:
  - `npm run lint`
  - note: this command uses `--fix` in `package.json`

## Naming / Structure Observed
- Feature modules use PascalCase folder names under `app/Features`.
- Backend classes commonly use names like:
  - `*Controller`
  - `*Service`
  - `*Action`
  - `*Request`
  - `*Resource`
- Vue pages often use nested `index.vue` files.
- Frontend API wrappers use `*.service.ts`.

## Testing Conventions Observed
- Pest is the active test framework.
- `tests/Pest.php` applies `RefreshDatabase` to feature tests.
- CI runs the Pest suite with `./vendor/bin/pest`.

## Error Handling Observed
- Some feature modules use `ApiException` for business errors.
- Many JSON endpoints return objects containing `status`, and often `message` and `data`.
- Needs confirmation: whether this JSON shape is mandatory across all modules.

## Laravel-Specific Conventions Observed
- Middleware aliases are registered in `bootstrap/app.php`.
- Custom feature generator commands exist and should be preferred when adding matching backend files.

## Unknown / Needs Confirmation
- Whether strict TypeScript typecheck is part of the normal workflow; `package.json` does not define a typecheck script.

