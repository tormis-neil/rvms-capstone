# Rescue Vehicle Management System
## Prototype Planning Document

---

# 1. Project Description

## Project Title
**Rescue Vehicle Management System**

## Project Summary

The Rescue Vehicle Management System is a web and mobile-based information system designed to improve the monitoring, maintenance, and operational readiness of rescue vehicles used by government agencies such as the Bureau of Fire Protection (BFP), Philippine National Police (PNP), City Disaster Risk Reduction and Management Office (CDRRMO), and City Health Office (CHO).

Based on interviews conducted with participating agencies, vehicle inspections, defect reporting, maintenance monitoring, dispatch logging, and vehicle availability tracking are often managed through paper-based records, logbooks, spreadsheets, verbal communication, and separate software tools. These fragmented processes make it difficult to maintain centralized records, monitor vehicle conditions, track maintenance activities, and determine vehicle readiness for deployment.

To address these challenges, the proposed system will provide a centralized platform consisting of:

- An Android mobile application for Authorized Drivers
- A Web-based dashboard for Agency Administrators

The system will support:

- Vehicle Information Management
- Driver Management
- Digital BLOWBAGETS Inspection
- Digital Damage and Defect Reporting
- Inspection Monitoring
- Repair Logging
- Preventive Maintenance Scheduling
- Dispatch Logging and Monitoring
- License Monitoring
- Notification Services
- Report Generation

The primary goal of the system is to help participating agencies maintain accurate records, improve maintenance monitoring, enhance vehicle readiness, and provide centralized visibility of rescue vehicle operations.

---

# 2. Schedule of Activities

The development of the Rescue Vehicle Management System will follow a structured software development process based on the approved capstone manuscript.

| Phase | Activities | Schedule |
|---------|---------|---------|
| Planning & Data Gathering | Research, interviews, workflow analysis, requirements gathering, Chapters 1–3 preparation | May 2026 |
| Proposed Prototype Design | UI/UX design, workflow finalization, wireframing, prototype planning | June 2026 |
| Software Development | Mobile app development, web dashboard development, database implementation, API development, module integration | July – September 2026 |
| Testing & Deployment | Unit testing, integration testing, user acceptance testing, bug fixing, pilot testing, deployment preparation, Chapters 4–5 completion | October – November 2026 |

---

# 3. Tech Stack for this Prototype Development

The Rescue Vehicle Management System will utilize a hybrid web-and-mobile architecture composed of modern development frameworks, database technologies, and notification services.

---

## Mobile Application

### Android Studio
**Version:** Android Studio Meerkat (2024.3.1)+

Purpose:
- Primary IDE for Android development
- Emulator testing
- APK generation

---

### Kotlin
**Version:** Kotlin 1.9+

Purpose:
- Main programming language for Android development
- Mobile application logic
- API communication

---

### Jetpack Compose
**Version:** Compose BOM 2024.09.00+

Purpose:
- Modern Android UI framework
- Declarative user interface development
- Responsive mobile screens

---

## Web Dashboard

### Bootstrap
**Version:** Bootstrap 5.3+

Purpose:
- Responsive administrative dashboard
- Consistent user interface
- Mobile-friendly layouts

---

### Visual Studio Code
**Version:** 1.95+

Purpose:
- Backend development
- Frontend development
- Database scripting
- Version control integration

---

## Backend and Full System Stack

The following tools form the backend and full system stack as defined in the capstone manuscript. These will be implemented during the Software Development phase (July–September 2026), not during the prototype phase.

### Laravel
**Version:** Laravel 11 (PHP 8.2+)

Purpose:
- Backend framework for REST API development
- Web dashboard server-side logic
- Authentication and role-based access control

---

### MySQL
**Version:** MySQL 8.0+

Purpose:
- Relational database for all system records
- Storage for vehicles, drivers, inspections, damage reports, repair logs, PM schedules, dispatch records, and generated reports

---

### Firebase Cloud Messaging (FCM)
**Version:** Firebase Android SDK 32.0.0+ / Firebase PHP via HTTP v1 API

Purpose:
- Push notification delivery to Authorized Drivers (PM reminders, vehicle status updates)
- Push notification delivery to Agency Administrators (new damage reports, license expiry alerts, PM due alerts)

---

## Target Platforms

### Mobile Platform
- Android 8.0 (Oreo)+

Users:
- Authorized Drivers

---

### Web Platform
- Google Chrome
- Mozilla Firefox
- Microsoft Edge

Users:
- Agency Administrators

# NOTE: The prototype development will NOT contain any backend-related tasks (Laravel, MySQL, FCM). All data used in the prototype will be sample or static data that simulates real-world tasks and scenarios. Backend implementation will follow the full system stack defined above during the Software Development phase.

---

# 4. Design Philosophy

## Theme: Structured Authority

The Rescue Vehicle Management System adopts a design theme called **Structured Authority** — a light-surfaced, dark-structured approach where the professionalism and authority of the system come from the bold navy structural framework (sidebar, top navigation bar, primary buttons), not from painting the entire interface dark. All content and work surfaces remain clean white, ensuring that status colors, data, and critical alerts communicate clearly without visual competition.

This approach was chosen for three specific reasons:

1. **Report generation produces printable documents.** A light-surface interface generates clean, readable print output without requiring separate print stylesheets or browser workarounds.
2. **Government acceptance testing in Philippine LGU contexts expects professional administrative tools.** Clean, structured, form-like interfaces read as credible and official to agency staff and review committees.
3. **Field staff are not necessarily regular tech users.** High-contrast light interfaces are more accessible under varying light conditions and for users who spend most of their day away from screens.

---

## Simplicity

The interface minimizes unnecessary visual elements and focuses only on information relevant to rescue vehicle operations. Users should be able to complete common tasks — vehicle inspections, damage reporting, preventive maintenance monitoring, and dispatch logging — with the fewest possible steps.

---

## Operational Visibility

Vehicle readiness is the primary objective of the system. Dashboard screens prominently display vehicle operational statuses, pending maintenance activities, damage reports, preventive maintenance schedules, dispatch records, and license expiry alerts to provide administrators with immediate situational awareness.

---

## Consistency

Both the Android mobile application and web dashboard follow a unified design language, ensuring that navigation patterns, colors, buttons, status indicators, and terminology remain consistent across all platforms.

---

## Mobile-First Data Capture

Since vehicle inspections and damage reports are performed in the field, the mobile application prioritizes quick data entry, large touch targets, simplified forms, photo attachment support, and responsive layouts optimized for Android devices.

---

## Role-Based Experience

The interface is tailored according to user responsibilities.

### Authorized Drivers

- View assigned vehicle information
- Submit BLOWBAGETS inspections
- Submit damage reports
- Receive notifications

### Agency Administrators

- Monitor fleet readiness
- Manage vehicle and driver records
- Review inspections and damage reports
- Schedule preventive maintenance
- Log dispatch activities
- Generate reports

This role-focused approach minimizes interface complexity and ensures that users only access functions relevant to their responsibilities.

---

## Information Hierarchy

The system emphasizes critical operational information through visual prioritization. Vehicle statuses, due preventive maintenance schedules, expiring licenses, and pending damage reports receive greater visual emphasis than routine informational records to support faster administrative decision-making.

---

## Accessibility and Readability

The interface utilizes high-contrast colors, clear typography, sufficient spacing, and easily recognizable status indicators to ensure usability across varying device sizes and environments. Since rescue personnel may access the system during or after emergency operations, readability and quick information recognition are considered essential design requirements.

---

# 5. Theme and Color Scheme

## Theme Concept

The Rescue Vehicle Management System will utilize a professional government-service visual identity inspired by the official colors shared among the participating agencies:

- Bureau of Fire Protection (BFP)
- Philippine National Police (PNP)
- City Disaster Risk Reduction and Management Office (CDRRMO)
- City Health Office (CHO)

Analysis of the agency logos identified **Navy Blue** and **Gold** as the only colors consistently present across all four organizations. These colors will serve as the primary branding colors of the Rescue Vehicle Management System, representing authority, reliability, public service, professionalism, and operational readiness.

The overall interface will follow a clean, modern, and data-centric design style similar to fleet management, emergency operations, and government administration systems.

---

## Primary Brand Colors

| Purpose | Color | Hex Code |
|----------|----------|----------|
| Primary Color | Navy Blue | #1B2F72 |
| Primary Dark | Dark Navy | #0E1C4A |
| Primary Light | Light Navy | #2A429A |
| Accent Color | Gold | #D4A017 |
| Accent Light | Light Gold | #E8BA3C |

### Navy Blue

Navy Blue serves as the primary structural color of the system and is used for:

- Sidebar navigation background
- Top navigation bar
- Primary action buttons
- System logo background container
- Major structural interface elements

Navy Blue does not appear as a content fill or decorative element — it anchors the structure of the interface and communicates authority, trust, and professionalism.

### Gold

Gold serves exclusively as a prestige accent and appears in only three specific contexts:

- System logo mark and branding container (the "RV" mark in the sidebar header)
- Active navigation item indicator (3px left border on the currently selected sidebar item)
- Agency identification badge elements in the sidebar context strip

Gold does not appear on buttons, links, table headers, input fields, notification indicators, or content areas. Primary interactive elements use Navy Blue. Alert and notification colors use the semantic status color system. This preserves Gold as a visual identity marker — consistent with how each agency logo uses it — as a prestige signal rather than a functional UI color.

> **Note on Red:** Red (#DC2626) is reserved exclusively for the Not Operational vehicle status and error or danger states. It does not serve as a brand or emphasis color — even though BFP's primary color is red — because its semantic meaning must remain unambiguous throughout the system.

---

## Vehicle Status Colors

Since operational status recognition is critical for rescue vehicle management, each vehicle status will use a dedicated color.

| Vehicle Status | Color | Hex Code |
|----------------|----------|----------|
| Operational | Green | #16A34A |
| Dispatched | Blue | #2563EB |
| Not Operational | Red | #DC2626 |
| Under Preventive Maintenance | Orange | #D97706 |

### Operational

- Green indicates vehicles that are available and ready for deployment.

### Dispatched

- Blue indicates vehicles currently assigned to an active mission.

### Not Operational

- Red indicates vehicles that are damaged, defective, or unavailable for service.

### Under Preventive Maintenance

- Orange indicates vehicles currently undergoing scheduled maintenance procedures.

---

## Neutral Colors

Neutral colors will be used to maintain readability and reduce visual fatigue during prolonged administrative use.

| Purpose | Color | Hex Code |
|----------|----------|----------|
| Background | Light Gray | #F5F7FA |
| Surface/Card | White | #FFFFFF |
| Border | Gray | #E2E8F0 |
| Primary Text | Dark Slate | #0F172A |
| Secondary Text | Muted Gray | #64748B |

---

## Dashboard Visual Style

The web dashboard and mobile application will utilize:

- Card-based layouts
- Minimalist navigation
- Rounded interface components
- Consistent iconography
- High-contrast status badges
- Color-coded operational indicators
- Data-focused dashboard widgets

Dashboard summary cards will prominently display:

- Total Vehicles
- Operational Vehicles
- Dispatched Vehicles
- Vehicles Under Preventive Maintenance
- Not Operational Vehicles
- Total Drivers
- Expiring Licenses
- Pending Damage Reports

This design approach ensures that Agency Administrators can quickly assess fleet readiness while maintaining a professional appearance appropriate for government agency operations.

# 6. System Workflow and Functionalities

The Rescue Vehicle Management System follows a centralized workflow that connects Authorized Drivers and Agency Administrators through a unified vehicle monitoring and maintenance platform. The workflow is designed to ensure that vehicle inspections, defect reporting, maintenance monitoring, dispatch operations, and report generation are properly documented and monitored within each participating agency.

---

## 6.1 SYSTEM ACCESS AND AUTHENTICATION

```
User opens app or browser
    → Login screen (email + password)
    → System authenticates credentials
    → System identifies user role
        → Authorized Driver  → Driver Mobile App
        → Agency Admin       → Admin Web Dashboard
            → Access limited to own agency's data only
```

---

## 6.2 VEHICLE AND DRIVER RECORD MANAGEMENT

```
Agency Admin → Vehicle Management
    → Add Vehicle
        → Fields: Vehicle Type, Plate No., Make, Model,
                  Engine No., Chassis No., Current Mileage,
                  Agency, Assigned Driver
        → Save
    → Update Vehicle → Edit fields → Save
    → View Vehicle Records → View full details and history

Agency Admin → Driver Management
    → Add Driver
        → Fields: Name, Agency, License No., License Expiry Date
        → Save
    → Update Driver → Edit fields → Save
    → View Driver Records → View full details
```

---

## 6.3 VEHICLE INFORMATION (Driver View)

```
Authorized Driver → Vehicle Information screen
    → Views assigned vehicle details:
        Vehicle Type, Plate No., Make, Model,
        Engine No., Chassis No., Assigned Driver, Agency
    → Views current Vehicle Status:
        Operational / Dispatched / Under PM / Not Operational
```

---

## 6.4 DAILY BLOWBAGETS INSPECTION FLOW

```
DRIVER SIDE
    Authorized Driver → Inspection screen
        → Assigned vehicle auto-loaded
        → Fills inspection checklist:
            Standard (all agencies): 12 items
                Battery, Lights, Oil, Water, Brakes, Air,
                Gas, Engine, Tires, Power Steering,
                Horn/Siren, Directional Signals
            BFP additional: 2 items
                Hydraulic System, Fire Pump
            Each item → OK  or  Has Issue → Remarks (required)
        → Submits inspection

ADMIN SIDE
    Agency Admin → Inspection Monitoring
        → Views BLOWBAGETS Results
            (by vehicle, by driver, by date)
        → Views Inspection History Per Vehicle
        → Views Inspection History Per Driver
        → Views Frequently Reported Issues
        → Evaluates findings
            → No action needed      → Leave status as Operational
            → Issue requires action → Update Vehicle Status:
                → Not Operational
                → Under Preventive Maintenance
```

---

## 6.5 DAMAGE REPORTING FLOW

```
DRIVER SIDE
    Authorized Driver → Damage Report screen
        → Vehicle info auto-filled (read-only):
            Vehicle Type, Make, Model,
            Engine No., Chassis No., Plate No., Assigned Driver
        → Fills damage information:
            Nature of Damage
            Suspected Defective Parts
            Photo Attachment (optional)
            Date Reported (auto-filled by system)
        → Submits report

SYSTEM
    → Sets Report Status → Pending
    → Sends notification to Agency Admin → New Damage Report Submitted

ADMIN SIDE
    Agency Admin → Damage Report Management
        → Views list → filters by Pending
        → Opens and reviews report
        → Evaluates severity
        → Updates Vehicle Status:
            → Operational         (no serious issue or repair done)
            → Not Operational     (unsafe for deployment / under repair)
            → Under PM            (maintenance required)
        → Marks Report Status → Reviewed
```

---

## 6.6 REPAIR LOGGING FLOW

```
Agency Admin → Repair Logging
    → Selects vehicle
    → Logs repair details:
        Vehicle, Assigned Driver, Date
        Scope of Work
        Parts Replaced
        Cost (optional)
        Repair Source:
            Internal Office
            GSO Motorpool
            External Repair Shop
        Remarks
    → Saves repair log

Agency Admin → Updates Vehicle Status separately:
    → Operational          (repair completed)
    → Not Operational      (repair still ongoing)
    → Under PM             (follow-up maintenance needed)

Result: Repair log saved permanently to vehicle maintenance history
```

---

## 6.7 PREVENTIVE MAINTENANCE FLOW

```
SETUP
    Agency Admin → PM Scheduling → Create PM Schedule
        → Selects vehicle
        → Configures schedule:
            Specific Part(s)
            PM Type: Mileage-Based  or  Time-Based
            Interval (km or time period)
            Last PM Mileage (for mileage-based)
            Due Soon Warning Threshold
        → Saves schedule

MONITORING
    System continuously checks PM schedules
        → Approaches threshold → PM Status → Due Soon
            → Sends notification to Agency Admin
        → Reaches threshold  → PM Status → Due
            → Sends notification to Agency Admin
        → Driver receives PM Reminder notification

SERVICING
    Agency Admin coordinates servicing with relevant party

    Agency Admin → Updates Vehicle Status → Under PM (while servicing)

COMPLETION
    Agency Admin → Marks PM as Completed
        → Logs completion fields:
            Date Serviced
            Repair Source (Internal Office / GSO Motorpool / External Repair Shop)
            Parts Replaced
            Remarks
        → Saves

    Agency Admin → Updates Vehicle Status → Operational

    NOTE: No auto-renewal. Admin manually creates the next PM schedule.
```

---

## 6.8 DISPATCH LOGGING AND MONITORING FLOW

```
OPENING A DISPATCH
    Agency Admin → Dispatch Logging → New Dispatch
        → Fills dispatch details:
            Vehicle
            Driver
            Mission Type:
                Fire Response / Medical Response / Rescue Operation /
                Patrol / Administrative Travel / Others (Specify)
            Location
            Date and Time Out
        → Saves dispatch
    → System automatically sets Vehicle Status → Dispatched

MONITORING
    Agency Admin → Dispatch Monitoring Dashboard
        → Views all agency vehicles and current status:
            Dispatched / Operational / Not Operational / Under PM
        → Tracks active missions in real time

CLOSING A DISPATCH
    Vehicle returns
    Agency Admin → Opens dispatch record → Close Dispatch
        → Logs Date and Time In
        → Logs Remarks (optional)
        → System prompts: Set return status
            → Operational          (vehicle returned fit for use)
            → Not Operational      (issue found on return)
            → Under PM             (maintenance needed on return)
        → Saves
    → System updates Vehicle Status to selected return status
```

---

## 6.9 LICENSE MONITORING FLOW

```
System continuously checks license expiry dates for all drivers

    License approaching expiry → System flags → Status: Expiring Soon
        → Sends notification to Agency Admin

    License already expired   → System flags → Status: Expired
        → Sends notification to Agency Admin

Agency Admin → License Monitoring screen
    → Views list of Expiring Soon and Expired licenses
    → Takes action (contacts driver, updates record after renewal)
    → Updates license expiry date in Driver Management after renewal
```

---

## 6.10 REPORT GENERATION FLOW

```
Agency Admin → Reports → Selects report type:

    Inspection Records
        Filters: Date Range, Vehicle, Driver

    Damage and Defect Reports
        Filters: Date Range, Vehicle, Review Status

    Repair and Maintenance History
        Filters: Date Range, Vehicle, Repair Source

    Preventive Maintenance Records
        Filters: Date Range, Vehicle, PM Status

    Dispatch Logs
        Filters: Date Range, Vehicle, Driver, Mission Type

    Vehicle Status Summary
        No filter (current snapshot of all vehicles)

    → Applies filters
    → Generates report
    → Prints or saves
```

---

## 6.11 NOTIFICATION FLOW

```
DRIVER receives in-app notifications:
    PM Reminder
        → Triggered when PM schedule reaches Due Soon or Due
    Vehicle Status Update
        → Triggered when Agency Admin updates the vehicle's status

AGENCY ADMIN receives in-app notifications:
    New Damage Report Submitted
        → Triggered when Authorized Driver submits a damage report
    License Expiring Soon / Expired
        → Triggered by License Monitoring when threshold is reached
    PM Due Soon / PM Due
        → Triggered by PM Scheduling when schedule approaches or reaches threshold
```

---

## 6.12 FULL VEHICLE STATUS TRANSITION MAP

```
                        +------------------+
                        |   Operational    |  ← Default active state
                        +------------------+
                         /       |        \
                        ↓        ↓         ↓
              Dispatched    Not Operational   Under PM
              (Admin logs   (Admin updates    (Admin updates
               dispatch)     status)          status)
                  |               |               |
                  ↓               ↓               ↓
              Admin closes    Admin updates   Admin marks
              dispatch +      status after    PM Completed +
              sets return     repair/         updates status
              status          assessment
                  |               |               |
                  └───────────────┴───────────────┘
                                  ↓
                           Back to the
                         appropriate status
                    (Operational / Not Operational / Under PM)
                    set manually by the Agency Admin
```

---

*All records are agency-scoped. Each Agency Admin operates within their own agency's data only.*
*Mileage-based PM relies on Current Mileage manually updated by the Agency Admin in Vehicle Management.*
*Vehicle status is a single field on the vehicle record, updated from any applicable module.*
---

# 7. Module-to-Objective Mapping

The Rescue Vehicle Management System modules were designed to directly address the four study objectives. Each objective is served by a specific set of system modules across both the Android mobile application and the web-based administrative dashboard.

---

## Objective 1: To Input Vehicle and Driver Information

This objective addresses the problem confirmed across all four agencies: vehicle records, driver assignments, and license expiration dates are maintained in separate paper records with no consolidated or searchable system. The following modules directly fulfill this objective.

---

### Vehicle Management — Admin Web Dashboard

Provides a centralized repository for all rescue vehicle records. Agency Administrators can add, update, and view vehicle information including Vehicle Type, Plate Number, Make, Model, Engine Number, Chassis Number, Current Mileage, Assigned Agency, and Assigned Driver. Vehicle status is also managed from this module.

---

### Driver Management — Admin Web Dashboard

Provides centralized storage and management of driver records. Agency Administrators can add, update, and view driver information including Name, Agency, License Number, and License Expiry Date.

---

### Vehicle Information View — Driver Mobile Application

Allows Authorized Drivers to view the complete details and current operational status of their assigned vehicle directly from the mobile application. This ensures drivers have immediate access to their vehicle's registration and assignment information in the field.

---

### License Monitoring — Admin Web Dashboard

Automatically detects driver licenses that are approaching expiry or have already expired. Sends in-app alerts to Agency Administrators and provides a consolidated view of all flagged licenses, prompting timely renewal actions before deployment issues occur.

---

## Objective 2: To Track Vehicle Maintenance

This objective addresses the fragmented and informal maintenance tracking practices confirmed across all agencies — paper-based BLOWBAGETS checklists, driver-triggered defect reporting without standardized forms, verbal maintenance communication, and physical Motor Vehicle Ledger Cards. The following modules directly fulfill this objective.

---

### Digital BLOWBAGETS Inspection — Driver Mobile Application

Allows Authorized Drivers to submit daily vehicle inspections through a standardized digital checklist. The standard checklist covers 12 items for all agencies; BFP drivers complete 14 items with the addition of Hydraulic System and Fire Pump. Each item is marked OK or Has Issue, with remarks required for flagged items.

---

### Inspection Monitoring — Admin Web Dashboard

Allows Agency Administrators to review submitted inspection results, view inspection history per vehicle and per driver, and monitor frequently reported issues across the fleet. Administrators can update vehicle operational status based on inspection findings.

---

### Digital Damage Reporting — Driver Mobile Application

Allows Authorized Drivers to submit standardized damage reports containing the Nature of Damage, Suspected Defective Parts, and an optional Photo Attachment. The Date Reported is auto-filled by the system. Vehicle information is pre-loaded and read-only.

---

### Damage Report Management — Admin Web Dashboard

Allows Agency Administrators to review submitted damage reports, update each report's status from Pending to Reviewed, and update the vehicle's operational status based on the severity of the reported damage.

---

### Repair Logging — Admin Web Dashboard

Allows Agency Administrators to document all repair activities for a vehicle. Repair records include Scope of Work, Parts Replaced, optional Cost, Repair Source (Internal Office, GSO Motorpool, or External Repair Shop), Assigned Driver, and Remarks. Vehicle operational status is updated separately after logging.

---

### Preventive Maintenance Scheduling — Admin Web Dashboard

Allows Agency Administrators to configure mileage-based or time-based PM schedules per vehicle with a configurable Due Soon warning threshold. PM status tracks Due Soon, Due, and Completed. Completion records capture Date Serviced, Repair Source, Parts Replaced, and Remarks. Schedules are manually created per maintenance cycle with no auto-renewal.

---

## Objective 3: To Provide an Admin Module for Dispatch and Vehicle Management

This objective addresses the fragmented fleet monitoring methods confirmed across agencies — group chats, status boards, sight-based confirmation, and logbooks — with no centralized view of vehicle deployment status or readiness. The following modules directly fulfill this objective.

---

### Dashboard Summary — Admin Web Dashboard

Displays a real-time summary of fleet status for the Agency Administrator's own agency. The dashboard shows current counts for Operational, Dispatched, Not Operational, and Under PM vehicles, along with Total Vehicles, Total Drivers, Expiring Licenses, and Pending Damage Reports. Provides immediate situational awareness without navigating individual records.

---

### Dispatch Logging and Monitoring — Admin Web Dashboard

Allows Agency Administrators to record vehicle dispatches with Vehicle, Driver, Mission Type (Fire Response, Medical Response, Rescue Operation, Patrol, Administrative Travel, or Others), Location, and Date and Time Out. Vehicle status automatically changes to Dispatched when a dispatch is opened. When closed, the administrator is prompted to set the return status (Operational, Not Operational, or Under PM). A dispatch monitoring view displays the current deployment status of all agency vehicles.

---

### Vehicle Status Management — Cross-Module

Vehicle operational status is a single field on the vehicle record (Operational, Dispatched, Not Operational, Under Preventive Maintenance) that can be updated from any applicable module: Vehicle Management, Inspection Monitoring, Damage Report Management, Repair Logging, Preventive Maintenance Scheduling, and Dispatch Logging. All status updates write to the same record, ensuring one consistent and current status across the system.

---

### Agency-Scoped Access Control — System-Level

Each Agency Administrator accesses and manages only the data belonging to their own agency. Vehicle records, driver records, inspection submissions, damage reports, repair logs, PM schedules, dispatch records, and notifications are all separated at the agency level. This ensures operational independence between the four participating agencies within the same shared platform.

---

### Notification Services — Both Platforms

Delivers in-app alerts to keep both user roles informed of time-sensitive events without requiring separate communication channels.

**Authorized Drivers receive:**
- Preventive Maintenance Reminder — triggered when a PM schedule reaches Due Soon or Due status
- Vehicle Status Update — triggered when the Agency Administrator updates the vehicle's operational status

**Agency Administrators receive:**
- New Damage Report Submitted — triggered when an Authorized Driver submits a damage report
- License Expiring Soon / Expired — triggered by License Monitoring when a threshold is reached
- PM Due Soon / PM Due — triggered by PM Scheduling when a schedule approaches or reaches its threshold

---

## Objective 4: To Generate Relevant Reports

This objective addresses the absence of digital report generation across all four agencies. Current reporting relies on handwritten logbooks, spreadsheets, and verbal records with no consolidated or filterable output. The following module directly fulfills this objective.

---

### Report Generation — Admin Web Dashboard

Allows Agency Administrators to generate filtered, printable records for all operational and maintenance activities. Reports are based solely on data encoded within the system.

| Report Type | Available Filters |
|---|---|
| Inspection Records | Date Range, Vehicle, Driver |
| Damage and Defect Reports | Date Range, Vehicle, Review Status |
| Repair and Maintenance History | Date Range, Vehicle, Repair Source |
| Preventive Maintenance Records | Date Range, Vehicle, PM Status |
| Dispatch Logs | Date Range, Vehicle, Driver, Mission Type |
| Vehicle Status Summary | None (current snapshot) |

---


# 8. Scope

The Rescue Vehicle Management System (RVMS) is designed to support the monitoring, maintenance, and operational readiness of rescue vehicles used by four participating government agencies in Calbayog City:

* Bureau of Fire Protection (BFP)
* Philippine National Police (PNP)
* City Disaster Risk Reduction and Management Office (CDRRMO)
* City Health Office (CHO)

The system consists of two platforms:

* Android Mobile Application for Authorized Drivers
* Web-Based Dashboard for Agency Administrators

Each Agency Administrator can only access and manage records belonging to their respective agency.

---

## Vehicle and Driver Management

The system shall support the management of vehicle and driver records.

### Vehicle Information

The system shall store:

* Vehicle Type
* Plate Number
* Make
* Model
* Engine Number
* Chassis Number
* Current Mileage
* Assigned Agency
* Assigned Driver

### Driver Information

The system shall store:

* Driver Name
* Agency
* License Number
* License Expiry Date

The system shall monitor approaching and expired driver licenses and provide notification alerts to Agency Administrators.

---

## Digital BLOWBAGETS Inspection

Authorized Drivers shall perform daily vehicle inspections through a Digital BLOWBAGETS Checklist.

### Standard Inspection Items

* Battery
* Lights
* Oil
* Water
* Brakes
* Air
* Gas
* Engine
* Tires
* Power Steering
* Horn/Siren
* Directional Signals

### Additional BFP Inspection Items

* Hydraulic System
* Fire Pump

Each checklist item shall be assigned one of the following statuses:

* OK
* Has Issue

Remarks shall be required for items marked as "Has Issue."

Agency Administrators shall be able to:

* Review inspection submissions
* View inspection history by vehicle
* View inspection history by driver
* Monitor recurring vehicle issues

---

## Damage Report Management

Authorized Drivers shall be able to submit standardized damage reports containing:

* Nature of Damage
* Suspected Defective Parts
* Optional Photo Attachments

All submitted reports shall initially receive a status of:

* Pending

Agency Administrators shall be able to:

* Review submitted reports
* Mark reports as Reviewed
* Update vehicle operational status after assessment

---

## Repair Logging

The system shall support documentation of repair activities.

Repair records shall include:

* Scope of Work
* Parts Replaced
* Optional Cost
* Repair Source
* Assigned Driver
* Remarks

Supported Repair Sources:

* Internal Office
* GSO Motorpool
* External Repair Shop

Vehicle operational status shall be updated separately by the Agency Administrator after repair logging.

---

## Preventive Maintenance Scheduling

The system shall support preventive maintenance monitoring through:

* Mileage-Based Scheduling
* Time-Based Scheduling

Agency Administrators shall configure:

* Maintenance Intervals
* Due Soon Warning Thresholds

Supported Preventive Maintenance Statuses:

* Due Soon
* Due
* Completed

Maintenance completion records shall include:

* Date Serviced
* Repair Source
* Parts Replaced
* Remarks

Preventive maintenance schedules shall be manually created for each maintenance cycle.

---

## Dispatch Logging

Agency Administrators shall be able to record vehicle dispatch activities.

Dispatch records shall include:

* Vehicle
* Driver
* Mission Type
* Location
* Date and Time Out
* Date and Time In

Supported Mission Types:

* Fire Response
* Medical Response
* Rescue Operation
* Patrol
* Administrative Travel
* Others

When a dispatch is opened:

* Vehicle status automatically changes to Dispatched

When a dispatch is closed:

* Administrators select the vehicle return status:

  * Operational
  * Not Operational
  * Under Preventive Maintenance

---

## Dashboard Monitoring

The administrative dashboard shall display summary information including:

* Total Vehicles
* Operational Vehicles
* Dispatched Vehicles
* Not Operational Vehicles
* Vehicles Under Preventive Maintenance
* Total Drivers
* Expiring Licenses
* Pending Damage Reports

---

## Report Generation

The system shall support report generation for:

* Inspection Records
* Damage and Defect Reports
* Repair and Maintenance History
* Preventive Maintenance Records
* Dispatch Logs
* Vehicle Status Summaries

Reports shall support applicable filters such as:

* Date Range
* Vehicle
* Driver
* Maintenance Status

---

## Notification Services

### Notifications for Authorized Drivers

* Preventive Maintenance Reminders
* Vehicle Status Updates

### Notifications for Agency Administrators

* Newly Submitted Damage Reports
* Approaching Driver License Expiry
* Expired Driver Licenses
* Due Soon Preventive Maintenance Schedules
* Due Preventive Maintenance Schedules

---

# 9. Limitations

The Rescue Vehicle Management System is developed specifically for rescue vehicle monitoring and maintenance operations of the four participating agencies in Calbayog City.

The following limitations apply to the proposed system.

---

## User Roles

The system supports only two user roles:

* Agency Administrator
* Authorized Driver

The following personnel shall not have direct system access:

* GSO Personnel
* External Mechanics
* Third-Party Repair Providers

---

## Platform Limitations

The mobile application supports Android devices only.

The system does not support:

* iOS Devices
* Native Desktop Applications

---

## Connectivity Requirements

Both the Android application and web dashboard require a stable internet connection for:

* Data Submission
* Data Synchronization
* Notification Delivery
* Dashboard Access

Offline operation is not included in the current scope.

---

## Vehicle Monitoring Limitations

The system does not support:

* GPS Tracking
* GIS Mapping
* Route Navigation
* Real-Time Vehicle Tracking
* IoT-Based Vehicle Diagnostics
* Telematics Integration

Vehicle location and condition monitoring remain dependent on manual user updates.

---

## Dispatch Limitations

The dispatch module is limited to:

* Dispatch Logging
* Vehicle Availability Monitoring

The system does not support:

* Automated Dispatch Assignment
* Emergency Communication Systems
* Dispatch Recommendation Engines
* Real-Time Mission Tracking

Vehicle and driver assignment decisions remain under the responsibility of each participating agency.

The system assigns one primary driver per vehicle record. Rotating or shift-based driver arrangements, as practiced by CHO, are not accommodated within the current scope. Driver attribution in those cases relies on the dispatch record rather than the vehicle assignment.

---

## Maintenance Monitoring Limitations

Inspection records, damage reports, repair records, and preventive maintenance information rely entirely on manual user input.

Mileage-based preventive maintenance monitoring depends on mileage values manually updated by Agency Administrators.

The system does not perform:

* Automated Mechanical Diagnostics
* Vehicle Health Detection
* Predictive Maintenance Analysis

---

## Repair Process Limitations

The system supports repair documentation and status monitoring only.

The following activities remain outside the system:

* Mechanical Inspection Validation
* Repair Verification
* Procurement Processes
* Parts Acquisition
* External Maintenance Operations

For vehicles under manufacturer warranty, repair activities remain dependent on authorized dealers or external service providers.

---

## Reporting Limitations

Generated reports are based solely on data encoded within the system.

The system does not generate:

* Official Government Forms
* Commission on Audit (COA) Standardized Documents
* Legally Certified Maintenance Records

---

## Existing Agency Systems

The RVMS is designed as a standalone platform and does not integrate with any existing tools currently in use by the participating agencies.

* The PNP currently operates a Preventive Maintenance System (PMS) for repair record logging only. The RVMS covers the full maintenance and fleet monitoring scope that the PNP's PMS does not, and the two systems will run independently.
* The CDRRMO operates a separate data management software for emergency response analytics. That system covers incident data only and is unrelated to fleet maintenance monitoring. The RVMS addresses the CDRRMO's fleet maintenance gap independently.

---

## Project Scope Limitation

The study focuses on:

* System Design
* System Development
* Workflow Implementation
* Pilot-Level Testing

within the duration of the capstone project.

Large-scale deployment, long-term operational evaluation, and city-wide implementation are beyond the scope of the study.
