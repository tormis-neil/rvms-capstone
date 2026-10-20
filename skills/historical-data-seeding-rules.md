# RVMS Historical Data Seeding Rules & Agency Master Playbook

> **Strict Operational Playbook & Master Data Reference**  
> **Location:** `skills/historical-data-seeding-rules.md`  
> **Status:** Active reference playbook for UAT seeding and manuscript defense.  
> **Purpose:** Prevent regressions, maintain credible UAT data for the final defense, and ensure zero duplicate or conflicting records across agencies, vehicles, and drivers.

---

## 1. Strict Rules & Lessons Learned (The Golden Principles)

### Rule 1: Always Use Direct `DB::table(...)` Inserts for Past Timestamps
* **The Past Bug:** Using Eloquent models (`Inspection::create(...)`, `Notification::create(...)`, `DamageReport::create(...)`) causes Laravel's model boot events and auto-timestamping to overwrite `created_at` with `now()`. This caused all notifications and inspection cards to group unnaturally under *"Today at 8:46 PM"*.
* **The Strict Rule:** Always insert historical records using `DB::table('table_name')->insert([...])` or `DB::table('table_name')->insertGetId([...])` with explicit strings for `'created_at'`, `'updated_at'`, and `'reviewed_at'` (e.g., `'2026-09-28 09:30:00'`).

### Rule 2: Date Boundaries & Working Hours (Strictly No Weekends)
* **Master Date Range:** **September 14, 2026 to October 23, 2026** (Historical repair logs can date back to January – August 2026).
* **Flexible Sub-Date Ranges:** Each driver must operate within an isolated, realistic sub-range within the master window (e.g., Driver A: Sept 14 – Oct 02; Driver B: Sept 28 – Oct 22).
* **Working Hours:** **Mondays to Fridays only, 9:00 AM – 5:00 PM**.
  - Daily Morning Inspections: Submitted between `09:05:00` and `09:35:00`.
  - Admin Reviews: Performed 1 to 2 hours later (e.g., `10:30:00` to `11:45:00`).
  - Dispatches: Typical departure `09:30:00` or `13:00:00`, returning within duty hours.
  - **Never** seed submissions on Saturdays or Sundays.

### Rule 3: Authentic Calbayognon Waray-Waray ("sa / san" Rule)
* **The Strict Language Constraint:** Drivers in Calbayog City speak Waray with specific municipal nuances.
  - **Always use "sa" and "san"** (e.g., *"sa makina"*, *"san radiator"*, *"sa Petron"*, *"san karsada"*).
  - **NEVER use "ha" or "han"** (e.g., avoid *"ha makina"*, *"han radiator"*).
* Combine simple, functional English technical terms (*brake fluid*, *clutch pedal*, *34 psi*, *alternator belt*, *wiper blade*) with natural Waray remarks (*"Kompleto an lana"*, *"Hapsay an dalagan"*, *"Nagraragubrob kon naigo sa libaong"*).

### Rule 4: Driver Role & Shift Parity (Primary vs. Secondary Drivers)
* When a vehicle has two assigned drivers (e.g., Hilux `SHA 2625` with Rowel Gamao as Primary and Idelfonso Balloca as Secondary):
  - Both drivers have the operational right to inspect the vehicle and submit damage reports.
  - **Shift Alternation:** They must inspect on **alternating days**. They must never submit daily inspections on the same date or at the same time.
  - **Issue Independence:** Each driver reports their own unique observations and mechanical issues. They must never copy-paste the same inspection notes or reported defects.

### Rule 5: Vehicle-Shared vs. Driver-Isolated Data Integrity
* **Shared Data on the Vehicle (Must match across both drivers and web admin):**
  - Dispatches (a trip taken by the vehicle updates the vehicle odometer for everyone).
  - Repair Logs (maintenance performed on the vehicle is universal).
  - Preventive Maintenance (PM) Schedules (the maintenance milestones belong to the vehicle).
  - Vehicle Operational Status (`Operational`, `Dispatched`, `Under Maintenance`, `Not Operational`).
* **Driver-Isolated Data (Visible only on that driver's mobile app and filtered under their name in Admin Reports):**
  - BLOWBAGETS Daily Inspection forms and checklists.
  - Damage/Incident reports authored by that driver.
  - Driver Mobile "Recent Activity" feed and personal alert history.

### Rule 6: Notification Standardization & Duplicate Prevention
* **PM Alert Title:** Must **ALWAYS** be `'Preventive Maintenance'`. Never use *"Preventive Maintenance Approaching"* or *"Upcoming Preventive Maintenance"*.
* **PM JSON Data Payload:** Must **ALWAYS** include:
  ```php
  'pm_schedule_id' => $pmId,
  'vehicle_id' => $vehicleId,
  'plate' => $plateNumber,
  'service_target' => $serviceTargetString,
  'state' => 'Due Soon', // or 'Due'
  ```
* **Admin Idempotency Guard:** Whenever seeding a driver PM reminder, always seed the corresponding Admin notification (`TYPE_PM_DUE_SOON` with `data->pm_schedule_id`) so the live `MaintenanceAlerts::raiseForSchedule` engine sees that the schedule is already alerted and does not fire a second "Today" duplicate.

### Rule 7: Zero Disruption to Master Data (Safety & Cleanup Scope)
* **NEVER DELETE Master Entities:** Under NO circumstances delete records from:
  - `agencies`
  - `users` (Admin, Drivers, Staff)
  - `vehicles`
  - `inspection_checklist_items`
* **Idempotent Cleanup:** When re-running a driver seeder, only clean up operational rows authored by or specifically targeting that driver:
  - `inspections` & child `inspection_items` where `driver_id = $driverId`
  - `damage_reports` where `driver_id = $driverId`
  - `notifications` where `user_id = $driverId` (and admin notifications referencing that driver/vehicle)

### Rule 8: Strictly No Seeded Dummy Documents or Images (Always `null`)
* **The Reason:** Simulated file paths (e.g. `'receipts/something.pdf'`, `'damage/photo.jpg'`, `'travel_orders/order.pdf'`) point to files that do NOT physically exist on the Railway storage volume. When a panelist or evaluator clicks *"View Document"* or *"View Image"*, the browser opens a 404 Not Found error.
* **The Strict Rule:** 
  - `repair_logs.receipt_path` MUST ALWAYS BE `null`.
  - `damage_reports.photo_path` MUST ALWAYS BE `null`.
  - `pm_schedules.schedule_document_path` MUST ALWAYS BE `null`.
  - `pm_schedules.completion_receipt_path` MUST ALWAYS BE `null`.
  - `dispatches.travel_order_path` MUST ALWAYS BE `null`.
* Real uploads should ONLY originate from actual, interactive user demonstrations during the live defense (e.g., uploading an actual photo or repair receipt during the demo).

### Rule 9: Vehicle Status Dependency & Operational Paper Trail
* **The Strict Principle:** A vehicle can **NEVER** transition to an out-of-service status (`Not Operational` or `Under Preventive Maintenance`) without a concrete operational origin and documented justification:
  1. **Transition to `Not Operational` via Flagged Inspection:** If a daily BLOWBAGETS inspection flags a critical safety item (e.g., severe brake failure, broken steering linkage, blown radiator), Admin reviews the inspection and changes vehicle status to `Not Operational` (Source: `'Inspection Review'`).
  2. **Transition to `Not Operational` via Damage Report:** If a driver submits a damage/incident report that renders the vehicle unfit to drive, Admin reviews the report and sets vehicle status to `Not Operational` (Source: `'Damage Review'`).
  3. **Restoration to `Operational` via Repair Logs:** Once motorpool or an external shop resolves the defect, Admin logs the work into **Repair Logs** (`repair_logs`), recording the date, cost (₱), parts replaced, and changes status back to `Operational` (Source: `'Repair Logs'`).
  4. **Transition to & from `Under Preventive Maintenance` via PM Schedules:** A vehicle enters `Under Preventive Maintenance` when undergoing PMS (Source: `'PM Schedules'`). Once completed, Admin marks the PM schedule as **Completed** (recording date serviced, completion mileage, parts replaced, and shop), returning the vehicle to `Operational`.
  5. **Operational Lockout:** When a vehicle is `Not Operational` or `Under Preventive Maintenance`, the mobile app automatically disables daily BLOWBAGETS inspections (`Vehicle::isAvailableForDailyInspection()` returns false) to preserve business logic integrity.
* **Repair Logs vs. PM Schedule Completed Records (The Distinction):**
  - **Repair Logs (FR-13):** Flat log of corrective/ad-hoc repairs and breakdowns resulting from defects, damages, or inspection failures, with mandatory cost (₱) tracking for accounting audits.
  - **PM Schedules Completed Tab (FR-14):** Milestone-based compliance records tracking recurring manufacturer service intervals (mileage or time cycles) with proactive `Due Soon` warnings.

### Rule 10: Mandatory Driver Status Notifications Bound by UAT Date Window
* **The Feedback Loop:** In the live system, `VehicleStatusWriter` automatically fires a `Notification::TYPE_VEHICLE_STATUS_UPDATE` alert to assigned and secondary drivers whenever vehicle status changes.
* **Mandatory Dispatch Alerts:**
  - When a dispatch departs: Driver receives `Vehicle Status Updated` — `"{Plate} is now Dispatched."` (Source: `'Dispatch Opened'`).
  - When the dispatch returns: Driver receives `Vehicle Status Updated` — `"{Plate} is now Operational."` (Source: `'Dispatch Closed'`).
* **Strict Date Boundary & Realistic UAT Window:**
  - All driver mobile notifications (PM reminders, dispatch status updates, inspection reviews) MUST fall strictly within the driver's realistic sub-date range (Mondays to Fridays, 9:00 AM – 5:00 PM, between **September 14, 2026 and October 23, 2026**).
  - This ensures chronological consistency when panelists review the driver's mobile **Alerts** tab during the defense.

---

## 2. City Health Office (CHO) Master Reference Matrix

### Agency Overview
* **Agency Name:** City Health Office
* **Agency Code:** `CHO`
* **Agency ID:** `4`
* **Admin Account:** `cho@email.com` (User ID: `3`)

---

### Vehicle 1: `SHA 2625` — Toyota Hilux (Service Vehicle)
* **Vehicle ID:** `6`
* **Type:** Service Vehicle | **Make/Model:** Toyota Hilux E 4x2 Manual
* **Current Mileage:** `58,400 km`
* **Current Status:** `Not Operational` (Awaiting clutch pressure plate replacement)
* **Assigned Drivers:**
  - **Primary Driver:** Rowel Gamao (ID: `14`, `gamao.cho@email.com`)
  - **Secondary Driver:** Idelfonso Balloca (ID: `15`, `balloca.cho@email.com`)

#### Shared Records on `SHA 2625`:
* **PM Schedule (1 Record):**
  - **Target:** `60,000 km Major Preventive Maintenance Service (PMS)`
  - **Status:** `Due Soon` (Triggered at 58,400 km; Due mileage: 60,000 km; Threshold: 2,000 km)
  - **Type:** Mileage-Based (5,000 km interval)
* **Repair Logs (2 Records - Historical 2026):**
  1. *2026-06-16* | **Source:** GSO Motorpool | **Cost:** ₱2,650.00 | **Doc:** None
     - *Scope:* Replaced worn front stabilizer link bushings and conducted front-end wheel alignment.
     - *Parts:* Front stabilizer link bushings (4 pcs), steering tie rod end (right).
  2. *2026-08-28* | **Source:** External Shop (Calbayog Auto Supply) | **Cost:** ₱3,800.00 | **Doc:** None
     - *Scope:* Replaced squealing alternator/fan serpentine drive belt and serviced front brake caliper slider pins.
     - *Parts:* Bando 7PK Serpentine Drive Belt, caliper slider pins & dust boots.
* **Dispatches (4 Records - Ending at exactly 58,400 km):**
  1. *2026-09-17* (09:30–12:00) | Brgy. San Policarpo Health Station | Odo: 58,280 → 58,315 km (35 km) | Monthly vaccine delivery.
  2. *2026-09-24* (13:00–16:00) | Brgy. Dagum & Matobato Centers | Odo: 58,315 → 58,350 km (35 km) | Dengue misting equipment.
  3. *2026-10-02* (10:00–11:45) | City Hall Motorpool & GSO Supply | Odo: 58,350 → 58,372 km (22 km) | Emergency medical logbooks.
  4. *2026-10-07* (09:30–12:30) | Brgy. Obrero Rural Health Unit | Odo: 58,372 → 58,400 km (28 km) | Maternal health kits.

---

#### Driver 1A: Idelfonso Balloca (Secondary Driver)
* **Seeder Command:** `php artisan rvms:seed-cho-balloca --fresh`
* **Sub-Date Range:** **September 17, 2026 to October 14, 2026**
* **Daily Inspections (5 Submissions):**
  1. *2026-09-17 (09:10)* — All OK. *"Kompleto an lana san makina ngan tubig sa radiator. Hapsay dalagan."*
  2. *2026-09-24 (09:15)* — All OK. *"Ginsusi an preno, suga, ngan dashboard gauges. Waray aberya."*
  3. *2026-10-02 (09:20)* — **Flagged: Lights**. *"Right tail light socket loose, re-seated tightly sa terminal."*
  4. *2026-10-07 (09:05)* — All OK. *"Naka-andam an spare tire ngan hydraulic jack. Hapsay an dalagan."*
  5. *2026-10-14 (09:15)* — **Flagged: Engine**. *"Medyo humok an clutch pedal ngan makuri isulod an primera kambio."*
* **Damage Report (1 Submission):**
  - *2026-10-12 (11:15)* | **Reviewed:** *2026-10-13 (09:00)* by Admin
  - *Nature:* Medyo nag-iislayd an clutch pag tungas sa bukid san Brgy. Obrero samtang naghahatud ug medical supplies.
  - *Suspected Parts:* Clutch pressure plate / clutch disc wear or release bearing.
* **Notifications for Idelfonso Balloca (10 Mobile Alerts):**
  1. *2026-09-17 (09:30)* — `Vehicle_Status_Update`: `SHA 2625 is now Dispatched.` (Trip 1 departure)
  2. *2026-09-17 (12:00)* — `Vehicle_Status_Update`: `SHA 2625 is now Operational.` (Trip 1 return)
  3. *2026-09-24 (13:00)* — `Vehicle_Status_Update`: `SHA 2625 is now Dispatched.` (Trip 2 departure)
  4. *2026-09-24 (16:00)* — `Vehicle_Status_Update`: `SHA 2625 is now Operational.` (Trip 2 return)
  5. *2026-10-02 (10:00)* — `Vehicle_Status_Update`: `SHA 2625 is now Dispatched.` (Trip 3 departure)
  6. *2026-10-02 (11:45)* — `Vehicle_Status_Update`: `SHA 2625 is now Operational.` (Trip 3 return)
  7. *2026-10-05 (08:30)* — `PM_Reminder`: `Preventive Maintenance` (60,000 km Major PMS Due Soon)
  8. *2026-10-07 (09:30)* — `Vehicle_Status_Update`: `SHA 2625 is now Dispatched.` (Trip 4 departure)
  9. *2026-10-07 (12:30)* — `Vehicle_Status_Update`: `SHA 2625 is now Operational.` (Trip 4 return)
  10. *2026-10-13 (09:00)* — `Vehicle_Status_Update`: `SHA 2625 is now Not Operational.` (Awaiting clutch repair)
* **Admin Notifications for SHA 2625 (4 Records):**
  1. *2026-10-02 (09:20)* — Flagged inspection (Lights)
  2. *2026-10-05 (08:30)* — PM Due Soon alert on SHA 2625
  3. *2026-10-12 (11:15)* — New Damage Report submitted by Balloca
  4. *2026-10-14 (09:15)* — Flagged inspection (Engine)

---

#### Driver 1B: Rowel Gamao (Primary Driver)
* **Seeder Command:** `php artisan rvms:seed-cho-gamao`
* **Sub-Date Range:** **September 15, 2026 to October 09, 2026**
* **Daily Inspections (5 Submissions - Alternating with Balloca):**
  1. *2026-09-15 (09:20)* — All OK. *"Kompleto an lana san makina ngan tubig sa radiator. Waray tulo sa idarom. Hapsay an dalagan."*
  2. *2026-09-21 (09:15)* — All OK. *"Ginsusi an preno ngan mga suga sa gawas. Tanan nga dashboard gauge normal an basa."*
  3. *2026-09-28 (09:30)* — **Flagged: Tires**. *"Low pressure rear right tire, gin-pahanginan ko sa Petron 34 psi."*
  4. *2026-10-05 (09:10)* — All OK. *"Naka-set-up an jack, yawi sa ligid, ngan early warning device sa likod san lingkoran."*
  5. *2026-10-09 (09:25)* — **Flagged: Directional Signals**. *"Front right directional signal bulb socket slightly loose, re-seated tightly sa terminal."*
* **Damage Report (1 Submission):**
  - *2026-09-22 (10:15)* | **Reviewed:** *2026-09-23 (09:30)* by Admin
  - *Nature:* Nalugak an trangka san tailgate latch sa likod san cargo bed samtang nagbabagtas pakadto sa Brgy. Hamorawon.
  - *Suspected Parts:* Tailgate lock striker plate / rubber bumper cushion.
* **Notifications (5 Records):**
  - *2026-09-22 (10:15)* — Admin: New Damage Report submitted by Rowel Gamao
  - *2026-09-28 (09:30)* — Admin: Flagged inspection on SHA 2625 (Tires)
  - *2026-10-05 (08:30)* — Driver Gamao: Preventive Maintenance (`Due Soon`)
  - *2026-10-09 (09:25)* — Admin: Flagged inspection on SHA 2625 (Directional Signals)
  - *2026-10-13 (09:00)* — Driver Gamao: Status updated to `Not Operational`

---

### Vehicle 2: `SHA 1247` — Nissan Urvan NV350 (Ambulance)
* **Vehicle ID:** `7` (or designated Ambulance ID)
* **Type:** Ambulance | **Make/Model:** Nissan NV350 Urvan High Roof Ambulance
* **Current Mileage:** `38,920 km`
* **Current Status:** `Operational`
* **Assigned Driver:** Reynaldo Serrato (ID: `12`, `serrato.cho@email.com`)
* **Seeder Command:** `php artisan rvms:seed-cho-serrato`
* **Sub-Date Range:** **September 28, 2026 to October 22, 2026**

#### Historical Operations on `SHA 1247`:
* **PM Schedule (1 Record):**
  - **Target:** `Semi-Annual Ambulance PMS (Brake Pads, Suction Pump Filter, AC Cabin Filter & Alternator Belt)`
  - **Status:** `Due Soon` | **Type:** Time-Based (Scheduled for October 31, 2026)
* **Repair Logs (3 Records - Jan to Aug 2026):**
  1. *2026-02-12* | **Source:** GSO Motorpool | **Cost:** ₱3,450.00 | **Doc:** None
     - *Scope:* Replaced front ceramic brake pad kit and resurfaced front brake rotors after emergency response brake pulsation.
  2. *2026-05-08* | **Source:** External Shop (Calbayog Auto Electrical) | **Cost:** ₱5,800.00 | **Doc:** `receipts/cho_ambulance_siren_or_20260508.pdf`
     - *Scope:* Replaced auxiliary siren speaker horn driver (100W) and rewiring of dual roof LED lightbar strobe relays.
  3. *2026-08-19* | **Source:** GSO Motorpool | **Cost:** ₱1,950.00 | **Doc:** None
     - *Scope:* Replaced leaking high-pressure power steering return hose line and flushed ATF Dexron III hydraulic fluid.
* **Daily Inspections (6 Submissions):**
  1. *2026-09-28 (09:10)* — All OK. *"Kumpleto an emergency oxygen cylinder pressure, suction apparatus, ngan sirena."*
  2. *2026-10-02 (09:15)* — All OK. *"Ginsusi an stretcher lock mechanism ngan folding wheelchair sa luyo. Maupay dalagan."*
  3. *2026-10-08 (09:20)* — **Flagged: Water**. *"Guti-ay nala an reserve coolant sa radiator expansion tank, gin-pun-an san distilyado nga tubig."*
  4. *2026-10-13 (09:10)* — All OK. *"Tanan nga emergency strobe lights ngan patient cabin dual AC gumagana hin hapsay."*
  5. *2026-10-16 (09:25)* — **Flagged: Brakes**. *"Medyo humok an brake pedal kon tigda an pag-preno. Kinahanglan icheck sa maabot nga PMS."*
  6. *2026-10-22 (09:15)* — All OK. *"Andam an ambulansya para sa medical standby ngan emergency patient transport."*
* **Damage Report (1 Submission):**
  - *2026-10-08 (10:30)* | **Reviewed:** *2026-10-09 (09:15)* by Admin
  - *Nature:* Medyo nag-gugukod ngan makuri ig-slide an sliding door san patient cabin labi na kon naka-park sa bakilid.
  - *Suspected Parts:* Sliding door lower roller guide bearing / center track alignment.
* **Dispatches (3 Records):**
  1. *2026-10-01 (10:00–12:30)* | Calbayog District Hospital → St. Camillus Hospital | Mission: Patient Transport (Others) | Odo: 38,760 → 38,810 km (50 km)
  2. *2026-10-09 (13:30–16:00)* | City Health Office → DOH Provincial Health Office Catbalogan | Mission: Administrative Travel | Odo: 38,810 → 38,875 km (65 km)
  3. *2026-10-15 (14:00–16:15)* | Brgy. Tinambacan Emergency Station → Calbayog District Hospital | Mission: Patient Transport (Others) | Odo: 38,875 → 38,920 km (45 km)
* **Notifications (4 Records):**
  - *2026-10-08 (10:30)* — Admin: New Damage Report submitted by Reynaldo Serrato
  - *2026-10-08 (09:20)* — Admin: Flagged inspection on SHA 1247 (Water)
  - *2026-10-10 (08:30)* — Driver Serrato: Preventive Maintenance (`Due Soon`)
  - *2026-10-10 (08:30)* — Admin: PM Due Soon Alert on SHA 1247

---

### Vehicle 3: `SHA 1248` — Toyota HiAce Commuter Deluxe (Ambulance)
* **Vehicle ID:** `8` (or designated HiAce ID)
* **Type:** Ambulance | **Make/Model:** Toyota HiAce Commuter Deluxe Ambulance
* **Current Mileage:** `47,075 km`
* **Current Status:** `Operational`
* **Assigned Driver:** Robert Canaman (ID: `13`, `canaman.cho@email.com`)
* **Optional 2nd Driver:** Reynaldo Serrato (allowed as crew / co-driver)
* **Seeder Command:** `php artisan rvms:seed-cho-canaman`
* **Sub-Date Range:** **September 14, 2026 to October 02, 2026**

#### Historical Operations on `SHA 1248`:
* **PM Schedules (2 Records):**
  1. *Serviced on 2026-06-18* | **Status:** `Completed` | **Type:** Mileage-Based (45,000 km)
     - *Target:* `45,000 km Periodic Maintenance Service (PMS) - Engine Oil, Fuel Filter, & Front Brake Pads Overhaul`
     - *Completed at:* 45,050 km at GSO Motorpool (No document)
  2. *Upcoming* | **Status:** `Upcoming` | **Type:** Mileage-Based (Due at 50,000 km, threshold 1,000 km)
     - *Target:* `50,000 km Major Periodic Maintenance Service (PMS) - Transmission Fluid & Brake System Check`
* **Repair Log (1 Record - April 2026, No Document):**
  - *2026-04-22* | **Source:** GSO Motorpool | **Cost:** ₱450.00 | **Doc:** None
  - *Scope:* Replaced blown 100W emergency roof lightbar flasher fuse and re-secured loose siren speaker bracket bolts.
  - *Parts:* 30A automotive blade fuses (2 pcs), zinc mounting bolts with nylon lock nuts.
* **Daily Inspections (0 Submissions):**
  - Omitted by realistic UAT design (assumes driver Canaman did not test the mobile inspection workflow; mobile app displays clean empty state).
* **Damage Reports (0 Submissions):**
  - Omitted by realistic UAT design (mobile app displays clean empty state).
* **Dispatches (2 Records - Crewed with Serrato as Optional 2nd Driver):**
  1. *2026-09-17 (10:00–11:30)* | Brgy. Rawis to Calbayog District Hospital ER | Mission: Patient Transport (Others) | Odo: 47,020 → 47,048 km (28 km)
  2. *2026-09-24 (13:45–15:30)* | Brgy. Dagum Plaza Standby | Mission: Medical Response | Odo: 47,048 → 47,075 km (27 km)
* **Notifications (4 Records for Canaman):**
  - *2026-09-17 (10:00)* — Driver Canaman: `SHA 1248 is now Dispatched.`
  - *2026-09-17 (11:30)* — Driver Canaman: `SHA 1248 is now Operational.`
  - *2026-09-24 (13:45)* — Driver Canaman: `SHA 1248 is now Dispatched.`
  - *2026-09-24 (15:30)* — Driver Canaman: `SHA 1248 is now Operational.`

---

## 3. Master Summary: CHO Verification Checklist for Panelist Demo

| Entity / Screen | Rowel Gamao (Primary) | Idelfonso Balloca (Secondary) | Reynaldo Serrato | Robert Canaman |
| :--- | :--- | :--- | :--- | :--- |
| **Vehicle Assigned** | `SHA 2625` (Hilux) | `SHA 2625` (Hilux) | `SHA 1247` (NV350 Ambulance) | `SHA 1248` (HiAce Ambulance) |
| **Vehicle Status** | `Not Operational` | `Not Operational` | `Operational` | `Operational` |
| **Sub-Date Window** | Sept 15 – Oct 09 | Sept 17 – Oct 14 | Sept 28 – Oct 22 | Sept 17 – Sept 24 |
| **BLOWBAGETS Count** | 5 inspections | 5 inspections | 6 inspections | 0 inspections (Empty state) |
| **Flagged Inspections** | Tires, Directional Signals | Lights, Engine | Water, Tires | None |
| **Damage Reports** | 1 (Tailgate latch) | 1 (Clutch slipping) | 1 (Sliding door roller) | 0 reports (Empty state) |
| **Dispatches** | 4 shared | 4 shared | 3 trips | 2 trips |
| **PM Schedules** | 1 (`Due Soon` 60k km) | 1 (`Due Soon` 60k km) | 1 (`Due Soon` Semi-Annual) | 2 (1 Completed, 1 Upcoming) |
| **Historical Repairs** | 2 shared (GSO + Auto Supply)| 2 shared (GSO + Auto Supply) | 3 repairs (GSO + Electrical) | 1 repair (GSO Lightbar) |
| **Mobile Status Alerts** | 8 dispatch + 1 Not Op | 8 dispatch + 1 Not Op | 6 dispatch alerts | 4 dispatch alerts |
| **Mobile PM Alerts** | 1 PM Due Soon reminder | 1 PM Due Soon reminder | 1 PM Due Soon reminder | None |
| **Mobile App Sync** | Full driver workflow | Full driver workflow | Full driver workflow | Web-admin dispatch profile |

---

---

## 4. CDRRMO Agency Master Operational Playbook

* **Agency Code:** `CDRRMO`
* **Agency Name:** City Disaster Risk Reduction and Management Office
* **Active Admin Account:** `CDRRMO Administrator` (`cdrrmo.admin@email.com`)
* **Agency Drivers:**
  - Jonathan Dela Peña (`jonathan@email.com`, ID: 30)
  - Christopher Clark Mabini (`mabini@email.com`, ID: 31)
  - Rolando Calbiga (`calbiga@email.com`, ID: 16)

---

### Unassigned Fleet Segment (Shared Reserve Pool)

#### Vehicle 1: `SHA-2006` — Hino XZU302L (Rescue Truck)
* **Vehicle ID:** `20` | **Plate:** `SHA-2006`
* **Type:** Rescue Truck | **Make/Model:** Hino XZU302L
* **Current Mileage:** `60,475 km` | **Status:** `Operational`
* **Assigned Driver:** `None (Unassigned Reserve)`
* **Seeder Command:** `php artisan rvms:seed-cdrrmo-unassigned`
* **Sub-Date Range:** **September 16, 2026 to October 08, 2026**
* **PM Schedule (1 Record):**
  - *Completed on 2026-09-08* | **Status:** `Completed` | **Type:** Mileage-Based (60,000 km)
  - *Target:* `60,000 km Major PMS - Heavy-Duty Engine Oil, Oil Filter, & Fuel Filter Replacement`
  - *Serviced at:* 60,020 km at GSO Motorpool
* **Repair Log (1 Record - May 2026, GSO Motorpool):**
  - *2026-05-22* | **Source:** GSO Motorpool | **Cost:** ₱3,450.00 | **Doc:** None
  - *Scope:* Replaced frayed steel wire rope on front hydraulic rescue winch and lubricated heavy-duty 4-way roller fairlead assembly.
  - *Parts:* 12mm x 28m galvanized aircraft-grade winch steel cable, clevis hook with safety latch.
* **Dispatches (3 Records - Calibrated 60,380 km → 60,475 km):**
  1. *2026-09-16 (09:30–12:30)* | CDRRMO Station → Brgy. Hamorawon & Brgy. Nijaga | Driver: Rolando Calbiga | 2nd: Christopher Clark Mabini | Mission: Rescue Operation | Odo: 60,380 → 60,415 km (35 km)
  2. *2026-09-25 (13:00–16:00)* | City Hall Compound → Brgy. Dagum Evacuation Center | Driver: Jonathan Dela Peña | Mission: Rescue Operation | Odo: 60,415 → 60,448 km (33 km)
  3. *2026-10-08 (10:00–12:30)* | CDRRMO Warehouse → Brgy. Obrero Multi-Purpose Hall | Driver: Christopher Clark Mabini | Mission: Administrative Travel | Odo: 60,448 → 60,475 km (27 km)

#### Vehicle 2: `AND-9023` — Hino 300 Series (Water Tanker)
* **Vehicle ID:** `17` | **Plate:** `AND-9023`
* **Type:** Water Tanker | **Make/Model:** Hino 300 Series
* **Current Mileage:** `64,950 km` | **Status:** `Operational`
* **Assigned Driver:** `None (Unassigned Reserve)`
* **Seeder Command:** `php artisan rvms:seed-cdrrmo-unassigned`
* **Sub-Date Range:** **September 18, 2026 to October 02, 2026**
* **PM Schedule (1 Record):**
  - *Upcoming / Due Soon* | **Status:** `Due Soon` | **Type:** Mileage-Based (Due at 65,000 km, threshold 1,000 km)
  - *Target:* `65,000 km Periodic Maintenance Service (PMS) - Transmission & Differential Gear Oil, Brake Fluid Flush, & Pump Pressure Seals`
* **Repair Log (1 Record - June 2026, External Shop):**
  - *2026-06-18* | **Source:** External Shop (Calbayog Auto Supply & Engineering Services) | **Cost:** ₱4,800.00
  - *Scope:* Overhauled centrifugal water pump discharge valve assembly and replaced leaking high-pressure quick-connect hose coupler gasket seals.
  - *Parts:* 3-inch brass ball valve, neoprene flange packing gaskets (4 pcs), rubber hose clamp bands.
* **Dispatches (2 Records - Calibrated 64,880 km → 64,950 km):**
  1. *2026-09-18 (10:00–14:00)* | Water District Pumping Station → Brgy. Carayman & Brgy. San Policarpo | Driver: Jonathan Dela Peña | 2nd: Rolando Calbiga | Mission: Others (Potable Water Distribution) | Odo: 64,880 → 64,918 km (38 km)
  2. *2026-10-02 (13:30–16:15)* | CDRRMO Station → Calbayog Sports Center / City Evacuation Ground | Driver: Rolando Calbiga | Mission: Others (Emergency Water Standby) | Odo: 64,918 → 64,950 km (32 km)

### Assigned Fleet Segment

#### Vehicle 3: `CHO-4467` — Toyota Coaster (Patient Transport Vehicle)
* **Vehicle ID:** `15` | **Plate:** `CHO-4467`
* **Type:** Patient Transport Vehicle | **Make/Model:** Toyota Coaster 30-Seater
* **Current Mileage:** `53,820 km` | **Status:** `Operational`
* **Primary Assigned Driver:** Jonathan Dela Peña (`jonathan@email.com`, ID: 30)
* **Secondary Assigned Driver:** Christopher Clark Mabini (`mabini@email.com`, ID: 31)
* **Seeder Command:** `php artisan rvms:seed-cdrrmo-jonathan`
* **Sub-Date Range:** **September 15, 2026 to October 09, 2026**
* **PM Schedule (1 Record):**
  - *Upcoming / Due Soon* | **Status:** `Due Soon` | **Type:** Mileage-Based (Due at 55,000 km, threshold 1,500 km)
  - *Target:* `55,000 km Periodic Maintenance Service (PMS) - Dual Air Filter Cleaning, Injector Calibration, & Clutch Fluid Service`
* **Repair Log (1 Record - June 2026, GSO Motorpool):**
  - *2026-06-10* | **Source:** GSO Motorpool | **Cost:** ₱3,200.00 | **Doc:** None
  - *Scope:* Adjusted dual air-over-hydraulic brake booster clearances, bled pneumatic brake lines, and renewed front passenger pneumatic folding door emergency pressure release valve.
  - *Parts:* Air brake booster seal repair kit, DOT 4 heavy-duty brake fluid (2L), pneumatic door valve O-rings.
* **Daily Inspections (5 Submissions by Jonathan Dela Peña):**
  1. *2026-09-15 (09:10)* — All OK. *"Ginsusi an air brake pressure gauge ngan lana san makina. Maupay an bugnaw san dual aircon."*
  2. *2026-09-21 (09:20)* — All OK. *"Hapsay an dalagan san Coaster. Kompleto an first-aid trauma kit ngan stretcher tie-downs sa luyo."*
  3. *2026-09-29 (09:15)* — **Flagged: Lights**. *"Medyo malubay an suga san overhead passenger cabin dome light sa may ikalima nga row. Pero an tanan nga headlight ngan signal lights functional."*
  4. *2026-10-05 (09:25)* — All OK. *"Ginsusi an ligid (mga 55 psi) ngan radiator coolant reservoir. Andam an sakyanan para sa mass evacuation standby."*
  5. *2026-10-09 (09:30)* — **Flagged: Air / Brakes**. *"Medyo mahinay an pag-alsa san air pressure gauge sa dashboard kon bag-o pa paandaron an makina. Kinahanglan pahimangnoan an mekaniko kon magpadayon."*
* **Damage Report (1 Submission by Jonathan Dela Peña):**
  - *2026-10-02 (11:00)* | **Reviewed:** *2026-10-02 (14:30)* by Admin
  - *Nature:* Medyo nagtitiktik an automatic passenger folding door pagbuklad samtang nagpapasakay ug mga evacuee sa Brgy. Matobato. Makuri ig-lock san motor kon waray manual nga suporta.
  - *Suspected Parts:* Passenger pneumatic door actuator arm linkage / magnetic door lock switch.
* **Dispatches (3 Records - Crewed with Mabini, Calibrated 53,680 km → 53,820 km):**
  1. *2026-09-17 (09:30–13:30)* | CDRRMO Compound → Brgy. Tinambacan Evacuation Center | Driver: Jonathan Dela Peña | 2nd: Christopher Clark Mabini | Mission: Rescue Operation | Odo: 53,680 → 53,745 km (65 km)
  2. *2026-09-24 (13:00–16:00)* | Calbayog District Hospital → City Sports Complex | Driver: Jonathan Dela Peña | 2nd: Christopher Clark Mabini | Mission: Medical Response | Odo: 53,745 → 53,785 km (40 km)
  3. *2026-10-06 (10:00–13:00)* | CDRRMO Station → LGU City Hall Annex & Brgy. Obrero | Driver: Jonathan Dela Peña | Mission: Administrative Travel | Odo: 53,785 → 53,820 km (35 km)
* **Notifications for Jonathan Dela Peña (7 Mobile Alerts):**
  1. *2026-09-17 (09:30)* — `Vehicle_Status_Update`: `CHO-4467 is now Dispatched.` (Trip 1 departure to Brgy. Tinambacan)
  2. *2026-09-17 (13:30)* — `Vehicle_Status_Update`: `CHO-4467 is now Operational.` (Trip 1 return)
  3. *2026-09-24 (13:00)* — `Vehicle_Status_Update`: `CHO-4467 is now Dispatched.` (Trip 2 departure to City Sports Complex)
  4. *2026-09-24 (16:00)* — `Vehicle_Status_Update`: `CHO-4467 is now Operational.` (Trip 2 return)
  5. *2026-10-05 (08:30)* — `PM_Reminder`: `Preventive Maintenance` (`CHO-4467 — 55,000 km Periodic Maintenance Service (PMS) is due soon.`)
  6. *2026-10-06 (10:00)* — `Vehicle_Status_Update`: `CHO-4467 is now Dispatched.` (Trip 3 departure to City Hall Annex)
  7. *2026-10-06 (13:00)* — `Vehicle_Status_Update`: `CHO-4467 is now Operational.` (Trip 3 return)
* **Admin Notifications for CHO-4467 (4 Records):**
  1. *2026-09-29 (09:15)* — Flagged inspection on CHO-4467 (Lights)
  2. *2026-10-02 (11:00)* — New Damage Report submitted by Jonathan Dela Peña
  3. *2026-10-05 (08:30)* — PM Due Soon alert on CHO-4467 (55,000 km PMS)
  4. *2026-10-09 (09:30)* — Flagged inspection on CHO-4467 (Air)

---

#### Vehicle 4: `SAB-5120` — Isuzu D-Max 4x4 (Rescue Truck)
* **Vehicle ID:** `14` | **Plate:** `SAB-5120`
* **Type:** Rescue Truck | **Make/Model:** Isuzu D-Max 4x4
* **Current Mileage:** `42,150 km` | **Status:** `Operational`
* **Assigned Driver:** Rolando Calbiga (`calbiga@email.com`, ID: `16`)
* **Seeder Command:** `php artisan rvms:seed-cdrrmo-calbiga`
* **Sub-Date Range:** **October 01, 2026 to October 21, 2026**
* **PM Schedules (2 Records):**
  1. *Completed on 2026-08-14* | **Status:** `Completed` | **Type:** Mileage-Based (10,000 km interval)
     - *Target:* `40,000 km Major PMS - Heavy-Duty Engine Oil, Transmission & Differential Gear Lube, & Multi-Point Brake Service`
     - *Serviced at:* 40,050 km at GSO Motorpool (Receipt: `null`)
  2. *Upcoming* | **Status:** `Upcoming` | **Type:** Mileage-Based (Due at 45,000 km, threshold 2,000 km, 2,850 km remaining)
     - *Target:* `45,000 km Periodic Maintenance Service (PMS) - Engine Oil & Filter Change, Air Cleaner Element, & Drive Belt Tension Inspection`
* **Daily Inspections (5 Submissions by Rolando Calbiga):**
  1. *2026-10-01 (09:10)* — All OK. *"Ginsusi an lana san makina, coolant, ngan ligid. Hapsay an dalagan san 4x4 D-Max."*
  2. *2026-10-06 (09:15)* — All OK. *"Kompleto an early warning device, tow strap, ngan hydraulic jack sa likod. Waray aberya."*
  3. *2026-10-08 (09:20)* — **Flagged: Power Steering**. *"Medyo nag-uuyog an manibela pag-abot sa 60 kph sa may diversion road."*
  *(Oct 09–14: Off road during damage review and repair)*
  4. *2026-10-15 (09:10)* — All OK. *"Bag-o an ball joint ngan tie rod end tikang sa GSO Motorpool. Hapsay na liwat an pagliko san manibela."*
  5. *2026-10-21 (09:25)* — **Flagged: Lights**. *"Overhead lightbar strobe relay medyo ma-delay pagpitik pero functional an sirena ngan driving headlights."*
* **Damage Report (1 Submission by Rolando Calbiga):**
  - *2026-10-09 (10:15)* | **Reviewed:** *2026-10-09 (11:30)* by Admin
  - *Severity Action:* Admin sets vehicle status to `Not Operational` (Source: `'Damage Review'`).
  - *Nature:* Nagraragubrob ngan makuri an manibela pagliko sa tuo pag-agi sa libaong ngan baha sa Brgy. Nijaga samtang nagdudul-ong ug rescue equipment. May tulo liwat nga hydraulic fluid sa idarom.
  - *Suspected Parts:* Front right lower suspension ball joint, outer tie rod end, power steering rack seal kit. Photo: `null`.
* **Repair Log (1 Record - October 14, 2026, GSO Motorpool):**
  - *2026-10-14 (14:00)* | **Source:** GSO Motorpool | **Cost:** ₱4,350.00 | **Doc:** `null`
  - *Restoration Action:* Vehicle status restored to `Operational` (Source: `'Repair Logs'`).
  - *Scope:* Replaced sheared front right lower suspension ball joint and outer steering tie rod end; renewed high-pressure power steering line seals; performed front-wheel toe-in alignment and road testing.
  - *Parts:* Front right lower ball joint assembly, outer tie rod end, power steering high-pressure O-ring kit, Dexron III ATF fluid (2L).
* **Dispatches (3 Records - Calibrated 41,990 km → 42,150 km):**
  1. *2026-10-02 (09:30–12:30)* | CDRRMO Base → Brgy. Hamorawon & Brgy. Dagum | Driver: Rolando Calbiga | 2nd: Christopher Clark Mabini | Mission: Rescue Operation | Odo: 41,990 → 42,045 km (55 km)
  2. *2026-10-07 (13:00–16:30)* | City Hall Compound → Brgy. Tinambacan Emergency Outpost | Driver: Rolando Calbiga | Mission: Rescue Operation | Odo: 42,045 → 42,105 km (60 km)
  3. *2026-10-19 (10:00–13:15)* | CDRRMO Base → Brgy. Obrero & Brgy. Carayman (Flood Monitoring) | Driver: Rolando Calbiga | 2nd: Jonathan Dela Peña | Mission: Rescue Operation | Odo: 42,105 → 42,150 km (45 km)
* **Notifications for Rolando Calbiga (8 Mobile Alerts):**
  1. *2026-10-02 (09:30)* — `Vehicle_Status_Update`: `SAB-5120 is now Dispatched.` (Trip 1 departure)
  2. *2026-10-02 (12:30)* — `Vehicle_Status_Update`: `SAB-5120 is now Operational.` (Trip 1 return)
  3. *2026-10-07 (13:00)* — `Vehicle_Status_Update`: `SAB-5120 is now Dispatched.` (Trip 2 departure)
  4. *2026-10-07 (16:30)* — `Vehicle_Status_Update`: `SAB-5120 is now Operational.` (Trip 2 return)
  5. *2026-10-09 (11:30)* — `Vehicle_Status_Update`: `SAB-5120 is now Not Operational.` (From Damage Report review)
  6. *2026-10-14 (14:00)* — `Vehicle_Status_Update`: `SAB-5120 is now Operational.` (From GSO Motorpool repair log)
  7. *2026-10-19 (10:00)* — `Vehicle_Status_Update`: `SAB-5120 is now Dispatched.` (Trip 3 departure)
  8. *2026-10-19 (13:15)* — `Vehicle_Status_Update`: `SAB-5120 is now Operational.` (Trip 3 return)
* **Admin Notifications for SAB-5120 (3 Records):**
  1. *2026-10-08 (09:20)* — Flagged inspection on SAB-5120 (Power Steering)
  2. *2026-10-09 (10:15)* — New Damage Report submitted by Rolando Calbiga
  3. *2026-10-21 (09:25)* — Flagged inspection on SAB-5120 (Lights)

---

## 5. Expansion Framework for Subsequent Agencies

When adding future agencies (**CDRRMO Assigned Fleet (Calbiga & Mabini)**, **BFP**, **PNP**), duplicate this section and apply the same strict rules:
1. **Agency Metadata:** Name, Code, Admin email, Agency ID.
2. **Vehicle Inventory:** Plate number, Model, Type, Current Odo, Initial Status.
3. **Driver Assignment:** Primary vs Secondary driver, Driver email & user ID.
4. **Driver-Specific Sub-Range:** Pick an isolated, non-overlapping window within the master boundary.
5. **Inspections & Issues:** Tailor mechanical observations to the agency's vehicles.
6. **Linguistic Rule:** Preserve Waray-waray with strict `"sa"` and `"san"` usage.
7. **Deduplication Check:** Link all alerts with `pm_schedule_id` and standardized titles.


