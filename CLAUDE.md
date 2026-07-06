# CLAUDE.md

> ⚠️ **ALWAYS READ `skills/rvms-source-of-truth.md` IN FULL BEFORE STARTING ANY TASK.**
> It is the single source of truth for scope, requirements (FR-01–FR-21, NFR-01–NFR-05),
> workflows, and enumerations. Never invent features, tables, columns, or statuses that
> it does not justify. The prototype lives in `web/` (static Bootstrap dashboard) and
> `mobile/` (Jetpack Compose driver app); `skills/rvms-prototype-plan.md` details workflows.

---

## Project Snapshot

**RVMS (Rescue Vehicle Management System)** — centralized vehicle/driver, inspection,
damage, repair, preventive-maintenance, dispatch, and reporting platform for four
Calbayog City agencies. Capstone, Northwest Samar State University.

- **Two platforms:** (1) Android mobile app for **Authorized Drivers**; (2) web admin
  dashboard for **Agency Administrators** (access limited to their own agency).
- **Four agencies:** BFP, PNP, CDRRMO, CHO.
- **Tech stack:** Laravel 11 (PHP 8.2+) REST API + Blade/Bootstrap 5.3 dashboard ·
  MySQL 8.0+ · Laravel Sanctum (bearer tokens) · Firebase Cloud Messaging (server-side
  PHP, HTTP v1 API) · Android Kotlin 1.9+/Jetpack Compose (separate repo area).
- **Vehicle statuses (exactly four):** `Operational`, `Dispatched`, `Not Operational`,
  `Under Preventive Maintenance` — a single field on the vehicle, written from any module.

---

## Key Development Commands

```bash
php artisan migrate:fresh --seed     # rebuild schema + seed agencies/admins/drivers/checklist + sample vehicles/inspections
php artisan test                     # run PHPUnit feature + unit tests
php artisan route:list --path=api/v1 # verify registered API routes/middleware
php artisan tinker                   # inspect Eloquent records/scopes
php artisan schedule:list            # confirm scheduled PM/license alert jobs
php artisan rvms:recalculate-pm      # recompute PM Due Soon/Due statuses
php artisan rvms:license-alerts      # fire license expiry notifications
php artisan storage:link             # expose uploaded damage-report photos
php artisan queue:work               # process queued FCM sends
```

---

## API Conventions (all endpoints)

- All API routes are prefixed **`/api/v1/`**.
- Auth = **Laravel Sanctum** bearer tokens (`Authorization: Bearer <token>`). Admin
  dashboard Blade pages use the session web guard.
- **Agency scoping is mandatory on every query** (global Eloquent scope + middleware):
  an admin only ever reads/writes their own agency's records. Enforce on list, show,
  create, update, delete.
- Roles: `admin`, `driver`. Role middleware gates every route.
- Status codes: 200/201 success · 401 unauthenticated · 403 wrong role / cross-agency
  · 422 validation errors (descriptive messages) · 404 missing/foreign record.
- Configurable thresholds live in **DB columns**, never hardcoded constants
  (`agencies.license_expiry_warning_days`, `pm_schedules.due_soon_threshold_km/_days`).
- FCM is handled **server-side in PHP** via the HTTP v1 API.

---

# Approved Database Design

A few deliberate modeling decisions:

1. **Drivers are modeled as `users` with `role = 'driver'`** (carrying license fields),
   not a separate table. Drivers must authenticate on the mobile app (FR-01/FR-07), so a
   driver record and a login account are 1:1. Admins are `users` with `role = 'admin'` and
   null license fields. This avoids duplicating name/email/agency across two tables.
2. **`agencies` is a real table**, not an enum column — the profile page edits agency
   name/location/contact/email, and every scoped query needs an FK target (FR-02, NFR-02).
3. **PM status** keeps the three source-of-truth values (Due Soon, Due, Completed) plus
   **`Upcoming`** for the not-yet-due-soon active state that the prototype (`pmActive`)
   surfaces. The due-state is recalculated by a scheduled job from thresholds (plan §6.7),
   not set by hand.
4. **Admin-configurable thresholds are columns**: PM "Due Soon" threshold lives on each
   schedule; the license "approaching expiry" window lives on `agencies` (so it is not a
   hardcoded constant).
5. **Vehicle status is one ENUM of exactly four values** on `vehicles`, written from every
   module (FR-18).
6. **Driver accounts have two creation paths** (FR-03, FR-06): admins add drivers (created
   `active` immediately), or drivers self-register and start `pending` until their agency
   admin approves/rejects them. `users.status` tracks `pending`/`active`/`rejected`. Drivers
   and admins can self-edit their own name/email/password with no approval or notification
   (FR-04). Agency administrator accounts are provisioned (seeded) only; there is no admin self-registration, and the public registration endpoint is driver-only.
7. **Deliberately excluded (objectives audit, July 2026 — do not add):** no admin-remarks
   columns on vehicle status changes or inspection/damage reviews; no passenger/patient
   fields on dispatches (privacy + outside vehicle-management scope); no agency-info
   editing feature (no FR backs it). A driver MAY be the primary driver of more than one
   vehicle (Ch4 ERD); each vehicle still has at most one primary driver.

## ERD PLAN

### Tables (13 domain tables + framework tables)

```
agencies ──┬─< users ─────────────┐
           ├─< vehicles ───────────┤ (vehicles.assigned_driver_id → users.id)
           ├─< inspections          
           ├─< damage_reports       
           ├─< repair_logs          
           ├─< pm_schedules         
           ├─< dispatches           
           └─< notifications        

vehicles ──┬─< inspections ──< inspection_items >── inspection_checklist_items
           ├─< damage_reports
           ├─< repair_logs
           ├─< pm_schedules
           └─< dispatches

users (driver) ──< inspections / damage_reports / repair_logs / dispatches   (as driver_id)
users (admin)  ──< inspections.reviewed_by / damage_reports.reviewed_by
users          ──< notifications  (recipient)
```

### Relationships

| Relationship | Type | Mechanism |
|---|---|---|
| agency → users | one-to-many | `users.agency_id` |
| agency → vehicles | one-to-many | `vehicles.agency_id` |
| agency → inspections / damage_reports / repair_logs / pm_schedules / dispatches / notifications | one-to-many | `agency_id` on each (enforces FR-02 scoping) |
| driver (user) → vehicle(s) | one-to-many (each vehicle has at most one primary driver; a driver may be the primary driver of more than one vehicle, per Ch4 ERD) | `vehicles.assigned_driver_id` |
| vehicle → inspections / damage_reports / repair_logs / pm_schedules / dispatches | one-to-many | `vehicle_id` on each |
| driver (user) → inspections / damage_reports / repair_logs / dispatches | one-to-many | `driver_id` on each |
| admin (user) → reviewed inspections / damage_reports | one-to-many | `reviewed_by` |
| inspection → inspection_items | one-to-many | `inspection_items.inspection_id` |
| checklist item (catalog) → inspection_items | one-to-many | `inspection_items.checklist_item_id` |
| user → notifications | one-to-many | `notifications.user_id` |

**Framework tables (Laravel/Sanctum):** `personal_access_tokens` (NFR-02 token auth),
`password_reset_tokens`, `jobs`/`failed_jobs` (queued FCM sends, FR-21), `cache`. These are
standard and not detailed below.

## DATA DICTIONARY

> Conventions: every `id` is `BIGINT UNSIGNED, AUTO_INCREMENT, PK`. Every FK is
> `BIGINT UNSIGNED`. `created_at`/`updated_at` are nullable `TIMESTAMP`. Every scoped table
> is indexed on `agency_id`. FR/NFR codes in the per-table heading.

### `agencies` — FR-02
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| code | VARCHAR(10) | No | — | Short code: BFP, PNP, CDRRMO, CHO. Unique. Drives BFP-only checklist rule. |
| name | VARCHAR(255) | No | — | Full agency name (e.g., "Bureau of Fire Protection"). |
| location | VARCHAR(255) | Yes | NULL | City/office location shown on profile page. |
| contact_number | VARCHAR(50) | Yes | NULL | Agency contact number (profile page). |
| email | VARCHAR(255) | Yes | NULL | Agency contact email / domain basis (profile page). |
| logo_path | VARCHAR(255) | Yes | NULL | Path to agency logo asset used in dashboard chrome. |
| license_expiry_warning_days | SMALLINT UNSIGNED | No | 30 | **Configurable threshold** (FR-08): days before expiry a license is flagged "Expiring Soon". Stored as a column, not a constant. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `users` — FR-01, FR-02, FR-03, FR-04, FR-06, FR-08, FR-21
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies. Scopes the user to one agency (FR-02). |
| role | ENUM('admin','driver') | No | — | Authorization role; routes admin→web, driver→mobile (FR-01). |
| name | VARCHAR(255) | No | — | Full name of admin or driver (FR-06, self-editable FR-04). |
| email | VARCHAR(255) | No | — | Login identifier; unique (FR-01, self-editable FR-04). |
| password | VARCHAR(255) | No | — | Bcrypt/Argon hash (NFR-02, self-editable FR-04). |
| status | ENUM('pending','active','rejected') | No | 'active' | Account state (FR-03). Admin-added drivers and admins are 'active'; self-registered drivers start 'pending' until an admin approves. Only 'active' users can log in. |
| license_number | VARCHAR(50) | Yes | NULL | Driver license no. (FR-06). Null for admins. |
| license_expiry_date | DATE | Yes | NULL | Driver license expiry; drives FR-08 monitoring. Null for admins. |
| fcm_token | VARCHAR(255) | Yes | NULL | Firebase device token for push delivery (FR-21). |
| email_verified_at | TIMESTAMP | Yes | NULL | Framework field. |
| remember_token | VARCHAR(100) | Yes | NULL | Framework field. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `vehicles` — FR-05, FR-07, FR-17, FR-18
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (FR-02 scoping). |
| assigned_driver_id | BIGINT UNSIGNED | Yes | NULL | FK → users (role=driver). Primary assigned driver (FR-05). |
| type | VARCHAR(100) | No | — | Vehicle type (Fire Truck, Ambulance, Patrol Car…). |
| plate_number | VARCHAR(20) | No | — | Plate number; unique per agency. |
| make | VARCHAR(100) | No | — | Manufacturer (Isuzu, Toyota…). |
| model | VARCHAR(100) | No | — | Model (FTR 850, Hiace…). |
| engine_number | VARCHAR(50) | Yes | NULL | Engine number (FR-05). |
| chassis_number | VARCHAR(50) | Yes | NULL | Chassis number (FR-05). |
| current_mileage | INT UNSIGNED | No | 0 | Current odometer (km); manually updated, drives mileage-based PM (FR-14). |
| status | ENUM('Operational','Dispatched','Not Operational','Under Preventive Maintenance') | No | 'Operational' | **Single shared operational status** (FR-18), written from every module. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `inspection_checklist_items` — FR-09 (reference/seed catalog)
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| name | VARCHAR(100) | No | — | Item label (Battery, Lights, …, Hydraulic System, Fire Pump). |
| is_bfp_only | TINYINT(1) | No | 0 | 1 for the two BFP-only items (Hydraulic System, Fire Pump). |
| sort_order | SMALLINT UNSIGNED | No | 0 | Display order on the checklist. |

### `inspections` — FR-09, FR-10
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles (history per vehicle). |
| driver_id | BIGINT UNSIGNED | No | — | FK → users (history per driver). |
| inspection_date | DATE | No | — | Date of the daily inspection. |
| review_status | ENUM('Pending','Reviewed') | No | 'Pending' | Admin review state (prototype `review`; FR-10). |
| reviewed_by | BIGINT UNSIGNED | Yes | NULL | FK → users (admin who assessed it). |
| reviewed_at | DATETIME | Yes | NULL | When reviewed. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | `created_at` = submission time. |

### `inspection_items` — FR-09
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| inspection_id | BIGINT UNSIGNED | No | — | FK → inspections. |
| checklist_item_id | BIGINT UNSIGNED | No | — | FK → inspection_checklist_items. |
| status | ENUM('OK','Has Issue') | No | — | Per-item result (FR-09). |
| remarks | TEXT | Yes | NULL | Required when status = 'Has Issue' (enforced at validation). |

### `damage_reports` — FR-11, FR-12, FR-19
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles. |
| driver_id | BIGINT UNSIGNED | No | — | FK → users (submitting driver). |
| nature_of_damage | TEXT | No | — | Description of damage (FR-11). |
| suspected_parts | VARCHAR(255) | Yes | NULL | Suspected defective parts (FR-11). |
| photo_path | VARCHAR(255) | Yes | NULL | Optional photo attachment (FR-11). |
| date_reported | DATE | No | — | Auto-set on submission (FR-11). |
| status | ENUM('Pending','Reviewed') | No | 'Pending' | Default Pending → Reviewed by admin (FR-12). |
| reviewed_by | BIGINT UNSIGNED | Yes | NULL | FK → users (admin reviewer). |
| reviewed_at | DATETIME | Yes | NULL | When reviewed. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `repair_logs` — FR-13
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles. |
| driver_id | BIGINT UNSIGNED | Yes | NULL | FK → users (assigned driver) (FR-13). |
| repair_date | DATE | No | — | Date repair was logged/performed. |
| scope_of_work | TEXT | No | — | Work performed (FR-13). |
| parts_replaced | TEXT | Yes | NULL | Parts replaced (FR-13). |
| cost | DECIMAL(10,2) | Yes | NULL | Optional cost (FR-13). |
| repair_source | ENUM('Internal Office','GSO Motorpool','External Repair Shop') | No | — | Source of repair (FR-13). |
| external_shop_name | VARCHAR(255) | Yes | NULL | Shop name when source = External Repair Shop (prototype `shop`). |
| remarks | TEXT | Yes | NULL | Notes (FR-13). |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `pm_schedules` — FR-14, FR-21
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles. |
| service_target | VARCHAR(255) | No | — | Specific part(s)/service (e.g., "Oil Change & Filter") (FR-14). |
| pm_type | ENUM('Mileage-Based','Time-Based') | No | — | Scheduling basis (FR-14). |
| interval_km | INT UNSIGNED | Yes | NULL | Mileage interval (mileage-based only). |
| last_pm_mileage | INT UNSIGNED | Yes | NULL | Odometer at last service (mileage-based). |
| due_mileage | INT UNSIGNED | Yes | NULL | Target mileage = last_pm_mileage + interval_km. |
| due_date | DATE | Yes | NULL | Target date (time-based only). |
| due_soon_threshold_km | INT UNSIGNED | Yes | NULL | **Configurable** Due-Soon window in km (mileage-based) (FR-14). |
| due_soon_threshold_days | SMALLINT UNSIGNED | Yes | NULL | **Configurable** Due-Soon window in days (time-based) (FR-14). |
| status | ENUM('Upcoming','Due Soon','Due','Completed') | No | 'Upcoming' | Recalculated by scheduled job; SoT values Due Soon/Due/Completed + prototype 'Upcoming'. |
| date_serviced | DATE | Yes | NULL | Completion: date serviced (FR-14). |
| completion_repair_source | ENUM('Internal Office','GSO Motorpool','External Repair Shop') | Yes | NULL | Completion: repair source (FR-14). |
| completion_parts_replaced | TEXT | Yes | NULL | Completion: parts replaced (FR-14). |
| completion_remarks | TEXT | Yes | NULL | Completion: remarks (FR-14). |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `dispatches` — FR-15, FR-16, FR-17
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles. |
| driver_id | BIGINT UNSIGNED | No | — | FK → users (dispatched driver). |
| mission_type | ENUM('Fire Response','Medical Response','Rescue Operation','Patrol','Administrative Travel','Others') | No | — | Mission type (FR-15). |
| mission_other | VARCHAR(255) | Yes | NULL | Free text when mission_type = Others (prototype `missionOther`). |
| location | VARCHAR(255) | No | — | Dispatch location (FR-15). |
| time_out | DATETIME | No | — | Date/time out; opening sets vehicle → Dispatched (FR-15). |
| time_in | DATETIME | Yes | NULL | Date/time in on close; NULL = active (FR-16). |
| return_status | ENUM('Operational','Not Operational','Under Preventive Maintenance') | Yes | NULL | Return status chosen on close (FR-16). |
| remarks | TEXT | Yes | NULL | Optional close remarks (plan §6.8). |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

*(Active vs. Completed is derived from `time_in IS NULL`.)*

### `notifications` — FR-21
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| user_id | BIGINT UNSIGNED | No | — | FK → users (recipient). |
| type | ENUM('PM_Reminder','Vehicle_Status_Update','New_Damage_Report','License_Expiring','License_Expired','PM_Due_Soon','PM_Due','New_Access_Request') | No | — | Notification category (FR-21; `New_Access_Request` → admins on driver self-registration, FR-03). |
| title | VARCHAR(255) | No | — | Short headline. |
| message | TEXT | No | — | Body text. |
| data | JSON | Yes | NULL | Reference payload (e.g., vehicle plate, link target). |
| is_read | TINYINT(1) | No | 0 | Read/unread flag. |
| read_at | DATETIME | Yes | NULL | When read. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

The dashboard summary (FR-19) and all reports (FR-20) are computed from these tables — no
separate tables needed.

---

# Approved Backend Development Plan

Phases are ordered by technical dependency (auth/scoping → records → field modules →
cross-cutting notifications → reporting → hardening). Every functional module ships its
`/api/v1/` endpoints (Sanctum bearer) plus its admin Blade dashboard page — **whose UI/UX
must copy the corresponding prototype page in `web/pages/` (see Non-Negotiable Rule 9)** —
and each phase closes with a testing task before the next begins.

---

## Manual Testing Guide — READ THIS ONCE (applies to every phase)

Every phase below has a **"Manual testing (plain language)"** checklist. They all assume the
setup here, so you only have to learn it once. All commands run inside the `backend` folder
in PowerShell (or your terminal).

**A. First-time setup (only once, ever):**
1. `Copy-Item .env.example .env` — makes the app's config file.
2. In MySQL, run `CREATE DATABASE rvms;` (leave it empty).
3. Open `backend\.env` and set `DB_USERNAME` and `DB_PASSWORD` to your MySQL login (for XAMPP
   this is usually username `root` with a blank password). Save.
4. `php artisan key:generate` — sets the app's security key.

**B. Start-of-testing setup (every time you sit down to test a phase):**
1. Make sure MySQL is running.
2. `php artisan migrate:fresh --seed` — wipes and rebuilds all tables with fresh sample data.
   *This is itself a test:* it must finish with green `DONE` lines and no red errors. If it
   errors, stop and report it — nothing else will work until the tables build.
3. `php artisan serve` — starts the app at **http://127.0.0.1:8000**. Leave this window open.
4. Open a **second** terminal (also in `backend`) for the `tinker` / `route:list` commands,
   because the first window is busy running the app.

**C. How to check the API without Postman (nothing to install, works offline):**
Everything here runs on your own computer (localhost), so it does NOT use your internet data — a
phone hotspot is fine. You have three tools, all already on your machine:
- **Browser** — for anything an admin does (all the dashboard pages). This is the main hands-on way
  to test, since admins work in the web dashboard.
- **`php artisan test`** — runs the automated checks that prove the API rules: correct status codes,
  agency isolation (one agency can't see another's data), and input validation. This is the
  reliable, one-command way to verify the API — including driver-only endpoints that don't have a
  screen yet. Each phase points out which behaviors this covers. Green = those rules hold.
- **`php artisan tinker`** — an interactive console to look at the database. Type one line, press
  Enter, read the result; type `exit` to leave.

Optional — if you want to hit an endpoint by hand, use **`curl.exe`** (built into Windows 10/11, no
install, localhost only so no internet needed). Run it in PowerShell:
  1. Log in to get a token:
     `curl.exe -s -X POST http://127.0.0.1:8000/api/v1/login -H "Accept: application/json" -H "Content-Type: application/json" -d "{\"email\":\"bfp.admin@rvms.local\",\"password\":\"password\"}"`
     Copy the long `token` value from the output.
  2. Call an endpoint using that token (example — your own profile):
     `curl.exe -s http://127.0.0.1:8000/api/v1/me -H "Accept: application/json" -H "Authorization: Bearer PASTE_TOKEN_HERE"`
  3. For POST/PATCH, copy the shape of the login command: keep the `-H` lines and put your data
     after `-d`, with a backslash before every quote — e.g. `-d "{\"status\":\"Operational\"}"`.
  Status codes: **200/201** success · **401** not logged in / bad token · **403** logged in but not
  allowed (wrong role or another agency) · **422** invalid input (the message says what).

**D. Common commands used below:**
- `php artisan route:list --path=api/v1` — lists the API endpoints so you can confirm they exist.
- `php artisan test` — runs the automated suite (proves the API rules; see section C).
- `php artisan tinker` — peek at the database (see section C).
- "Check in MySQL" means use phpMyAdmin or MySQL Workbench to look at a table
  (e.g. `DESCRIBE vehicles;` shows its columns).

**Golden rule:** if any step's real result does not match its **Expected** result, stop and
report it with the exact message — don't push past a failing check.

---
PHASE 1: Foundation, Authentication & Agency Scoping
Goal: A running Laravel 11 + MySQL app where any user can log in, receive a Sanctum token carrying their role and agency, and where every query is automatically restricted to the caller's own agency.

Tasks:
  1. Project bootstrap & database config — Foundation, NFR-01
      What gets built: `composer create laravel 11`, install `laravel/sanctum`, configure `.env` MySQL 8 connection, base `config/` + `/api/v1` route group in `routes/api.php`.
  2. Agencies & users schema + seeders — FR-02, Foundation
      What gets built: `agencies` and `users` migrations (role enum, agency_id, license fields, fcm_token, `license_expiry_warning_days`); `Agency`/`User` models; `AgencySeeder` (BFP/PNP/CDRRMO/CHO) + `UserSeeder` (one admin + sample drivers per agency).
  3. Authentication endpoints — FR-01, NFR-02
      What gets built: `AuthController` with `POST /api/v1/login` (returns token + role + agency), `POST /api/v1/logout`, `GET /api/v1/me`; hashed-password verification; `LoginRequest` validation.
  4. Role & agency-scope enforcement layer — FR-02, NFR-02
      What gets built: `EnsureRole` middleware; `BelongsToAgency` trait + global Eloquent scope auto-filtering by `agency_id`; base `Policy` scaffolding; auto-stamp `agency_id` on create.
  5. Admin web login + dashboard shell — FR-01, FR-02, NFR-03
      What gets built: session-guard web login Blade, `layouts/app.blade.php` (Bootstrap 5.3 sidebar/topbar), agency context strip; redirect by role.
  6. Driver self-registration & self-service profile — FR-03, FR-04
      What gets built: public `POST /api/v1/register` (driver selects agency, account created `status='pending'`, login blocked until approved); `PATCH /api/v1/me/profile` (name/email/password self-edit for both roles, no approval/notification); login rejects non-`active` accounts.

Testing task (end of phase):
  Automated — `php artisan test`:
    - `AuthLoginTest`: valid credentials return 200 with `{token, user.role, user.agency}`; bad password returns 422; missing email/password returns 422 with descriptive errors; a `pending` driver cannot log in.
    - `RegisterTest`: self-registration creates a `pending` driver (201); duplicate email 422; `ProfileTest`: a user updates own name/email/password (200) and cannot edit another user.
    - `AuthMeTest`: `GET /api/v1/me` returns 200 + correct user with a valid token; returns 401 without a token.
    - `AuthLogoutTest`: token is revoked (200), and a reused revoked token returns 401.
    - `AgencyScopeUnitTest` (unit): the global scope adds an `agency_id` filter to a model query for an admin; `EnsureRole` rejects a driver token on an admin-only route (403).
  Manual testing checklist (plain language):
    Seeded logins (password is `password` for everyone): admins bfp.admin@rvms.local,
    pnp.admin@rvms.local, cdrrmo.admin@rvms.local, cho.admin@rvms.local; sample drivers
    like ramon.villanueva@rvms.local.

    Setup (every time):
      [ ] `php artisan migrate:fresh --seed`  → fresh sample data (clean, known starting point).
      [ ] `php artisan serve`  → app at http://127.0.0.1:8000 (leave this window open).

    Commands to run — and why they matter:
      [ ] `php artisan route:list --path=api/v1`  → lists 5 routes (login, register, logout,
          me, me/profile).  Why: confirms the login/account endpoints exist.
      [ ] `php artisan test`  → all green.  Why: one command proves the login rules — a correct
          password returns a token; a wrong password or empty fields are rejected (422); /me
          needs a valid token (401 without it); logout kills the token; a pending driver can't
          log in (403).

    What must work (browser, as the BFP admin):
      [ ] Open http://127.0.0.1:8000  → it redirects to a login page.
          Why: pages are protected — you can't reach them while logged out.
      [ ] Log in as the BFP admin  → dashboard shows "Bureau of Fire Protection".
          Why: login works and the account carries its agency.
      [ ] Sign out, log in as the CHO admin  → the top now shows "City Health Office".
          Why: each admin sees only their OWN agency — the core privacy rule (FR-02).
      [ ] Sign out, try to log in with a driver account  → refused ("use the mobile app").
          Why: the web dashboard is admin-only; drivers use the phone app.
      [ ] Signed out, type /dashboard in the address bar  → bounced back to login.
          Why: nobody reaches the dashboard without logging in.

    Look at the saved data (optional — `php artisan tinker`, then `exit`):
      [ ] `App\Models\Agency::pluck('code')`  → the 4 codes (BFP, PNP, CDRRMO, CHO).
      [ ] `App\Models\User::count()`  → 12.
      [ ] `App\Models\User::first()->password`  → a scrambled `$2y$...` hash (never plain text).
          Why: passwords are stored safely, not readable.

---
PHASE 2: Vehicle & Driver Records + License Monitoring
Goal: Admins can fully manage their agency's vehicles and drivers, a driver can view their assigned vehicle, and expiring/expired licenses are detected against the configurable threshold.

Tasks:
  1. Vehicles schema + model — FR-05, FR-18
      What gets built: `vehicles` migration (status enum = the four values, `assigned_driver_id`, mileage, engine/chassis); `Vehicle` model with `agency()`, `assignedDriver()` relations + `BelongsToAgency`.
  2. Vehicle CRUD + status API — FR-05, FR-18
      What gets built: `VehicleController` → `GET/POST/PUT /api/v1/vehicles`, `GET /api/v1/vehicles/{id}`, `PATCH /api/v1/vehicles/{id}/status`; `VehicleRequest` validation; `VehicleResource`.
  3. Driver records API + access-request approval — FR-03, FR-06
      What gets built: `DriverController` (users where role=driver) → `GET/POST/PUT /api/v1/drivers` (admin-added drivers created `active`), `GET /api/v1/drivers/{id}`, `GET /api/v1/drivers?status=pending` (access requests), `PATCH /api/v1/drivers/{id}/approve` and `/reject`; `DriverRequest` (license number/expiry).
  4. Assigned-vehicle viewing for drivers — FR-07
      What gets built: `GET /api/v1/my-vehicle` returning the driver's assigned vehicle details + current status (driver token only).
  5. License expiry monitoring — FR-08
      What gets built: `User` query scopes `expiringSoon()`/`expired()` using `agencies.license_expiry_warning_days`; `GET /api/v1/licenses/monitoring` consolidated view.
  6. Admin Blade pages: Vehicles & Drivers — FR-05, FR-06, NFR-03
      What gets built: `vehicles.blade.php`, `drivers.blade.php` (tables, add/edit/update-status modals).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `VehicleApiTest`: list/create/update/show return correct codes + shapes; `PATCH status` only accepts the four enum values (422 otherwise); unauthenticated 401; driver token 403; **admin from Agency A cannot GET/PUT/DELETE a vehicle owned by Agency B (404/403)**; invalid payload 422.
    - `DriverApiTest`: same five-assertion matrix (success shape, 401, 403, cross-agency isolation, 422 on bad license/expiry); admin-added driver is `active`; approve/reject flips a `pending` driver's status; admin cannot approve another agency's pending driver (404/403).
    - `MyVehicleApiTest`: driver gets their own vehicle (200); an admin token is rejected (403); a driver cannot see another agency's vehicle.
    - `LicenseMonitoringUnitTest` (unit): `expiringSoon()`/`expired()` scopes classify dates correctly around the threshold boundary; monitoring endpoint returns only the caller's agency.
  Manual testing checklist (plain language):
    Setup (every time):
      [ ] `php artisan migrate:fresh --seed`  → fresh sample data.
      [ ] `php artisan serve`  → app at http://127.0.0.1:8000 (leave it running).

    Commands to run — and why they matter:
      [ ] `php artisan route:list --path=api/v1`  → shows the vehicles, drivers, my-vehicle, and
          licenses/monitoring routes.  Why: confirms the new endpoints are wired up.
      [ ] `php artisan test`  → all green.  Why: proves the security + validation rules you can't
          easily see by clicking — a made-up vehicle status is rejected (422); an empty plate or
          negative mileage is rejected (422); a driver or no token is refused on admin routes
          (403/401); the driver-only my-vehicle returns just that driver's vehicle and refuses an
          admin (403); and — most important — one agency cannot read or edit another agency's
          vehicle or driver (blocked, 404/403).

    What must work (browser, as the BFP admin):
      [ ] Open Vehicles, click Add Vehicle (plate, type, make, model, mileage)  → it saves and
          appears in the table.  Why: admins can build their fleet record (FR-05).
      [ ] Edit that vehicle, then use Status to change it (e.g. Dispatched)  → the colored badge
          changes.  Why: the single shared vehicle status can be updated (FR-18).
      [ ] Open Drivers  → the pending sign-up shows under "Access Requests"; click Approve  →
          it becomes Active.  Why: admins approve driver self-registrations (FR-03).
      [ ] Click Add Driver (name, email, password, license)  → saves as Active immediately.
          Why: admin-added drivers don't need approval (FR-06).
      [ ] Sign out, log in as the CHO admin, open Vehicles/Drivers  → you do NOT see the BFP
          records.  Why: agencies can't see each other's data (FR-02) — the key security check.

    License monitoring (no screen yet — check by data):
      [ ] `php artisan tinker` → `$u = App\Models\User::where('role','driver')->first();`
          `$u->license_expiry_date = now()->addDays(5); $u->save(); exit`
      [ ] Then GET /api/v1/licenses/monitoring (curl pattern, guide section C, as that agency's
          admin)  → that driver appears under "expiring_soon".
          Why: expiring licenses are detected against each agency's configurable warning window (FR-08).

---
PHASE 3: Digital BLOWBAGETS Inspection
Goal: Drivers submit daily inspections (12 standard items, +2 for BFP) with remarks required on flagged items, and admins review submissions, browse per-vehicle/per-driver history, and see frequently reported issues.

Tasks:
  1. Checklist catalog + seeder — FR-09
      What gets built: `inspection_checklist_items` migration/model + seeder (12 standard + Hydraulic System/Fire Pump as `is_bfp_only`); `GET /api/v1/inspections/checklist` returning the correct list for the driver's agency.
  2. Inspection submission API (driver) — FR-09
      What gets built: `inspections` + `inspection_items` migrations/models; `POST /api/v1/inspections` (`InspectionRequest` enforces OK/Has Issue per item and required remarks when Has Issue).
  3. Inspection monitoring API (admin) — FR-10
      What gets built: `GET /api/v1/inspections` (filters: vehicle, driver, date), `GET /api/v1/inspections/{id}`, `GET /api/v1/inspections/frequent-issues` (grouped issue counts).
  4. Inspection review + status update — FR-10, FR-18
      What gets built: `PATCH /api/v1/inspections/{id}/review` (set Reviewed, optional vehicle status change).
  5. Admin Blade page: Inspections — FR-10, NFR-03
      What gets built: `inspections.blade.php` (results table, view-checklist + review-&-assess modals, BFP extra-items section).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `InspectionSubmitTest`: valid submission returns 201 + stored items; **Has Issue without remarks returns 422**; unauthenticated 401; admin token submitting returns 403; a BFP driver's checklist includes 14 items while others get 12.
    - `InspectionMonitoringTest`: admin lists/filters and reviews (200); driver token 403; **Agency A admin cannot read/review Agency B inspections (404/403)**; bad filter input 422.
    - `InspectionUnitTest` (unit): `resultLabel`/`issueCount` helper and `frequentIssues` aggregation compute correctly; `inspectionItemsFor(agency)` returns the BFP-extended list only for BFP.
  Manual testing checklist (plain language):
    Note: submitting an inspection is a DRIVER action, so it has no dashboard screen until the
    mobile app is built — those rules are checked by `php artisan test` (and optionally curl).
    The admin review side IS a screen you can click.

    Setup (every time):
      [ ] `php artisan migrate:fresh --seed`  → fresh sample data.
      [ ] `php artisan serve`  → app at http://127.0.0.1:8000 (leave it running).

    Commands to run — and why they matter:
      [ ] `php artisan route:list --path=api/v1/inspections`  → shows the inspection routes.
      [ ] `php artisan test`  → all green.  Why: proves the driver-side rules with no screen yet —
          a BFP driver's checklist has 14 items while other agencies get 12; a valid inspection
          saves (201); a "Has Issue" item with no remarks is rejected (422); an admin submitting is
          refused (403) and no token is 401; and one agency can't read/review another's inspection.

    What must work (browser, as the BFP admin — Inspections page):
      [ ] The list shows only your agency's submitted inspections.
          Why: agency isolation (FR-02).
      [ ] Open one  → you see its checklist; for BFP the two extra items (Hydraulic System,
          Fire Pump) appear.  Why: BFP trucks have 14 items, others 12 (FR-09).
      [ ] Mark it Reviewed  → its status changes.  Why: admins assess submissions (FR-10).
      [ ] The "frequently reported issues" area reflects the flagged items.
          Why: admins can spot recurring problems (FR-10).

    Look at the saved data (optional — `php artisan tinker`):
      [ ] `App\Models\Inspection::with('items.checklistItem')->latest()->first()`  → shows the
          item rows with OK / Has Issue (a BFP inspection has 14 item rows).

---
PHASE 4: Damage Reporting & Repair Logging
Goal: Drivers file damage reports with optional photos, admins review them and update vehicle status, and admins log repair activities with source/parts/cost and update status afterward.

Tasks:
  1. Damage reports schema + photo storage — FR-11
      What gets built: `damage_reports` migration/model; `storage` photo handling (`php artisan storage:link`); `DamageReport` relations + scope.
  2. Damage submission & listing API — FR-11
      What gets built: `POST /api/v1/damage-reports` (multipart photo, date auto-set, status Pending), `GET /api/v1/damage-reports`, `GET /api/v1/damage-reports/{id}` (driver sees own; admin sees agency).
  3. Damage review API (admin) — FR-12, FR-18
      What gets built: `PATCH /api/v1/damage-reports/{id}/review` (mark Reviewed + set vehicle status).
  4. Repair logging API — FR-13, FR-18
      What gets built: `repair_logs` migration/model; `RepairController` → `GET/POST/PUT /api/v1/repairs` (`repair_source` enum + `external_shop_name`), plus vehicle `PATCH status`.
  5. Admin Blade pages: Damage & Repairs — FR-12, FR-13, NFR-03
      What gets built: `inspections-damage.blade.php` damage section + `repairs.blade.php` (review/edit modals).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `DamageReportSubmitTest`: valid submit returns 201 with status Pending + date set; photo optional; unauthenticated 401; admin-submitting 403; missing nature-of-damage 422.
    - `DamageReviewTest`: admin marks Reviewed and updates vehicle status (200); driver token 403; **Agency A admin cannot review Agency B report (404/403)**.
    - `RepairApiTest`: create/list/update (200/201); invalid `repair_source` 422; External Repair Shop requires shop name (422 if missing); 401/403/cross-agency isolation asserted.
    - `RepairUnitTest` (unit): repair-source label helper resolves External Repair Shop + shop name; status-update writes the single `vehicles.status` field.
  Manual testing checklist (plain language):
    Note: filing a damage report is a DRIVER action (no screen until the mobile app) — checked by
    `php artisan test`. Reviewing damage and logging repairs ARE admin screens you can click.

    Setup (every time):
      [ ] `php artisan migrate:fresh --seed`  → fresh sample data.
      [ ] `php artisan serve`  → app at http://127.0.0.1:8000 (leave it running).

    Commands to run — and why they matter:
      [ ] `php artisan route:list`  → look for the damage-reports and repairs routes.
      [ ] `php artisan test`  → all green.  Why: proves the driver-side + validation rules — a
          damage report saves as "Pending" with the date auto-filled (201); the photo is optional;
          an empty nature-of-damage is rejected (422); an admin submitting is refused (403), no
          token is 401; a repair with a bad source or missing shop name is rejected (422); and one
          agency can't review another's report.

    What must work (browser, as the BFP admin):
      [ ] Damage page shows only your agency's reports.  Why: agency isolation (FR-02).
      [ ] Open a report, mark it Reviewed and set a new vehicle status  → then open that vehicle
          and confirm its status changed.  Why: reviewing damage updates the one shared status (FR-12/FR-18).
      [ ] Open an uploaded photo  → it displays.  Why: photo attachments are stored (FR-11).
      [ ] Repairs page: log a repair, try each source; "External Repair Shop" requires a shop name.
          Why: repairs are recorded with their source (FR-13).

    Look at the saved data (optional — `php artisan tinker`):
      [ ] `App\Models\DamageReport::latest()->first()`  → the report; after a review,
          `App\Models\Vehicle::find(ID)->status` shows the updated status.

---
PHASE 5: Preventive Maintenance Scheduling
Goal: Admins create mileage- or time-based PM schedules with a configurable Due-Soon threshold, the system recalculates Due Soon/Due automatically, and admins record completion details.

Tasks:
  1. PM schedule schema + model — FR-14
      What gets built: `pm_schedules` migration (pm_type, interval/last/due mileage, due_date, `due_soon_threshold_km/_days`, status enum, completion fields); `PmSchedule` model + scope.
  2. PM CRUD API — FR-14
      What gets built: `PmController` → `GET/POST/PUT /api/v1/pm-schedules`, `GET /api/v1/pm-schedules/{id}`; `PmRequest` (require km fields for Mileage-Based, date for Time-Based).
  3. PM completion API — FR-14
      What gets built: `PATCH /api/v1/pm-schedules/{id}/complete` (date serviced, repair source, parts, remarks → status Completed); no auto-renewal.
  4. PM status computation command — FR-14, NFR-04
      What gets built: `php artisan rvms:recalculate-pm` scheduled command computing Upcoming/Due Soon/Due from `vehicles.current_mileage`/date vs thresholds; registered in the scheduler.
  5. Admin Blade page: Preventive Maintenance — FR-14, NFR-03
      What gets built: `pm.blade.php` (active + completed tables, create/edit/mark-completed modals).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `PmScheduleApiTest`: create mileage-based and time-based (201); missing interval on mileage-based 422; missing date on time-based 422; 401; driver token 403; **cross-agency isolation** on read/update.
    - `PmCompletionTest`: completing sets status Completed + stores completion fields (200); driver 403.
    - `PmStatusUnitTest` (unit): the recompute logic returns Due/Due Soon/Upcoming correctly at threshold boundaries for both PM types, and never auto-creates a next cycle.
  Manual testing checklist (plain language):
    Setup (every time):
      [ ] `php artisan migrate:fresh --seed`  → fresh sample data.
      [ ] `php artisan serve`  → app at http://127.0.0.1:8000 (leave it running).

    Commands to run — and why they matter:
      [ ] `php artisan route:list --path=api/v1/pm-schedules`  → shows the PM routes.
      [ ] `php artisan schedule:list`  → shows `rvms:recalculate-pm` is scheduled.
          Why: confirms the due-status job runs automatically on its own.
      [ ] `php artisan test`  → all green.  Why: proves a Mileage-Based schedule with no km
          interval is rejected (422), a Time-Based one with no date is rejected (422), a driver
          token is refused (403), no token is 401, and one agency can't open/edit another's schedule.

    What must work (browser, as the BFP admin — PM page):
      [ ] Create a Mileage-Based schedule (needs a km interval) and a Time-Based one (needs a due
          date)  → both save.  Why: both scheduling styles are supported (FR-14).
      [ ] Mark one Completed with date serviced / repair source / parts / remarks  → it moves to a
          separate "completed" list and does NOT auto-create a new cycle.
          Why: each maintenance cycle is entered by hand, no auto-renewal (FR-14).
      [ ] Only your agency's schedules show, with the right colored status badges.
          Why: agency isolation (FR-02) + clear status at a glance.

    The auto due-status check (the important one — `php artisan tinker`):
      [ ] Set a vehicle's `current_mileage` close to its schedule's due mileage, then run
          `php artisan rvms:recalculate-pm`  → that schedule's status flips to "Due Soon" or "Due"
          on its own.  Why: the system tracks maintenance timing automatically (FR-14).

---
PHASE 6: Dispatch Logging & Vehicle Availability
Goal: Admins open dispatches (auto-setting the vehicle to Dispatched), close them with a return status, and view current availability of all agency vehicles.

Tasks:
  1. Dispatch schema + model — FR-15
      What gets built: `dispatches` migration (mission_type enum + mission_other, location, time_out/in, return_status); `Dispatch` model + scope; active = `time_in IS NULL`.
  2. Open-dispatch API — FR-15, FR-18
      What gets built: `POST /api/v1/dispatches` (`DispatchRequest`, require mission_other when Others) → sets `vehicles.status = Dispatched`.
  3. Close-dispatch API — FR-16, FR-18
      What gets built: `PATCH /api/v1/dispatches/{id}/close` (time_in + return_status enum) → updates vehicle status.
  4. Dispatch listing, edit & availability — FR-15, FR-17
      What gets built: `GET /api/v1/dispatches`, `PUT /api/v1/dispatches/{id}`, `GET /api/v1/vehicles/availability` (status of all agency vehicles).
  5. Admin Blade page: Dispatch — FR-15, FR-17, NFR-03
      What gets built: `dispatch.blade.php` (active-monitoring banner, close-dispatch modal).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `DispatchOpenTest`: opening returns 201 and vehicle becomes Dispatched; Others without `mission_other` 422; 401; driver token 403; **Agency A cannot dispatch/read Agency B's vehicle**.
    - `DispatchCloseTest`: close with each return status updates the vehicle's single status field (200); invalid return_status 422.
    - `AvailabilityTest`: returns only the caller's agency vehicles with current status; 403 for driver.
    - `DispatchUnitTest` (unit): active/completed derivation from `time_in`; mission label resolves Others + free text.
  Manual testing checklist (plain language):
    Setup (every time):
      [ ] `php artisan migrate:fresh --seed`  → fresh sample data.
      [ ] `php artisan serve`  → app at http://127.0.0.1:8000 (leave it running).

    Commands to run — and why they matter:
      [ ] `php artisan route:list`  → look for the dispatches and vehicles/availability routes.
      [ ] `php artisan test`  → all green.  Why: proves opening a dispatch sets the vehicle to
          Dispatched (201); mission type "Others" with no detail text is rejected (422); closing
          with each return status updates the single vehicle status (200) and a bad status is
          rejected (422); a driver token is 403, no token 401; availability returns only your
          agency; and one agency can't dispatch/read another's vehicle.

    What must work (browser, as the BFP admin — Dispatch page):
      [ ] Open a dispatch (vehicle, driver, mission type, location, time out)  → it saves and that
          vehicle's status automatically becomes "Dispatched".
          Why: dispatching a vehicle marks it unavailable everywhere at once (FR-15/FR-18).
      [ ] Close the dispatch with a time in and a return status (try Operational / Not Operational /
          Under Preventive Maintenance)  → the vehicle's status becomes what you chose.
          Why: closing records the return state on the one shared status (FR-16/FR-18).
      [ ] A banner counts the active dispatches; only your agency's records show.
          Why: admins see current fleet activity for their agency only (FR-17/FR-02).

    Look at the saved data (optional — `php artisan tinker`):
      [ ] `App\Models\Dispatch::whereNull('time_in')->get()`  → lists still-active dispatches;
          check a vehicle's `status` before/after open and close.

---
PHASE 7: Notification Services & FCM
Goal: All FR-21 in-app notifications are persisted and delivered, license/PM alerts fire on schedule, status/damage events notify the right role, and drivers receive FCM pushes.

Tasks:
  1. Notifications schema + in-app API — FR-21
      What gets built: `notifications` migration/model; `GET /api/v1/notifications`, `PATCH /api/v1/notifications/{id}/read`, `PATCH /api/v1/notifications/read-all`.
  2. FCM HTTP v1 client (server-side PHP) — FR-21, NFR-04
      What gets built: `FcmService` using Google service-account/HTTP v1; `POST /api/v1/fcm-token` for driver device registration; queued sends.
  3. Event-driven triggers — FR-21, FR-03
      What gets built: observers/events — damage submitted → agency admins; vehicle status changed → assigned driver (Vehicle Status Update); driver self-registration → agency admins (`New_Access_Request`).
  4. Scheduled alert jobs — FR-08, FR-14, FR-21
      What gets built: `rvms:license-alerts` (Expiring Soon/Expired → admins) and PM Due Soon/Due → admins + PM Reminder → drivers, hooked into the scheduler.
  5. Admin Blade page + bell — FR-21, NFR-03
      What gets built: `notifications.blade.php` + topbar bell dropdown with unread count.

Testing task (end of phase):
  Automated — `php artisan test`:
    - `NotificationApiTest`: list returns only the user's notifications (200); mark-read/read-all update state; 401; **cross-agency isolation** (cannot read another agency user's notifications); marking a foreign notification 403/404.
    - `FcmTokenTest`: driver registers a token (200); admin/driver role rules enforced; invalid token 422.
    - `NotificationTriggerTest`: submitting a damage report creates an admin notification; a vehicle status change creates a driver notification; the scheduled commands create license/PM notifications (FCM transport faked/mocked).
    - `NotificationUnitTest` (unit): notification-type → title/recipient mapping; license/PM threshold selection logic.
  Manual testing checklist (plain language):
    Note: real phone push (FCM) needs Firebase credentials; in local testing the push send is
    faked/mocked, but the notification ROWS saved in the database are real — those are what you check.

    Setup (every time):
      [ ] `php artisan migrate:fresh --seed`  → fresh sample data.
      [ ] `php artisan serve`  → app at http://127.0.0.1:8000 (leave it running).

    Commands to run — and why they matter:
      [ ] `php artisan route:list`  → look for the notifications and fcm-token routes.
      [ ] `php artisan schedule:list`  → shows the license-alerts and PM jobs are scheduled.
          Why: confirms the alerts fire automatically on a timer.
      [ ] `php artisan test`  → all green.  Why: proves you only ever see your own notifications;
          no token is 401; marking someone else's notification read is blocked (403/404); a driver
          can register a device token (200) and a bad token is rejected (422).

    Make the alerts happen, then confirm the rows (`php artisan tinker`):
      [ ] Give a driver a near-expiry license and a vehicle a mileage near its PM due point, then run
          `php artisan rvms:license-alerts` and `php artisan rvms:recalculate-pm`. Now
          `App\Models\Notification::latest()->take(5)->get()`  → new rows for the expiring license
          and the due PM.  Why: time-sensitive alerts are generated (FR-08/FR-14/FR-21).
      [ ] Change a vehicle's status (Phase 2 page)  → a "vehicle status update" row exists for that
          vehicle's assigned driver.  Why: drivers are told when their vehicle changes (FR-21).
      [ ] A new damage report (Phase 4)  → a "new damage report" row exists for the agency's admins.
          Why: admins are alerted to new reports (FR-21).

    What must work (browser, as the BFP admin):
      [ ] The bell icon shows an unread count; the notifications page groups them Today / Yesterday /
          Earlier; marking one read (and "mark all read") drops the count.
          Why: admins can see and clear their alerts (FR-21).

---
PHASE 8: Dashboard Summary & Report Generation
Goal: Admins see live agency fleet counts and can generate filtered, printable reports for all six report types.

Tasks:
  1. Dashboard summary API — FR-19
      What gets built: `GET /api/v1/dashboard/summary` (counts: 4 statuses, total vehicles, total drivers, expiring licenses, pending damage) — agency-scoped.
  2. Report query endpoints — FR-20
      What gets built: `ReportController` → `GET /api/v1/reports/{type}` for inspections, damage, repairs-maintenance, pm, dispatch, vehicle-status with the documented filters (date range, vehicle, driver, source, status, mission type).
  3. Printable report views — FR-20, NFR-03
      What gets built: print-friendly Blade templates (light-surface, no extra print CSS hacks) for each report type, each stamped with the generating admin's name + generation date; keep the current report layout. (Parked pending client validation: mimic the paper checklist form + show the assigned driver's name — not built yet.)
  4. Admin Blade pages: Dashboard & Reports — FR-19, FR-20
      What gets built: `dashboard.blade.php` (summary cards + action-required lists) and `reports.blade.php` (type selector + filters).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `DashboardSummaryTest`: returns correct counts for the caller's agency only (200); 401; driver token 403; **counts never include another agency's records**.
    - `ReportApiTest`: each of the six report types returns 200 with the expected shape and honors filters; invalid date range/filter 422; 401; driver 403; **cross-agency isolation** on every report.
    - `ReportUnitTest` (unit): filter-builder applies date/vehicle/driver/source/status/mission constraints correctly; vehicle-status summary reflects live statuses.
  Manual testing checklist (plain language):
    Setup (every time):
      [ ] `php artisan migrate:fresh --seed`  → fresh sample data.
      [ ] `php artisan serve`  → app at http://127.0.0.1:8000 (leave it running).

    Commands to run — and why they matter:
      [ ] `php artisan route:list --path=api/v1/reports` and `--path=api/v1/dashboard`  → shows
          the report + dashboard routes.
      [ ] `php artisan test`  → all green.  Why: proves the summary and all six reports return only
          your agency's data (never another's); a backwards/malformed date range is rejected (422);
          a driver token is 403 and no token 401.

    What must work (browser, as the BFP admin):
      [ ] Open the Dashboard  → cards show counts for the four vehicle statuses, total vehicles,
          total drivers, expiring licenses, and pending damage reports — for YOUR agency only.
          Why: admins get an at-a-glance fleet summary, agency-scoped (FR-19/FR-02).
      [ ] Open Reports, pick each of the six types (inspections, damage, repairs-maintenance, pm,
          dispatch, vehicle-status), apply the date range + other filters  → the right rows show.
          Why: admins can pull filtered records for any module (FR-20).
      [ ] Use the browser's Print Preview  → the printout is clean and stamped with your name and
          the date.  Why: reports are printable and show who generated them and when (FR-20).

    Cross-check the numbers (optional — `php artisan tinker`):
      [ ] `App\Models\Vehicle::where('status','Operational')->count()`  → matches the Dashboard card.

---
PHASE 9: NFR Hardening & Final Verification
Goal: The whole API meets the performance, security, reliability, usability, and compatibility standards, with a full green regression suite.

Tasks:
  1. Performance pass — NFR-01
      What gets built: pagination on all list endpoints, eager-loading audit (eliminate N+1), DB index review on `agency_id`/FKs/date columns, concurrent-load spot check.
  2. Security hardening — NFR-02
      What gets built: enforce HTTPS/encrypted transport config, login rate limiting, full policy/authorization audit, agency-isolation sweep across every endpoint, password strength rules.
  3. Reliability pass — NFR-04
      What gets built: queue + retry/backoff for FCM and scheduled jobs, confirm single-status consistency across all modules, idempotent status writes.
  4. Usability & compatibility verification — NFR-03, NFR-05
      What gets built: consistent JSON envelope/error format, API contract notes for the Android team, dashboard checks on Chrome/Firefox/Edge.

Testing task (end of phase):
  Automated — `php artisan test`:
    - `RegressionSuite`: run the full Phase 1–8 suite green.
    - `AgencyIsolationSweepTest`: parameterized test asserting every list/show/update/delete endpoint blocks cross-agency access (NFR-02).
    - `RateLimitTest`: repeated failed logins are throttled (NFR-02).
    - `PerformanceUnitTest`: list endpoints are paginated and key queries issue no N+1 (assert query count) (NFR-01).
  Manual testing checklist (plain language):
    This phase re-checks the WHOLE system, not one module. Do the setup, then work down the list.

    Setup (every time):
      [ ] `php artisan migrate:fresh --seed`  → fresh sample data.
      [ ] `php artisan serve`  → app at http://127.0.0.1:8000 (leave it running).

    Commands to run — and why they matter:
      [ ] `php artisan route:list`  → skim the whole `/api/v1` table; every endpoint has the right
          method, path, and middleware.  Why: a final check that nothing is mis-wired.
      [ ] `php artisan test`  → all green.  Why: runs the agency-isolation sweep across EVERY module
          (wrong role 403, no/expired token 401, another agency's record 403/404, bad input 422),
          confirms lists are paginated, and confirms repeated bad logins get throttled (429).

    What must work (browser / terminal):
      [ ] Rate limiting: run the curl login (guide section C) with a WRONG password several times
          fast  → after a handful of tries it refuses with 429 (Too Many Requests).
          Why: protects against password-guessing (NFR-02).
      [ ] One-status consistency: change a vehicle's status once, then confirm the SAME status shows
          in the vehicle record, the availability list, the dashboard summary, and the vehicle-status
          report.  Why: there is one shared status, never conflicting copies (FR-18/NFR-04).
      [ ] Big lists come back in pages, not thousands of rows at once, and stay fast.
          Why: the system stays responsive under load (NFR-01).
      [ ] Open the dashboard in Chrome, Firefox, and Edge  → looks and works the same in all three.
          Why: browser compatibility (NFR-05).

---

Coverage: FR-01–FR-04 (Phase 1; FR-03 driver-approval handled in Phase 2), FR-05–FR-08 (Phase 2), FR-09/FR-10 (Phase 3), FR-11–FR-13 (Phase 4), FR-14 (Phase 5), FR-15–FR-17 (Phase 6), FR-18 (built into every status-changing module across Phases 2–6), FR-19/FR-20 (Phase 8), FR-21 (Phase 7), NFR-01–NFR-05 (woven throughout, finalized in Phase 9).

---

## Non-Negotiable Rules (every task session)

1. **Read `skills/rvms-source-of-truth.md` first** — every session, before any work.
2. **Explain before executing** — describe what you will build and why, then wait.
3. **Wait for explicit approval** — do not start coding until told "start Phase X, Task Y".
4. **One task at a time** — complete and test a single task before moving on.
5. **Never invent features** — build only what the source of truth/approved plan defines.
6. **Vehicle statuses are exactly the four values**; PM/mission/repair-source/notification
   enums are exactly as documented above.
7. **Agency-scope every query**; **store thresholds as columns**; **all API routes use
   `/api/v1/`**; **Sanctum bearer auth**; **FCM server-side via HTTP v1**.
8. **Every phase ends with its testing task** (automated + manual) before the next begins.
9. **Copy the prototype's UI/UX for every dashboard page.** Each phase's Blade page must
   mirror the corresponding prototype page in `web/pages/*.html` — same page header +
   subtitle, card containers (`card border-0 shadow-sm rounded-3`), uppercase gray table
   headers, pill badges (`px-3 py-2 rounded-pill`), row action buttons (icon buttons
   `btn-sm btn-light border` for view/edit/status; solid navy `btn-navy text-white
   fw-medium` for primary row actions), modal conventions (form modals `bg-navy text-white`
   header + `btn-close-white`; read-only view modals `bg-light` header; danger flows
   `bg-danger`; footers `border-0` with Cancel `btn-light border` + navy/danger confirm),
   prototype titles/placeholders, and the card-footer pagination
   (`partials/table-footer` + the published Bootstrap 5 pager). Before building a page,
   read the prototype page and reuse its markup patterns. Only omit prototype elements
   that belong to a later phase or have no schema backing — and say so explicitly.
