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
   (FR-04). Agency administrator accounts are provisioned (seeded) only; there is no admin self-registration, and the public registration endpoint is driver-only. An agency may
   have MORE THAN ONE administrator account (per the interviews — e.g., logistics and
   operations officers); the seeder includes a second BFP admin as the sample, and no code
   may assume a single admin per agency (notifications target ALL of an agency's admins).
7. **Deliberately excluded (objectives audit, July 2026 — do not add):** no admin-remarks
   columns on inspection/damage reviews; no passenger/patient
   fields on dispatches (privacy + outside vehicle-management scope); no agency-info
   editing feature (no FR backs it). A driver MAY be the primary driver of more than one
   vehicle (Ch4 ERD); each vehicle still has at most one primary driver.
   **Amendment (2026-07, implementation-level, project-lead approved):** `vehicles.remarks`
   is an exception to this rule — a single optional note on the vehicle's most recent
   manual status change, overwritten on each update (like `current_mileage`, NOT a change
   log/history table). It exists only because the prototype's Update Status modal already
   had a "Remarks (Optional)" field; no FR backs it and it is intentionally NOT mirrored
   into the manuscript's Chapter 4 data dictionary — repo/code only.
8. **Dispatch odometer capture (2026-07, prototype-testing feedback — CDRRMO-confirmed, backed by
   FR-15/FR-16, IN the manuscript).** Two optional columns on `dispatches` — `odometer_out`
   (recorded at time out) and `odometer_in` (recorded at time in) — digitize the odometer field
   the agencies already fill on their paper dispatch form. Readings are keyed manually by the admin
   from the vehicle's own odometer; this is NOT GPS/IoT/telematics (all still excluded — Ch1 Scope &
   Limitations). Both are nullable so agencies that don't track odometer are not forced to. On close,
   when `odometer_in` is present and greater than the vehicle's `current_mileage`, it updates
   `current_mileage` (mileage-on-arrival → feeds mileage-based PM, FR-14). The prototype's dispatch
   form has time out/in but no odometer field, so the two inputs are a documented addition to the
   dispatch open/close modals. Unlike `vehicles.remarks`, this IS mirrored into the manuscript
   (FR-15/FR-16 wording + the `dispatches` data-dictionary rows); the ERD diagram is unchanged
   (two new attributes on the existing Dispatch entity — no new entity or relationship).

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
| current_mileage | INT UNSIGNED | No | 0 | Current odometer (km); drives mileage-based PM (FR-14). Updated manually on the vehicle, or automatically from the time-in odometer reading when a dispatch is closed (`dispatches.odometer_in`, FR-16). |
| status | ENUM('Operational','Dispatched','Not Operational','Under Preventive Maintenance') | No | 'Operational' | **Single shared operational status** (FR-18), written from every module. |
| remarks | TEXT | Yes | NULL | **Implementation-level addition, not in the manuscript's data dictionary** (design decision 7 amendment, 2026-07). Optional note on the most recent manual status change via the Update Status modal; overwritten on each update, no history kept. No FR backs it. |
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
| odometer_out | INT UNSIGNED | Yes | NULL | **Optional** odometer reading at time out (FR-15). Digitizes the odometer field on the agencies' existing paper dispatch form (CDRRMO-confirmed); manually keyed from the vehicle's own odometer, NOT device-captured. Nullable — agencies that do not track it leave it blank. |
| time_in | DATETIME | Yes | NULL | Date/time in on close; NULL = active (FR-16). |
| odometer_in | INT UNSIGNED | Yes | NULL | **Optional** odometer reading at time in (FR-16). On close, when present and greater than the vehicle's `current_mileage`, it updates `vehicles.current_mileage` (mileage-on-arrival → feeds mileage-based PM, FR-14). Nullable. |
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

## Testing BOTH platforms (web admin + mobile app) — READ THIS ONCE TOO

The system has two front doors sharing one database: the **web admin dashboard** (browser,
run by XAMPP) and the **mobile app** (the Android app the drivers use). Some features live on
one platform, some cross both. Every phase's manual checklist below is now **tagged** so you
always know where you are and what to do.

**The labels (used on every manual step):**
- **[AUTO]** — you just run one command (`php artisan test`). The robot proves the rules.
- **[WEB]** — you click in the **browser** (the admin dashboard).
- **[MOBILE]** — you tap in the **phone app** (as a driver).
- **[BOTH]** — a cross-platform check: do something on one platform, then confirm it on the other.

**Each step is written as:**
- **Do this:** the exact action (which button, which command, what to type).
- **Why:** what it proves (and which FR it maps to).
- **You should see:** the expected result.
- **Report back:** the one or two things to tell me so I understand your result (the what/why/how).

**Who does what:** *I* always seed realistic sample data and write the [AUTO] tests. *You* do the
hands-on clicking and typing on both platforms and report back. When a phase is **[WEB] only**,
you never need the phone; when it's **[WEB + MOBILE]**, you'll use both.

**One-time mobile setup (so the phone can reach your laptop's API):**
1. On the laptop, start the app open to your network (not just localhost):
   `php artisan serve --host=0.0.0.0 --port=8000`
2. Find your laptop's Wi-Fi address: in PowerShell run `ipconfig` and read the **IPv4 Address**
   (looks like `192.168.1.15`).
3. In the mobile app's settings/config, point the server URL at **your laptop's address**, e.g.
   `http://192.168.1.15:8000` — NOT `127.0.0.1` (on the phone, `127.0.0.1` means the phone itself).
4. The **phone and laptop must be on the same Wi-Fi**. If it can't connect, allow port 8000 through
   the Windows Firewall (or briefly disable it while testing at home).
5. Log in on the app as a **driver** (e.g. `ramon.villanueva@rvms.local` / `password`); log in on the
   web as an **admin** (e.g. `bfp.admin@rvms.local` / `password`). Both now read/write the same data.

> A phase tagged **[WEB] only** does not need any of the mobile setup — just the browser.

---
PHASE R0 — Day 1: Plumbing Foundation (no screens yet)
Goal: A running Laravel 11 + MySQL app where logging in returns a Sanctum token carrying the user's role and agency, and every future database query is automatically limited to the caller's own agency.
Prototype source: none — this is invisible plumbing, so the two-checkpoint screen method (below) does not apply to this phase.

Sub-tasks (Day 1):
  1. Create the Laravel 11 project in `backend/`, install Sanctum, configure `.env.example` for MySQL 8 (`rvms` database), register the `/api/v1` route group.
  2. `agencies` migration + model — columns exactly per the Data Dictionary above.
  3. `users` migration + model — role/status enums, license fields, `fcm_token`, exactly per the Data Dictionary.
  4. Seeders: 4 agencies (with logos) · 1 admin each PLUS the second BFP admin (`bfp.admin2@rvms.local`, multi-admin sample) · 2 sample drivers per agency. Password `password` everywhere.
  5. Auth API: `POST /login` (token + role + agency; non-active accounts get 403 with a reason), `POST /logout`, `GET /me`, public `POST /register` (driver-only, created `pending`), `PATCH /me/profile` (FR-04 self-edit).
  6. Enforcement layer: `AgencyScope` global scope + `BelongsToAgency` trait (auto-stamps `agency_id` on create) + `role:` middleware alias.
  7. Automated tests: AuthLogin, Register, Profile, Me, Logout, AgencyScope unit, MultiAdmin (~29 tests).

Testing task:
  Automated — `php artisan test`: login/logout/me/register/profile rules; pending driver blocked; revoked token dies; global scope filters and auto-stamps agency_id; two same-agency admins see the same data.
  Manual testing checklist (plain language):
    In plain words: nothing is visible yet — this day installed the electricity and the locks.
    You are checking that the wiring is safe: accounts exist, passwords are protected, and the
    "each agency sees only its own data" rule is active from day one.
      [ ] `php artisan migrate:fresh --seed`  → ends with green DONE lines, no red.
          Why: the database tables build correctly on your MySQL.
      [ ] `php artisan test`  → all green.  Why: one command proves every login rule — right
          password gets in, wrong password doesn't, a pending driver is politely refused, a
          logged-out token stops working, and one agency can never query another's records.
      [ ] `php artisan tinker` → `App\Models\Agency::pluck('code')` shows the 4 agencies;
          `App\Models\User::count()` shows 13 (5 admins — BFP has two — + 8 drivers);
          `App\Models\User::first()->password` is scrambled text starting `$2y$`.
          Why: the sample accounts exist and no password is readable by anyone.

---

## THE TWO-CHECKPOINT METHOD — applies to every phase below (R1–R9)

Every phase that produces a screen is split into two blocks, in this exact order:

**BLOCK A — static copy (no backend logic at all):**
  A1. Widget extraction: read the prototype file and list every widget (header, filters, table
      columns, buttons, modals, footer) as its own checkbox — nothing is built before this list exists.
  A2. Copy the prototype HTML file into the `.blade.php` file COMPLETELY UNCHANGED — same
      fake/hardcoded data, same dead links, same demo JavaScript. No logic, no routes, no models yet.
  A3. CHECKPOINT A (mechanical self-check): open the raw copy in a browser next to the prototype
      file — they must be PIXEL-IDENTICAL, because at this point the Blade file IS the prototype
      file. If anything differs, the copy was mistyped — fix it before writing a single line of logic.

**BLOCK B — make it live:**
  B1. Replace ONLY the hardcoded data with real Blade variables (`ABC-1234` → `{{ $vehicle->plate_number }}`,
      the static rows → `@foreach`) — build the migration/model/API underneath as needed.
  B2. Wire forms and buttons to real routes; add ONLY the explicitly-planned deviations from the
      prototype (e.g., "no Remember-me", "Access Requests section — documented addition"), each
      one left as a one-line comment so it is traceable later.
  B3. CHECKPOINT B (the project lead's approval): side-by-side with the prototype again — this
      proves that wiring in live data did not silently change any markup, button, or wording.
      A phase does not close until the lead approves Checkpoint B.

---
PHASE R1 — Day 2: Login Page + Dashboard Shell (verbatim)
Goal: The login screen and the dashboard frame (sidebar, topbar) look IDENTICAL to the prototype, and only agency administrators can enter.
Prototype source: `web/login.html` + the sidebar/topbar chrome of `web/pages/dashboard.html`.

Sub-tasks (Day 2):
  Block A:
    1. Widget extraction from both prototype files (logo card, inputs, eye-toggle, links, sidebar
       sections, topbar items).
    2. Copy `web/assets/css/style.css` + the RVMS/agency logos into `backend/public` unchanged —
       the stylesheet is never edited (any unavoidable backend-only addition goes in a separate `admin.css`).
    3. Copy `login.html` → `auth/login.blade.php` and the `dashboard.html` chrome → `layouts/app.blade.php`
       verbatim — hardcoded data and demo JS left as-is for now.
    4. CHECKPOINT A: raw copy vs prototype files — pixel-identical.
  Block B:
    5. Wire the login form to the real login route; drop "Remember me"; "Forgot password?" opens
       the contact-your-administrator modal; the demo agency chips are omitted (the prototype itself
       labels them demo-only — documented omission).
    6. Wire the sidebar/topbar to the logged-in user's real agency + logo; ALL 9 nav items visible,
       later-phase pages disabled ("available in a later phase"); bell is a placeholder (goes live R7);
       user dropdown + real Sign Out.
    7. Web session auth: admins only (drivers told to use the mobile app), guests bounced to login,
       role redirect.
    8. Automated tests: web login/logout/guest-redirect/driver-blocked.
    9. CHECKPOINT B: side-by-side with `login.html` and `dashboard.html`, now logged in — identical
       chrome, live agency name/logo — send to the project lead, wait for approval.

Testing task:
  Automated — `php artisan test`: web guard rules (admin in, driver refused, guest redirected, logout kills session).
  Manual testing checklist (plain language):
    In plain words: this is the front door. You are checking that it LOOKS exactly like the
    prototype's front door and that only the right people get in.
      [ ] Open http://127.0.0.1:8000 next to the prototype's login page  → they look identical
          (same card, same icons, same "Forgot password?", NO "Remember me").
      [ ] Log in as bfp.admin@rvms.local  → the frame around the page (blue sidebar, top bar with
          "Bureau of Fire Protection" and its logo) matches the prototype's dashboard frame.
      [ ] Log in as bfp.admin2@rvms.local  → exactly the same BFP view.
          Why: one agency can have two administrators and both must work.
      [ ] Try a driver account  → refused with "use the mobile app".
          Why: the website is for admins; drivers belong on the phone app.
      [ ] While logged out, type /dashboard in the address bar  → bounced to login.
          Why: nobody sneaks in without signing in.

---
PHASE R2 — Days 3–4: Vehicles + Drivers (FR-05, FR-06, FR-07, FR-08, FR-18, FR-03)
Goal: Admins fully manage their agency's vehicles and drivers with screens identical to the prototype; drivers can fetch their assigned vehicle(s) by API; expiring licenses are detected against each agency's configurable threshold.
Prototype source: `web/pages/vehicles.html`, `web/pages/drivers.html`.

Sub-tasks — Day 3 (Vehicles):
  Block A:
    1. Widget extraction from `vehicles.html` (header+subtitle, Add button, 3-field filter bar,
       6 table columns, 3 icon row-actions, Add/Edit/View/Status modals, card-footer pagination).
    2. Copy `vehicles.html` → `vehicles.blade.php` verbatim (hardcoded rows left as-is).
    3. CHECKPOINT A: raw copy vs prototype — pixel-identical.
  Block B:
    4. `vehicles` migration/model/factory per the Data Dictionary; seeder: 2 vehicles per agency,
       the first assigned to the agency's first driver.
    5. Vehicle API: `GET/POST/PUT /vehicles`, `GET /vehicles/{id}`, `PATCH /vehicles/{id}/status`
       (only the 4 enum values); `GET /my-vehicle` (driver token; a driver may hold several
       vehicles — return them all).
    6. Replace hardcoded rows with live data: working search/type/status filters, combined VEHICLE
       DETAILS cell, "45,230 km" mileage format, pill badges, eye/pencil/refresh icon buttons wired
       to real modals (View/Edit/Status), Status modal limited to 3 choices + the "Dispatched is set
       automatically by the Dispatch module" note, prototype pagination footer.
    7. Automated tests: VehicleApi + MyVehicle suites (shapes, 422s, 401/403, cross-agency 404,
       plate-unique-per-agency, same-agency driver rule).
    8. CHECKPOINT B: vehicles page vs prototype, with live seeded data → lead approves.

Sub-tasks — Day 4 (Drivers):
  Block A:
    9. Widget extraction from `drivers.html` (3 license summary cards, filter bar, 6 columns incl.
       monospace license + colored expiry, license-status pills, view/edit icon actions, Add/Edit/View
       modals with "Assign Vehicle (Optional)").
    10. Copy `drivers.html` → `drivers.blade.php` verbatim.
    11. CHECKPOINT A: raw copy vs prototype — pixel-identical.
  Block B:
    12. Driver API: `GET/POST/PUT /drivers` (admin-added = active), `?status=pending`, `PATCH
        approve`/`reject`; `GET /licenses/monitoring` using `agencies.license_expiry_warning_days`.
    13. Replace hardcoded rows with live data: license summary cards computed live, license pills,
        ASSIGNED VEHICLE column listing ALL of a driver's vehicles, assign-select that never steals
        an already-assigned vehicle ("No change" default on edit).
    14. Access Requests section for pending self-registrations — NOT in the prototype (approval was
        added to scope later); build it using the prototype's own card/table/badge/button
        conventions and leave a one-line comment noting it as a documented addition (FR-03).
    15. Automated tests: DriverApi + LicenseMonitoring suites (approve/reject, isolation, threshold
        boundaries, multi-vehicle listing).
    16. CHECKPOINT B: drivers page vs prototype, with live seeded data → lead approves.

Testing task:
  Automated — `php artisan test`: full R0–R2 suite green.
  Manual testing checklist (plain language):
    Platforms this phase touches: WEB + MOBILE.
    In plain words: these are the two filing cabinets — vehicles and drivers. You are checking
    that the cabinets look exactly like the prototype's, that BFP's cabinet is invisible to CHO,
    and that a driver can see their assigned vehicle on the phone.

    [AUTO]
      [ ] Do this: `php artisan test`.
          Why: proves the vehicle/driver rules — plate unique per agency, a driver can hold more
               than one vehicle, and one agency can never read another's records (FR-02/05/06/07).
          You should see: all green.
          Report back: green, or paste the first red line.

    [WEB] (browser, as bfp.admin@rvms.local)
      [ ] Do this: open Vehicles next to the prototype's vehicles page.
          Why: the screen must match the validated prototype (Non-Negotiable Rule 9).
          You should see: identical buttons (eye / pencil / circular-arrows), filter bar, badge
               pills, and the "Showing X to Y" footer.
          Report back: match or not; if not, what looks different.
      [ ] Do this: click Add Vehicle, fill it like the example placeholders, save.
          Why: the fleet record works (FR-05).
          You should see: the new vehicle appears in the table.
      [ ] Do this: click the circular-arrows (Update Status) button on any vehicle.
          Why: statuses can't contradict each other — "Dispatched" is set by the Dispatch module
               alone (FR-18).
          You should see: only 3 status choices + the note about Dispatched.
      [ ] Do this: open Drivers. In `php artisan tinker`, set a driver's `license_expiry_date` to
          next week, then refresh the page.
          Why: the system watches licenses automatically (FR-08).
          You should see: the EXPIRING SOON card goes up by one and that row's date turns orange.
      [ ] Do this: use seeded pending data (or register a driver — see [MOBILE] below), then click
          Approve in the "Access Requests" box.
          Why: self-registered drivers wait for the admin (FR-03).
          You should see: the driver moves into the main table as Active.
      [ ] Do this: assign a second vehicle to a driver who already has one (Edit driver → Assign).
          Why: a driver may hold several vehicles; each vehicle still has one primary driver.
          You should see: that driver now lists BOTH plates.
      [ ] Do this: log out, log in as the CHO admin, open Vehicles and Drivers.
          Why: the agency wall — the most important security rule (FR-02).
          You should see: none of BFP's vehicles or drivers here.
          Report back: confirm CHO sees only CHO's records.

    [MOBILE] (phone app, as ramon.villanueva@rvms.local — do the one-time mobile setup first)
      [ ] Do this: open the app, log in as the driver, open "My Vehicle".
          Why: a driver sees the vehicle(s) assigned to them (FR-07).
          You should see: the plate, type, and current status of the vehicle the admin assigned to
               you on the web.
          Report back: does the vehicle shown on the phone match what's assigned on the web?
      [ ] Do this (self-registration): from the app's sign-up screen, register a NEW driver for your
          agency and submit.
          Why: drivers self-register and wait for admin approval (FR-03).
          You should see: a "pending approval" message; you cannot log in yet.

    [BOTH] (assignment shows up across platforms)
      [ ] Do this (WEB): assign a vehicle to your driver; then (MOBILE) refresh "My Vehicle".
          Why: the admin's assignment reaches the driver's phone through the shared database (FR-07).
          You should see (MOBILE): the newly assigned vehicle now appears.
          Report back: did the phone show the vehicle the admin just assigned?
      [ ] Do this (MOBILE): self-register a driver; then (WEB) open Drivers.
          Why: a self-registration on the phone becomes an Access Request for the admin (FR-03).
          You should see (WEB): the new applicant in the "Access Requests" box; Approve turns them
               Active, after which they can log in on the app.
          Report back: did the applicant appear on the web, and could they log in after approval?

---
PHASE R3 — Days 5–6: Digital BLOWBAGETS Inspections (FR-09, FR-10)
Goal: Drivers submit the daily checklist by API (12 items, +2 for BFP, remarks required on flagged items); admins see and review them on a screen identical to the prototype's inspections section.
Prototype source: `web/pages/inspections-damage.html` — the "Daily BLOWBAGETS Inspections" table, "Frequently Reported Issues" block, View Checklist modal, Review Inspection modal.

Sub-tasks — Day 5 (data + API — no screen yet, so no checkpoints today):
  1. Widget extraction from the inspections section of the prototype file (recorded now, used Day 6).
  2. `inspection_checklist_items` migration/model/seeder — the 12 standard items + Hydraulic System
     + Fire Pump (`is_bfp_only`); `GET /inspections/checklist` returns 14 for BFP drivers, 12 otherwise.
  3. `inspections` + `inspection_items` migrations/models per the Data Dictionary.
  4. Submission API: `POST /inspections` — every checklist item present, OK/Has Issue each, remarks
     REQUIRED on Has Issue, vehicle must belong to the driver's agency.
  5. Monitoring API: `GET /inspections` (vehicle/driver/date filters), `GET /inspections/{id}`,
     `GET /inspections/frequent-issues` (grouped Has-Issue counts + last-reported date).
  6. Review API: `PATCH /inspections/{id}/review` — marks Reviewed, records who/when, optional
     vehicle status change (3 choices).
  7. Automated tests: submit/monitor/review suites.

Sub-tasks — Day 6 (screen):
  Block A:
    8. Copy the inspections section of `inspections-damage.html` → the relevant part of
       `inspections.blade.php` verbatim.
    9. CHECKPOINT A: raw copy vs prototype section — pixel-identical.
  Block B:
    10. Replace hardcoded rows with live data: page header "Inspections & Damage" + subtitle,
        section header + "N Pending Review" pill, table (DATE SUBMITTED as Today/Yesterday + time,
        VEHICLE & DRIVER combined, RESULT pill, REMARKS column, REVIEW STATUS pill), "View Checklist"
        light button + solid-navy "Review" button, ranked frequent-issues bars with counts and
        "Last:" dates, View Checklist modal (grouped Standard 12 / BFP Additional 2 with green ✓ /
        red ✗ and remarks), Review modal (context box, Driver's Submission box, 3-choice status
        select, "Mark Reviewed & Update Status").
    11. Sample seeder: per agency, yesterday's all-OK inspection already Reviewed + today's 2-issue
        inspection Pending — so the page demonstrates itself.
    12. CHECKPOINT B: inspections section vs prototype, with live seeded data → lead approves.

Testing task:
  Automated — `php artisan test`: BFP 14 vs others 12; flagged-without-remarks rejected; incomplete checklist rejected; admin can't submit; cross-agency blocked; review updates the vehicle.
  Manual testing checklist (plain language):
    Platforms this phase touches: WEB + MOBILE.
    In plain words: drivers fill a daily safety checklist on their phones; the web screen is where
    the admin reads and judges them. This is the first real "driver does something on the phone,
    admin sees it on the web" test.

    [AUTO]
      [ ] Do this: `php artisan test`.
          Why: proves the checklist rules — a BFP driver's list has 14 items, others 12; a flagged
               item without an explanation is rejected; an incomplete checklist is rejected; nobody
               can submit for another agency's vehicle (FR-09).
          You should see: all green.
          Report back: green, or paste the first red line.

    [WEB] (browser, as bfp.admin@rvms.local)
      [ ] Do this: open Inspections & Damage next to the prototype's inspections section.
          Why: the screen must match the validated prototype (Non-Negotiable Rule 9).
          You should see: identical layout, incl. the "N Pending Review" pill and the ranked
               Frequently Reported Issues bars.
      [ ] Do this: on today's seeded inspection, click View Checklist.
          Why: the admin sees exactly what the driver submitted (FR-10).
          You should see: green checks for OK items and red ✗ for flagged items with the driver's
               remarks; as BFP you also see the 2 extra BFP items (Hydraulic System, Fire Pump).
      [ ] Do this: click Review, choose "Not Operational", submit; then open the Vehicles page.
          Why: a bad inspection can take a vehicle off the road immediately (FR-10 + FR-18).
          You should see: the inspection row flips to Reviewed, and that vehicle now shows
               Not Operational on the Vehicles page.
      [ ] Do this: log in as the CHO admin, open Inspections.
          Why: the agency wall (FR-02).
          You should see: only CHO's inspections here.
          Report back: confirm CHO sees only CHO's inspections.

    [MOBILE] (phone app, as ramon.villanueva@rvms.local — do the one-time mobile setup first)
      [ ] Do this: open the app, log in, tap New Inspection, pick your vehicle. Mark "Brakes" as
          Has Issue and type a remark ("unusual noise"); leave the rest OK; tap Submit.
          Why: a driver submits the daily BLOWBAGETS checklist from the field (FR-09).
          You should see: a success message; the inspection appears in your history on the phone.
      [ ] Do this: on the checklist, try to submit with an item marked Has Issue but no remark.
          Why: a flagged item must be explained (FR-09).
          You should see: the app blocks it and asks for the remark.
      [ ] Do this: notice how many checklist items your app shows.
          Why: BFP drivers get 14 items (12 + Hydraulic System + Fire Pump); other agencies get 12.
          You should see: 14 boxes for a BFP driver (12 for PNP/CDRRMO/CHO drivers).
          Report back: how many items showed, and whether the flagged-without-remark was blocked.

    [BOTH] (driver submits, admin reviews — the core cross-platform flow)
      [ ] Do this (MOBILE): submit the inspection above with Brakes flagged. Then (WEB) open
          Inspections & Damage as the admin.
          Why: a real submission travels phone → API → shared database → admin screen (FR-09/FR-10).
          You should see (WEB): a new "Today" row for that vehicle, red "Has Issue", your remark,
               marked Pending.
          Report back: did the row appear on the web, and did the remark match what you typed?
      [ ] Do this (WEB): Review that inspection and set "Not Operational". Then (MOBILE) refresh
          "My Vehicle" in the app.
          Why: the admin's decision reaches the driver's phone (FR-10 + FR-18).
          You should see (MOBILE): the vehicle's status now reads Not Operational.
          Report back: did the phone show the status the admin just set?

---
PHASE R4 — Days 7–8: Damage Reports + Repair Logs (FR-11, FR-12, FR-13)
Goal: Drivers file damage reports (photo optional) by API; admins review them and update the vehicle's status; admins log repairs with source/parts/cost — screens identical to the prototype.
Prototype source: `web/pages/inspections-damage.html` — the "Damage Reports" section; `web/pages/repairs.html`.

Sub-tasks — Day 7 (Damage):
  Block A:
    1. Widget extraction from the damage section (pending pill, table with photo "View" button,
       red "Review & Assess" button, red review modal).
    2. Copy the damage section → the relevant part of `inspections.blade.php` verbatim (joined
       below the frequent-issues block, exactly like the prototype layout).
    3. CHECKPOINT A: raw copy vs prototype section — pixel-identical.
  Block B:
    4. `damage_reports` migration/model per the Data Dictionary; photo storage via `php artisan storage:link`.
    5. APIs: `POST /damage-reports` (multipart photo optional, date auto-set, Pending), `GET`
       list/show (driver sees own, admin sees agency), `PATCH /{id}/review` (Reviewed + vehicle status).
    6. Replace hardcoded rows with live data, wire the photo View button and the red Review & Assess modal.
    7. Automated tests: damage submit/review suites.
    8. CHECKPOINT B: damage section vs prototype, with live seeded data → lead approves.

Sub-tasks — Day 8 (Repairs):
  Block A:
    9. Widget extraction from `repairs.html` (wide table spacing, add/edit modals, source-conditional
       shop-name field).
    10. Copy `repairs.html` → `repairs.blade.php` verbatim.
    11. CHECKPOINT A: raw copy vs prototype — pixel-identical.
  Block B:
    12. `repair_logs` migration/model; APIs `GET/POST/PUT /repairs` (source enum; External Repair
        Shop requires the shop name).
    13. Replace hardcoded rows with live data, including the "specify shop" reveal when External
        Repair Shop is chosen.
    14. Sample seeders for both modules; automated tests: repair suite.
    15. CHECKPOINT B: repairs page vs prototype, with live seeded data → lead approves.

Testing task:
  Automated — `php artisan test`: photo optional; empty damage description rejected; admin-submit refused; External source without shop name rejected; cross-agency review blocked; review writes the one shared vehicle status.
  Manual testing checklist (plain language):
    In plain words: when something breaks, the driver reports it with a photo, the admin judges
    it, and repairs get written into the vehicle's history book.
      [ ] Damage section vs prototype side-by-side  → identical, including the red
          "Review & Assess" button and red-headed modal.
      [ ] Open a seeded report's photo  → the picture displays.  Why: photo evidence is stored (FR-11).
      [ ] Review & Assess one, set "Not Operational"  → the vehicle's status changes everywhere.
          Why: a damaged vehicle is immediately marked unusable (FR-12 + FR-18).
      [ ] Repairs page: log a repair choosing "External Repair Shop"  → a shop-name box appears
          and is required.  Why: the record says WHO fixed it (FR-13).
      [ ] As another agency's admin  → none of these reports/repairs are visible.

---
PHASE R5 — Days 9–10: Preventive Maintenance (FR-14)
Goal: Admins create mileage- or time-based PM schedules with configurable Due-Soon thresholds; the system flips statuses to Due Soon/Due automatically; completion is recorded manually — screen identical to `pm.html`.
Prototype source: `web/pages/pm.html`.

Sub-tasks — Day 9 (data + API — no screen yet, so no checkpoints today):
  1. Widget extraction from `pm.html` (tabs for active/completed, create/edit/complete modals,
     status badges incl. Upcoming) — recorded now, used Day 10.
  2. `pm_schedules` migration/model per the Data Dictionary (both types, thresholds, completion fields).
  3. APIs: `GET/POST/PUT /pm-schedules`, `GET /{id}`, `PATCH /{id}/complete` — mileage-based
     requires km fields, time-based requires a date; completing stores the 4 completion fields and
     NEVER auto-creates the next cycle.
  4. Automated tests: schedule/completion suites.

Sub-tasks — Day 10 (automation + screen):
  5. `php artisan rvms:recalculate-pm` command: computes Upcoming / Due Soon / Due from current
     mileage or dates vs the thresholds; registered in the scheduler.
  6. Automated tests: recompute boundary cases for both types.
  Block A:
    7. Copy `pm.html` → `pm.blade.php` verbatim.
    8. CHECKPOINT A: raw copy vs prototype — pixel-identical.
  Block B:
    9. Replace hardcoded rows with live data (active + completed views, all modals, badges).
    10. Sample seeder: one mileage-based near its threshold, one time-based, one completed — per agency.
    11. CHECKPOINT B: PM page vs prototype, with live seeded data → lead approves.

Testing task:
  Automated — `php artisan test`: 422 for missing km/date per type; completion stores fields; recompute flips statuses exactly at the boundaries; isolation.
  Manual testing checklist (plain language):
    In plain words: this is the automatic reminder calendar for oil changes and services. You are
    checking that the calendar flips to "Due Soon" BY ITSELF when a vehicle gets close.
      [ ] PM page vs prototype side-by-side  → identical, including the Upcoming badge color.
      [ ] Create one Mileage-Based schedule (needs a km interval) and one Time-Based (needs a
          date)  → both save.  Why: both maintenance styles exist in your agencies (FR-14).
      [ ] The magic moment: in tinker raise a vehicle's `current_mileage` to near its due point,
          run `php artisan rvms:recalculate-pm`, refresh the page  → the badge flipped to
          "Due Soon" or "Due" on its own.  Why: nobody has to remember maintenance — the system does.
      [ ] Mark one Completed with date/source/parts/remarks  → it moves to the completed view and
          NO new schedule appears by itself.  Why: each cycle is entered deliberately, no surprises.
      [ ] `php artisan schedule:list`  → shows the recalculation job runs automatically.

---
PHASE R6 — Day 11: Dispatch + Availability (FR-15, FR-16, FR-17)
Goal: Opening a dispatch automatically marks the vehicle Dispatched; closing it records the return status; availability shows every vehicle's live status — screen identical to `dispatch.html`.
Prototype source: `web/pages/dispatch.html`.

Sub-tasks (Day 11):
  Block A:
    1. Widget extraction from `dispatch.html` (active-dispatch banner, open/close modals,
       "Others — specify" reveal).
    2. Copy `dispatch.html` → `dispatch.blade.php` verbatim.
    3. CHECKPOINT A: raw copy vs prototype — pixel-identical.
  Block B:
    4. `dispatches` migration/model per the Data Dictionary (active = `time_in IS NULL`),
       including the two optional odometer columns (`odometer_out`, `odometer_in`).
    5. APIs: `POST /dispatches` (sets vehicle → Dispatched; Others requires `mission_other`;
       optional `odometer_out`), `PATCH /{id}/close` (time in + 3-choice return status → vehicle
       updated; optional `odometer_in`, and when present & greater than the vehicle's current
       mileage it updates `vehicles.current_mileage` — mileage-on-arrival, FR-16 → FR-14),
       `GET /dispatches`, `PUT /{id}`, `GET /vehicles/availability`.
    6. Replace hardcoded rows with live data; add the two odometer fields to the open/close
       modals (a documented addition — the prototype's dispatch form has time out/in but no
       odometer field; digitizes the agencies' existing paper odometer field, CDRRMO-confirmed);
       sample seeder (one active, one completed per agency).
    7. Automated tests: open/close/availability suites, incl. odometer optional + mileage-on-close.
    8. CHECKPOINT B: dispatch page vs prototype, with live seeded data → lead approves.

Testing task:
  Automated — `php artisan test`: open flips status; Others without detail rejected; each return status lands on the vehicle; odometer optional; closing with a higher time-in odometer bumps the vehicle's current mileage; availability is agency-only.
  Manual testing checklist (plain language):
    In plain words: this is the logbook of "who took which vehicle where." You are checking that
    taking a vehicle out and bringing it back updates its status with zero extra steps.
      [ ] Dispatch page vs prototype side-by-side  → identical, including the active-count banner
          (plus the added optional odometer field in the open/close modals — a documented addition).
      [ ] Open a dispatch  → that vehicle instantly shows "Dispatched" on the Vehicles page too.
          Why: everyone sees the truck is out, from every screen (FR-15 + FR-18).
      [ ] Close it choosing "Under Preventive Maintenance"  → the vehicle now shows exactly that.
          Why: the return condition is recorded the moment it parks (FR-16).
      [ ] Close a dispatch and type a higher odometer reading for "time in"  → the vehicle's mileage
          on the Vehicles page goes up to match.  Why: bringing a truck back keeps its mileage
          current on its own, which feeds the maintenance reminders (FR-16 → FR-14).
      [ ] Leave the odometer blank  → the dispatch still saves.  Why: it is optional; agencies that
          don't track odometer are not forced to.
      [ ] Pick mission "Others" without typing what it is  → refused until you specify.
      [ ] As another agency's admin  → their dispatch page shows only their own logbook.

---
PHASE R7 — Days 12–13: Notifications + FCM (FR-21, FR-03)
Goal: Every FR-21 alert is saved in the database and delivered — bell + notifications page identical to the prototype; drivers get real push messages via Firebase.
Prototype source: `web/pages/notifications.html` + the topbar bell dropdown present on every prototype page.

Sub-tasks — Day 12 (storage + delivery — no screen wiring yet, so no checkpoints today):
  1. Widget extraction from `notifications.html` + the bell dropdown — recorded now, used Day 13.
  2. `notifications` migration/model per the Data Dictionary (8-type enum incl. New_Access_Request).
  3. APIs: `GET /notifications`, `PATCH /{id}/read`, `PATCH /read-all`, `POST /fcm-token` (driver
     device registration).
  4. `FcmService` — server-side PHP, Google HTTP v1, queued sends (faked in local testing).
  5. Automated tests: notification API + token suites.

Sub-tasks — Day 13 (triggers + screens):
  6. Event triggers: new damage report → ALL agency admins; vehicle status change → the assigned
     driver; driver self-registration → ALL agency admins (New_Access_Request).
  7. Scheduled alerts: `rvms:license-alerts` (Expiring Soon/Expired → admins) + PM Due Soon/Due
     (→ admins, PM Reminder → drivers); both in the scheduler.
  8. Automated tests: trigger suite (FCM transport faked).
  Block A:
    9. Copy the bell dropdown markup into the layout + copy `notifications.html` → `notifications.blade.php`
       verbatim.
    10. CHECKPOINT A: raw copy vs prototype (both the bell and the page) — pixel-identical.
  Block B:
    11. Wire the bell to live unread counts/latest items; replace the notifications page's
        hardcoded rows with live data grouped Today / Yesterday / Earlier; wire mark-read/mark-all.
    12. CHECKPOINT B: bell + notifications page vs prototype, with live seeded data → lead approves.

Testing task:
  Automated — `php artisan test`: users see only their own notifications; foreign mark-read blocked; triggers create the right rows for the right people; commands generate license/PM alerts.
  Manual testing checklist (plain language):
    In plain words: this is the doorbell. You are checking that the right person gets rung for
    the right event — and that you can silence it by reading.
    Note: real phone pushes need Firebase credentials; locally the "send" is simulated, but the
    notification rows you check in the database and on screen are real.
      [ ] Bell + notifications page vs prototype side-by-side  → identical, grouped
          Today / Yesterday / Earlier.
      [ ] Make something happen: change a vehicle's status, file a damage report (robot test or
          curl), run `php artisan rvms:license-alerts`  → the bell count rises and rows appear —
          status change rings the DRIVER, damage/license ring ALL the agency's admins (both BFP
          admins!).  Why: alerts go to everyone responsible, not just one inbox (FR-21).
      [ ] Click one notification read, then "mark all read"  → the red count drops to zero.
      [ ] As another agency's admin  → your bell shows only your agency's news.

---
PHASE R8 — Days 14–15: Dashboard + Reports (FR-19, FR-20)
Goal: The dashboard's 8 live counters and all six filtered, printable reports — screens identical to `dashboard.html` and `reports.html`.
Prototype source: `web/pages/dashboard.html` (full content), `web/pages/reports.html`.

Sub-tasks — Day 14 (Dashboard):
  Block A:
    1. Widget extraction from `dashboard.html` (8 metric cards, action-required lists, layout).
    2. Copy `dashboard.html` → `dashboard.blade.php` verbatim.
    3. CHECKPOINT A: raw copy vs prototype — pixel-identical.
  Block B:
    4. `GET /dashboard/summary` API: the 4 status counts + total vehicles + total drivers +
       expiring licenses + pending damage — agency-scoped.
    5. Replace hardcoded numbers with live counts; the action-required lists render strictly as
       links built from the same FR-19 counts (no new data claims).
    6. Automated tests: summary suite (counts correct, never another agency's).
    7. CHECKPOINT B: dashboard vs prototype, with live data → lead approves.

Sub-tasks — Day 15 (Reports):
  Block A:
    8. Widget extraction from `reports.html` (type selector cards, filter row, print area).
    9. Copy `reports.html` → `reports.blade.php` verbatim.
    10. CHECKPOINT A: raw copy vs prototype — pixel-identical.
  Block B:
    11. `GET /reports/{type}` ×6 (inspections, damage, repairs-maintenance, pm, dispatch,
        vehicle-status) with the documented filters; 422 on bad ranges.
    12. Replace hardcoded content with live filtered data; print-friendly report views, each
        STAMPED with the generating admin's name + generation date (FR-20).
    13. Automated tests: per-report suite.
    14. CHECKPOINT B: reports page vs prototype, with live data → lead approves.

Testing task:
  Automated — `php artisan test`: summary + six report shapes, filters honored, malformed dates rejected, full isolation.
  Manual testing checklist (plain language):
    In plain words: the dashboard is the fleet at a glance; reports are what you print for the
    boss. You are checking the numbers never lie and the printouts are presentable.
      [ ] Dashboard vs prototype side-by-side  → identical cards; in tinker count something by
          hand (e.g. Operational vehicles) → matches the card exactly.
          Why: the summary is computed live from the same records, never a stale copy (FR-19).
      [ ] Reports page: run each of the six types with a date range  → only matching rows appear.
      [ ] Ctrl+P on a report  → clean printable layout, stamped with YOUR name and today's date.
          Why: printed records show who produced them and when (FR-20).
      [ ] All numbers and rows are yours only — spot-check as a second agency.

---
PHASE R9 — Day 16: Profile Page (FR-04)
Goal: The profile screen identical to `profile.html`, rescoped to what the requirements back: the admin edits their OWN account; agency information is display-only.
Prototype source: `web/pages/profile.html`.

Sub-tasks (Day 16):
  Block A:
    1. Widget extraction from `profile.html`.
    2. Copy `profile.html` → `profile.blade.php` verbatim.
    3. CHECKPOINT A: raw copy vs prototype — pixel-identical.
  Block B:
    4. Wire the admin's own name/email/password editing (FR-04, web surface); agency
       name/location/contact shown READ-ONLY (no FR backs editing agency info — documented
       omission per design decision 7).
    5. Enable the sidebar Profile link; automated tests: web profile update (incl. password re-login).
    6. Clear any parked UI nits from the checkpoint feedback of R1–R8.
    7. CHECKPOINT B: profile page vs prototype, with live data → lead approves.

Testing task:
  Automated — `php artisan test`: own-profile update rules (unique email, confirmed password), no cross-user edits.
  Manual testing checklist (plain language):
    In plain words: your own account settings. You are checking you can change your password —
    and that nobody can quietly rewrite the agency's identity.
      [ ] Profile page vs prototype side-by-side  → identical layout.
      [ ] Change your password, sign out, sign in with the new one  → works.
      [ ] Agency name/contact fields  → visible but not editable.
          Why: no requirement allows editing agency identity; showing it read-only keeps the
          prototype look without inventing a feature.

---
PHASE R10 — Days 17–18: Hardening + Final Verification (NFR-01…NFR-05)
Goal: The whole system meets the quality attributes, the complete suite is green, and every screen passes a final side-by-side.
Prototype source: all 10 screens (final pass).

Sub-tasks — Day 17 (performance + security):
  1. Pagination on every list endpoint + page; eager-loading/N+1 audit with query-count assertions.
  2. Login rate limiting (429 after repeated failures); password strength rules; HTTPS/encrypted-transport config notes.
  3. `AgencyIsolationSweepTest`: parameterized proof that EVERY list/show/update/delete endpoint blocks cross-agency access.

Sub-tasks — Day 18 (reliability + compatibility + final pass):
  4. Queue retry/backoff for FCM + scheduled jobs; idempotent status writes; single-status consistency check across all modules.
  5. Consistent JSON envelope + API contract notes handed to the Android build (its consumer).
  6. Chrome / Firefox / Edge pass on every page.
  7. FINAL CHECKPOINT B: all 10 screens vs the prototype with the lead — the v2 exit gate.

Testing task:
  Automated — `php artisan test`: full R0–R9 regression + isolation sweep + rate-limit + pagination/N+1 assertions.
  Manual testing checklist (plain language):
    In plain words: the final safety inspection of the whole building. Nothing new is built
    today — everything is re-checked harder.
      [ ] Run the login curl (guide section C) with a WRONG password ~6 times fast  → it starts
          refusing with "429 Too Many Requests".  Why: password-guessing robots get locked out.
      [ ] Change one vehicle's status once  → the SAME status shows on the vehicle row, the
          availability list, the dashboard card, and the vehicle-status report.
          Why: one truth, never conflicting copies (FR-18 / NFR-04).
      [ ] Long lists arrive in pages, and pages load fast.  Why: it stays quick when real data grows.
      [ ] Open the dashboard in Chrome, Firefox, and Edge  → same look, same behavior (NFR-05).
      [ ] `php artisan test`  → the entire suite green, one last time.

---

Coverage: FR-01–FR-04 (R0/R1/R9; approval flow in R2), FR-05–FR-08 (R2), FR-09/FR-10 (R3), FR-11–FR-13 (R4), FR-14 (R5), FR-15–FR-17 (R6), FR-18 (every status-writing phase R2–R6), FR-21 (R7), FR-19/FR-20 (R8), NFR-01–NFR-05 (throughout, sealed in R10).

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
