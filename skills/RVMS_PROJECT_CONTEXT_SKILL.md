# RVMS_PROJECT_CONTEXT_SKILL
**Rescue Vehicle Management System — Project Context Reference**
*Northwest Samar State University | BSIT/BSIS Capstone | Calbayog City, Samar, Philippines*

Use this file as the authoritative reference for all RVMS-related decisions: documentation writing, system design, code generation, interview interpretation, and scope boundary checks. Always read this before producing any RVMS output.

---

## 1. PROJECT OVERVIEW

**Full Title:** Rescue Vehicle Management System (RVMS)

**Institution:** Northwest Samar State University, College of Computing and Information Sciences, Calbayog City

**Degree:** Bachelor of Science in Information Technology / Bachelor of Science in Information Systems

**Team:**
- Neil Mayo C. Tormis (project lead, primary full-stack developer)
- Jenny Rose C. Monticod
- Jhon Lex C. Mahait
- Sim Harold J. Doren
- Christian Jay F. Abarro

**Adviser:** Sir Ervin

**Target completion:** 
- Prototype (2nd week of June, 2026)
- Full system (2nd week of September, 2026)
- Final output (1st week of November, 2026)

---

**What it is:** A two-platform digital system that replaces the manual, paper-based, and verbally coordinated vehicle management practices of four government rescue agencies in Calbayog City.

**Two platforms:**
- **Android mobile application** — for Authorized Drivers
- **Web-based administrative dashboard** — for Agency Administrators

**Core problem it solves:** The four agencies collectively manage a fleet of rescue vehicles (ambulances, fire trucks, patrol cars) using paper forms, verbal reports, and disconnected agency-level records. There is no shared digital system. Vehicle status, inspection results, maintenance records, and dispatch information cannot be checked at any given time from a single place.

---

## 2. OBJECTIVES

**Main objective:** Develop the Rescue Vehicle Management System.

**Specific objectives:**
1. To input vehicle and driver information.
2. To track vehicle maintenance.
3. To provide an admin module for dispatch and vehicle management.
4. To generate relevant reports.

### Objective-to-Module Mapping

| Objective | Modules That Fulfill It |
|---|---|
| 1. To input vehicle and driver information | Vehicle & Driver Record Management |
| 2. To track vehicle maintenance | Digital BLOWBAGETS Inspection, Defect & Damage Reporting, Repair Logging, Preventive Maintenance Scheduling & Tracking |
| 3. To provide an admin module for dispatch and vehicle management | Dispatch Logging & Fleet Availability Monitoring, Vehicle Status Management |
| 4. To generate relevant reports | Report Generation |

> **Cross-cutting modules:** The In-App Notification System supports all four objectives by delivering alerts related to inspections, maintenance, dispatch, and reports.

---

## 3. SCOPE

The system covers the following agencies within Calbayog City:
- City Disaster Risk Reduction and Management Office (CDRRMO)
- Bureau of Fire Protection (BFP)
- City Health Office (CHO)
- Philippine National Police (PNP)

**Modules in scope:**

| Module | Driver App | Admin Dashboard |
|---|---|---|
| Vehicle & Driver Record Management | View own record | Full CRUD |
| Digital BLOWBAGETS Inspection | Submit daily inspection | Review, update status |
| Defect & Damage Reporting | Submit with optional photo | Review, respond, print |
| Repair Logging | — | Log, update status, print |
| Preventive Maintenance Scheduling & Tracking | Receive notifications | Configure, log completion |
| Dispatch Logging & Fleet Availability Monitoring | — | Log dispatch, close dispatch, fleet board |
| Vehicle Status Management | View assigned vehicle status | Full control |
| Report Generation | — | All report types |
| In-App Notification System | Receive notifications | Receive alerts |

**Vehicle types covered:** Ambulances, fire trucks, mini-fire trucks, patrol cars, service cars.

**User roles:** Two roles only — Authorized Driver and Agency Administrator.

**Supported platform:** Android only (mobile app).

---

## 4. LIMITATIONS

These are confirmed limitations. Do not design around them unless instructed.

- **No GPS, GIS, or real-time vehicle tracking.** No route navigation, no map view, no live location of deployed vehicles.
- **No IoT or telematics.** No automated vehicle diagnostics, no OBD integration.
- **No iOS support.** Android only.
- **No GSO portal or third-party access.** GSO Motorpool, external mechanics, and repair shops have no login. Their involvement is recorded by the Agency Admin only.
- **No automated dispatch assignment.** The system logs dispatches; it does not recommend which vehicle or driver to deploy.
- **No real-time emergency communication.** RVMS is not a dispatch radio or emergency coordination system.
- **No COA-standard or legally binding official forms.** Generated reports reflect encoded records only. They are not official government documents.
- **Mileage and inspection data are manual-input only.** There is no odometer reader, sensor, or auto-sync from the vehicle.
- **Warranty repairs are external.** When a new vehicle is under warranty, RVMS will mark it "Not Operational" but the repair itself goes to the authorized dealer — outside the system.
- **Internet connection required.** Both platforms require a stable connection. No offline mode.
- **Scope is limited to Calbayog City** during development and pilot testing.
- **Pilot testing only.** The study will not deliver a fully production-deployed system.

---

## 5. AGENCIES INVOLVED

### CDRRMO (City Disaster Risk Reduction and Management Office)
- Under: Local Government Unit (LGU)
- Chain of command: Driver → Maintenance Officer → GSO Motorpool
- Vehicles: Ambulances, mini-fire trucks (12 rescue vehicles across barangays and stations)
- Inspection style: No standardized daily checklist. Driver-triggered defect reporting.
- PM schedule: Mileage-based (Toyota: every 5,000 km; Hino: every 6 months or 10,000 km). Tire replacement every 6 months.
- Repair flow: Maintenance Officer labels vehicle "Not Operational" → reports to GSO → pre-inspection checklist → GSO Motorpool repairs → post-inspection checklist.
- Minor issues: Informal direct contact with GSO, no heavy paperwork.
- Warranty: New vehicles go to the brand dealer.
- Also monitors: Driver licenses and assigned vehicles.

### BFP (Bureau of Fire Protection)
- Under: National government (DILG family), reports to BFP Provincial Office
- Chain of command: Driver → Logistics Officer → Chief Operations Officer → decision on severity
- Vehicles: Fire trucks, ambulances, service cars
- Inspection style: Daily paper-based **Motor Vehicle Daily Safety Checklist** (BLOWBAGETS protocol, 14 items including Hydraulic System and Fire Pump).
- Defect logging: Physical **Motor Vehicle Ledger Card** — driver records defect details; noted by MFM/CFM officer.
- Status reporting: Reports "Unserviceable" to BFP Provincial Office and to the LGU/Mayor via text when a vehicle is grounded. Reports "Serviceable" when repaired.
- Parts & fuel: LGU provides fuel weekly, distributed across vehicles. Minor parts: station supplies. Major repairs: tap LGU for assistance.
- No mileage-based standard: Uses parts "as long as they are moving." Ambulance is the exception — follows a scheduled standard since it is LGU-provided.
- For major repairs: outsources labor to GSO Motorpool but supplies their own parts. Required to complete pre/post repair inspection checklist for liquidation.

### CHO (City Health Office)
- Under: Local Government Unit (LGU)
- Vehicles: Ambulances only (from LGU, PCSO, or DOH donations)
- Inspection style: No rigid daily checklist. Driver performs informal checks; reports triggered by visible defects or mileage milestones.
- PM schedule: Odometer-based, typically every 5,000 km depending on vehicle brand.
- Repair workflow: Same as CDRRMO — goes through GSO Motorpool for major issues.
- Minor issues: Administrative staff contacts GSO informally.
- Warranty: Ambulances still under warranty go directly to the brand dealer; GSO Motorpool does not touch them.
- Chain of command: Driver → Logistics/Supply Officer → Head Officer → GSO.

### PNP (Philippine National Police)
- Under: National government (DILG family)
- Vehicles: Patrol cars (some LGU-provided)
- Inspection style: Daily BLOWBAGETS (verbal/informal). No paper forms at the station level.
- Change oil: Every 3,000–5,000 km.
- Repair workflow: Driver → Supply Office → PNP Chief/Chief of Operations → decision on severity → internal repair (minor) or tap LGU (major).
- No fixed motorpool: Outsources to external repair shops.
- Communication: Verbal only for vehicle maintenance. No forms.
- Existing digital tool: **Preventive Maintenance System (PMS)** — used by the Supply Office to log repair history. Limited to logging only; does not cover inspections, dispatch, or report generation.

### GSO Motorpool (City General Services Office — Equipment Management Division)
- Not a user of the system (no login role), but a key external actor.
- Handles: Repair labor and parts for all LGU/government vehicles. CDRRMO and CHO vehicles always outsource to GSO Motorpool. BFP and PNP outsource only for major repairs (and supply their own parts).
- Pre-inspection and post-inspection checklists are required for every repair — needed for liquidation.
- Issue: Officers' damage reports are sometimes inaccurate. GSO Motorpool often discovers additional damage during their own pre-inspection. Communication from agency officers to GSO is currently verbal or by phone.
- Also manages fuel allocation from LGU to agencies.

---

## 6. WORKFLOWS

### 6.1 Daily BLOWBAGETS Inspection
```
Driver (mobile app)
  └─ Submits 14-item BLOWBAGETS inspection
       └─ Each item: OK or Has Issue + optional remarks
            └─ System marks: Pending Review

Agency Admin (dashboard)
  └─ Reviews inspection results per vehicle/driver/date
       └─ Updates vehicle operational status
            └─ Inspection stored permanently in history
```

### 6.2 Defect & Damage Reporting
```
Driver (mobile app)
  └─ Submits standardized damage report
       (Vehicle type & model, engine no., chassis no., description, optional photo)
            └─ System marks: Pending Review

Agency Admin (dashboard)
  └─ Reviews report
       └─ Sets vehicle status (Operational / Partially Operational / Not Operational)
            └─ Report can be printed for external coordination
```

### 6.3 Repair Logging
```
Agency Admin (dashboard)
  └─ Logs repair:
       - Vehicle type & model
       - What was repaired
       - Repaired by: Internal Station / GSO Motorpool / External Shop (name required)
       - Parts replaced (for major repairs)
       - Repair dates, remarks, assigned driver
            └─ Updates vehicle status to Operational
                 └─ Printable repair log generated
```

### 6.4 Preventive Maintenance (PM)
```
Agency Admin configures PM schedule per vehicle:
  └─ Mileage-based (e.g., every 5,000 km) or time-based (e.g., every 6 months)
       └─ Type: Oil Change, Tire Replacement, etc.

Dashboard flags (thresholds configurable by Agency Admin):
  └─ Due Soon (approaching next service interval)
  └─ Overdue (past next service interval)
  └─ Completed

Agency Admin notifies driver → logs PM completion:
  (vehicle details, activities performed, parts replaced,
   serviced by, service date, assigned driver)
```

### 6.5 Dispatch Logging & Fleet Monitoring
```
Agency Admin logs dispatch:
  └─ Vehicle assigned, driver assigned
       Mission type: Fire Response / Medical Response / Rescue Operation / Patrol / Other
       Location, date and time dispatched
            └─ Vehicle status auto-sets: Dispatched

Agency Admin closes dispatch on vehicle return:
  └─ Date/time returned, optional remarks
       └─ Vehicle status returns: Operational

Fleet board: Live view of all vehicles and statuses.
```

### 6.6 Report Generation
Agency Admin can generate and print:
- Vehicle Inspection Reports (BLOWBAGETS results)
- Defect and Damage Reports
- Maintenance History Reports
- Repair Log Records
- Preventive Maintenance Records
- Dispatch Logs
- Vehicle Operational Status Reports

---

## 7. USER ROLES

### Authorized Driver
- One per assigned vehicle (or may share across vehicles in some agencies).
- Uses the **Android mobile app**.
- Capabilities: Submit daily BLOWBAGETS inspection, submit defect/damage report with optional photo, view vehicle status and maintenance history, receive FCM notifications.
- Cannot: Access the web dashboard, manage other vehicles, log repairs, generate reports.

### Agency Administrator
- One per agency (or designated officers per agency).
- Uses the **web-based dashboard**.
- Capabilities: Full record management (vehicles, drivers, assignments), review inspections and defect reports, manage vehicle statuses, log dispatches, configure and track PM schedules, log repairs, generate and print all reports, receive all alert notifications.
- Cannot: Use the Android mobile app as their primary interface; does not represent GSO, external shops, or other agencies.

> **Note:** The system does not include a Super Admin, GSO account, or cross-agency admin role in the current scope.

---

## 8. VEHICLE STATUSES

These are the five official status values. Use these exact terms throughout all documentation and code.

| Status | Meaning |
|---|---|
| **Operational** | Available and fit for deployment |
| **Dispatched** | Currently out on an active mission |
| **Partially Operational** | Deployable but with a known limitation or minor defect |
| **Not Operational** | Grounded — under repair or unsafe to deploy |
| **Under Preventive Maintenance** | Undergoing scheduled maintenance (oil change, tire replacement, etc.) |

Status transitions are controlled by the Agency Admin, except "Dispatched" which is set automatically when a dispatch is logged, and returns to "Operational" when the dispatch is closed.

---

## 9. AGREED TERMINOLOGY

Use these exact terms in all documents, UI labels, code variables, and API field names.

| Term | Meaning |
|---|---|
| **RVMS** | Rescue Vehicle Management System |
| **Authorized Driver** | The mobile app user role (vehicle driver) |
| **Agency Administrator / Agency Admin** | The web dashboard user role |
| **BLOWBAGETS** | Battery, Lights, Oil, Water, Brakes, Air, Gas, Engine, Tires, Power Steering, Horn/Siren, Directional Signals, Hydraulic System, Fire Pump — standardized 14-item Philippine vehicle inspection protocol adopted by RVMS |
| **Motor Vehicle Daily Safety Checklist** | BFP's paper-based daily inspection form (BLOWBAGETS-based) |
| **Motor Vehicle Ledger Card** | BFP's paper-based defect and maintenance history record |
| **Preventive Maintenance (PM)** | Scheduled, interval-based servicing (oil change, tire replacement, etc.) |
| **Not Operational** | Official status for a grounded vehicle; mirrors real-agency usage |
| **Serviceable / Unserviceable** | BFP-specific status terms used internally by BFP; RVMS uses "Operational" / "Not Operational" as the system-wide equivalents |
| **GSO** | General Services Office (city-level) |
| **GSO Motorpool** | Equipment Management Division under GSO; handles all government vehicle repairs |
| **PMS** | Preventive Maintenance System — the PNP's existing (limited) digital tool; not part of RVMS |
| **Dispatch** | A logged mission event where a vehicle and driver are assigned to a response |
| **Fleet Availability Board** | The dashboard view showing all vehicles and their current statuses |
| **Pre-inspection Checklist** | GSO Motorpool's inspection done before a repair begins |
| **Post-inspection Checklist** | GSO Motorpool's inspection done after a repair is completed |
| **BLOWBAGETS Items (14)** | Battery, Lights, Oil, Water, Brakes, Air, Gas, Engine, Tires, Power Steering, Horn/Siren, Directional Signals, Hydraulic System, Fire Pump — all agencies use this standardized 14-item checklist |

---

## 10. INTERVIEW FINDINGS (SUMMARY BY AGENCY)

### What is consistent across all four agencies:
- All four track vehicle plate numbers, assigned drivers, and driver license records — manually.
- All four report vehicle status changes to a higher authority (provincial office, LGU, or commanding officer) — verbally or via text.
- All four rely on the driver as the first-line defect reporter.
- None have a centralized shared system.
- All four use or outsource to GSO Motorpool for major repairs.
- LGU provides fuel to all four agencies weekly or per need.

### Key differences that affect system design:
| Factor | CDRRMO | BFP | CHO | PNP |
|---|---|---|---|---|
| Daily inspection form | None | Paper (BLOWBAGETS) | None | Verbal BLOWBAGETS |
| Defect logging form | None | Motor Vehicle Ledger Card | None | None |
| PM standard | Mileage/time-based | No standard (except ambulance) | Odometer-based | Mileage-based |
| Repair outsource | GSO Motorpool | GSO (labor only, own parts) | GSO Motorpool | External shops |
| Existing digital tool | None | None | None | PMS (limited) |
| Reporting chain | LGU (local) | BFP Provincial + LGU | LGU (local) | PNP Chief + LGU |

### Notable operational facts:
- CDRRMO has 12 rescue vehicles distributed across barangays and stations.
- All agencies will use a standardized 14-item BLOWBAGETS checklist. BFP's existing 14-item paper form was used as the basis for the standardized checklist.
- GSO Motorpool sometimes discovers unreported damage during pre-inspection — a validation gap the system addresses through structured digital reporting.
- PNP's PMS only logs repair history; it has no inspection, dispatch, or reporting module.
- CHO and CDRRMO share the same GSO-based repair workflow.
- BFP and PNP are national agencies; CDRRMO and CHO are local LGU agencies. This affects command structure but does not change the RVMS user roles.

---

## 11. DESIGN PHILOSOPHY

These principles were established from interviews and confirmed operational realities. They should guide all system and UI decisions.

1. **Preserve existing chain of command.** RVMS digitizes the process — it does not restructure it. Drivers still report to officers; officers still make decisions. The system supports those decisions with better data.

2. **Agency-aware, not agency-siloed.** All four agencies use one system. The system must accommodate workflow differences (e.g., the standardized 14-item BLOWBAGETS checklist) without creating separate systems for each.

3. **Driver experience must be simple.** Drivers are field personnel. Mobile forms should be fast, clear, and require minimal taps. No unnecessary fields.

4. **Admin dashboard is the control center.** The web dashboard is where all decisions happen — status changes, repair logging, PM scheduling, dispatch management, report generation. Admins are the authority users.

5. **Paper trail, digitized.** Every major action (inspection, defect report, repair, PM, dispatch) should be printable and auditable. This mirrors how agencies currently validate work (pre/post inspection checklists for GSO liquidation, ledger cards, etc.).

6. **Manual input is the standard.** No sensor, no GPS, no IoT. Mileage is typed. Defects are described. Status is set by a human. Design forms accordingly — clear fields, not complex.

7. **Notifications replace verbal coordination.** Currently, officers call or text for status updates. In-app notifications should handle routine alerts (inspection submitted, PM due, repair done, status change) without requiring external communication.

8. **GSO Motorpool is external.** GSO is a key actor in real workflows but is not a system user. Their involvement is documented by the Agency Admin in repair logs.

---

## 12. WHAT NOT TO IMPLEMENT

This list is final within the current scope. Do not propose, design, or code any of the following.

- GPS tracking or live vehicle location map
- GIS mapping or route display
- iOS mobile application
- Automated dispatch assignment or recommendation engine
- Real-time emergency communication (radio, live call, SOS button outside of scope)
- IoT or OBD (On-Board Diagnostics) integration
- GSO Motorpool portal or login account
- External repair shop portal or login account
- COA-compliant or legally standardized official government forms
- Automatic odometer reading or mileage sync from the vehicle
- Cross-agency administrator (one admin overseeing multiple agencies)
- Super Admin role (not in current scope)
- Predictive maintenance using AI or machine learning
- Offline mode / offline-first data sync
- iOS push notifications
- Integration with PNP's existing PMS software
- Automated procurement or parts ordering features
- Multi-city or regional deployment (Calbayog City only for now)
- Real-time driver tracking during dispatch

---

*Last updated: May 2026. Reflects interview data from CDRRMO, BFP, CHO, PNP, and GSO Motorpool Calbayog City, and the finalized capstone manuscript chapters 1–3.*
