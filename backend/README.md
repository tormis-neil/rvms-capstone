# RVMS Backend (Laravel 11)

REST API (`/api/v1`, Sanctum bearer tokens) + Blade admin dashboard for the Rescue Vehicle
Management System. This is the running system: the Android driver app and the web dashboard
are two front doors onto this one database.

See the repo root `CLAUDE.md` and `skills/rvms-source-of-truth.md` for scope, requirements
(FR-01–FR-21, NFR-01–NFR-05), and the approved data model.

## Setup

```bash
cd backend
composer install
cp .env.example .env          # then set DB_USERNAME / DB_PASSWORD for your MySQL
php artisan key:generate
php artisan migrate --seed
php artisan storage:link      # without this every damage photo 404s
php artisan serve             # http://127.0.0.1:8000
```

Two settings in `.env` matter more than they look:

- **`APP_TIMEZONE=Asia/Manila`** — the agencies work in Philippine time. Under the framework's
  UTC default, anything recorded before 8:00 AM Manila is filed on the *previous* day, so a
  7:30 AM inspection reads "Yesterday".
- **`APP_DEBUG=false`** on any machine an agency can reach — debug pages print database
  credentials in their stack traces.

> **`php artisan migrate` is the routine command after a code update.** It applies new
> migrations and keeps every existing record. `migrate:fresh --seed` **drops every table** and
> erases the accounts and records you created by hand — reach for it only when you deliberately
> want a clean slate.

## Running the full feature set

Three processes, not one. The server alone gives you the screens; the other two are what make
notifications actually arrive:

```bash
php artisan serve             # the dashboard and the API
php artisan queue:work        # sends the queued FCM pushes (FR-21)
php artisan schedule:work     # dev stand-in for cron: licence + PM alerts
```

**On a deployed server**, `schedule:work` is replaced by ONE crontab entry, added once:

```
* * * * * cd /path/to/rvms/backend && php artisan schedule:run >> /dev/null 2>&1
```

Windows: a Task Scheduler task running `php artisan schedule:run` every minute, starting in the
backend folder. Without it the time-driven alerts (an expiring licence, a PM schedule coming
due) never fire — the single easiest way to hand over a system that looks complete and silently
isn't. `php artisan rvms:doctor` checks for exactly this class of omission.

## Custom commands

| Command | What it does |
|---|---|
| `php artisan rvms:doctor` | Checks that this deployment is complete and safe to hand over — timezone, debug mode, storage link, queue backlog, scheduler registration, admin accounts, seeded demo passwords. Exits non-zero on failure |
| `php artisan rvms:fcm-doctor` | Diagnoses the Firebase setup step by step and can send a test push |
| `php artisan rvms:recalculate-pm` | Recomputes Upcoming / Due Soon / Due for active PM schedules (daily 01:00) |
| `php artisan rvms:pm-alerts` | Notifies admins and drivers of Due Soon / Due maintenance (daily 01:15) |
| `php artisan rvms:license-alerts` | Notifies admins of expiring and expired driver licences (daily 06:00) |

The three scheduled commands are also called directly by the controllers for the single record
an admin just created, so a schedule entered already-due alerts immediately instead of waiting
for tomorrow's sweep. Both paths go through `App\Services\MaintenanceAlerts`, which owns the
idempotency rules, so they can never double-alert.

## Tests

```bash
php artisan test
```

**528 passing** (11 skipped — checks that depend on `storage:link` being present in the
checkout). The suite runs against an in-memory SQLite database configured in `phpunit.xml`, so
no MySQL is needed. Beyond the per-module feature tests it includes:

- `AgencyIsolationSweepTest` — parameterised proof that **every** list/show/update/delete
  endpoint blocks cross-agency access (FR-02)
- `PaginationAndQueryCountTest` — pagination on every growing list, with query-count assertions
  against N+1 regressions
- `LoginThrottleTest`, `DoctorCommandTest`, `QueueResilienceTest` — rate limiting, the
  deployment check, and retry/idempotency behaviour
- `TableColumnAlignmentTest`, `DashboardScriptOrderTest` — guards on the two prototype-fidelity
  bugs that HTTP-level tests cannot see

## Seeded accounts

Password is `password` for every seeded account.

| Role | Email |
|---|---|
| BFP admin | `bfp.admin@rvms.local` |
| BFP deputy admin (2nd admin sample) | `bfp.admin2@rvms.local` |
| PNP admin | `pnp.admin@rvms.local` |
| CDRRMO admin | `cdrrmo.admin@rvms.local` |
| CHO admin | `cho.admin@rvms.local` |
| Sample drivers (2 per agency) | e.g. `ramon.villanueva@rvms.local` |

An agency may have more than one administrator (per the interviews — e.g. logistics and
operations officers); the BFP deputy admin demonstrates this, and notifications target **all**
of an agency's admins. Admins sign in at `/login`; drivers authenticate via
`POST /api/v1/login` from the mobile app and are refused on the web.

Change these passwords before any machine an agency can reach — `rvms:doctor` flags accounts
still using the seeded demo password.

## Admin dashboard (session auth, admins only)

| Route | Screen |
|---|---|
| `/login` · `/logout` | Sign in / out |
| `/dashboard` | 8 readiness counters + Action Required lists (FR-19) |
| `/vehicles` | Fleet records + status updates (FR-05, FR-18) |
| `/drivers` | Drivers, licence monitoring, access requests (FR-06, FR-08, FR-03) |
| `/inspections` | BLOWBAGETS review + damage review (FR-10, FR-12) |
| `/repairs` | Repair logs (FR-13) |
| `/pm` | Preventive-maintenance schedules (FR-14) |
| `/dispatch` | Dispatch logs + availability (FR-15, FR-16, FR-17) |
| `/notifications` | Inbox, mark read, clear read (FR-21) |
| `/reports` | Six printable report types (FR-20) |
| `/profile` | Own account; agency information read-only (FR-04) |

## API surface (52 routes)

All routes are prefixed `/api/v1/` and, except where noted, require
`Authorization: Bearer <token>`.

**Auth and profile**

| Method | URI | Auth | Purpose |
|---|---|---|---|
| POST | `/login` | public | Issue a Sanctum token (active accounts only) |
| POST | `/register` | public | Driver self-registration → `pending` (FR-03) |
| POST | `/logout` | token | Revoke the current token |
| GET | `/me` | token | Authenticated user + agency |
| PATCH | `/me/profile` | token | Self-edit name / email / password (FR-04) |
| GET | `/agencies` | public | Agency list for the sign-up screen |

**Driver-facing**

| Method | URI | Purpose |
|---|---|---|
| GET | `/my-vehicle` | The driver's assigned vehicle(s) and live status (FR-07) |
| GET | `/inspections/checklist` | 14 items for a BFP driver, 12 otherwise (FR-09) |
| POST | `/inspections` | Submit the daily checklist; remarks required on any flagged item |
| GET | `/inspections`, `/inspections/{id}` | Own submission history |
| POST | `/damage-reports` | File a report, photo optional (multipart) (FR-11) |
| GET | `/damage-reports`, `/damage-reports/{id}` | Own reports |
| POST · DELETE | `/fcm-token` | Register / clear this device for push (FR-21) |
| GET | `/notifications` · PATCH `/{id}/read` · PATCH `/read-all` | Alerts inbox |

**Admin-facing**

| Module | Routes |
|---|---|
| Vehicles | `GET/POST /vehicles` · `GET/PUT /vehicles/{id}` · `PATCH /vehicles/{id}/status` · `GET /vehicles/availability` |
| Drivers | `GET/POST /drivers` · `GET/PUT /drivers/{id}` · `PATCH /drivers/{id}/approve` · `/reject` · `/license` · `GET /licenses/monitoring` |
| Inspections | `GET /inspections` · `GET /inspections/frequent-issues` · `PATCH /inspections/{id}/review` |
| Damage | `GET /damage-reports` · `PATCH /damage-reports/{id}/review` |
| Repairs | `GET/POST /repairs` · `GET/PUT /repairs/{id}` |
| PM | `GET/POST /pm-schedules` · `GET/PUT /pm-schedules/{id}` · `PATCH /pm-schedules/{id}/complete` |
| Dispatch | `GET/POST /dispatches` · `GET/PUT /dispatches/{id}` · `PATCH /dispatches/{id}/close` |
| Reporting | `GET /dashboard/summary` · `GET /reports/{type}` (6 types) |

Status codes: **200/201** success · **401** unauthenticated · **403** wrong role or non-active
account · **404** missing *or another agency's* record (404 rather than 403 on purpose — a 403
would confirm the record exists) · **422** validation · **429** rate limited (login and report
generation).

## Two rules worth knowing before you change anything

1. **Every vehicle status write goes through `App\Services\VehicleStatusWriter`** — never
   `$vehicle->update(['status' => ...])`. The writer enforces that a vehicle on an active
   dispatch cannot be re-statused by another module, stamps where the change came from, and
   notifies the assigned driver.
2. **`public/assets/css/style.css` is a verbatim copy of the prototype stylesheet and is never
   edited.** Every screen is checked side by side against `web/pages/*.html`; unavoidable
   backend-only styling goes in a separate `admin.css`.

## Security

`docs/security-audit.md` records the findings from the R10 audit, what was fixed, what became a
ticket with an effort estimate, and what was checked and found sound.
