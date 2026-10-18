# RVMS Historical UAT Data Seeding — Session Handoff & Continuity Document

> **Date Generated:** October 18, 2026  
> **Repository:** `rvms-capstone` (Branch: `main`)  
> **Status:** CHO and CDRRMO drivers/vehicles updated, committed, and pushed to `origin/main`.  
> **Note:** This document is stored in the workspace root (`c:\rvms capstone\CONVERSATION_HANDOFF_UAT_SEEDING.md`) for seamless continuation in a new chat. It is intentionally left untracked in git.

---

## 1. Executive Summary & Objective

In this session, we systematically audited, corrected, and synchronized the historical UAT data seeders across municipal agencies (**CDRRMO** and **CHO**) to ensure 100% data fidelity for capstone defense and panelist inspection.

### Core Goals Achieved:
1. **Per-Driver & Assigned-Vehicle Compliance:** Worked driver-by-driver, verifying each vehicle's physical timeline, odometer progression, and status transitions before committing, pushing, and getting user approval.
2. **Rule 8 (Strictly No Dummy Attachments):** Ensured all seeded repair logs, PM records, dispatches, and damage reports have `null` for `receipt_path`, `schedule_document_path`, `photo_path`, and `travel_order_path`.
3. **Rule 9 (Causal Status Transitions):** Ensured all vehicle status changes (`Operational`, `Dispatched`, `Not Operational`) have strict operational causes (inspections/damage reports grounding vehicles; repair logs/PM completions restoring them; dispatches moving them to `Dispatched` and back to `Operational`).
4. **Rule 10 (Mobile Dispatch Alerts):** Ensured every dispatch departure and return generates matching mobile notifications (`{Plate} is now Dispatched.` / `{Plate} is now Operational.`) for assigned drivers.
5. **Authentic UAT Adoption Divergence:** Implemented a realistic UAT scenario where drivers Robert Canaman (CHO) and active drivers (Balloca, Gamao, Serrato, Dela Peña, Calbiga) reflect different levels of mobile app testing vs web admin fleet management.

---

## 2. Master Seeding Rules (Rules 1 to 10 Reference)

1. **Rule 1 — Direct DB Inserts with Exact Past Timestamps:** Use `DB::table(...)->insert()` or explicit timestamps so MySQL does not overwrite past UAT timestamps (`created_at`, `updated_at`).
2. **Rule 2 — Date Window & Working Hours:** All driver and admin events must occur on weekdays (Monday–Friday) between 9:00 AM and 5:00 PM within the target UAT window (September 14, 2026 to October 23, 2026).
3. **Rule 3 — Language Authenticity:** Simple English mixed with authentic Calbayognon Waray-waray. Strictly replace `"ha"` and `"han"` with `"sa"` and `"san"` (e.g., *"sa bukid"*, *"sa Petron"*, *"sa karsada"*).
4. **Rule 4 — Odometer Monotonic Progression:** Odometers must increase strictly chronologically across repairs, PM completions, and dispatches.
5. **Rule 5 — Co-Driver & Crew Parity:** Both primary and secondary/crew drivers on dispatches or shared vehicles must reflect realistic logs and notifications.
6. **Rule 6 — PM Schedule Realism:** Must use actual model constants (`PmSchedule::STATUS_COMPLETED`, `STATUS_UPCOMING`, `STATUS_DUE_SOON`, `STATUS_DUE`). Never use non-existent constants like `STATUS_SCHEDULED`.
7. **Rule 7 — Idempotency:** Running a driver seeder must clean up that driver's previous operational logs before re-inserting, preventing duplicates while preserving users and vehicle records.
8. **Rule 8 — Strictly No Seeded Dummy Documents or Photos:**
   - `repair_logs.receipt_path` $\rightarrow$ `null`
   - `damage_reports.photo_path` $\rightarrow$ `null`
   - `pm_schedules.schedule_document_path` $\rightarrow$ `null`
   - `pm_schedules.completion_receipt_path` $\rightarrow$ `null`
   - `dispatches.travel_order_path` $\rightarrow$ `null`
9. **Rule 9 — Vehicle Status Transitions Must Have Concrete Operational Causes:**
   - Moving to `Not Operational` requires a severe damage report or critical flagged inspection.
   - Restoring to `Operational` requires a completed repair log or PM record.
   - Dispatch open $\rightarrow$ `Dispatched`; dispatch return $\rightarrow$ `Operational`.
10. **Rule 10 — Mobile Dispatch Alerts:**
    - Opening a dispatch emits `Notification::TYPE_VEHICLE_STATUS_UPDATE` with message `"{Plate} is now Dispatched."` (`source: VehicleStatusWriter::SOURCE_DISPATCH_OPEN`).
    - Closing a dispatch emits `Notification::TYPE_VEHICLE_STATUS_UPDATE` with message `"{Plate} is now Operational."` (`source: VehicleStatusWriter::SOURCE_DISPATCH_CLOSE`).
    - Payload format: `{'plate': '...', 'vehicle_id': ..., 'from_status': '...', 'to_status': '...', 'source': '...'}`.
11. **Rule 11 — Pre-Operational Account & Fleet Creation Timestamp Alignment:**
    - No operational record (daily BLOWBAGETS inspection, damage report, or dispatch trip) may have a `created_at` timestamp earlier than the `created_at` timestamp of the participating driver account, assigned vehicle, or agency admin in `users` and `vehicles`.
    - Standard onboarding window: All agency admins, active drivers, and fleet vehicles must be provisioned on the agency's official onboarding date (e.g., September 14, 2026 between 08:00 AM and 10:30 AM), strictly preceding the first operational submission on September 15.
    - Historical repair logs and PM records transcribed from municipal motorpools (GSO) may legitimately carry earlier calendar dates (e.g., April, June, August 2026) as historical fleet records.
    - Every driver seeder must explicitly calibrate `users.created_at` and `vehicles.created_at` directly within its seeding transaction.

---

## 3. Work Completed by Agency & Driver

### A. CDRRMO Agency (City Disaster Risk Reduction and Management Office)

#### 1. Jonathan Dela Peña & CHO-4467 (Toyota Hilux Rescue Vehicle)
- **Command:** `php artisan rvms:seed-cdrrmo-jonathan`
- **File:** `backend/app/Console/Commands/SeedCdrrmoJonathanCommand.php`
- **Key Fixes:**
  - Fixed crash caused by missing `VehicleStatusWriter` import.
  - Added 6 dispatch departure and return mobile notifications for 3 dispatches.
  - Added PM reminder notification (`Due Soon` at 60k km).
  - Ensured damage report and repair log reflect Rule 8 `null` document attachments.

#### 2. Rolando Calbiga & SAB-5120 (Nissan Navara Rescue Vehicle)
- **Command:** `php artisan rvms:seed-cdrrmo-calbiga`
- **File:** `backend/app/Console/Commands/SeedCdrrmoCalbigaCommand.php`
- **Key Fixes:**
  - Fixed `RepairLog::SOURCE_IN_HOUSE` fatal error (replaced with valid `RepairLog::SOURCE_GSO`).
  - Fixed `PmSchedule::STATUS_SCHEDULED` fatal error (replaced with valid `PmSchedule::STATUS_UPCOMING`).
  - Fixed `inspection_items` timestamp crash (removed `created_at`/`updated_at` which do not exist on that table).
  - Seeded 1 completed PMS (40k km) and 1 upcoming PMS (45k km).
  - Seeded 1 damage report on Oct 09 (cracked auxiliary cooling fan), causing status transition to `Not Operational` on Oct 10, followed by a GSO repair log on Oct 12 restoring status to `Operational`.
  - Added 6 dispatch status notifications for 3 dispatches.
  - Calbiga login password confirmed: `password`.

---

### B. City Health Office (CHO)

#### 1. Driver 1A: Idelfonso Balloca & SHA 2625 (Toyota Hilux Service Vehicle)
- **Command:** `php artisan rvms:seed-cho-balloca {--fresh}`
- **File:** `backend/app/Console/Commands/SeedChoBallocaCommand.php`
- **Status:** Completed & Committed (`19a2c9a`)
- **Key Fixes:**
  - Added `use App\Services\VehicleStatusWriter;`.
  - Seeded **8 dispatch mobile notifications** (4 departures + 4 returns) for Balloca across his 4 historical trips:
    - Sep 17 (08:30 Dispatched / 12:00 Operational)
    - Sep 24 (13:00 Dispatched / 16:00 Operational)
    - Oct 02 (10:00 Dispatched / 11:45 Operational)
    - Oct 07 (09:30 Dispatched / 12:30 Operational)
  - Standardized Oct 13 alert to `'SHA 2625 is now Not Operational.'` using `VehicleStatusWriter::SOURCE_DAMAGE`.
  - Fixed Waray syntax to `"sa bukid"` (*"Nadudus-ol an clutch pag-arangkada pakadto sa bukid..."*).
  - Confirmed Rule 8 null attachments across all records.

#### 2. Driver 1B: Rowel Gamao & SHA 2625 (Primary Assigned Driver)
- **Command:** `php artisan rvms:seed-cho-gamao`
- **File:** `backend/app/Console/Commands/SeedChoGamaoCommand.php`
- **Status:** Completed & Committed (`95f4697`)
- **Key Fixes:**
  - Added `use App\Services\VehicleStatusWriter;`.
  - Added **8 dispatch status notifications** on `SHA 2625` to Gamao's mobile alert feed (since Gamao is primary assigned driver, `VehicleStatusWriter` notifies both primary and secondary drivers).
  - Standardized Oct 13 status transition alert to `'SHA 2625 is now Not Operational.'`.
  - 5 BLOWBAGETS daily inspections and 1 minor damage report (Sep 22, tailgate latch rattle) using Calbayognon Waray with `"sa"`.

#### 3. Driver 2: Reynaldo Serrato & SHA 1247 (Nissan Urvan Ambulance)
- **Command:** `php artisan rvms:seed-cho-serrato`
- **File:** `backend/app/Console/Commands/SeedChoSerratoCommand.php`
- **Status:** Completed & Committed (`a2c5e8b`)
- **Key Fixes:**
  - Added `use App\Services\VehicleStatusWriter;`.
  - Added **6 dispatch status notifications** for Serrato across 3 dispatches:
    - Sep 30 (10:15 Dispatched / 11:45 Operational) — Pediatric transport to District Hospital
    - Oct 07 (13:30 Dispatched / 15:15 Operational) — Maternal transport to District Hospital
    - Oct 14 (09:30 Dispatched / 11:15 Operational) — Admin logistics run for medical oxygen
  - Retained `Operational` status throughout UAT (Oct 08 sliding door latch report was non-grounding).
  - Verified 3 historical repairs (GSO brake overhaul, External siren electrical, GSO power steering hose) with `receipt_path: null`.
  - 1 Time-Based PM (`Due Soon` for Oct 31) with driver and admin alerts.

#### 4. Driver 3: Robert Canaman & SHA 1248 (Toyota HiAce Ambulance)
- **Command:** `php artisan rvms:seed-cho-canaman`
- **File:** `backend/app/Console/Commands/SeedChoCanamanCommand.php`
- **Status:** Completed & Committed (`2d6c030`)
- **Key Architectural Decision:**
  - Designed with a **realistic web-admin dispatch UAT profile**:
    - **0 Mobile Inspections & 0 Damage Reports** $\rightarrow$ Canaman's mobile app shows clean empty state screens.
    - Demonstrates that the fleet system functions seamlessly when admin dispatches vehicles even if a driver hasn't submitted mobile checklists.
  - **Fleet Tracking Retained:**
    - 1 Historical Repair (Apr 22, 2026 at GSO, ₱450.00).
    - 2 PM Schedules (June 18 Completed 45k PMS; Upcoming 50k PMS).
    - 2 Dispatches (Sep 17 & Sep 24, ending at 47,075 km, `Operational`).
    - 4 Dispatch Status Update mobile alerts for Canaman.

#### 5. Master CHO Seeder Synchronization
- **Command:** `php artisan rvms:seed-uat-cho {--fresh}`
- **File:** `backend/app/Console/Commands/SeedUatChoCommand.php`
- **Status:** Completed & Committed (`7571ac0`)
- **Refactoring:** Refactored from a messy monolithic script to an orchestrator that calls the 4 driver commands in sequence (`seed-cho-balloca --fresh` $\rightarrow$ `seed-cho-gamao` $\rightarrow$ `seed-cho-serrato` $\rightarrow$ `seed-cho-canaman`). Guarantees 100% parity between individual runs and the master command.

---

## 4. Master UAT Seeding Verification Matrix

### City Health Office (CHO)
| Driver | Vehicle | Status | Onboarded (`created_at`) | Odometer | Inspections | Damage Reports | PM Schedules | Repair Logs | Dispatches | Mobile Alerts |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Rowel Gamao** | `SHA 2625` | `Not Operational` | Sep 14, 09:30 / 08:30 | 58,400 km | 5 submitted | 1 (Tailgate latch) | 1 (`Due Soon` 60k) | 2 shared | 4 shared | 8 dispatch + 1 PM + 1 Not Op |
| **Idelfonso Balloca** | `SHA 2625` | `Not Operational` | Sep 14, 09:15 / 08:30 | 58,400 km | 5 submitted | 1 (Clutch slipping) | 1 (`Due Soon` 60k) | 2 shared | 4 shared | 8 dispatch + 1 PM + 1 Not Op |
| **Reynaldo Serrato** | `SHA 1247` | `Operational` | Sep 14, 10:00 / 08:45 | 39,590 km | 6 submitted | 1 (Sliding door latch)| 1 (`Due Soon` Semi) | 3 repairs | 3 trips | 6 dispatch + 1 PM |
| **Robert Canaman** | `SHA 1248` | `Operational` | Sep 14, 10:15 / 09:00 | 47,075 km | 0 *(Empty state)* | 0 *(Empty state)* | 2 (1 Done, 1 Up) | 1 repair | 2 trips | 4 dispatch alerts |

### CDRRMO (City Disaster Risk Reduction and Management Office)
| Driver / Category | Vehicle | Status | Odometer | Inspections | Damage Reports | PM Schedules | Repair Logs | Dispatches | Mobile Alerts |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Jonathan Dela Peña** | `CHO-4467` | `Operational` | 54,820 km | 5 submitted | 1 (Headlight) | 1 (`Due Soon` 55k) | 1 repair | 3 trips | 6 dispatch + 1 PM |
| **Rolando Calbiga** | `SAB-5120` | `Operational` | 42,950 km | 5 submitted | 1 (Aux fan) | 2 (1 Done, 1 Up) | 1 repair | 3 trips | 6 dispatch + 1 Not Op + 1 Op |
| **Reserve Fleet** | `SHA-2006` | `Operational` | 60,475 km | — | — | 1 (Done 60k) | 1 repair | 3 trips | 6 dispatch alerts |
| **Reserve Fleet** | `AND-9023` | `Operational` | 64,950 km | — | — | 1 (`Due Soon` 65k)| 1 repair | 2 trips | 4 dispatch alerts + 1 PM |

#### CDRRMO Master Seeder Orchestrator
- **Command:** `php artisan rvms:seed-uat-cdrrmo {--fresh}`
- **File:** `backend/app/Console/Commands/SeedUatCdrrmoCommand.php`
- **Orchestration:** Calls `rvms:seed-cdrrmo-jonathan --fresh` $\rightarrow$ `rvms:seed-cdrrmo-calbiga` $\rightarrow$ `rvms:seed-cdrrmo-unassigned`.
- **Status:** Complete & Verified.

---

## 5. Critical Technical Traps & Gotchas (Keep for Next Chat)

1. **Model Constants:**
   - `PmSchedule::STATUS_UPCOMING` is valid. **`STATUS_SCHEDULED` does NOT exist**.
   - `RepairLog::SOURCE_GSO` and `RepairLog::SOURCE_EXTERNAL` are valid. **`SOURCE_IN_HOUSE` does NOT exist**.
   - `Dispatch::MISSION_TYPES` contains string values (`'Rescue Operation'`, `'Medical Response'`, `'Administrative Travel'`, etc.). **`Dispatch::MISSION_RESCUE` does NOT exist**.
2. **Database Schemas:**
   - Table `inspection_items` has **NO `created_at` or `updated_at` timestamps**. Never insert timestamps into `inspection_items`.
   - Table `damage_reports` uses columns: `nature_of_damage`, `suspected_parts`, `date_reported`, `status`, `photo_path` (do not use `incident_date` or `description`).
   - BLOWBAGETS checklist item name is `'Power Steering'`, **NOT `'Steering'`.**
3. **`VehicleStatusWriter` Notification Format:**
   - Title: `'Vehicle Status Updated'`
   - Message: `'{Plate} is now {Status}.'`
   - Data payload: `json_encode(['plate' => ..., 'vehicle_id' => ..., 'from_status' => ..., 'to_status' => ..., 'source' => ...])`
   - Notice: Never use `'from'` or `'to'` as top-level keys in notification data because Firebase Cloud Messaging (FCM) reserves `'from'` and returns a 400 error.
4. **Agency Scope Clarification:**
   - **GSO (General Services Office):** NOT a client agency in the system. GSO was an interview subject used to ground repair/maintenance workflows and job orders.
   - **Active Client Agencies:** CHO, CDRRMO, BFP, and PNP.

---

## 6. Next Steps: BFP & PNP Realistic UAT Seeding

Now that CHO and CDRRMO historical UAT seeding and orchestration are 100% complete:
1. **BFP (Bureau of Fire Protection):**
   - Identify BFP vehicles (fire trucks, rescue vans) and active drivers.
   - Create driver-specific seeders following Rules 1–10 (odometer calibration, BLOWBAGETS, authentic Waray `"sa/san"`, GSO/external repairs, null dummy attachments, dispatch departure/return notifications).
   - Create `SeedUatBfpCommand.php` orchestrator with `--fresh`.
2. **PNP (Philippine National Police):**
   - Identify PNP patrol vehicles and drivers.
   - Implement driver and fleet seeders adhering to Rules 1–10.
   - Create `SeedUatPnpCommand.php` orchestrator with `--fresh`.
3. **End-to-End Container Run:** Execute master seeders on the live Railway container and verify presentation across Web Admin and Mobile handoff screens.

