# OfficePro

**OfficePro** is a full-stack office management and HR ERP for small and medium-sized companies, built with **Laravel 12, PHP 8.2+ (pinned to and verified on PHP 8.3.35), MySQL, Blade, Bootstrap 5 and vanilla JavaScript** (no React/Vue/Angular/Tailwind, no Node build step).

It covers employees, departments, designations, attendance, leave, tasks (list + Kanban), payroll with PDF salary slips, expenses, assets, documents, reports (PDF/CSV), notifications, an activity log, global search, settings and role-based access control, with a light/dark responsive interface.

---

## Contents

1. [Requirements](#1-requirements)
2. [Installation](#2-installation)
3. [Environment configuration](#3-environment-configuration)
4. [Database, migrations and seed data](#4-database-migrations-and-seed-data)
5. [Storage](#5-storage)
6. [Running the app](#6-running-the-app)
7. [Demo login credentials](#7-demo-login-credentials)
8. [Roles and permissions](#8-roles-and-permissions)
9. [Features](#9-features)
10. [Project structure](#10-project-structure)
11. [Database tables](#11-database-tables)
12. [Main routes](#12-main-routes)
13. [Scheduled jobs and queues](#13-scheduled-jobs-and-queues)
14. [Testing](#14-testing)
15. [Troubleshooting](#15-troubleshooting)
16. [Known limitations](#16-known-limitations)

---

## 1. Requirements

| Requirement | Version / notes |
|---|---|
| PHP | **8.2 or newer.** `composer.json` requires `^8.2`, and dependency resolution is **pinned to and verified against PHP 8.3.35** (see [PHP version pinning](#php-version-pinning) below). Also verified on 8.3.6 and 8.4.19. Required extensions: `pdo_mysql`, `pdo_sqlite` (for tests), `mbstring`, `openssl`, `tokenizer`, `xml`, `dom`, `ctype`, `json`, `fileinfo`, `curl`, `gd` (PDF/image rendering), `zip`, `bcmath`, `intl` |
| Composer | 2.x |
| MySQL | 8.0+ (or MariaDB 10.6+; tested on MariaDB 10.11) |
| Node.js / npm | **Not required.** Bootstrap, Bootstrap Icons and Chart.js are bundled in `public/vendor`. |
| Web server | `php artisan serve` for development, or Nginx/Apache pointing at `public/` |

### PHP version pinning

`composer.json` sets `"config": {"platform": {"php": "8.3.35"}}`. This tells Composer to resolve and lock every dependency (`composer.lock`) as if PHP 8.3.35 were the runtime — regardless of which PHP version actually runs `composer install` — so the exact same, verified dependency graph is installed everywhere. `composer.lock` records `"platform-overrides": {"php": "8.3.35"}` to confirm this.

This was verified end-to-end: a real PHP 8.3.6 CLI (patch releases within a minor version do not change Composer's dependency resolution or Laravel's runtime behaviour) ran `composer install` from a clean checkout, then `php artisan migrate --seed`, `php artisan serve`, a live login, the dashboard, PDF salary-slip generation, and the full `php artisan test` suite (77 tests / 460 assertions) against both SQLite and real MySQL — all passed. The codebase contains no PHP 8.4-only syntax (property hooks, asymmetric visibility, the new `array_find`/`array_any`/`array_all` functions, etc.), so it also runs unchanged on 8.4.

**If you later upgrade PHP:** either remove the `config.platform.php` line and run `composer update`, or change it to your new version and run `composer update` to re-resolve.

## 2. Installation

```bash
git clone https://github.com/nishathashmi06-collab/officepro-erp.git
cd officepro-erp

composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

## 3. Environment configuration

Edit `.env`. Secrets live **only** in `.env`, never in the source code.

```dotenv
APP_NAME=OfficePro
APP_ENV=local
APP_DEBUG=true               # set to false in production
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=officepro
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=sync        # "database" + `php artisan queue:work` in production
MAIL_MAILER=log              # reset / notification e-mails go to storage/logs/laravel.log
```

The company name, logo, timezone, date format, currency and working hours are set in the app under **Settings**, not in `.env`.

## 4. Database, migrations and seed data

Create the database:

```sql
CREATE DATABASE officepro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run the migrations and load the demo data:

```bash
php artisan migrate --seed
# or, to start over from scratch:
php artisan migrate:fresh --seed
```

> **Demo / seed data.** `DatabaseSeeder` runs `DemoDataSeeder`. Every person, e-mail, salary, document and record it creates is fictional and exists only to show the system working. Seeded employees carry the note *"DEMO DATA — created by DemoDataSeeder"*, and the demo PDFs say so on the page.

**Production install without demo data:** set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`, then run:

```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
```

This creates the roles, permissions, leave types, expense categories and designations, plus one primary super admin.

## 5. Storage

```bash
php artisan storage:link
```

- **Public disk** (`storage/app/public`, served via `public/storage`): profile photos and the company logo only.
- **Private disk** (`storage/app/private`): employee documents, expense receipts and leave attachments. These are **never** reachable by a public URL. They are streamed through authorised routes (`/documents/{id}/download`, `/expenses/{id}/receipt`, `/leaves/{id}/attachment`) after a policy check. Stored filenames are random; the original name is kept only for display.

Make sure `storage/` and `bootstrap/cache/` are writable by the web server.

## 6. Running the app

```bash
php artisan serve              # http://localhost:8000
# optional, in separate terminals:
php artisan schedule:work      # deadline / expiry reminders, auto-absent marking
php artisan queue:work         # only if QUEUE_CONNECTION=database
```

The shortcut `composer run setup` installs dependencies, creates `.env`, generates the key, migrates with seed data and links storage.

## 7. Demo login credentials

All demo accounts use the password **`password`**. On local installs the login page also shows one-click buttons that fill in each account.

| Role | Email | Name |
|---|---|---|
| Super Admin (primary, cannot be deleted) | `superadmin@officepro.test` | Olivia Bennett |
| Admin | `admin@officepro.test` | Marcus Reid |
| HR Manager | `hr@officepro.test` | Priya Sharma |
| Manager (IT) | `manager@officepro.test` | James Carter |
| Manager (Sales & Marketing) | `manager2@officepro.test` | Sofia Martinez |
| Employee (IT developer) | `employee@officepro.test` | Daniel Brooks |
| Other employees | `aisha.khan@`, `lucas.weber@`, `emma.thompson@`, `noah.kim@`, `chloe.dubois@`, `ethan.walker@`, `grace.okafor@`, `mateo.rossi@`, `hannah.schmidt@` (all `@officepro.test`) | — |

**Change or remove these accounts before going live.**

## 8. Roles and permissions

Permissions are checked **on the server** by `can:` route middleware, controller `authorize()` calls, Form Request `authorize()` methods, policies (`app/Policies`) and query scopes (`visibleTo()` on the models). Hiding a menu item is only cosmetic.

| Role | Access |
|---|---|
| **Super Admin** | Everything, including roles & permissions, system settings, and managing admins. Always has every permission. |
| **Admin** | All operational modules: employees, departments, attendance, leave, tasks, payroll, expenses, assets, documents, reports, users (not admin roles), activity log. |
| **HR Manager** | Employees, departments, designations, attendance, leave (approve), payroll, documents, reports; can submit expenses. |
| **Manager** | Their team (employees in departments they manage): profiles, attendance, leave approval, assign & review tasks, submit expenses. |
| **Employee** | Self-service: own profile, attendance check-in/out, leave, tasks, approved payslips, visible documents, notifications. |

Super admins can edit the role → permission matrix under **Roles & Permissions**, and changes apply immediately. Other safeguards:

- An admin cannot grant the Admin or Super Admin role, or edit a super admin.
- The primary super admin cannot be deleted, disabled or demoted.
- Nobody can approve their own leave or their own expense (except a super admin for expenses).
- Disabled accounts are signed out on their next request.

## 9. Features

- **Authentication:** login with remember-me, logout, rate limiting, forgot/reset password, optional self-registration (off by default, switchable in Settings), bcrypt hashing, sessions stored in the database.
- **Dashboard:** built entirely from live queries. Greeting, check-in/out, stat cards (total/active employees, present/absent/late/on-leave today, pending leaves and tasks, monthly payroll and expenses), six Chart.js charts, pending leaves, upcoming deadlines, birthdays, expiring documents and recent activity. Employees get a personal version.
- **Employees:** search, filters, sortable columns, pagination; create/edit with photo upload and optional login-account creation; deactivate or soft delete; a profile with **Overview / Attendance / Leaves / Tasks / Payroll / Documents / Activity** tabs.
- **Departments & designations:** CRUD, assign a department manager, headcount and salary cost, delete guards.
- **Attendance:** one-click check-in/out, late detection (start time plus grace minutes), half-day detection, automatic working hours, one record per employee per day (database unique index), manual create/edit by HR, filters (today / week / month / custom range, employee, department, status), period summary, and an `officepro:mark-absent` command.
- **Leave:** five leave types (configurable), working-day calculation, balance tracking and enforcement, overlap prevention, required attachments per type, notifications to manager + HR, approve/reject with a note, cancellation.
- **Tasks:** CRUD, priorities, statuses, progress, comments, activity history, a **drag-and-drop Kanban board**, and team-scoped assignment for managers.
- **Payroll:** monthly generation (basic salary, allowance %, tax %, pro-rata deduction for unpaid leave). Gross = Basic + Allowances + Overtime + Bonus; Net = Gross − Deductions − Tax − Other deductions. Totals are always recalculated on the server. Edit with a live summary, then approve, bulk-approve, mark paid, view history, and download a **PDF salary slip** (dompdf).
- **Expenses:** CRUD, private receipt upload, approve/reject, filters, category summary, category management.
- **Assets:** register, assign, return (with condition), maintenance log and completion, assignment history, activity trail.
- **Documents:** private uploads with type and size validation, per-employee or company-wide, an "employee visible" flag, download/preview, search and filters, expiry tracking, warnings and a daily alert command.
- **Reports:** Employee, Attendance, Leave, Payroll, Expense, Task, Asset and Document reports, filtered by date, department, employee and status, exported as **PDF** or **CSV** (Excel-friendly, protected against formula injection) or printed.
- **Notifications:** in-app database notifications with an unread badge, mark as read, mark all as read, and optional e-mail per type (user preference).
- **Activity log:** user, action, module, record, description, IP and time, with filters.
- **Global search:** live dropdown (Ctrl/⌘ K) and a full results page grouped by Employees, Tasks, Departments, Documents and Assets, all permission-aware.
- **Settings:** company profile and logo, timezone, date format, currency, working hours, late grace, document expiry window, registration toggle. Settings are cached.
- **UI:** reusable Blade components (`x-card`, `x-stat-card`, `x-status-badge`, `x-alert`, `x-modal`, `x-button`, `x-delete-button`, `x-table`, `x-form.*`, `x-page-header`, `x-breadcrumb`, `x-avatar`, `x-progress`, `x-chart`, custom pagination), a collapsible sidebar, mobile off-canvas navigation, tables that stack into cards on phones, and dark mode saved to localStorage and the user profile.
- **Error pages:** friendly 403, 404, 419, 429, 500 and 503 pages. With `APP_DEBUG=false`, no stack traces are shown.

## 10. Project structure

```
app/
  Console/Commands/        officepro:task-deadlines, officepro:document-expiry, officepro:mark-absent
  Http/Controllers/        one controller per module (+ Auth/)
  Http/Middleware/         EnsureAccountIsActive, SecurityHeaders
  Http/Requests/           Form Requests with validation + authorisation
  Models/                  Eloquent models with relationships and visibleTo() scopes
  Notifications/           AppNotification base + 7 notification types
  Policies/                Employee, Attendance, Leave, Task, Payroll, Expense, Document, User
  Services/                LeaveBalanceService, ReportService
  Support/                 Permissions registry, Settings (cached), ActivityLogger
  helpers.php              setting(), money(), fmt_date(), fmt_time(), label(), activity()
database/
  migrations/              all tables, foreign keys, indexes, soft deletes
  seeders/                 RolePermission, ReferenceData, DemoData (demo), Production
resources/views/
  layouts/ partials/ components/ auth/ errors/
  dashboard/ employees/ departments/ designations/ attendance/ leaves/ leave-types/
  tasks/ payroll/ expenses/ expense-categories/ assets/ documents/ reports/
  notifications/ activity-logs/ users/ roles/ settings/ profile/ search/
public/
  css/app.css  js/app.js  js/charts.js  images/  vendor/ (Bootstrap, Bootstrap Icons, Chart.js)
routes/web.php  routes/console.php
tests/Unit  tests/Feature
```

The web controllers call models, services and policies, which have no web-specific code. A JSON API for mobile apps can reuse them from `routes/api.php`.

## 11. Database tables

`users`, `roles`, `permissions`, `permission_role`, `employees`, `departments`, `designations`, `attendances`, `leave_types`, `leaves`, `tasks`, `task_comments`, `payrolls`, `expenses`, `expense_categories`, `assets`, `asset_assignments`, `asset_maintenances`, `documents`, `notifications`, `activity_logs`, `settings`, plus Laravel's `password_reset_tokens`, `sessions`, `cache`, `jobs` and `failed_jobs`.

Key relationships:

- Department `hasMany` Employees and `belongsTo` a manager (Employee).
- Employee `belongsTo` Department, Designation and User, and `hasMany` Attendances, Leaves, Tasks (`assigned_to`), Payrolls, Documents and Assets.
- Task `belongsTo` an Employee (assignee) and a User (creator).
- Leave and Payroll `belongsTo` Employee.
- Asset `belongsTo` Employee when assigned.

Unique indexes: `attendances(employee_id, date)` and `payrolls(employee_id, period)`. Soft deletes are used on users, employees, departments, leaves, tasks, expenses, assets and documents.

## 12. Main routes

| Area | Routes |
|---|---|
| Auth | `GET/POST /login`, `POST /logout`, `/register`, `/forgot-password`, `/reset-password/{token}` |
| Dashboard / search | `GET /`, `GET /search?q=` (HTML or JSON) |
| Employees | `resource /employees`, `POST /employees/{id}/deactivate` |
| Org | `resource /departments`, `resource /designations` |
| Attendance | `GET /attendance`, `POST /attendance/check-in`, `POST /attendance/check-out`, create/edit/update/delete |
| Leave | `GET/POST /leaves`, `GET /leaves/{id}`, `POST /leaves/{id}/approve / reject / cancel`, `GET /leaves/{id}/attachment`, `resource /leave-types` |
| Tasks | `resource /tasks`, `GET /tasks/board`, `PATCH /tasks/{id}/status`, `POST /tasks/{id}/comments` |
| Payroll | `GET /payroll`, `GET/POST /payroll/generate`, `POST /payroll/bulk-approve`, `GET/PUT/DELETE /payroll/{id}`, `POST /payroll/{id}/approve`, `POST /payroll/{id}/pay`, `GET /payroll/{id}/slip` |
| Expenses | `resource /expenses`, `POST /expenses/{id}/approve / reject`, `GET /expenses/{id}/receipt`, `resource /expense-categories` |
| Assets | `resource /assets`, `POST /assets/{id}/assign / return / maintenance`, `POST /assets/{id}/maintenance/{m}/complete` |
| Documents | `resource /documents`, `GET /documents/{id}/download` |
| Reports | `GET /reports`, `GET /reports/{type}`, `GET /reports/{type}/export/{csv\|pdf}` |
| System | `/notifications`, `/activity-logs`, `resource /users`, `/roles`, `/settings`, `/profile` |

Run `php artisan route:list --except-vendor` for the full list (136 routes).

## 13. Scheduled jobs and queues

Defined in `routes/console.php`. Run `php artisan schedule:work` for local development, or add this cron entry in production:

```
* * * * * cd /path/to/officepro-erp && php artisan schedule:run >> /dev/null 2>&1
```

| Command | Schedule | Purpose |
|---|---|---|
| `officepro:task-deadlines` | daily 08:00 | Notify assignees of tasks due within a day or overdue (once per task) |
| `officepro:document-expiry` | daily 08:15 | Alert document managers and the owner about expiring documents |
| `officepro:mark-absent {date?}` | weekdays 23:30 | Create *absent*/*leave* attendance records for employees who did not check in |

Notifications are queued (`ShouldQueue`). With `QUEUE_CONNECTION=sync` they are sent immediately. With `database`, run `php artisan queue:work`.

## 14. Testing

```bash
php artisan test
```

There are 77 tests with about 460 assertions: unit tests for the payroll, hours and working-day calculations, and feature tests for authentication, role permissions, employees, departments, attendance, leave workflow, tasks and the Kanban API, payroll and PDF slips, expenses and receipts, assets, private documents, reports (CSV/PDF), search, notifications, settings, error pages, and a full demo-data smoke test across roles.

By default the tests use in-memory SQLite. To run them against MySQL:

```bash
DB_CONNECTION=mysql DB_DATABASE=officepro_test DB_USERNAME=root DB_PASSWORD= php artisan test
```

All 77 tests pass on **PHP 8.3** (see [PHP version pinning](#php-version-pinning)) against both SQLite and MySQL, and also on PHP 8.4.

## 15. Troubleshooting

| Problem | Fix |
|---|---|
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL isn't running, or `DB_HOST`/`DB_PORT` are wrong. |
| `SQLSTATE[HY000] [1049] Unknown database` | Create the database (see section 4). |
| `No application encryption key has been specified` | `php artisan key:generate` |
| Profile photos / logo not showing | `php artisan storage:link`, and check that `APP_URL` matches the URL you browse. |
| 419 "Session expired" after a long idle period | Refresh and sign in again. The CSRF token expired with the session (`SESSION_LIFETIME`). |
| Permission denied writing logs/cache | `chmod -R 775 storage bootstrap/cache` (and chown to the web server user). |
| Password-reset e-mail doesn't arrive | With `MAIL_MAILER=log`, the e-mail (including the link) is in `storage/logs/laravel.log`. Configure SMTP for real delivery. |
| Notifications not appearing | With `QUEUE_CONNECTION=database`, run `php artisan queue:work`. |
| Changed settings not applied | `php artisan cache:clear` (settings are cached; saving in the UI clears them automatically). |
| Charts or icons missing | Make sure `public/vendor` was deployed. There is no npm build step. |
| Fonts look slightly different offline | The Inter font loads from Google Fonts and falls back to the system font. |

## 16. Known limitations

- Attendance is web-based check-in/out. There is no biometric device or geo-fencing integration.
- Leave balances are a yearly allowance per type. Carry-forward, accrual and public-holiday calendars are not modelled; weekends (Sat/Sun) are excluded.
- Payroll uses a flat configurable tax percentage, not country-specific tax tables.
- The "Excel" export is UTF-8 CSV, which opens in Excel. Native `.xlsx` is not generated.
- There is no REST API yet. The service/policy layer is ready for one.
- The UI is English only.
