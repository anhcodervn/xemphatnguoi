# ARCHITECTURE

## Stack
- PHP backend with Laravel 12
- Vue 3 frontend with Vite
- TailwindCSS
- Axios for API calls
- Pest for tests

## Important Folders
- `app/Features`
  - grouped by `Admin`, `Auth`, `Client`
- `app/Models`
  - Eloquent models
- `app/Console/Commands`
  - custom feature generators
- `resources/js/pages`
  - frontend pages
- `resources/js/services`
  - frontend API wrappers
- `resources/js/router`
  - router modules for `admin` and `client`
- `database/migrations`
  - schema history
- `tests/Feature`
  - feature tests

## Route Layout
- `routes/api.php`
  - includes many client feature route files
  - also includes some admin feature route files
- `routes/web.php`
  - serves auth views
  - includes some admin `/admin-api/...` feature route files
  - includes SPA catch-all route behind `auth`

## Backend Structure Observed
- Feature modules commonly contain:
  - `Controllers`
  - `Requests`
  - `Services`
  - `Actions`
  - `Resources`
  - `routes.php`
- This structure is supported by custom artisan commands in `app/Console/Commands`.

## Frontend Structure Observed
- Pages are grouped by area:
  - `resources/js/pages/admin/...`
  - `resources/js/pages/client/...`
- API calls are usually wrapped in `resources/js/services/*.service.ts`.

## Data Flow (Observed, High-Level)
1. Vue page calls a frontend service.
2. Service sends HTTP request to Laravel route.
3. Controller delegates to request validation and service/action logic.
4. Models read/write the database.
5. JSON response returns to frontend.

## Unknown / Needs Confirmation
- Formal architectural boundary between `Services` and `Actions`; both are used in the codebase.
- Whether all admin API endpoints are intended to be session-authenticated rather than token-authenticated.

