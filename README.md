# TaskNest API

Laravel 13 REST API for **TaskNest**, a project and task management app with role-based access, collaboration (comments, activity, notifications) and a due-date reminder scheduler. 
> **Frontend:** the Flutter client lives in [sytal/tasknest-app](https://github.com/sytal/tasknest-app). Run this API on port 8012 and the app connects to it by default.

![Tests](https://github.com/sytal/tasknest-backend/actions/workflows/tests.yml/badge.svg)

## Features
- **Auth:** Sanctum tokens (30-day expiry), login/forgot/avatar/comment rate limits, account deactivation, password reset.
- **Roles:** `director`, `ceo` (management) and `member`. The account role is only visible to management (and to the user themselves); everyone sees a free-text **designation**, which only the CEO can change.
- **Projects:** members with project roles (lead/member), archive/restore (archived = read-only), labels, due dates.
- **Tasks:** status, priority, assignee, start/due dates, estimates, subtasks, labels, bulk assign, status moves.
- **Collaboration:** comments, activity log, in-app notifications, daily due reminders (idempotent).
- **Misc:** global search, dashboard (due soon, recent activity, team workload), avatar processing (512px JPEG).
- Policies return 404 for resources a user must not know exist; list endpoints paginate when `page`/`per_page` is sent.

## Quick start
```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed        # demo data (blocked in production)
php artisan storage:link
php artisan serve --port=8012     # API at http://127.0.0.1:8012/api
```
Demo logins (password `password`): `director@`, `ceo@`, `alice@` (lead of Website Relaunch), `bob@`, `carol@`, `dave@`, `erin@` + `tasknest.test`.

## Tests
```bash
php artisan test
```

## Configuration
| Variable | Purpose |
|---|---|
| `APP_URL` | Public API URL |
| `FRONTEND_URL` | Used in password-reset links |
| `CORS_ALLOWED_ORIGINS` | Comma-separated web origins allowed to call the API |
| `MAIL_*` | Mail transport for password reset (defaults to `log`) |
| `QUEUE_CONNECTION` | `database` by default; run `php artisan queue:work` |
| `ALLOW_DEMO_SEED` | Set to `true` to allow the demo seeder in production (don't) |

## Production checklist
- `APP_ENV=production`, `APP_DEBUG=false`, a generated `APP_KEY`, HTTPS only.
- Do **not** run the demo seeder; create the first director/CEO account manually (`php artisan tinker`).
- Use a real database (MySQL/PostgreSQL) instead of SQLite for multi-user load.
- Set `CORS_ALLOWED_ORIGINS`, `FRONTEND_URL` and real `MAIL_*` settings.
- Run a queue worker and the scheduler (`* * * * * php artisan schedule:run`) for reminders.
- `php artisan config:cache route:cache` and `storage:link`.

## API overview
`/api/login`, `/me`, `/users`, `/projects`, `/projects/{id}/members|labels|activity`, `/tasks`, `/tasks/{id}/comments|subtasks|activity`, `/dashboard`, `/search`, `/notifications`, `/meta`, `/health`. All except login/forgot/reset/meta/health need `Authorization: Bearer <token>`.

## Screenshots
See the [app repository](https://github.com/sytal/tasknest-app#screenshots).

## Connect the app
```bash
git clone https://github.com/sytal/tasknest-app && cd tasknest-app
flutter pub get && flutter run -d chrome   # uses http://127.0.0.1:8012/api
```
For a web build served from another origin, add it to `CORS_ALLOWED_ORIGINS`.
