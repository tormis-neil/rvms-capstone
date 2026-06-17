# CLAUDE.md

> ⚠️ **ALWAYS READ `skills/rvms-source-of-truth.md` IN FULL BEFORE STARTING ANY TASK.**
> It is the single source of truth for scope, requirements (FR-01–FR-19, NFR-01–NFR-05),
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
php artisan migrate:fresh --seed     # rebuild schema + seed agencies/admins/drivers/checklist
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
   not a separate table. Drivers must authenticate on the mobile app (FR-01/FR-05), so a
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
   module (FR-16).

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
| driver (user) → vehicle | one-to-one (primary driver) | `vehicles.assigned_driver_id` |
| vehicle → inspections / damage_reports / repair_logs / pm_schedules / dispatches | one-to-many | `vehicle_id` on each |
| driver (user) → inspections / damage_reports / repair_logs / dispatches | one-to-many | `driver_id` on each |
| admin (user) → reviewed inspections / damage_reports | one-to-many | `reviewed_by` |
| inspection → inspection_items | one-to-many | `inspection_items.inspection_id` |
| checklist item (catalog) → inspection_items | one-to-many | `inspection_items.checklist_item_id` |
| user → notifications | one-to-many | `notifications.user_id` |

**Framework tables (Laravel/Sanctum):** `personal_access_tokens` (NFR-02 token auth),
`password_reset_tokens`, `jobs`/`failed_jobs` (queued FCM sends, FR-19), `cache`. These are
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
| license_expiry_warning_days | SMALLINT UNSIGNED | No | 30 | **Configurable threshold** (FR-06): days before expiry a license is flagged "Expiring Soon". Stored as a column, not a constant. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `users` — FR-01, FR-02, FR-04, FR-06, FR-19
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies. Scopes the user to one agency (FR-02). |
| role | ENUM('admin','driver') | No | — | Authorization role; routes admin→web, driver→mobile (FR-01). |
| name | VARCHAR(255) | No | — | Full name of admin or driver (FR-04). |
| email | VARCHAR(255) | No | — | Login identifier; unique (FR-01). |
| password | VARCHAR(255) | No | — | Bcrypt/Argon hash (NFR-02). |
| license_number | VARCHAR(50) | Yes | NULL | Driver license no. (FR-04). Null for admins. |
| license_expiry_date | DATE | Yes | NULL | Driver license expiry; drives FR-06 monitoring. Null for admins. |
| fcm_token | VARCHAR(255) | Yes | NULL | Firebase device token for push delivery (FR-19). |
| email_verified_at | TIMESTAMP | Yes | NULL | Framework field. |
| remember_token | VARCHAR(100) | Yes | NULL | Framework field. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `vehicles` — FR-03, FR-05, FR-15, FR-16
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (FR-02 scoping). |
| assigned_driver_id | BIGINT UNSIGNED | Yes | NULL | FK → users (role=driver). Primary assigned driver (FR-03). |
| type | VARCHAR(100) | No | — | Vehicle type (Fire Truck, Ambulance, Patrol Car…). |
| plate_number | VARCHAR(20) | No | — | Plate number; unique per agency. |
| make | VARCHAR(100) | No | — | Manufacturer (Isuzu, Toyota…). |
| model | VARCHAR(100) | No | — | Model (FTR 850, Hiace…). |
| engine_number | VARCHAR(50) | Yes | NULL | Engine number (FR-03). |
| chassis_number | VARCHAR(50) | Yes | NULL | Chassis number (FR-03). |
| current_mileage | INT UNSIGNED | No | 0 | Current odometer (km); manually updated, drives mileage-based PM (FR-12). |
| status | ENUM('Operational','Dispatched','Not Operational','Under Preventive Maintenance') | No | 'Operational' | **Single shared operational status** (FR-16), written from every module. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `inspection_checklist_items` — FR-07 (reference/seed catalog)
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| name | VARCHAR(100) | No | — | Item label (Battery, Lights, …, Hydraulic System, Fire Pump). |
| is_bfp_only | TINYINT(1) | No | 0 | 1 for the two BFP-only items (Hydraulic System, Fire Pump). |
| sort_order | SMALLINT UNSIGNED | No | 0 | Display order on the checklist. |

### `inspections` — FR-07, FR-08
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles (history per vehicle). |
| driver_id | BIGINT UNSIGNED | No | — | FK → users (history per driver). |
| inspection_date | DATE | No | — | Date of the daily inspection. |
| review_status | ENUM('Pending','Reviewed') | No | 'Pending' | Admin review state (prototype `review`; FR-08). |
| reviewed_by | BIGINT UNSIGNED | Yes | NULL | FK → users (admin who assessed it). |
| reviewed_at | DATETIME | Yes | NULL | When reviewed. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | `created_at` = submission time. |

### `inspection_items` — FR-07
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| inspection_id | BIGINT UNSIGNED | No | — | FK → inspections. |
| checklist_item_id | BIGINT UNSIGNED | No | — | FK → inspection_checklist_items. |
| status | ENUM('OK','Has Issue') | No | — | Per-item result (FR-07). |
| remarks | TEXT | Yes | NULL | Required when status = 'Has Issue' (enforced at validation). |

### `damage_reports` — FR-09, FR-10, FR-17
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles. |
| driver_id | BIGINT UNSIGNED | No | — | FK → users (submitting driver). |
| nature_of_damage | TEXT | No | — | Description of damage (FR-09). |
| suspected_parts | VARCHAR(255) | Yes | NULL | Suspected defective parts (FR-09). |
| photo_path | VARCHAR(255) | Yes | NULL | Optional photo attachment (FR-09). |
| date_reported | DATE | No | — | Auto-set on submission (FR-09). |
| status | ENUM('Pending','Reviewed') | No | 'Pending' | Default Pending → Reviewed by admin (FR-10). |
| reviewed_by | BIGINT UNSIGNED | Yes | NULL | FK → users (admin reviewer). |
| reviewed_at | DATETIME | Yes | NULL | When reviewed. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `repair_logs` — FR-11
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles. |
| driver_id | BIGINT UNSIGNED | Yes | NULL | FK → users (assigned driver) (FR-11). |
| repair_date | DATE | No | — | Date repair was logged/performed. |
| scope_of_work | TEXT | No | — | Work performed (FR-11). |
| parts_replaced | TEXT | Yes | NULL | Parts replaced (FR-11). |
| cost | DECIMAL(10,2) | Yes | NULL | Optional cost (FR-11). |
| repair_source | ENUM('Internal Office','GSO Motorpool','External Repair Shop') | No | — | Source of repair (FR-11). |
| external_shop_name | VARCHAR(255) | Yes | NULL | Shop name when source = External Repair Shop (prototype `shop`). |
| remarks | TEXT | Yes | NULL | Notes (FR-11). |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `pm_schedules` — FR-12, FR-19
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles. |
| service_target | VARCHAR(255) | No | — | Specific part(s)/service (e.g., "Oil Change & Filter") (FR-12). |
| pm_type | ENUM('Mileage-Based','Time-Based') | No | — | Scheduling basis (FR-12). |
| interval_km | INT UNSIGNED | Yes | NULL | Mileage interval (mileage-based only). |
| last_pm_mileage | INT UNSIGNED | Yes | NULL | Odometer at last service (mileage-based). |
| due_mileage | INT UNSIGNED | Yes | NULL | Target mileage = last_pm_mileage + interval_km. |
| due_date | DATE | Yes | NULL | Target date (time-based only). |
| due_soon_threshold_km | INT UNSIGNED | Yes | NULL | **Configurable** Due-Soon window in km (mileage-based) (FR-12). |
| due_soon_threshold_days | SMALLINT UNSIGNED | Yes | NULL | **Configurable** Due-Soon window in days (time-based) (FR-12). |
| status | ENUM('Upcoming','Due Soon','Due','Completed') | No | 'Upcoming' | Recalculated by scheduled job; SoT values Due Soon/Due/Completed + prototype 'Upcoming'. |
| date_serviced | DATE | Yes | NULL | Completion: date serviced (FR-12). |
| completion_repair_source | ENUM('Internal Office','GSO Motorpool','External Repair Shop') | Yes | NULL | Completion: repair source (FR-12). |
| completion_parts_replaced | TEXT | Yes | NULL | Completion: parts replaced (FR-12). |
| completion_remarks | TEXT | Yes | NULL | Completion: remarks (FR-12). |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

### `dispatches` — FR-13, FR-14, FR-15
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| vehicle_id | BIGINT UNSIGNED | No | — | FK → vehicles. |
| driver_id | BIGINT UNSIGNED | No | — | FK → users (dispatched driver). |
| mission_type | ENUM('Fire Response','Medical Response','Rescue Operation','Patrol','Administrative Travel','Others') | No | — | Mission type (FR-13). |
| mission_other | VARCHAR(255) | Yes | NULL | Free text when mission_type = Others (prototype `missionOther`). |
| location | VARCHAR(255) | No | — | Dispatch location (FR-13). |
| time_out | DATETIME | No | — | Date/time out; opening sets vehicle → Dispatched (FR-13). |
| time_in | DATETIME | Yes | NULL | Date/time in on close; NULL = active (FR-14). |
| return_status | ENUM('Operational','Not Operational','Under Preventive Maintenance') | Yes | NULL | Return status chosen on close (FR-14). |
| remarks | TEXT | Yes | NULL | Optional close remarks (plan §6.8). |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

*(Active vs. Completed is derived from `time_in IS NULL`.)*

### `notifications` — FR-19
| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | auto | PK. |
| agency_id | BIGINT UNSIGNED | No | — | FK → agencies (scoping). |
| user_id | BIGINT UNSIGNED | No | — | FK → users (recipient). |
| type | ENUM('PM_Reminder','Vehicle_Status_Update','New_Damage_Report','License_Expiring','License_Expired','PM_Due_Soon','PM_Due') | No | — | Notification category (FR-19). |
| title | VARCHAR(255) | No | — | Short headline. |
| message | TEXT | No | — | Body text. |
| data | JSON | Yes | NULL | Reference payload (e.g., vehicle plate, link target). |
| is_read | TINYINT(1) | No | 0 | Read/unread flag. |
| read_at | DATETIME | Yes | NULL | When read. |
| created_at / updated_at | TIMESTAMP | Yes | NULL | Audit timestamps. |

The dashboard summary (FR-17) and all reports (FR-18) are computed from these tables — no
separate tables needed.

---

# Approved Backend Development Plan

Phases are ordered by technical dependency (auth/scoping → records → field modules →
cross-cutting notifications → reporting → hardening). Every functional module ships its
`/api/v1/` endpoints (Sanctum bearer) plus its admin Blade dashboard page, and each phase
closes with a testing task before the next begins.

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

Testing task (end of phase):
  Automated — `php artisan test`:
    - `AuthLoginTest`: valid credentials return 200 with `{token, user.role, user.agency}`; bad password returns 422; missing email/password returns 422 with descriptive errors.
    - `AuthMeTest`: `GET /api/v1/me` returns 200 + correct user with a valid token; returns 401 without a token.
    - `AuthLogoutTest`: token is revoked (200), and a reused revoked token returns 401.
    - `AgencyScopeUnitTest` (unit): the global scope adds an `agency_id` filter to a model query for an admin; `EnsureRole` rejects a driver token on an admin-only route (403).
  Manual:
    - Run `php artisan route:list --path=api/v1` and confirm login/logout/me are registered with method, URI, `auth:sanctum`/role middleware, and `AuthController`.
    - Postman: call `/api/v1/login` with valid creds (expect token), missing fields (422), then `/api/v1/me` with no token (401) and with an expired/revoked token (401).
    - `php artisan tinker`: `User::count()` and `Agency::all()` confirm seeded admins/drivers map to the four agencies; `User::first()->password` is a hash, not plaintext.
    - Browser: log in as a BFP admin, confirm the dashboard shell loads and shows the BFP agency context.
    - MySQL CLI: `DESCRIBE agencies; DESCRIBE users;` confirm columns/enums match the approved ERD and seed rows are correct.

---
PHASE 2: Vehicle & Driver Records + License Monitoring
Goal: Admins can fully manage their agency's vehicles and drivers, a driver can view their assigned vehicle, and expiring/expired licenses are detected against the configurable threshold.

Tasks:
  1. Vehicles schema + model — FR-03, FR-16
      What gets built: `vehicles` migration (status enum = the four values, `assigned_driver_id`, mileage, engine/chassis); `Vehicle` model with `agency()`, `assignedDriver()` relations + `BelongsToAgency`.
  2. Vehicle CRUD + status API — FR-03, FR-16
      What gets built: `VehicleController` → `GET/POST/PUT /api/v1/vehicles`, `GET /api/v1/vehicles/{id}`, `PATCH /api/v1/vehicles/{id}/status`; `VehicleRequest` validation; `VehicleResource`.
  3. Driver records API — FR-04
      What gets built: `DriverController` (users where role=driver) → `GET/POST/PUT /api/v1/drivers`, `GET /api/v1/drivers/{id}`; `DriverRequest` (license number/expiry).
  4. Assigned-vehicle viewing for drivers — FR-05
      What gets built: `GET /api/v1/my-vehicle` returning the driver's assigned vehicle details + current status (driver token only).
  5. License expiry monitoring — FR-06
      What gets built: `User` query scopes `expiringSoon()`/`expired()` using `agencies.license_expiry_warning_days`; `GET /api/v1/licenses/monitoring` consolidated view.
  6. Admin Blade pages: Vehicles & Drivers — FR-03, FR-04, NFR-03
      What gets built: `vehicles.blade.php`, `drivers.blade.php` (tables, add/edit/update-status modals).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `VehicleApiTest`: list/create/update/show return correct codes + shapes; `PATCH status` only accepts the four enum values (422 otherwise); unauthenticated 401; driver token 403; **admin from Agency A cannot GET/PUT/DELETE a vehicle owned by Agency B (404/403)**; invalid payload 422.
    - `DriverApiTest`: same five-assertion matrix (success shape, 401, 403, cross-agency isolation, 422 on bad license/expiry).
    - `MyVehicleApiTest`: driver gets their own vehicle (200); an admin token is rejected (403); a driver cannot see another agency's vehicle.
    - `LicenseMonitoringUnitTest` (unit): `expiringSoon()`/`expired()` scopes classify dates correctly around the threshold boundary; monitoring endpoint returns only the caller's agency.
  Manual:
    - `php artisan route:list --path=api/v1` → confirm all vehicle/driver/my-vehicle/licenses routes, methods, middleware, controllers.
    - Postman: create/update a vehicle with valid token; submit bad mileage/empty plate (422); call with a wrong-role and a missing token; attempt to fetch another agency's vehicle id (expect blocked).
    - Postman (driver bearer token): call `/api/v1/my-vehicle` and confirm only the assigned vehicle returns.
    - `php artisan tinker`: `Vehicle::with('assignedDriver')->get()` confirms relations + agency scoping; manually set a driver `license_expiry_date` near today and confirm it appears in `expiringSoon()`.
    - Browser: as a BFP admin, open Vehicles and Drivers pages and confirm only BFP records appear.
    - MySQL CLI: `DESCRIBE vehicles;` confirm status enum + FKs match the ERD.

---
PHASE 3: Digital BLOWBAGETS Inspection
Goal: Drivers submit daily inspections (12 standard items, +2 for BFP) with remarks required on flagged items, and admins review submissions, browse per-vehicle/per-driver history, and see frequently reported issues.

Tasks:
  1. Checklist catalog + seeder — FR-07
      What gets built: `inspection_checklist_items` migration/model + seeder (12 standard + Hydraulic System/Fire Pump as `is_bfp_only`); `GET /api/v1/inspections/checklist` returning the correct list for the driver's agency.
  2. Inspection submission API (driver) — FR-07
      What gets built: `inspections` + `inspection_items` migrations/models; `POST /api/v1/inspections` (`InspectionRequest` enforces OK/Has Issue per item and required remarks when Has Issue).
  3. Inspection monitoring API (admin) — FR-08
      What gets built: `GET /api/v1/inspections` (filters: vehicle, driver, date), `GET /api/v1/inspections/{id}`, `GET /api/v1/inspections/frequent-issues` (grouped issue counts).
  4. Inspection review + status update — FR-08, FR-16
      What gets built: `PATCH /api/v1/inspections/{id}/review` (set Reviewed, optional vehicle status change).
  5. Admin Blade page: Inspections — FR-08, NFR-03
      What gets built: `inspections.blade.php` (results table, view-checklist + review-&-assess modals, BFP extra-items section).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `InspectionSubmitTest`: valid submission returns 201 + stored items; **Has Issue without remarks returns 422**; unauthenticated 401; admin token submitting returns 403; a BFP driver's checklist includes 14 items while others get 12.
    - `InspectionMonitoringTest`: admin lists/filters and reviews (200); driver token 403; **Agency A admin cannot read/review Agency B inspections (404/403)**; bad filter input 422.
    - `InspectionUnitTest` (unit): `resultLabel`/`issueCount` helper and `frequentIssues` aggregation compute correctly; `inspectionItemsFor(agency)` returns the BFP-extended list only for BFP.
  Manual:
    - `php artisan route:list --path=api/v1/inspections` → verify routes, methods, middleware.
    - Postman (driver token): submit an all-OK and a flagged inspection; omit remarks on a flagged item (422); submit with missing/expired token (401).
    - Postman (admin token): list with `?vehicle=&driver=&date=`, open one, review it; try a wrong-role driver token (403).
    - `php artisan tinker`: `Inspection::with('items.checklistItem')->latest()->first()` confirms item rows + statuses; confirm a BFP inspection has 14 item rows.
    - Browser: as an admin, open Inspections; confirm only the agency's submissions and that BFP shows the two extra items.
    - MySQL CLI: confirm `inspection_checklist_items` seeded (14 rows, two `is_bfp_only=1`) and `inspection_items` FK integrity.

---
PHASE 4: Damage Reporting & Repair Logging
Goal: Drivers file damage reports with optional photos, admins review them and update vehicle status, and admins log repair activities with source/parts/cost and update status afterward.

Tasks:
  1. Damage reports schema + photo storage — FR-09
      What gets built: `damage_reports` migration/model; `storage` photo handling (`php artisan storage:link`); `DamageReport` relations + scope.
  2. Damage submission & listing API — FR-09
      What gets built: `POST /api/v1/damage-reports` (multipart photo, date auto-set, status Pending), `GET /api/v1/damage-reports`, `GET /api/v1/damage-reports/{id}` (driver sees own; admin sees agency).
  3. Damage review API (admin) — FR-10, FR-16
      What gets built: `PATCH /api/v1/damage-reports/{id}/review` (mark Reviewed + set vehicle status).
  4. Repair logging API — FR-11, FR-16
      What gets built: `repair_logs` migration/model; `RepairController` → `GET/POST/PUT /api/v1/repairs` (`repair_source` enum + `external_shop_name`), plus vehicle `PATCH status`.
  5. Admin Blade pages: Damage & Repairs — FR-10, FR-11, NFR-03
      What gets built: `inspections-damage.blade.php` damage section + `repairs.blade.php` (review/edit modals).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `DamageReportSubmitTest`: valid submit returns 201 with status Pending + date set; photo optional; unauthenticated 401; admin-submitting 403; missing nature-of-damage 422.
    - `DamageReviewTest`: admin marks Reviewed and updates vehicle status (200); driver token 403; **Agency A admin cannot review Agency B report (404/403)**.
    - `RepairApiTest`: create/list/update (200/201); invalid `repair_source` 422; External Repair Shop requires shop name (422 if missing); 401/403/cross-agency isolation asserted.
    - `RepairUnitTest` (unit): repair-source label helper resolves External Repair Shop + shop name; status-update writes the single `vehicles.status` field.
  Manual:
    - `php artisan route:list` → verify damage + repair routes/middleware.
    - Postman (driver token): submit a damage report with and without a photo; submit empty nature (422); wrong-role/missing token checks.
    - Postman (admin token): review a report and confirm the vehicle status changes; log a repair with each repair source.
    - `php artisan tinker`: `DamageReport::where('status','Pending')->get()`; after review confirm `Vehicle::find(id)->status` updated; `RepairLog::latest()->first()`.
    - Browser: as an admin, open Damage and Repairs pages; confirm only agency records; open an uploaded photo.
    - MySQL CLI: `DESCRIBE damage_reports; DESCRIBE repair_logs;` confirm enums/FKs; confirm a stored `photo_path`.

---
PHASE 5: Preventive Maintenance Scheduling
Goal: Admins create mileage- or time-based PM schedules with a configurable Due-Soon threshold, the system recalculates Due Soon/Due automatically, and admins record completion details.

Tasks:
  1. PM schedule schema + model — FR-12
      What gets built: `pm_schedules` migration (pm_type, interval/last/due mileage, due_date, `due_soon_threshold_km/_days`, status enum, completion fields); `PmSchedule` model + scope.
  2. PM CRUD API — FR-12
      What gets built: `PmController` → `GET/POST/PUT /api/v1/pm-schedules`, `GET /api/v1/pm-schedules/{id}`; `PmRequest` (require km fields for Mileage-Based, date for Time-Based).
  3. PM completion API — FR-12
      What gets built: `PATCH /api/v1/pm-schedules/{id}/complete` (date serviced, repair source, parts, remarks → status Completed); no auto-renewal.
  4. PM status computation command — FR-12, NFR-04
      What gets built: `php artisan rvms:recalculate-pm` scheduled command computing Upcoming/Due Soon/Due from `vehicles.current_mileage`/date vs thresholds; registered in the scheduler.
  5. Admin Blade page: Preventive Maintenance — FR-12, NFR-03
      What gets built: `pm.blade.php` (active + completed tables, create/edit/mark-completed modals).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `PmScheduleApiTest`: create mileage-based and time-based (201); missing interval on mileage-based 422; missing date on time-based 422; 401; driver token 403; **cross-agency isolation** on read/update.
    - `PmCompletionTest`: completing sets status Completed + stores completion fields (200); driver 403.
    - `PmStatusUnitTest` (unit): the recompute logic returns Due/Due Soon/Upcoming correctly at threshold boundaries for both PM types, and never auto-creates a next cycle.
  Manual:
    - `php artisan route:list --path=api/v1/pm-schedules` → verify routes/middleware.
    - `php artisan schedule:list` and `php artisan rvms:recalculate-pm` → confirm the command runs and updates statuses.
    - Postman (admin token): create both PM types; submit a bad threshold (422); complete one; wrong-role/missing-token checks.
    - `php artisan tinker`: set a vehicle's `current_mileage` near `due_mileage`, run the command, confirm `status` flips to Due Soon/Due.
    - Browser: as an admin, open the PM page; confirm only agency schedules and correct status badges.
    - MySQL CLI: `DESCRIBE pm_schedules;` confirm enums/threshold columns; verify a completed row retains completion fields.

---
PHASE 6: Dispatch Logging & Vehicle Availability
Goal: Admins open dispatches (auto-setting the vehicle to Dispatched), close them with a return status, and view current availability of all agency vehicles.

Tasks:
  1. Dispatch schema + model — FR-13
      What gets built: `dispatches` migration (mission_type enum + mission_other, location, time_out/in, return_status); `Dispatch` model + scope; active = `time_in IS NULL`.
  2. Open-dispatch API — FR-13, FR-16
      What gets built: `POST /api/v1/dispatches` (`DispatchRequest`, require mission_other when Others) → sets `vehicles.status = Dispatched`.
  3. Close-dispatch API — FR-14, FR-16
      What gets built: `PATCH /api/v1/dispatches/{id}/close` (time_in + return_status enum) → updates vehicle status.
  4. Dispatch listing, edit & availability — FR-13, FR-15
      What gets built: `GET /api/v1/dispatches`, `PUT /api/v1/dispatches/{id}`, `GET /api/v1/vehicles/availability` (status of all agency vehicles).
  5. Admin Blade page: Dispatch — FR-13, FR-15, NFR-03
      What gets built: `dispatch.blade.php` (active-monitoring banner, close-dispatch modal).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `DispatchOpenTest`: opening returns 201 and vehicle becomes Dispatched; Others without `mission_other` 422; 401; driver token 403; **Agency A cannot dispatch/read Agency B's vehicle**.
    - `DispatchCloseTest`: close with each return status updates the vehicle's single status field (200); invalid return_status 422.
    - `AvailabilityTest`: returns only the caller's agency vehicles with current status; 403 for driver.
    - `DispatchUnitTest` (unit): active/completed derivation from `time_in`; mission label resolves Others + free text.
  Manual:
    - `php artisan route:list` → verify dispatch + availability routes/middleware.
    - Postman (admin token): open a dispatch, confirm vehicle status flips to Dispatched; close it choosing each return status; submit Others with no detail (422); wrong-role/missing-token checks.
    - `php artisan tinker`: `Dispatch::whereNull('time_in')->get()` lists active dispatches; confirm `Vehicle` status transitions on open/close.
    - Browser: as an admin, open the Dispatch page; confirm the active count banner and that only agency dispatches show.
    - MySQL CLI: `DESCRIBE dispatches;` confirm enums/FKs.

---
PHASE 7: Notification Services & FCM
Goal: All FR-19 in-app notifications are persisted and delivered, license/PM alerts fire on schedule, status/damage events notify the right role, and drivers receive FCM pushes.

Tasks:
  1. Notifications schema + in-app API — FR-19
      What gets built: `notifications` migration/model; `GET /api/v1/notifications`, `PATCH /api/v1/notifications/{id}/read`, `PATCH /api/v1/notifications/read-all`.
  2. FCM HTTP v1 client (server-side PHP) — FR-19, NFR-04
      What gets built: `FcmService` using Google service-account/HTTP v1; `POST /api/v1/fcm-token` for driver device registration; queued sends.
  3. Event-driven triggers — FR-19
      What gets built: observers/events — damage submitted → agency admins; vehicle status changed → assigned driver (Vehicle Status Update).
  4. Scheduled alert jobs — FR-06, FR-12, FR-19
      What gets built: `rvms:license-alerts` (Expiring Soon/Expired → admins) and PM Due Soon/Due → admins + PM Reminder → drivers, hooked into the scheduler.
  5. Admin Blade page + bell — FR-19, NFR-03
      What gets built: `notifications.blade.php` + topbar bell dropdown with unread count.

Testing task (end of phase):
  Automated — `php artisan test`:
    - `NotificationApiTest`: list returns only the user's notifications (200); mark-read/read-all update state; 401; **cross-agency isolation** (cannot read another agency user's notifications); marking a foreign notification 403/404.
    - `FcmTokenTest`: driver registers a token (200); admin/driver role rules enforced; invalid token 422.
    - `NotificationTriggerTest`: submitting a damage report creates an admin notification; a vehicle status change creates a driver notification; the scheduled commands create license/PM notifications (FCM transport faked/mocked).
    - `NotificationUnitTest` (unit): notification-type → title/recipient mapping; license/PM threshold selection logic.
  Manual:
    - `php artisan route:list` + `php artisan schedule:list` → verify notification routes and that `rvms:license-alerts`/PM jobs are scheduled.
    - Run `php artisan rvms:license-alerts` and the PM recompute → confirm rows appear in `notifications`.
    - Postman (driver token): register an FCM token, list notifications, mark one read; (admin token) confirm a new damage report produced an admin notification.
    - `php artisan tinker`: trigger a vehicle status change and confirm a `Notification` row for the assigned driver; inspect `data` payload.
    - Browser: as an admin, confirm the bell shows unread agency notifications and the notifications page groups Today/Yesterday/Earlier.
    - MySQL CLI: `DESCRIBE notifications;` confirm type enum + `is_read`/`read_at`.

---
PHASE 8: Dashboard Summary & Report Generation
Goal: Admins see live agency fleet counts and can generate filtered, printable reports for all six report types.

Tasks:
  1. Dashboard summary API — FR-17
      What gets built: `GET /api/v1/dashboard/summary` (counts: 4 statuses, total vehicles, total drivers, expiring licenses, pending damage) — agency-scoped.
  2. Report query endpoints — FR-18
      What gets built: `ReportController` → `GET /api/v1/reports/{type}` for inspections, damage, repairs-maintenance, pm, dispatch, vehicle-status with the documented filters (date range, vehicle, driver, source, status, mission type).
  3. Printable report views — FR-18, NFR-03
      What gets built: print-friendly Blade templates (light-surface, no extra print CSS hacks) for each report type.
  4. Admin Blade pages: Dashboard & Reports — FR-17, FR-18
      What gets built: `dashboard.blade.php` (summary cards + action-required lists) and `reports.blade.php` (type selector + filters).

Testing task (end of phase):
  Automated — `php artisan test`:
    - `DashboardSummaryTest`: returns correct counts for the caller's agency only (200); 401; driver token 403; **counts never include another agency's records**.
    - `ReportApiTest`: each of the six report types returns 200 with the expected shape and honors filters; invalid date range/filter 422; 401; driver 403; **cross-agency isolation** on every report.
    - `ReportUnitTest` (unit): filter-builder applies date/vehicle/driver/source/status/mission constraints correctly; vehicle-status summary reflects live statuses.
  Manual:
    - `php artisan route:list --path=api/v1/reports` and `--path=api/v1/dashboard` → verify routes/middleware.
    - Postman (admin token): call summary and each report with valid filters; pass a malformed date range (422); wrong-role/missing-token checks.
    - `php artisan tinker`: cross-check `Vehicle::where('status',...)->count()` against the summary endpoint numbers.
    - Browser: as an admin, open the Dashboard (verify card counts match data) and Reports (generate each type, use the browser print preview to confirm clean output).
    - MySQL CLI: spot-check that report rows correspond to actual table records for the agency.

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
  Manual:
    - `php artisan route:list` → final review of the complete route table (method, URI, middleware, controller) for the `/api/v1` surface.
    - Postman: re-run the wrong-role / expired-token / cross-agency / bad-input matrix against a sample endpoint from each module.
    - `php artisan tinker`: confirm a single status change is reflected identically via vehicle, dispatch, and report queries (NFR-04).
    - Browser: open the dashboard in Chrome, Firefox, and Edge and confirm parity (NFR-05).
    - MySQL CLI: final schema review against the approved ERD; `EXPLAIN` a couple of scoped list queries to confirm `agency_id` indexes are used (NFR-01).

---

Coverage: FR-01/FR-02 (Phase 1), FR-03–FR-06 (Phase 2), FR-07/FR-08 (Phase 3), FR-09–FR-11
(Phase 4), FR-12 (Phase 5), FR-13–FR-15 (Phase 6), FR-16 (built into every status-changing
module across Phases 2–6), FR-17/FR-18 (Phase 8), FR-19 (Phase 7), NFR-01–NFR-05 (woven
throughout, finalized in Phase 9).

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
