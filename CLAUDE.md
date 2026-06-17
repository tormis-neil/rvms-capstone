# CLAUDE.md

> ⚠️ **ALWAYS READ `rvms-source-of-truth.md` (project root) IN FULL BEFORE STARTING ANY TASK.**
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

## Approved ERD

13 domain tables + Laravel/Sanctum framework tables.

```
agencies ─┬─< users ──────────────┐
          ├─< vehicles ───────────┤  vehicles.assigned_driver_id → users.id (driver)
          ├─< inspections          
          ├─< damage_reports       
          ├─< repair_logs          
          ├─< pm_schedules         
          ├─< dispatches           
          └─< notifications        

vehicles ─┬─< inspections ─< inspection_items >─ inspection_checklist_items
          ├─< damage_reports
          ├─< repair_logs
          ├─< pm_schedules
          └─< dispatches

users(driver) ─< inspections / damage_reports / repair_logs / dispatches  (driver_id)
users(admin)  ─< inspections.reviewed_by / damage_reports.reviewed_by
users         ─< notifications (recipient)
```

**Relationships:** agency 1—* {users, vehicles, inspections, damage_reports, repair_logs,
pm_schedules, dispatches, notifications}; driver(user) 1—1 vehicle (primary, via
`vehicles.assigned_driver_id`); vehicle 1—* {inspections, damage_reports, repair_logs,
pm_schedules, dispatches}; inspection 1—* inspection_items; checklist_item 1—*
inspection_items; user 1—* notifications.

**Modeling notes:** drivers are `users` with `role='driver'` (carry license fields);
admins are `users` with `role='admin'` (null license fields). Dispatch active/completed is
derived from `time_in IS NULL`. PM due-state is recomputed by a scheduled job.

---

## Approved Data Dictionary

Conventions: `id` = BIGINT UNSIGNED AI PK; FKs = BIGINT UNSIGNED; `created_at`/`updated_at`
nullable TIMESTAMP; every scoped table indexed on `agency_id`.

**agencies** (FR-02): `code` VARCHAR(10) NN unique · `name` VARCHAR(255) NN · `location`
VARCHAR(255) · `contact_number` VARCHAR(50) · `email` VARCHAR(255) · `logo_path`
VARCHAR(255) · `license_expiry_warning_days` SMALLINT UNSIGNED NN default 30 (configurable, FR-06).

**users** (FR-01/02/04/06/19): `agency_id` FK NN · `role` ENUM('admin','driver') NN ·
`name` VARCHAR(255) NN · `email` VARCHAR(255) NN unique · `password` VARCHAR(255) NN (hash)
· `license_number` VARCHAR(50) null · `license_expiry_date` DATE null · `fcm_token`
VARCHAR(255) null · `email_verified_at`/`remember_token` (framework).

**vehicles** (FR-03/05/15/16): `agency_id` FK NN · `assigned_driver_id` FK users null ·
`type` VARCHAR(100) NN · `plate_number` VARCHAR(20) NN · `make` VARCHAR(100) NN · `model`
VARCHAR(100) NN · `engine_number` VARCHAR(50) null · `chassis_number` VARCHAR(50) null ·
`current_mileage` INT UNSIGNED NN default 0 · `status`
ENUM('Operational','Dispatched','Not Operational','Under Preventive Maintenance') NN
default 'Operational'.

**inspection_checklist_items** (FR-07, seed catalog): `name` VARCHAR(100) NN · `is_bfp_only`
TINYINT(1) NN default 0 · `sort_order` SMALLINT UNSIGNED NN default 0. (12 standard +
Hydraulic System & Fire Pump as BFP-only.)

**inspections** (FR-07/08): `agency_id` FK NN · `vehicle_id` FK NN · `driver_id` FK users NN
· `inspection_date` DATE NN · `review_status` ENUM('Pending','Reviewed') NN default
'Pending' · `reviewed_by` FK users null · `reviewed_at` DATETIME null.

**inspection_items** (FR-07): `inspection_id` FK NN · `checklist_item_id` FK NN · `status`
ENUM('OK','Has Issue') NN · `remarks` TEXT null (required when Has Issue).

**damage_reports** (FR-09/10/17): `agency_id` FK NN · `vehicle_id` FK NN · `driver_id` FK
users NN · `nature_of_damage` TEXT NN · `suspected_parts` VARCHAR(255) null · `photo_path`
VARCHAR(255) null · `date_reported` DATE NN (auto) · `status` ENUM('Pending','Reviewed') NN
default 'Pending' · `reviewed_by` FK users null · `reviewed_at` DATETIME null.

**repair_logs** (FR-11): `agency_id` FK NN · `vehicle_id` FK NN · `driver_id` FK users null
· `repair_date` DATE NN · `scope_of_work` TEXT NN · `parts_replaced` TEXT null · `cost`
DECIMAL(10,2) null · `repair_source` ENUM('Internal Office','GSO Motorpool','External Repair
Shop') NN · `external_shop_name` VARCHAR(255) null · `remarks` TEXT null.

**pm_schedules** (FR-12/19): `agency_id` FK NN · `vehicle_id` FK NN · `service_target`
VARCHAR(255) NN · `pm_type` ENUM('Mileage-Based','Time-Based') NN · `interval_km` INT
UNSIGNED null · `last_pm_mileage` INT UNSIGNED null · `due_mileage` INT UNSIGNED null ·
`due_date` DATE null · `due_soon_threshold_km` INT UNSIGNED null · `due_soon_threshold_days`
SMALLINT UNSIGNED null · `status` ENUM('Upcoming','Due Soon','Due','Completed') NN default
'Upcoming' · `date_serviced` DATE null · `completion_repair_source` ENUM(same as
repair_source) null · `completion_parts_replaced` TEXT null · `completion_remarks` TEXT null.

**dispatches** (FR-13/14/15): `agency_id` FK NN · `vehicle_id` FK NN · `driver_id` FK users
NN · `mission_type` ENUM('Fire Response','Medical Response','Rescue Operation','Patrol',
'Administrative Travel','Others') NN · `mission_other` VARCHAR(255) null · `location`
VARCHAR(255) NN · `time_out` DATETIME NN · `time_in` DATETIME null · `return_status`
ENUM('Operational','Not Operational','Under Preventive Maintenance') null · `remarks` TEXT null.

**notifications** (FR-19): `agency_id` FK NN · `user_id` FK NN · `type` ENUM('PM_Reminder',
'Vehicle_Status_Update','New_Damage_Report','License_Expiring','License_Expired',
'PM_Due_Soon','PM_Due') NN · `title` VARCHAR(255) NN · `message` TEXT NN · `data` JSON null ·
`is_read` TINYINT(1) NN default 0 · `read_at` DATETIME null.

---

## Approved Backend Development Plan (phase order)

Each phase ships `/api/v1` endpoints + the matching admin Blade page, and **closes with a
testing task** (PHPUnit feature tests per endpoint asserting: success shape/code, 401, 403
wrong-role, cross-agency isolation, 422; plus unit tests for model methods/scopes/helpers;
plus manual `route:list` / Postman / `tinker` / browser / MySQL checks) before the next phase.

1. **Foundation, Auth & Agency Scoping** — FR-01, FR-02, NFR-02. Laravel+MySQL+Sanctum,
   agencies/users + seeders, login/logout/me, role middleware + agency global scope,
   admin web login + dashboard shell.
2. **Vehicle & Driver Records + License Monitoring** — FR-03, FR-04, FR-05, FR-06, FR-16.
   Vehicle CRUD + status, driver CRUD, `my-vehicle`, `licenses/monitoring`, Blade pages.
3. **Digital BLOWBAGETS Inspection** — FR-07, FR-08. Checklist catalog/seeder, driver
   submission (remarks required on Has Issue; BFP +2 items), monitoring/history/frequent
   issues, review + status update, Blade page.
4. **Damage Reporting & Repair Logging** — FR-09, FR-10, FR-11, FR-16. Damage submit (photo,
   Pending), admin review + status, repair CRUD + status, Blade pages.
5. **Preventive Maintenance Scheduling** — FR-12. PM CRUD, completion, scheduled
   `rvms:recalculate-pm` status job (Upcoming/Due Soon/Due), Blade page.
6. **Dispatch Logging & Availability** — FR-13, FR-14, FR-15, FR-16. Open (→Dispatched),
   close (return status), list/edit, `vehicles/availability`, Blade page.
7. **Notifications & FCM** — FR-06, FR-12, FR-19, NFR-04. notifications API, FCM HTTP v1
   service + token registration, event triggers (damage→admin, status→driver), scheduled
   license/PM alert jobs, bell + Blade page.
8. **Dashboard Summary & Reports** — FR-17, FR-18. Summary counts, six filtered report
   endpoints, printable Blade views, dashboard + reports pages.
9. **NFR Hardening & Final Verification** — NFR-01–NFR-05. Pagination/eager-load/indexes,
   security + agency-isolation sweep + rate limiting, queue retries/status consistency,
   cross-browser checks, full green regression.

---

## Non-Negotiable Rules (every task session)

1. **Read `rvms-source-of-truth.md` first** — every session, before any work.
2. **Explain before executing** — describe what you will build and why, then wait.
3. **Wait for explicit approval** — do not start coding until told "start Phase X, Task Y".
4. **One task at a time** — complete and test a single task before moving on.
5. **Never invent features** — build only what the source of truth/approved plan defines.
6. **Vehicle statuses are exactly the four values**; PM/mission/repair-source/notification
   enums are exactly as documented above.
7. **Agency-scope every query**; **store thresholds as columns**; **all API routes use
   `/api/v1/`**; **Sanctum bearer auth**; **FCM server-side via HTTP v1**.
8. **Every phase ends with its testing task** (automated + manual) before the next begins.
