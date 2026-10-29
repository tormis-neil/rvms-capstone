# RVMS Post-Defense Recommendations Implementation & Manuscript Synchronization Plan

**Status:** Reference Master Plan (5 Phases)  
**Target Repository:** `c:\rescue vehicle management system`  
**Target Manuscript:** `after final defense (rvms).pdf` / Word source document  
**Diagram Rule:** All existing diagram images (**Figure 2** System Architecture, **Figure 3** Context Diagram, **Figure 4.1–4.2** DFDs, **Figure 5** FDD, and **Figure 6.1–6.2** ERDs) remain **100% untouched**.  
**Universal Account & Data Rule:** All three features must work dynamically across **all accounts and records**—including your existing custom admin and driver accounts, any new administrator or driver accounts you create in the future, and the pre-seeded UAT accounts. Nothing may be hardcoded to seeded emails or IDs, and we will **never wipe or overwrite your existing database accounts/records** (migrations will run non-destructively without `migrate:fresh`).  
**Writing Rule (Phase 5):** Every manuscript edit must pass through the **Humanizer (`skills/SKILL.md`)** workflow to strip AI-writing patterns (no inflated symbolism, no superficial `-ing` participial tails, no em-dash overuse, no copula avoidance, no rule-of-three padding, and natural academic prose calibrated to the manuscript's existing voice).

---

## Overview of the Three Panel Features

1. **Feature 1 — Unified Dual-Trigger Preventive Maintenance (Whichever Comes First):**
   Unify all preventive maintenance schedules in RVMS so mileage intervals (`interval_km`, `last_pm_mileage`, `due_mileage`, `due_soon_threshold_km`) and calendar dates (`due_date`, `due_soon_threshold_days`) run concurrently on every schedule (with no single-trigger `PM Type` selector in the UI), triggering `Due Soon` or `Due` alerts based on whichever threshold is reached first.
2. **Feature 2 — Driver Diagnostic Transparency on Status Alerts:**
   Allow Authorized Drivers on the Android mobile app to tap any vehicle status notification (`Vehicle_Status_Update`) to view the Agency Administrator's **Administrative Findings / Mechanical Remarks**, status transition (`from_status` $\rightarrow$ `to_status`), and triggering module explaining why the vehicle was grounded, serviced, or cleared for duty.
3. **Feature 3 — Unified Mandatory Administrative Findings / Mechanical Remarks (Initial Registration & All Status Updates):**
   Unify the remarks field across initial vehicle registration, vehicle edits, manual status changes, inspection reviews, and damage assessments under the single mandatory label **`Administrative Findings / Mechanical Remarks (Required)`** so baseline intake assessments and subsequent mechanical status reasons are consistently documented from day one.

---

## Phase 1: Feature 3 — Mandatory Baseline Remarks upon Initial Vehicle Registration

### 1. Goal
Require Agency Administrators to record baseline condition / intake remarks when registering a new vehicle into the fleet (while keeping remarks optional when editing basic vehicle metadata later), and surface those baseline/current remarks on both the Web Dashboard and the Android Mobile App.

### 2. Tasks
- **Backend Validation & Controllers:**
  - Update `backend/app/Http/Requests/StoreVehicleRequest.php`:
    - Require `remarks` (`['required', 'string', 'max:1000']`) when creating a new vehicle (`$this->isMethod('POST')`), with a clear validation message (`"Please provide baseline condition or intake remarks for the vehicle."`).
    - Allow `remarks` (`['nullable', 'string', 'max:1000']`) on `PUT`/`PATCH` updates so editing a plate/driver assignment does not wipe or force re-entry unless modified.
  - Update `backend/app/Http/Controllers/Web/VehicleController.php` (`store`) and `backend/app/Http/Controllers/Api/V1/VehicleController.php` (`store`):
    - Stamp `status_source = 'Initial Registration'` and `status_changed_at = now()` when creating a vehicle so the initial intake remark has complete provenance.
- **Web Dashboard UI:**
  - Update `backend/resources/views/vehicles.blade.php` and `backend/resources/views/partials/update-status-modal.blade.php`:
    - Unify the label and behavior across **Register New Vehicle**, **Edit Vehicle Details**, **Update Vehicle Status**, **Review Inspection**, and **Review & Assess Damage** to **`Administrative Findings / Mechanical Remarks (Required)`** (`required`).
    - Fix the vehicle summary header layout in `#updateStatusModal`, `#reviewInspectionModal`, and `#reviewDamageModal` so long status pills (`Under Preventive Maintenance`, `Not Operational`) sit in the top-right row (`flex-shrink-0 text-nowrap`) and never overlap the origin timestamp or driver text below.
    - Display the vehicle's current **Administrative Findings / Mechanical Remarks** cleanly in the Vehicle Information modal (`#viewVehicleModal`).
- **Android Mobile App UI:**
  - Update `mobile/app/src/main/java/com/example/rvms/ui/vehicle/VehicleInfoScreen.kt`:
    - Display the vehicle's `remarks` under **`Administrative Findings / Mechanical Remarks`** so assigned drivers can view the baseline/current mechanical note on their assigned vehicle card.
- **Database Seeding:**
  - Update vehicle seeders (`backend/database/seeders/VehicleSeeder.php` and UAT agency seeders in `backend/app/Console/Commands/Seed*Command.php`) so all seeded vehicles carry realistic initial baseline/operational remarks.

### 3. Unit Tests & Verification
- Update and add test cases in:
  - `backend/tests/Feature/VehicleApiTest.php` (verify `POST /api/v1/vehicles` fails with `422` when `remarks` is missing or blank, succeeds when provided, and stores `remarks` + `status_source = 'Initial Registration'`).
  - `backend/tests/Feature/WebVehiclePageTest.php` (verify web vehicle creation requires `remarks`, persists it, and renders it on `/vehicles`).
- Run `php artisan test --filter=Vehicle` and `./gradlew testDebugUnitTest` to confirm zero regressions.

### 4. Report Output (Software Files Changed Deliverable)
At the end of Phase 1, provide a structured **Phase 1 Change Report** listing:
- Every modified/created file path across `backend/` and `mobile/` with a summary of lines and logic changed.
- Test execution results (`php artisan test` & Gradle unit test summary).

---

## Phase 2: Feature 2 — Driver Diagnostic Transparency on Status Alerts

### 1. Goal
Ensure every vehicle operational status change across all administrative modules records the administrator's mechanical remarks/findings, embeds those remarks into the driver's `Vehicle_Status_Update` notification payload (`notifications.data` JSON and message body), and lets drivers tap any status notification in the Android app to open a detailed diagnostic modal explaining why the vehicle was grounded, placed under preventive maintenance, or cleared for duty.

### 2. Tasks
- **Backend Single Status Gate (`VehicleStatusWriter.php`):**
  - Update `backend/app/Services/VehicleStatusWriter.php`:
    - Ensure `commit()` and `notifyAssignedDriver()` include `'remarks' => $vehicle->remarks` inside the `notifications.data` JSON payload alongside `plate`, `vehicle_id`, `from_status`, `to_status`, and `source`.
    - Enrich the notification body (`message`) or keep a concise summary while ensuring full remarks are available in `payload.remarks`.
- **All Administrative Status-Write Entry Points (Web & API):**
  - **Inspection Review (`ReviewInspectionRequest.php`, `Web\InspectionController.php`, `Api\V1\InspectionController.php`, `resources/views/inspections.blade.php`):**
    - Add an **Admin Review Findings / Mechanical Remarks** input when reviewing an inspection (required when changing vehicle status, optional when keeping status unchanged) and pass it to `VehicleStatusWriter::write()`.
  - **Damage Report Assessment (`ReviewDamageReportRequest.php`, `Web\DamageReportController.php`, `Api\V1\DamageReportController.php`, `resources/views/damage.blade.php`):**
    - Add an **Admin Assessment Findings / Mechanical Remarks** input in the Review Damage modal (required when updating `vehicle_status`) and pass it to `VehicleStatusWriter::write()`.
  - **Repair Logs (`Web\RepairController.php`, `Api\V1\RepairLogController.php`):**
    - When `vehicle_status` is updated during repair logging, automatically forward the repair's `remarks` (or `scope_of_work` fallback) into `VehicleStatusWriter::write()`.
  - **PM Completion (`Web\PmController.php`, `Api\V1\PmScheduleController.php`):**
    - When `vehicle_status` is updated upon PM completion, automatically forward `completion_remarks` (or service target completion summary) into `VehicleStatusWriter::write()`.
  - **Dispatch Closing (`Web\DispatchController.php`, `Api\V1\DispatchController.php`):**
    - When a dispatch is closed and `return_status` transitions the vehicle, forward the dispatch return `remarks` into `VehicleStatusWriter::writeFromDispatch()`.
- **Android Mobile App (`NotificationScreen.kt`):**
  - Update `mobile/app/src/main/java/com/example/rvms/ui/notification/NotificationScreen.kt`:
    - Make notification items tappable to both mark the notification as read (if unread) and open a **Diagnostic Status Detail Modal / Bottom Sheet**.
    - Display inside the modal:
      1. Notification Title & Timestamp
      2. Vehicle Plate Number (`payload["plate"]`)
      3. Operational Status Transition (`from_status` $\rightarrow$ `to_status`) with color-coded status pills
      4. Administrative Source Module (`source`, e.g., *Damage Review*, *Inspection Review*, *Vehicle Management*, *PM Schedules*, *Repair Logs*, *Dispatch Closed*)
      5. **Administrator's Mechanical Remarks & Findings** (`payload["remarks"]`, with a clean fallback for PM reminders or legacy alerts)
- **Database Seeding:**
  - Update UAT notification seeders (`Seed*Command.php`) so seeded `Vehicle_Status_Update` alerts include realistic mechanical `remarks` inside their `data` JSON payload.

### 3. Unit Tests & Verification
- Add/update tests in:
  - `backend/tests/Feature/NotificationTriggerTest.php` and `backend/tests/Feature/VehicleStatusGovernanceTest.php` (verify that status transitions from Vehicle Management, Inspection Review, Damage Assessment, Repair Logs, PM Completion, and Dispatch Close populate `data.remarks`, `data.from_status`, `data.to_status`, and `data.source` in the driver's notification record).
  - `mobile/app/src/test/java/com/example/rvms/data/NotificationRepositoryTest.kt` / `DtoSerializationTest.kt` (verify JSON payload parsing of `remarks`, `from_status`, `to_status`, `source`).
- Run `php artisan test` and `./gradlew testDebugUnitTest`.

### 4. Report Output (Software Files Changed Deliverable)
At the end of Phase 2, provide a structured **Phase 2 Change Report** listing:
- Every modified/created file path across `backend/` and `mobile/` with a summary of changes.
- Test execution results verifying the end-to-end notification remarks pipeline.

---

## Phase 3: Feature 1 — Dual-Trigger Preventive Maintenance (`Mileage & Time-Based`)

### 1. Goal
Support dual-trigger preventive maintenance scheduling (`Mileage & Time-Based`) where mileage thresholds and calendar dates run concurrently on the same schedule and trigger `Due Soon` or `Due` alerts based on whichever threshold is reached first (e.g., every 5,000 km or 6 months), without adding or renaming any columns in the ERD.

### 2. Tasks
- **Database Migration (Enum Expansion Only — Zero New Columns):**
  - Create migration `backend/database/migrations/2026_10_29_000001_add_dual_trigger_pm_type_to_pm_schedules.php` expanding `pm_schedules.pm_type` enum to `['Mileage-Based', 'Time-Based', 'Mileage & Time-Based']`.
- **Model & Status Recalculation Engine (`PmSchedule.php`):**
  - Add constant `public const TYPE_BOTH = 'Mileage & Time-Based';` (and helper methods `usesMileage(): bool` and `usesTime(): bool`).
  - Update `recalculatedStatus(?int $currentMileage = null): string`:
    - Extract mileage status evaluation and time status evaluation.
    - When `pm_type === self::TYPE_BOTH`, compute both statuses and return the higher-severity state (`Due` > `Due Soon` > `Upcoming`).
  - Update `targetLabel(): string`:
    - When `pm_type === self::TYPE_BOTH`, return both thresholds formatted cleanly (e.g., `"45,000 km / Apr 28, 2027"`).
- **Form Requests & Controllers:**
  - Update `backend/app/Http/Requests/StorePmScheduleRequest.php`:
    - Allow `PmSchedule::TYPE_BOTH` in `pm_type`.
    - Set `$usesMileage = in_array($this->input('pm_type'), [PmSchedule::TYPE_MILEAGE, PmSchedule::TYPE_BOTH], true);` and `$usesTime = in_array($this->input('pm_type'), [PmSchedule::TYPE_TIME, PmSchedule::TYPE_BOTH], true);`.
    - Require mileage fields (`interval_km`, `last_pm_mileage`, `due_soon_threshold_km`) when `$usesMileage` is true, and require calendar fields (`due_date`, `due_soon_threshold_days`) when `$usesTime` is true.
  - Update `backend/app/Http/Controllers/Web/PmController.php` and `backend/app/Http/Controllers/Api/V1/PmScheduleController.php`:
    - Compute `due_mileage = last_pm_mileage + interval_km` whenever `$usesMileage` is true (both `Mileage-Based` and `Mileage & Time-Based`).
  - Update `backend/app/Http/Requests/CompletePmScheduleRequest.php` and completion logic:
    - Require `completion_mileage` whenever the schedule uses mileage (`Mileage-Based` or `Mileage & Time-Based`), and advance `vehicle->current_mileage` if `completion_mileage` is higher.
- **Alerts & Printable Reports:**
  - Update `backend/app/Services/MaintenanceAlerts.php`:
    - Format alert messages for `Mileage & Time-Based` schedules so admins and drivers see both the mileage target and calendar due date (and which threshold triggered the alert).
  - Update `backend/app/Services/ReportBuilder.php` (`pmDueLabel()`):
    - Format dual-trigger schedules as `"<due_mileage> km / <due_date>"` in printable PM reports.
- **Web Dashboard UI (`pm.blade.php`):**
  - Remove the `PM Type` dropdown from both the **Create PM Schedule** and **Edit PM Schedule** modals and remove the `SCHEDULE TYPE` column from the Active Schedules table.
  - Always display and require **both** the mileage field group (`interval_km`, `last_pm_mileage`, `due_soon_threshold_km`) and the calendar date field group (`due_date`, `due_soon_threshold_days`) on every schedule ("Whichever Comes First"), and always prompt for `completion_mileage` (`Odometer at Service`) upon completion.
  - Keep `pm_type` in the underlying `pm_schedules` schema (automatically set to `'Mileage & Time-Based'` and backfilled on existing rows via `2026_10_29_000002_unify_existing_pm_schedules_to_dual_trigger.php`) so **Figure 6.2 (Admin ERD) remains 100% untouched**.
- **Database Seeding:**
  - Update `PmScheduleSeeder.php` and UAT agency seeders (`Seed*Command.php`) to include realistic `Mileage & Time-Based` schedules (e.g., 5,000 km or 6 months) alongside existing schedules.

### 3. Unit Tests & Verification
- Add comprehensive unit and feature tests in:
  - `backend/tests/Feature/PmScheduleTest.php` (creating/updating `Mileage & Time-Based` schedules, validation rules when either mileage or date fields are missing).
  - `backend/tests/Feature/RecalculatePmTest.php` (verify whichever-comes-first logic: mileage `Due` + date `Upcoming` $\rightarrow$ `Due`; mileage `Upcoming` + date `Due Soon` $\rightarrow$ `Due Soon`; both `Upcoming` $\rightarrow$ `Upcoming`).
  - `backend/tests/Feature/PmCompletionMileageTest.php` (verify completing a `Mileage & Time-Based` schedule requires `completion_mileage` and updates vehicle odometer).
  - `backend/tests/Feature/WebPmPageTest.php` and `backend/tests/Feature/ReportGenerationTest.php`.
- Run full test suites across the entire repository (`php artisan test` and `./gradlew testDebugUnitTest`).

### 4. Report Output (Software Files Changed Deliverable)
At the end of Phase 3, provide a structured **Phase 3 Change Report** listing:
- Every modified/created file path across `backend/` with a summary of changes.
- Full test suite execution results across all backend and mobile tests.

---

## Phase 4: Comprehensive Manual Verification & UAT Walkthrough (Leader Sign-Off)

### 1. Goal
Enable you (as Project Leader) to manually test all three implemented features end-to-end across the Web Dashboard and Android Mobile App using:
1. **All Agency Administrator Accounts** (your existing custom admin accounts, newly created admin accounts via Profile $\rightarrow$ Create Admin, and the seeded `BFP`, `PNP`, `CDRRMO`, `CHO` admin accounts),
2. **Existing Drivers** (both the drivers you already created and the seeded drivers across all four agencies), and
3. **Newly Registered Drivers** that you register and approve from scratch.

### 2. Tasks
- **Non-Destructive Environment Preparation:**
  - Run `php artisan migrate` (never `migrate:fresh`, so all of your existing custom admin/driver accounts, vehicles, and records remain intact) and backfill any null baseline remarks on existing vehicles if needed.
  - Build the updated Android APK (`./gradlew assembleDebug`) and start the local backend + scheduler (`php artisan serve` and `php artisan schedule:work`).
- **Guided Manual Verification Checklist (for your testing across all existing, seeded, and newly created accounts):**
  1. **New Admin & New Driver Account Creation Flow:**
     - Using your existing admin account (or any agency admin), create a new administrator account or log in with your custom admin account.
     - Self-register a new driver on the Android app (or create one from the Web Dashboard) $\rightarrow$ approve the driver $\rightarrow$ assign the driver (primary or secondary) to a vehicle.
  2. **Feature 3 (Baseline Remarks upon Vehicle Registration):**
     - As Admin, open **Vehicles $\rightarrow$ Add Vehicle** $\rightarrow$ attempt submission without Baseline Remarks (verify validation blocks it) $\rightarrow$ register the vehicle with baseline intake condition remarks $\rightarrow$ verify the remark appears on the Web Vehicles list and on the Driver's **Assigned Vehicle** screen on the mobile app.
  3. **Feature 2 (Driver Diagnostic Transparency on Status Alerts):**
     - Trigger vehicle status changes from each admin workflow:
       - Manual Status Change on **Vehicles** page with mechanical remarks
       - Flagged **BLOWBAGETS Inspection Review** with admin findings changing vehicle status
       - **Damage Report Review** with admin assessment findings grounding the vehicle (`Not Operational`)
       - **Repair Log** creation or **PM Schedule Completion** returning the vehicle to `Operational` with remarks
       - **Dispatch Close** with post-trip remarks
     - Log in on the Android app as the assigned **current driver** and **newly registered driver** $\rightarrow$ open **Notifications** $\rightarrow$ tap each `Vehicle Status Updated` alert $\rightarrow$ verify the diagnostic modal displays the plate number, `From` $\rightarrow$ `To` status transition, source module, timestamp, and the administrator's exact mechanical remarks/findings.
  4. **Feature 1 (Dual-Trigger Preventive Maintenance):**
     - As Admin, open **Preventive Maintenance $\rightarrow$ Add Schedule** $\rightarrow$ select **`Mileage & Time-Based`** $\rightarrow$ verify both Mileage fields (`interval_km`, `last_pm_mileage`, `due_soon_threshold_km`) and Time fields (`due_date`, `due_soon_threshold_days`) appear and are required.
     - Test scenario A: Mileage threshold is reached first (via Dispatch Close odometer update) while calendar date is still months away $\rightarrow$ verify schedule transitions to `Due Soon` / `Due` and fires notifications.
     - Test scenario B: Calendar date enters `Due Soon` / `Due` window while mileage is still low $\rightarrow$ verify schedule transitions to `Due Soon` / `Due` and fires notifications.
     - Complete the `Mileage & Time-Based` schedule $\rightarrow$ verify both service date and completion mileage are recorded and reflected in **Printable Reports**.
- **Bug Fix Loop (if any UI/UX polish is requested during your manual test):**
  - Apply any adjustments you request during testing and re-verify until you give final sign-off.

### 3. Unit Tests & Verification
- Run `php artisan rvms:doctor`, `php artisan test`, and `./gradlew testDebugUnitTest` after any manual-testing adjustments to ensure 100% automated test pass rate.

### 4. Report Output (Software Files Changed Deliverable)
At the end of Phase 4, provide a **Phase 4 Verification & Change Report** summarizing:
- Any polish or bug-fix files modified during manual testing.
- Final verification status across all 4 agencies, current drivers, and newly registered drivers.

---

## Phase 5: Manuscript Synchronization & Humanizer Pass (`skills/SKILL.md`)

### 1. Goal
Update all affected chapters, tables, figure narratives, recommendations, and source code appendices of the manuscript so they match the implemented software 100%—while keeping **all ERD, DFD, Context, and FDD diagram images untouched** and applying the **Humanizer skill (`skills/SKILL.md`)** to every edited paragraph.

### 2. Specific Chapters & Sections to Edit

#### A. Chapter 1 — Introduction
1. **Purpose and Description (Pages 3–4):**
   - **Paragraph 1 (p. 3–4):**
     - Update preventive maintenance description from *"monitor mileage- and time-based preventive maintenance schedules"* to include concurrent dual-trigger (`mileage-based, time-based, or dual-trigger mileage-and-calendar`) schedules triggered by whichever threshold comes first.
     - Update the closing notification sentence on p. 4 (*"...while ensuring drivers receive immediate updates when their assigned vehicles change operational status."*) to state that drivers can tap status notifications to view the administrator's mechanical remarks and findings behind the status change.
2. **Scope and Limitations (Pages 4–6):**
   - **Paragraph 1 (p. 4–5):**
     - In the vehicle registry sentence (p. 4–5), add that registering a vehicle requires mandatory baseline remarks to record pre-existing conditions at intake.
     - In the preventive maintenance sentence (p. 5), change *"preventive maintenance tracking across mileage or date intervals with configurable 'Due Soon' thresholds"* to cover mileage, calendar, or concurrent dual-trigger intervals triggered by whichever threshold is reached first.
     - In the notifications sentence (p. 5), note that mobile status alerts include the administrator's diagnostic remarks so drivers can inspect why a unit was grounded, serviced, or cleared.
     - Align external repair receipt wording with commit `6127a54` (optional supporting receipts rather than mandatory).

#### B. Chapter 4 — Methodology
1. **Requirements Documentation — Table 3: Functional Requirements & Narrative (Pages 26–30):**
   - **`Vehicle Record Management` (p. 27):** Add mandatory baseline remarks upon initial vehicle registration.
   - **`Inspection Monitoring` & `Damage Report Management` (p. 28):** Note that administrators record review findings/remarks when updating a vehicle's operational status.
   - **`Preventive Maintenance Scheduling` (p. 28):** Update from *"discrete, non-recurring mileage or calendar-based PM schedules"* to *"discrete, non-recurring mileage-based, calendar-based, or concurrent dual-trigger PM schedules that evaluate both mileage and calendar thresholds and flag the schedule based on whichever threshold is reached first"*.
   - **`Preventive Maintenance Completion` (p. 28):** Note that completion mileage is recorded for both mileage-based and dual-trigger schedules.
   - **`Vehicle Status Management` (p. 29):** Note that status transitions record the administrator's remarks/findings explaining the change.
   - **`Driver Notifications` (p. 29):** Add that drivers can tap vehicle status notifications to view the administrator's mechanical remarks, status transition, and source module.
   - **Table 3 Narrative Paragraph (p. 29–30):** Update the narrative sentences on vehicle registration, preventive maintenance scheduling, and driver notifications to match Table 3.
2. **Data Model — Entity Relationship Diagram Narrative (Page 48):**
   - **ERD Images (Figures 6.1 & 6.2):** **UNTOUCHED.**
   - **Figure 6.2 Narrative Paragraph (p. 48):** Update the sentence *"A pm_schedules record maintains one vehicle (vehicle_id) through a mileage-based or time-based plan, with due thresholds and completion details"* to *"through a mileage-based, time-based, or dual-trigger plan..."*.
3. **Data Dictionary (Pages 48–69):**
   - **Table 7 (`vehicles`, p. 53–55) & Narrative:**
     - Add the `remarks` row (`Text`, `No` on initial creation / `Yes` in schema, *"Baseline condition remarks recorded upon initial fleet registration and updated with administrator findings when operational status changes"*), and fix the `secondary_id` typo to `secondary_driver_id`.
     - Update the Table 7 narrative paragraph (p. 55) to mention baseline intake remarks and status-change remarks.
   - **Table 13 (`pm_schedules`, p. 63–66) & Narrative:**
     - Update `pm_type` description to: *"Basis of the schedule: Mileage-Based, Time-Based, or Mileage & Time-Based."*
     - Update `interval_km`, `last_pm_mileage`, and `completion_mileage` descriptions from *"Mileage-based schedules only"* to *"Mileage-based and dual-trigger schedules."*
     - Update `due_date` description from *"Time-based schedules only"* to *"Time-based and dual-trigger schedules."*
     - Update the Table 13 narrative paragraph (p. 65–66) to explain single-trigger and dual-trigger (whichever-comes-first) plans.
   - **Table 15 (`notifications`, p. 68–69) & Narrative:**
     - Update `data` (`JSON`) description to: *"Reference details such as the plate number, record concerned, status transition, source module, and administrator diagnostic remarks."*
     - Update the Table 15 narrative paragraph (p. 69) to mention that driver status notifications store the administrator's diagnostic remarks in the reference payload for mobile viewing.
   - **Pre-existing Data Dictionary Fixes:**
     - Clean up the copy-paste errors in Table 6 (`users` — fix `nc_ii_expiry_date` description and remove stray `travel_order_path` row) and Table 14 (`dispatches` — fix `second_driver_id` name/description and include `travel_order_path`).
4. **Implementation Results (Pages 69–80):**
   - **Objective 1 — Figure 7.1 & Narrative (p. 71–72):** Update the Add Vehicle modal screenshot and paragraph to include mandatory baseline/intake remarks upon registration.
   - **Objective 2 — Figures 8.1, 8.2, 8.3 & Narratives (p. 73–77):**
     - Update Figure 8.1/mobile narrative (or add the mobile notification detail view alongside the driver screens) to describe tapping a vehicle status notification to view administrator diagnostic remarks.
     - Update Figure 8.2 narrative (p. 75) to mention recording administrative findings when reviewing flagged inspections or damage reports (and fix the `Figure 8.3` typo on line 2347 to `Figure 8.2`).
     - Update Figure 8.3 screenshot & narrative (p. 76–77) to show `Mileage & Time-Based` dual-trigger scheduling and dual thresholds (`km / date`).
   - **System Evaluation Narrative (p. 85):** Fix the `Table 17` typo on line 2654 to `Table 16`.

#### C. Chapter 5 — Recommendations (Page 86)
- **Complete Rewrite of Chapter 5 (1 Paragraph, p. 86):**
  - Since the three previous recommendations (dual-trigger PM, driver notification diagnostic transparency, and mandatory baseline vehicle registration remarks) are now built into the system, replace them with three practical future-development recommendations that naturally extend the system's current Scope and Limitations:
    1. **Automated Next-Cycle PM Renewal:** Automatically generating the next recurring preventive maintenance schedule upon completing a service cycle, using the recorded completion mileage and service date as the new baseline instead of requiring manual re-entry for each cycle.
    2. **OBD-II Telematics and GPS Odometer Synchronization:** Optional integration with onboard diagnostics (OBD-II) and GPS hardware to automate live odometer updates and fault-code ingestion when municipal budgets allow hardware procurement.
    3. **Statutory COA Report Templates and SMS Fallback Alerts:** Built-in export templates formatted directly to Commission on Audit (COA) motor vehicle ledger standards, paired with an SMS gateway fallback for stations operating in areas with intermittent mobile data connectivity.

#### D. Appendices — Relevant Source Code (Pages 89–114)
- **Appendix A (`InspectionController`) & Appendix B (`DamageReportController`):** Sync the `review()` method listings to include the administrator remarks passed to `VehicleStatusWriter`.
- **Appendix C (`MaintenanceAlerts` / `PmSchedule`):** Sync `MaintenanceAlerts.php` (and/or `PmSchedule::recalculatedStatus()`) with the updated dual-trigger PM code.
- **Appendix F (`ReportBuilder`):** Sync `pmDueLabel()` and PM report rows with the updated dual-trigger formatting.

### 3. Humanizer Verification (`skills/SKILL.md` Compliance Check)
Every newly written or edited manuscript paragraph in Phase 5 will be audited against `skills/SKILL.md`:
1. **Voice Calibration:** Match the direct, concrete, third-person academic style of your existing manuscript.
2. **Pattern Elimination Check:**
   - Zero inflated significance words (*testament, pivotal, crucial, landscape, tapestry, underscore, showcase, delve, foster*).
   - Zero superficial `-ing` participial tails (*", ensuring that...", ", highlighting...", ", showcasing..."*).
   - Zero copula avoidance (*"serves as"*, *"stands as"*, *"boasts"* $\rightarrow$ use plain *"is"*, *"are"*, *"has"*).
   - Zero em-dash (`—`) overuse, negative parallelisms (*"not only... but also"*), or chatbot filler.
   - Straight quotation marks (`"..."`) and clean, varied sentence lengths.
3. **Two-Step Self-Audit (`SKILL.md` §Process steps 6–9):**
   - Run the draft through *"What makes the below so obviously AI generated?"* $\rightarrow$ fix every remaining tell $\rightarrow$ output the final humanized manuscript text.

### 4. Report Output (Manuscript & Reference Files Changed Deliverable)
At the end of Phase 5, provide:
- A complete **Manuscript Synchronization & Humanized Edits Deliverable** containing the exact before-and-after text, table rows, code appendix listings, and screenshot checklist (plus direct updates to `CalbayogGO-1.docx` or any repo markdown docs if requested).

