---
name: RVMS_SOFTWARE_DEVELOPMENT_SKILL
description: Rescue Vehicle Management System Software Development Reference
---

# RVMS_SOFTWARE_DEVELOPMENT_SKILL

**Rescue Vehicle Management System — Software Development Reference**
*Northwest Samar State University | BSIT/BSIS Capstone | Calbayog City, Samar, Philippines*

Use this file as the authoritative technical reference for all RVMS software development: code generation, architecture decisions, database design, API design, UI component creation, and deployment. Always read this before writing any RVMS code.

> [!IMPORTANT]
> This document must be read in conjunction with `RVMS_PROJECT_CONTEXT_SKILL`. The project context defines **what** the system does; this document defines **how** it is built.

---

## 1. AGREEMENTS

### 1.1 Development Agreements

| Agreement | Detail |
|---|---|
| **Primary Developer** | Neil Mayo C. Tormis (project lead, full-stack) |
| **Development Model** | Solo-developer-led with team review and testing support |
| **Source Control** | Git with GitHub; `main` branch is protected; all work via feature branches |
| **Branch Naming** | `feature/<module>-<description>`, `fix/<issue>`, `chore/<task>` |
| **Commit Convention** | Conventional Commits: `feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `test:` |
| **Code Review** | Self-review checklists before merging; peer testing by team members |
| **Environments** | `development` (local), `staging` (test server), `production` (live server) |
| **Prototype Deadline** | 2nd week of June 2026 |
| **Full System Deadline** | 2nd week of September 2026 |
| **Final Output Deadline** | 1st week of November 2026 |

### 1.2 AI Code Generation Agreement

| Agreement | Detail |
|---|---|
| **Plain Language Explanation** | All AI-generated code must include a plain-language explanation written in simple, non-technical English. Every function, class, block, or logic section must be accompanied by a comment or summary that explains *what it does* and *why it exists* so that any team member — regardless of technical skill level — can understand it. |
| **No Unexplained Code** | The AI must never output raw code without context. If a block of code is generated, a short paragraph or inline comments must explain the purpose, inputs, outputs, and any important behavior in everyday language. |
| **Educational Tone** | Explanations should be written as if teaching a beginner. Avoid jargon unless it is immediately defined. For example: "This function checks if a vehicle is available for dispatch (meaning it is marked as 'Operational' and is not currently out on a mission)." |
| **Step-by-Step for Complex Logic** | For multi-step logic (e.g., PM due date calculation, status transitions, notification triggers), the AI must break the explanation into numbered steps in plain language before or alongside the code. |
| **Match the Tech Stack** | All generated code must use the technologies specified in this document: Kotlin + Jetpack Compose for mobile, Laravel + Bootstrap for backend/web, MySQL for database, and Firebase Cloud Messaging for push notifications. Do not substitute other frameworks or languages unless explicitly instructed. |

### 1.3 Technical Agreements

| Agreement | Detail |
|---|---|
| **Separate Codebases** | Two independent projects: an Android Studio project (mobile app) and a Laravel project (backend + web dashboard) |
| **Languages** | Kotlin for Android; PHP for Laravel backend and web views |
| **Mobile IDE** | Android Studio Meerkat (2024.3.1)+ |
| **Backend IDE** | Visual Studio Code 1.95+ |
| **Linting** | Android: ktlint / detekt; Laravel: Laravel Pint (PSR-12) |
| **Testing** | Android: JUnit + Espresso; Laravel: PHPUnit + Laravel Dusk (browser testing) |
| **No Offline Mode** | Internet required at all times — no offline-first sync or local caching of writes |
| **No GPS/IoT** | No location services, no device sensors beyond camera (for photo uploads) |
| **Android Only** | Mobile app targets Android 8.0 (API level 26) minimum |
| **Pilot Scope** | Calbayog City only; four agencies (CDRRMO, BFP, CHO, PNP) |

### 1.4 Data Agreements

| Agreement | Detail |
|---|---|
| **Data Isolation** | Each agency's data is scoped by `agency_id`; no cross-agency data access |
| **Data Ownership** | Agency Admin owns all data within their agency scope |
| **Timestamps** | All timestamps stored as MySQL `DATETIME` in UTC; displayed in `Asia/Manila` (UTC+8) via application formatting |
| **Soft Delete** | Records are soft-deleted using Laravel's built-in `SoftDeletes` trait (`deleted_at` column) — never hard-deleted |
| **Audit Trail** | All create/update operations log `created_by`, `created_at`, `updated_by`, `updated_at`; delete operations set `deleted_at` |
| **Photo Storage** | Server filesystem (`storage/app/public/`) managed by Laravel; max file size 5 MB per image; JPEG/PNG only |
| **Report Format** | PDF generation using Laravel DomPDF or Snappy for printable reports |

---

## 2. TECH STACK

### 2.1 Overview

```
┌──────────────────────────────────────────────────────────────┐
│                       CLIENT LAYER                           │
│                                                              │
│  ┌──────────────────────┐    ┌────────────────────────────┐  │
│  │   Android Mobile App │    │   Web Admin Dashboard      │  │
│  │                      │    │                            │  │
│  │   Kotlin 1.9+        │    │   Laravel 11 (Blade)       │  │
│  │   Jetpack Compose     │    │   Bootstrap 5.3+           │  │
│  │   Android Studio      │    │   jQuery (minimal)         │  │
│  │                      │    │                            │  │
│  └──────────┬───────────┘    └──────────┬─────────────────┘  │
│             │                           │                    │
│             │  HTTP (REST API)          │  Server-side       │
│             │                           │  rendering         │
├─────────────┴───────────────────────────┴────────────────────┤
│                      SERVER LAYER                            │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐    │
│  │              Laravel 11 (PHP 8.2+)                   │    │
│  │                                                      │    │
│  │  ├── REST API (for mobile app)                       │    │
│  │  ├── Web Routes (for admin dashboard - Blade views)  │    │
│  │  ├── Authentication (Laravel Sanctum)                │    │
│  │  ├── Task Scheduling (PM checks, license alerts)     │    │
│  │  ├── FCM Push Notifications (HTTP v1 API)            │    │
│  │  └── File Storage (photos, reports)                  │    │
│  └──────────────────────┬───────────────────────────────┘    │
│                         │                                    │
├─────────────────────────┴────────────────────────────────────┤
│                      DATA LAYER                              │
│                                                              │
│  ┌──────────────────────┐    ┌────────────────────────────┐  │
│  │   MySQL 8.0+         │    │   Firebase Cloud Messaging │  │
│  │   (All system data)  │    │   (Push notifications only)│  │
│  └──────────────────────┘    └────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 Stack Details

| Layer | Technology | Version | Purpose |
|---|---|---|---|
| **Mobile App** | Kotlin | 1.9+ | Primary language for Android development |
| **Mobile UI** | Jetpack Compose | BOM 2024.09.00+ | Declarative UI toolkit for Android interfaces |
| **Mobile IDE** | Android Studio | Meerkat (2024.3.1)+ | Android development environment |
| **Mobile HTTP** | Retrofit | 2.9+ | Type-safe HTTP client for REST API calls |
| **Mobile JSON** | Gson / Moshi | Latest | JSON serialization/deserialization |
| **Mobile Image** | Coil | 3.0+ | Image loading and caching for Compose |
| **Mobile DI** | Hilt (Dagger) | 2.51+ | Dependency injection framework |
| **Mobile State** | ViewModel + StateFlow | Lifecycle 2.8+ | MVVM state management |
| **Mobile Navigation** | Compose Navigation | 2.8+ | Screen navigation within Compose |
| **Mobile Camera** | CameraX / Activity Result API | Latest | Photo capture for defect reports |
| **Mobile Notifications** | Firebase Android SDK | 32.0.0+ | FCM push notification handling |
| **Backend Framework** | Laravel | 11 (PHP 8.2+) | REST API + web dashboard framework |
| **Backend Auth** | Laravel Sanctum | Built-in | API token authentication for mobile; session auth for web |
| **Backend IDE** | Visual Studio Code | 1.95+ | Code editor for Laravel development |
| **Database** | MySQL | 8.0+ | Relational database for all system data |
| **Web UI** | Bootstrap | 5.3+ | Responsive CSS framework for admin dashboard |
| **Web Views** | Laravel Blade | Built-in | Server-side template engine |
| **Web JS** | jQuery | 3.7+ | Minimal DOM manipulation, AJAX calls, DataTables |
| **Web Tables** | DataTables | 2.0+ | Interactive sortable/filterable/paginated tables |
| **Web Charts** | Chart.js | 4.0+ | Dashboard data visualizations |
| **PDF Generation** | Laravel DomPDF | 2.0+ | Server-side PDF report generation |
| **Push Notifications** | FCM HTTP v1 API | Latest | Server-to-device push notifications via Laravel |
| **Task Scheduling** | Laravel Scheduler | Built-in | Scheduled tasks (PM checks, license expiry alerts) |
| **File Storage** | Laravel Filesystem | Built-in | Local disk storage for photos and generated reports |
| **Queue** | Laravel Queue (database driver) | Built-in | Background jobs for notifications, PDF generation |

### 2.3 Why This Stack

| Decision | Rationale |
|---|---|
| **Kotlin + Jetpack Compose** | Modern Android-first development; Compose is Google's recommended UI toolkit; strong type safety; official language for Android since 2019; aligns with academic curriculum |
| **Laravel 11** | Full-featured PHP framework with built-in auth, ORM, queue, scheduler, file storage, and Blade templating; ideal for rapid development of both REST APIs and server-rendered dashboards; extensive documentation; large community |
| **Bootstrap 5.3** | Proven responsive CSS framework; fast prototyping for admin interfaces; Bootstrap components (tables, modals, cards, forms) cover all dashboard needs; no build step required |
| **MySQL 8.0** | Mature relational database; perfect for structured vehicle/driver/maintenance records; strong referential integrity via foreign keys; well-supported by Laravel Eloquent ORM |
| **Firebase Cloud Messaging** | Only Firebase service used; handles push notifications to Android devices without building custom push infrastructure; free tier is more than sufficient |
| **Laravel Sanctum** | Simple token-based auth for mobile API; cookie/session-based auth for web dashboard; no need for full OAuth complexity |
| **Hilt (Dagger)** | Google-recommended dependency injection for Android; integrates seamlessly with ViewModel and Compose |

---

## 3. FIREBASE STRUCTURE

### 3.1 Firebase Usage — FCM Only

> [!IMPORTANT]
> Firebase is used **exclusively for Firebase Cloud Messaging (FCM)** — push notifications to the Android mobile app. All other data (auth, database, file storage) is handled by Laravel + MySQL. There is no Firestore, no Firebase Auth, no Firebase Storage, and no Cloud Functions.

```
rvms-capstone (Firebase Project)
└── Cloud Messaging (FCM)
    ├── Server-side: Laravel sends via FCM HTTP v1 API
    │   └── Uses a Firebase Service Account JSON key
    └── Client-side: Android app registers device token
        └── Token stored in MySQL `fcm_tokens` table
```

### 3.2 Firebase Project Setup

1. Create a Firebase project (e.g., `rvms-capstone`) in the Firebase Console.
2. Register an Android app with the package name (e.g., `com.rvms.driver`).
3. Download `google-services.json` and place it in `app/` of the Android project.
4. Download the **Service Account JSON key** from Firebase Console → Project Settings → Service Accounts → Generate new private key.
5. Place the Service Account JSON in the Laravel project's `storage/app/firebase/` directory. **Never commit this file to Git.**

### 3.3 FCM Integration Points

| Component | What It Does |
|---|---|
| **Android App** | On login, registers the device with FCM and sends the device token to the Laravel API. On receiving a push, displays the notification and optionally navigates to the relevant screen. |
| **Laravel Backend** | When a notification-triggering event occurs (e.g., inspection submitted, dispatch assigned), Laravel sends a push notification to the target user's device token(s) via the FCM HTTP v1 API. |
| **MySQL `fcm_tokens` table** | Stores each user's device token(s). A user may have multiple tokens if they use multiple devices. Tokens are refreshed on each login and cleaned up when invalid. |

### 3.4 FCM Server-Side (Laravel)

```php
// How the Laravel backend sends a push notification to a driver's phone:
// 
// 1. When something happens (e.g., admin assigns a dispatch), the system
//    looks up the driver's FCM token(s) from the database.
// 2. It builds a message with a title, body, and optional data payload.
// 3. It sends the message to Google's FCM server using the HTTP v1 API.
// 4. Google's server then delivers the notification to the driver's phone.

// Laravel uses a notification channel or a dedicated FCM service class
// to handle this. See Section 7 for full notification logic.
```

---

## 4. DATABASE TABLES

### 4.1 Entity-Relationship Overview

```
agencies ──┬── users ──── fcm_tokens
           │     │
           │     ├── inspections ──── inspection_items
           │     │
           │     └── defect_reports ──── defect_report_photos
           │
           ├── vehicles ──┬── inspections
           │              ├── defect_reports
           │              ├── repairs ──── repair_parts
           │              ├── preventive_maintenances ──── pm_logs ──── pm_log_parts
           │              └── dispatches
           │
           ├── notifications
           │
           └── activity_logs
```

### 4.2 Table Definitions

> [!NOTE]
> All tables use Laravel conventions: `id` as auto-incrementing BIGINT primary key, `created_at` and `updated_at` as DATETIME columns managed by Eloquent, and `deleted_at` for soft-deletable tables. Foreign keys use `_id` suffix with the singular table name (e.g., `agency_id`, `vehicle_id`).

#### `agencies`

```sql
-- This table stores the profile of each rescue agency using the system.
-- There are four agencies: CDRRMO, BFP, CHO, and PNP.
-- All agencies use the standardized 14-item BLOWBAGETS checklist.

CREATE TABLE agencies (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50) NOT NULL,           -- Short name: "CDRRMO", "BFP", "CHO", "PNP"
    full_name       VARCHAR(255) NOT NULL,          -- Full name: "City Disaster Risk Reduction..."
    agency_type     ENUM('LGU', 'NATIONAL') NOT NULL, -- LGU (CDRRMO, CHO) or NATIONAL (BFP, PNP)
    address         VARCHAR(500),                   -- Office address in Calbayog City
    contact_number  VARCHAR(20),
    license_alert_days  SMALLINT UNSIGNED NOT NULL DEFAULT 30, -- Admin-configurable: alert X days before license expiry
    pm_alert_km         SMALLINT UNSIGNED,                    -- Admin-configurable: flag DUE_SOON when X km from next due mileage (NULL = disabled)
    pm_alert_days       SMALLINT UNSIGNED,                    -- Admin-configurable: flag DUE_SOON when X days from next due date (NULL = disabled)
    head_officer    VARCHAR(255),                   -- Name of the agency head / chief
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### `agency_blowbagets_items`

```sql
-- This table stores the BLOWBAGETS inspection checklist items for each agency.
-- All agencies use the standardized 14-item BLOWBAGETS checklist.
-- The 'sort_order' column controls the display order on the form.

CREATE TABLE agency_blowbagets_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id       BIGINT UNSIGNED NOT NULL,
    item_name       VARCHAR(100) NOT NULL,          -- e.g., "Battery", "Lights", "Fire Pump"
    sort_order      TINYINT UNSIGNED NOT NULL,      -- Display order (1, 2, 3...)
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_agency_item (agency_id, item_name)
);
```

#### `users`

```sql
-- This table stores all user accounts — both Authorized Drivers (who use the 
-- mobile app) and Agency Administrators (who use the web dashboard).
-- Each user belongs to exactly one agency. Passwords are hashed by Laravel.

CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id       BIGINT UNSIGNED NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,          -- Bcrypt hash (managed by Laravel)
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    middle_name     VARCHAR(100),
    role            ENUM('DRIVER', 'ADMIN') NOT NULL,
    license_number  VARCHAR(50),                    -- Driver's license number (drivers only)
    license_expiry  DATE,                           -- License expiration (drivers only)
    contact_number  VARCHAR(20),
    profile_photo   VARCHAR(500),                   -- File path in storage
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,  -- Can be deactivated without deletion
    created_by      BIGINT UNSIGNED,                -- Admin who created this account
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by      BIGINT UNSIGNED,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME,                       -- Soft delete (Laravel SoftDeletes)
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### `fcm_tokens`

```sql
-- This table stores Firebase Cloud Messaging device tokens for each user.
-- When a driver logs in on their phone, the app registers a token with FCM
-- and sends it to the server. The server uses these tokens to send push 
-- notifications to the correct device.

CREATE TABLE fcm_tokens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    token           VARCHAR(500) NOT NULL,          -- FCM device registration token
    device_info     VARCHAR(255),                   -- Device model / Android version
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token)
);
```

#### `vehicles`

```sql
-- This table stores every rescue vehicle managed by the system.
-- Each vehicle belongs to one agency and may be assigned to one primary driver.
-- Status tracks whether the vehicle is available, dispatched, under repair, etc.

CREATE TABLE vehicles (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id           BIGINT UNSIGNED NOT NULL,
    plate_number        VARCHAR(20) NOT NULL,
    vehicle_type        ENUM('AMBULANCE', 'FIRE_TRUCK', 'MINI_FIRE_TRUCK', 'PATROL_CAR', 'SERVICE_CAR') NOT NULL,
    brand               VARCHAR(100) NOT NULL,      -- e.g., "Toyota", "Hino", "Isuzu"
    model               VARCHAR(100) NOT NULL,      -- e.g., "Hilux", "Ranger"
    year_model          SMALLINT UNSIGNED,
    engine_number       VARCHAR(100),
    chassis_number      VARCHAR(100),
    color               VARCHAR(50),
    current_mileage_km  DECIMAL(10,2) NOT NULL DEFAULT 0, -- Last reported odometer reading
    fuel_type           ENUM('GASOLINE', 'DIESEL') NOT NULL DEFAULT 'DIESEL',
    status              ENUM('OPERATIONAL', 'DISPATCHED', 'PARTIALLY_OPERATIONAL', 'NOT_OPERATIONAL', 'UNDER_PREVENTIVE_MAINTENANCE') NOT NULL DEFAULT 'OPERATIONAL',
    assigned_driver_id  BIGINT UNSIGNED,            -- Primary assigned driver (nullable)
    acquisition_date    DATE,
    acquisition_source  VARCHAR(255),               -- e.g., "LGU", "PCSO", "DOH Donation"
    is_under_warranty   BOOLEAN NOT NULL DEFAULT FALSE,
    warranty_expiry     DATE,
    remarks             TEXT,
    created_by          BIGINT UNSIGNED,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by          BIGINT UNSIGNED,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          DATETIME,
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (assigned_driver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_plate (plate_number)
);
```

#### `vehicle_driver_assignments`

```sql
-- This table tracks the many-to-many relationship between vehicles and drivers.
-- A vehicle may have multiple assigned drivers (e.g., shift-based), and a driver
-- may be assigned to multiple vehicles. The 'is_primary' flag marks the main driver.
--
-- DESIGN DECISION: Multi-vehicle assignment per driver is restricted to Admin
-- management only. The mobile app always shows the driver's primary assigned vehicle.
-- This accommodates agencies where drivers may operate different vehicles across shifts.

CREATE TABLE vehicle_driver_assignments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id      BIGINT UNSIGNED NOT NULL,
    driver_id       BIGINT UNSIGNED NOT NULL,
    is_primary      BOOLEAN NOT NULL DEFAULT FALSE,
    assigned_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unassigned_at   DATETIME,                       -- NULL means currently assigned
    assigned_by     BIGINT UNSIGNED,                -- Admin who made the assignment
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_active_assignment (vehicle_id, driver_id, unassigned_at)
);
```

#### `inspections`

```sql
-- This table stores each daily BLOWBAGETS inspection submitted by a driver.
-- When a driver opens the mobile app and fills out the checklist, a row is 
-- created here with status 'pending_review'. The admin later reviews it.

CREATE TABLE inspections (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id               BIGINT UNSIGNED NOT NULL,
    vehicle_id              BIGINT UNSIGNED NOT NULL,
    driver_id               BIGINT UNSIGNED NOT NULL,
    inspection_date         DATE NOT NULL,
    mileage_at_inspection   DECIMAL(10,2),          -- Odometer reading at time of inspection
    overall_remarks         TEXT,
    status                  ENUM('PENDING_REVIEW', 'REVIEWED') NOT NULL DEFAULT 'PENDING_REVIEW',
    reviewed_by             BIGINT UNSIGNED,
    reviewed_at             DATETIME,
    review_remarks          TEXT,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### `inspection_items`

```sql
-- This table stores the individual checklist items within a single inspection.
-- Each row represents one BLOWBAGETS item (e.g., "Battery", "Brakes") and 
-- records whether it is OK or has an issue, with optional remarks.

CREATE TABLE inspection_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspection_id   BIGINT UNSIGNED NOT NULL,
    item_name       VARCHAR(100) NOT NULL,          -- e.g., "Battery", "Lights", "Fire Pump"
    `condition`     ENUM('OK', 'HAS_ISSUE') NOT NULL,
    remarks         TEXT,                           -- Required when condition is 'HAS_ISSUE'
    sort_order      TINYINT UNSIGNED NOT NULL,      -- Same order as agency_blowbagets_items
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
);
```

#### `defect_reports`

```sql
-- This table stores defect/damage reports submitted by drivers.
-- When a driver notices damage or a defect on their vehicle, they fill out
-- a form in the mobile app with a description and optional photos.
-- The admin reviews it and decides the vehicle's operational status.

CREATE TABLE defect_reports (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id                   BIGINT UNSIGNED NOT NULL,
    vehicle_id                  BIGINT UNSIGNED NOT NULL,
    reported_by                 BIGINT UNSIGNED NOT NULL, -- Driver user ID
    report_date                 DATE NOT NULL,
    description                 TEXT NOT NULL,            -- Detailed defect/damage description
    status                      ENUM('PENDING_REVIEW', 'ACKNOWLEDGED', 'RESOLVED') NOT NULL DEFAULT 'PENDING_REVIEW',
    reviewed_by                 BIGINT UNSIGNED,
    reviewed_at                 DATETIME,
    admin_remarks               TEXT,
    vehicle_status_after_review ENUM('OPERATIONAL', 'PARTIALLY_OPERATIONAL', 'NOT_OPERATIONAL'),
    resolved_at                 DATETIME,
    created_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at                  DATETIME,
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (reported_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### `defect_report_photos`

```sql
-- This table stores the file paths for photos attached to a defect report.
-- Drivers can optionally upload photos with their defect report. The actual files are stored
-- on the server filesystem; this table stores the path to each file.

CREATE TABLE defect_report_photos (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    defect_report_id  BIGINT UNSIGNED NOT NULL,
    file_path         VARCHAR(500) NOT NULL,         -- Relative path in storage/app/public/
    original_name     VARCHAR(255),                  -- Original filename from the device
    file_size         INT UNSIGNED,                  -- Size in bytes
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (defect_report_id) REFERENCES defect_reports(id) ON DELETE CASCADE
);
```

#### `repairs`

```sql
-- This table logs all repair actions performed on vehicles.
-- Only the Agency Admin can create repair records via the web dashboard.
-- IMPLEMENTATION DECISION: A repair may optionally be linked to the defect
-- report that triggered it, enabling traceability between reported defects
-- and their corresponding repair actions.

CREATE TABLE repairs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id           BIGINT UNSIGNED NOT NULL,
    vehicle_id          BIGINT UNSIGNED NOT NULL,
    defect_report_id    BIGINT UNSIGNED,             -- Optional: the defect that triggered this repair
    description         TEXT NOT NULL,               -- What was repaired
    repair_type         ENUM('MINOR', 'MAJOR') NOT NULL,
    repaired_by_type    ENUM('INTERNAL_STATION', 'GSO_MOTORPOOL', 'EXTERNAL_SHOP') NOT NULL,
    repaired_by_name    VARCHAR(255),                -- Name of shop (required for EXTERNAL_SHOP)
    repair_start_date   DATE NOT NULL,
    repair_end_date     DATE,                        -- NULL if still in progress
    status              ENUM('IN_PROGRESS', 'COMPLETED') NOT NULL DEFAULT 'IN_PROGRESS',
    mileage_at_repair   DECIMAL(10,2),
    assigned_driver_id  BIGINT UNSIGNED,             -- Driver assigned to vehicle at time of repair
    remarks             TEXT,
    logged_by           BIGINT UNSIGNED NOT NULL,    -- Admin who logged this repair
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          DATETIME,
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (defect_report_id) REFERENCES defect_reports(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_driver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (logged_by) REFERENCES users(id)
);
```

#### `repair_parts`

```sql
-- This table records individual parts that were replaced during a repair.
-- Each row is one part. For example: "Oil Filter", quantity 1.

CREATE TABLE repair_parts (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repair_id   BIGINT UNSIGNED NOT NULL,
    part_name   VARCHAR(255) NOT NULL,
    quantity    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    remarks     TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE
);
```

#### `preventive_maintenances`

```sql
-- This table stores the preventive maintenance (PM) schedule configured by
-- the admin for each vehicle. For example: "Oil Change every 5,000 km" or
-- "Tire Replacement every 6 months". The system checks these schedules 
-- periodically and flags vehicles that are due or overdue.

CREATE TABLE preventive_maintenances (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id                   BIGINT UNSIGNED NOT NULL,
    vehicle_id                  BIGINT UNSIGNED NOT NULL,
    pm_type                     ENUM('OIL_CHANGE', 'TIRE_REPLACEMENT', 'BRAKE_INSPECTION', 'BATTERY_REPLACEMENT', 'FILTER_REPLACEMENT', 'GENERAL_CHECKUP', 'OTHER') NOT NULL,
    interval_type               ENUM('MILEAGE', 'TIME') NOT NULL,
    interval_value_km           DECIMAL(10,2),       -- e.g., 5000.00 (every 5,000 km)
    interval_value_days         INT UNSIGNED,        -- e.g., 180 (every 6 months)
    last_completed_date         DATE,
    last_completed_mileage_km   DECIMAL(10,2),
    next_due_date               DATE,                -- Calculated from interval
    next_due_mileage_km         DECIMAL(10,2),       -- Calculated from interval
    pm_status                   ENUM('ON_SCHEDULE', 'DUE_SOON', 'OVERDUE', 'COMPLETED') NOT NULL DEFAULT 'ON_SCHEDULE',
    is_active                   BOOLEAN NOT NULL DEFAULT TRUE,
    configured_by               BIGINT UNSIGNED NOT NULL,
    created_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (configured_by) REFERENCES users(id)
);
```

#### `pm_logs`

```sql
-- This table records each time a preventive maintenance task is actually completed.
-- For example, when the oil change is done, the admin logs the details here.

CREATE TABLE pm_logs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pm_id               BIGINT UNSIGNED NOT NULL,    -- Links to the PM schedule
    agency_id           BIGINT UNSIGNED NOT NULL,
    vehicle_id          BIGINT UNSIGNED NOT NULL,
    pm_type             ENUM('OIL_CHANGE', 'TIRE_REPLACEMENT', 'BRAKE_INSPECTION', 'BATTERY_REPLACEMENT', 'FILTER_REPLACEMENT', 'GENERAL_CHECKUP', 'OTHER') NOT NULL,
    activities_performed TEXT NOT NULL,
    serviced_by_type    ENUM('INTERNAL_STATION', 'GSO_MOTORPOOL', 'EXTERNAL_SHOP') NOT NULL,
    serviced_by_name    VARCHAR(255),
    service_date        DATE NOT NULL,
    mileage_at_service  DECIMAL(10,2),
    assigned_driver_id  BIGINT UNSIGNED,
    remarks             TEXT,
    logged_by           BIGINT UNSIGNED NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pm_id) REFERENCES preventive_maintenances(id),
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (assigned_driver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (logged_by) REFERENCES users(id)
);
```

#### `pm_log_parts`

```sql
-- Parts replaced during a preventive maintenance service.

CREATE TABLE pm_log_parts (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pm_log_id   BIGINT UNSIGNED NOT NULL,
    part_name   VARCHAR(255) NOT NULL,
    quantity    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    remarks     TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pm_log_id) REFERENCES pm_logs(id) ON DELETE CASCADE
);
```

#### `dispatches`

```sql
-- This table logs each dispatch event — when a vehicle and driver are sent 
-- out on a mission. When a dispatch is created, the vehicle's status is 
-- automatically set to 'DISPATCHED'. When the dispatch is closed (vehicle 
-- returns), the status goes back to 'OPERATIONAL'.

CREATE TABLE dispatches (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id       BIGINT UNSIGNED NOT NULL,
    vehicle_id      BIGINT UNSIGNED NOT NULL,
    driver_id       BIGINT UNSIGNED NOT NULL,
    mission_type    ENUM('FIRE_RESPONSE', 'MEDICAL_RESPONSE', 'RESCUE_OPERATION', 'PATROL', 'OTHER') NOT NULL,
    location        VARCHAR(500) NOT NULL,           -- Destination or area description
    dispatched_at   DATETIME NOT NULL,
    returned_at     DATETIME,                        -- NULL while dispatch is active
    status          ENUM('ACTIVE', 'CLOSED') NOT NULL DEFAULT 'ACTIVE',
    remarks         TEXT,
    return_remarks  TEXT,
    logged_by       BIGINT UNSIGNED NOT NULL,        -- Admin who created the dispatch
    closed_by       BIGINT UNSIGNED,                 -- Admin who closed the dispatch
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES users(id),
    FOREIGN KEY (logged_by) REFERENCES users(id),
    FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### `notifications`

```sql
-- This table stores in-app notifications for each user.
-- Every notification has a type (e.g., "inspection submitted", "dispatch assigned")
-- and is marked as read/unread. Push notifications (FCM) are sent separately 
-- but correspond to records in this table.

CREATE TABLE notifications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_id    BIGINT UNSIGNED NOT NULL,        -- The user who receives this notification
    agency_id       BIGINT UNSIGNED NOT NULL,
    type            VARCHAR(50) NOT NULL,            -- e.g., 'INSPECTION_SUBMITTED', 'DISPATCH_ASSIGNED'
    title           VARCHAR(255) NOT NULL,
    body            TEXT NOT NULL,
    data            JSON,                            -- Extra data for deep linking (screen, record ID)
    is_read         BOOLEAN NOT NULL DEFAULT FALSE,
    read_at         DATETIME,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);
```

#### `activity_logs`

```sql
-- This table is the system's audit trail. Every important action (creating a
-- vehicle, changing a status, logging a dispatch, etc.) is recorded here so
-- the admin can review what happened, who did it, and when.

CREATE TABLE activity_logs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id           BIGINT UNSIGNED NOT NULL,
    user_id             BIGINT UNSIGNED NOT NULL,    -- Who performed the action
    action              VARCHAR(100) NOT NULL,       -- e.g., "CREATE_VEHICLE", "UPDATE_STATUS"
    target_table        VARCHAR(100) NOT NULL,       -- e.g., "vehicles", "dispatches"
    target_record_id    BIGINT UNSIGNED NOT NULL,
    description         TEXT NOT NULL,               -- Human-readable description
    previous_data       JSON,                        -- Snapshot before change (for updates)
    new_data            JSON,                        -- Snapshot after change
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 4.3 Database Indexes

```sql
-- Key indexes for query performance:

-- Users
CREATE INDEX idx_users_agency_role ON users(agency_id, role);

-- Vehicles
CREATE INDEX idx_vehicles_agency_status ON vehicles(agency_id, status);

-- Inspections
CREATE INDEX idx_inspections_agency_date ON inspections(agency_id, inspection_date DESC);
CREATE INDEX idx_inspections_vehicle_date ON inspections(vehicle_id, inspection_date DESC);
CREATE INDEX idx_inspections_driver ON inspections(driver_id);

-- Defect Reports
CREATE INDEX idx_defect_reports_agency_date ON defect_reports(agency_id, report_date DESC);
CREATE INDEX idx_defect_reports_status ON defect_reports(agency_id, status);

-- Dispatches
CREATE INDEX idx_dispatches_agency_status ON dispatches(agency_id, status, dispatched_at DESC);

-- Preventive Maintenances
CREATE INDEX idx_pm_agency_status ON preventive_maintenances(agency_id, pm_status);
CREATE INDEX idx_pm_vehicle ON preventive_maintenances(vehicle_id, is_active);

-- Notifications
CREATE INDEX idx_notifications_recipient ON notifications(recipient_id, is_read, created_at DESC);

-- Activity Logs
CREATE INDEX idx_activity_logs_agency ON activity_logs(agency_id, created_at DESC);
```

---

## 5. API STRUCTURE

### 5.1 API Design Principles

- RESTful conventions with resource-based URLs
- Mobile API routes use **Laravel Sanctum token authentication** (Bearer token in `Authorization` header)
- Web dashboard uses **session-based authentication** (Laravel's built-in cookie auth)
- API responses follow a consistent JSON envelope
- Agency-scoped: all queries automatically filter by the authenticated user's `agency_id`
- Pagination via `page` and `per_page` query parameters (Laravel's built-in paginator)

### 5.2 Route Groups

```php
// Laravel routes are organized into two groups:
//
// 1. api.php — REST API endpoints consumed by the Android mobile app
//    - Protected by Sanctum token auth middleware
//    - Returns JSON responses
//    - Prefixed with /api/v1/
//
// 2. web.php — Web dashboard routes rendered with Blade + Bootstrap
//    - Protected by session-based auth middleware
//    - Returns HTML views (or JSON for AJAX calls)
//    - No prefix
```

### 5.3 API Endpoints (Mobile App — `routes/api.php`)

#### Base URL

```
# Development (local)
http://localhost:8000/api/v1

# Production
https://your-server.com/api/v1
```

#### Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/auth/login` | Login with email/password; returns Sanctum token |
| POST | `/api/v1/auth/logout` | Revoke current token |
| GET | `/api/v1/auth/me` | Get authenticated user profile with role/agency |
| PUT | `/api/v1/auth/change-password` | Change own password |
| POST | `/api/v1/auth/fcm-token` | Register or update FCM device token |
| DELETE | `/api/v1/auth/fcm-token` | Remove FCM device token (on logout) |

#### Vehicles (Driver: read assigned only)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/vehicles` | List driver's assigned vehicles |
| GET | `/api/v1/vehicles/{id}` | Get single vehicle details |
| GET | `/api/v1/vehicles/{id}/maintenance-history` | Get repairs + PM history for a vehicle |

#### Inspections (Driver)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/inspections` | List driver's submitted inspections |
| GET | `/api/v1/inspections/{id}` | Get single inspection details |
| POST | `/api/v1/inspections` | Submit new BLOWBAGETS inspection |
| GET | `/api/v1/inspections/form-items` | Get BLOWBAGETS items for driver's agency |

#### Defect Reports (Driver)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/defect-reports` | List driver's submitted defect reports |
| GET | `/api/v1/defect-reports/{id}` | Get single defect report |
| POST | `/api/v1/defect-reports` | Submit new defect report (with photo upload) |

#### Notifications (Driver)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/notifications` | Get driver's notifications (paginated) |
| PUT | `/api/v1/notifications/{id}/read` | Mark a notification as read |
| PUT | `/api/v1/notifications/read-all` | Mark all notifications as read |
| GET | `/api/v1/notifications/unread-count` | Get count of unread notifications |

### 5.4 Web Dashboard Routes (`routes/web.php`)

```php
// The web dashboard is server-side rendered using Laravel Blade templates
// with Bootstrap styling. Most pages return HTML views. Some interactive
// features (like the Fleet Board) use AJAX with JSON responses.

// All web routes require login and ADMIN role.
```

| Method | URL | Controller Method | Description |
|---|---|---|---|
| GET | `/login` | `AuthController@showLogin` | Login page |
| POST | `/login` | `AuthController@login` | Process login |
| POST | `/logout` | `AuthController@logout` | Logout |
| GET | `/dashboard` | `DashboardController@index` | Main dashboard overview |
| — | **Vehicles** | — | — |
| GET | `/vehicles` | `VehicleController@index` | Vehicle list with filters |
| GET | `/vehicles/create` | `VehicleController@create` | Create vehicle form |
| POST | `/vehicles` | `VehicleController@store` | Store new vehicle |
| GET | `/vehicles/{id}` | `VehicleController@show` | Vehicle detail page |
| GET | `/vehicles/{id}/edit` | `VehicleController@edit` | Edit vehicle form |
| PUT | `/vehicles/{id}` | `VehicleController@update` | Update vehicle |
| DELETE | `/vehicles/{id}` | `VehicleController@destroy` | Soft-delete vehicle |
| PUT | `/vehicles/{id}/status` | `VehicleController@updateStatus` | Change vehicle status |
| GET | `/fleet-board` | `VehicleController@fleetBoard` | Fleet availability board |
| — | **Drivers** | — | — |
| GET | `/drivers` | `DriverController@index` | Driver list |
| GET | `/drivers/create` | `DriverController@create` | Create driver form |
| POST | `/drivers` | `DriverController@store` | Store new driver |
| GET | `/drivers/{id}` | `DriverController@show` | Driver detail |
| GET | `/drivers/{id}/edit` | `DriverController@edit` | Edit driver |
| PUT | `/drivers/{id}` | `DriverController@update` | Update driver |
| DELETE | `/drivers/{id}` | `DriverController@destroy` | Soft-delete driver |
| — | **Inspections** | — | — |
| GET | `/inspections` | `InspectionController@index` | Inspection list with filters |
| GET | `/inspections/{id}` | `InspectionController@show` | Inspection detail + review |
| PUT | `/inspections/{id}/review` | `InspectionController@review` | Submit review |
| — | **Defect Reports** | — | — |
| GET | `/defect-reports` | `DefectReportController@index` | Defect report list |
| GET | `/defect-reports/{id}` | `DefectReportController@show` | Defect report detail |
| PUT | `/defect-reports/{id}/review` | `DefectReportController@review` | Acknowledge report |
| PUT | `/defect-reports/{id}/resolve` | `DefectReportController@resolve` | Resolve report |
| — | **Repairs** | — | — |
| GET | `/repairs` | `RepairController@index` | Repair list |
| GET | `/repairs/create` | `RepairController@create` | Log repair form |
| POST | `/repairs` | `RepairController@store` | Store repair |
| GET | `/repairs/{id}` | `RepairController@show` | Repair detail |
| GET | `/repairs/{id}/edit` | `RepairController@edit` | Edit repair |
| PUT | `/repairs/{id}` | `RepairController@update` | Update repair |
| PUT | `/repairs/{id}/complete` | `RepairController@complete` | Mark as completed |
| — | **Preventive Maintenance** | — | — |
| GET | `/preventive-maintenance` | `PMController@index` | PM schedule list + alerts |
| GET | `/preventive-maintenance/create` | `PMController@create` | Configure PM form |
| POST | `/preventive-maintenance` | `PMController@store` | Store PM schedule |
| GET | `/preventive-maintenance/{id}` | `PMController@show` | PM detail + log history |
| PUT | `/preventive-maintenance/{id}` | `PMController@update` | Update PM config |
| POST | `/preventive-maintenance/{id}/log` | `PMController@logCompletion` | Log PM completion |
| — | **Dispatches** | — | — |
| GET | `/dispatches` | `DispatchController@index` | Dispatch list |
| GET | `/dispatches/create` | `DispatchController@create` | Log dispatch form |
| POST | `/dispatches` | `DispatchController@store` | Store dispatch |
| GET | `/dispatches/{id}` | `DispatchController@show` | Dispatch detail |
| PUT | `/dispatches/{id}/close` | `DispatchController@close` | Close dispatch |
| — | **Reports** | — | — |
| GET | `/reports` | `ReportController@index` | Report type selection |
| GET | `/reports/{type}` | `ReportController@generate` | Generate + preview report |
| GET | `/reports/{type}/pdf` | `ReportController@downloadPdf` | Download PDF |
| — | **Notifications** | — | — |
| GET | `/notifications` | `NotificationController@index` | Notification center |

### 5.5 API Response Format (JSON — for Mobile)

```php
// Every JSON API response follows this consistent format.

// Success response:
{
    "success": true,
    "message": "Inspection submitted successfully.",
    "data": { /* the returned resource or collection */ },
    "pagination": {                    // Only for list endpoints
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 72
    }
}

// Error response:
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "plate_number": ["The plate number field is required."],
        "vehicle_type": ["The selected vehicle type is invalid."]
    }
}
```

### 5.6 Laravel Scheduled Tasks (replacing Cloud Function triggers)

```php
// In app/Console/Kernel.php — these replace what would have been 
// Cloud Functions scheduled triggers. Laravel's task scheduler runs 
// these automatically via a single cron entry on the server.

// Step 1: Add this single cron entry to the server:
//   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1

// Step 2: Define scheduled tasks:
Schedule::command('pm:check-status')->everyFourHours();
// Checks all active PM schedules and flags DUE_SOON or OVERDUE.
// DUE_SOON is triggered when the vehicle approaches the next service
// interval within the Agency Admin's configured threshold:
//   - Mileage-based PM: uses agencies.pm_alert_km (km before due mileage)
//   - Time-based PM:    uses agencies.pm_alert_days (days before due date)
// If the threshold is not configured (NULL), DUE_SOON is skipped for
// that interval type. The threshold is set by the Admin in agency settings.
// Sends notifications to admins for all flagged DUE_SOON and OVERDUE items.

Schedule::command('license:check-expiry')->dailyAt('08:00');
// Checks driver license expiry dates against each agency's configured
// alert threshold (stored in agencies.license_alert_days).
// Sends a notification to the Agency Admin when a driver's license
// is approaching expiry.
```

---

## 6. AUTHENTICATION RULES

### 6.1 Authentication Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    MOBILE APP (Driver)                          │
│                                                                 │
│  Login Screen                                                   │
│       │                                                         │
│       ▼                                                         │
│  POST /api/v1/auth/login  ──▶  Laravel validates credentials    │
│       │                             │                           │
│       │                    Creates Sanctum API token             │
│       │                             │                           │
│       ◀── Returns token ───────────┘                            │
│       │                                                         │
│  Token stored in app (encrypted DataStore)                      │
│  All subsequent API calls include: Authorization: Bearer <token>│
│       │                                                         │
│  POST /api/v1/auth/fcm-token  ──▶  Registers device for push   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    WEB DASHBOARD (Admin)                        │
│                                                                 │
│  Login Page (/login)                                            │
│       │                                                         │
│       ▼                                                         │
│  POST /login  ──▶  Laravel validates credentials                │
│       │                  │                                      │
│       │         Creates session + CSRF token                    │
│       │                  │                                      │
│       ◀── Redirect to /dashboard                                │
│                                                                 │
│  All subsequent requests use session cookie (standard Laravel)  │
└─────────────────────────────────────────────────────────────────┘
```

### 6.2 Role-Based Access

```php
// Laravel middleware checks the user's role on every request.
//
// How it works in plain language:
// - When a user logs in, the system knows their role (DRIVER or ADMIN)
//   and which agency they belong to.
// - Every page and API endpoint checks: "Is this user allowed to do this?"
// - Drivers can only access mobile API endpoints and only see their own data.
// - Admins can only access the web dashboard and only see their agency's data.
// - Nobody can see data from another agency.

// Middleware stack:
// 1. auth:sanctum — verifies the API token is valid (mobile)
//    auth:web — verifies the session is valid (web dashboard)
// 2. role:DRIVER or role:ADMIN — checks the user has the required role
// 3. Agency scope — automatically added to all database queries via a global scope
```

### 6.3 Laravel Sanctum Setup

```php
// config/sanctum.php — key settings:
// - Stateful domains: the web dashboard domain (for cookie/session auth)
// - Token expiration: API tokens expire after 30 days of inactivity
// - Token abilities: not used (role-based access is enforced via middleware)
```

### 6.4 Agency Scope (Data Isolation)

```php
// Every Eloquent model that belongs to an agency uses a "global scope"
// that automatically filters queries to only return data from the 
// currently logged-in user's agency. This means:
// - An admin from CDRRMO can never see BFP's vehicles, even by accident.
// - A driver from PNP can never see CHO's inspections.
//
// This is enforced at the database query level, not just the UI level.

// Example (simplified):
// When Admin from CDRRMO (agency_id = 1) runs Vehicle::all(),
// Laravel automatically adds: WHERE agency_id = 1
```

---

## 7. NOTIFICATION LOGIC

### 7.1 Notification Flow

```
  Event Occurs (e.g., driver submits inspection)
       │
       ▼
  Laravel Controller / Service handles the action
       │
       ▼
  Dispatches a Laravel Notification (via queue)
       │
       ├──▶ Database Channel: saves to `notifications` table (in-app)
       │
       └──▶ FCM Channel: sends push to device via FCM HTTP v1 API
                │
                ▼
          Driver's phone shows a push notification
```

### 7.2 Notification Matrix

| Event | Recipient | Type Constant | Title | Body Template |
|---|---|---|---|---|
| Inspection submitted | Agency Admin(s) | `INSPECTION_SUBMITTED` | New Inspection Submitted | `{driverName} submitted a BLOWBAGETS inspection for {plateNumber}` |
| Inspection reviewed | Submitting Driver | `INSPECTION_REVIEWED` | Inspection Reviewed | `Your inspection for {plateNumber} on {date} has been reviewed` |
| Defect report submitted | Agency Admin(s) | `DEFECT_REPORT_SUBMITTED` | New Defect Report | `{driverName} reported a defect on {plateNumber}` |
| Defect report acknowledged | Reporting Driver | `DEFECT_REPORT_ACKNOWLEDGED` | Defect Report Update | `Your defect report for {plateNumber} has been acknowledged` |
| Defect report resolved | Reporting Driver | `DEFECT_REPORT_RESOLVED` | Defect Resolved | `The defect you reported on {plateNumber} has been resolved` |
| Vehicle status changed | Assigned Driver | `VEHICLE_STATUS_CHANGED` | Vehicle Status Update | `{plateNumber} status changed to {newStatus}` |
| PM due soon (approaching agency-configured alert threshold) | Agency Admin(s) | `PM_DUE_SOON` | PM Due Soon | `{pmType} for {plateNumber} is due {dueInfo}` |
| PM overdue | Agency Admin(s) | `PM_OVERDUE` | PM Overdue | `{pmType} for {plateNumber} is overdue!` |
| PM completed | Assigned Driver | `PM_COMPLETED` | Maintenance Complete | `{pmType} has been completed on {plateNumber}` |
| Repair completed | Assigned Driver | `REPAIR_COMPLETED` | Repair Complete | `Repair on {plateNumber} has been completed` |
| Dispatch assigned | Assigned Driver | `DISPATCH_ASSIGNED` | Dispatch Assignment | `You have been dispatched: {missionType} at {location}` |
| Dispatch closed | Assigned Driver | `DISPATCH_CLOSED` | Dispatch Closed | `Your dispatch for {missionType} has been closed` |
| License expiring (configurable threshold) | Agency Admin(s) | `LICENSE_EXPIRING` | License Expiring | `{driverName}'s license expires on {expiryDate}` |

### 7.3 Implementation (Laravel)

```php
// How the notification system works in plain language:
//
// 1. Something happens in the system (e.g., a driver submits an inspection).
// 2. The Laravel controller calls a Notification class (e.g., InspectionSubmitted).
// 3. That notification class decides:
//    - WHO gets the notification (e.g., all admins in the driver's agency)
//    - WHERE to send it:
//      a) Database: saves a row in the `notifications` table for in-app display
//      b) FCM: sends a push notification to the admin's phone (if they have one)
// 4. The notification is dispatched to a background queue so it doesn't slow 
//    down the original request.

// Laravel Notification class example structure:
// app/Notifications/InspectionSubmittedNotification.php
//
// - via(): returns ['database', 'fcm'] — meaning save to DB AND send push
// - toDatabase(): returns the data to store in the notifications table
// - toFcm(): returns the FCM message payload (title, body, data)
```

### 7.4 FCM Service Class

```php
// app/Services/FcmService.php
//
// This service handles sending push notifications to Android devices.
//
// How it works step by step:
// 1. It loads the Firebase Service Account JSON from storage.
// 2. It generates a short-lived OAuth 2.0 access token using that key.
// 3. It builds the notification message (title, body, data payload).
// 4. It sends an HTTP POST request to:
//    https://fcm.googleapis.com/v1/projects/{project-id}/messages:send
// 5. If the token is invalid (device unregistered), it removes the token
//    from the fcm_tokens table.
```

### 7.5 Notification Channels (Android)

| Channel ID | Name | Importance | Description |
|---|---|---|---|
| `rvms_default` | RVMS Notifications | Default | General notifications |
| `rvms_dispatch` | Dispatch Alerts | High | Dispatch assignments |
| `rvms_urgent` | Urgent Alerts | High | Overdue PM, critical defects |

---

## 8. VEHICLE STATUS LOGIC

### 8.1 Status State Machine

```
                        ┌─────────────────────────────────────┐
                        │                                     │
                        ▼                                     │
              ┌──────────────────┐                            │
    ┌────────▶│   OPERATIONAL    │◀──────────┐                │
    │         └────────┬─────────┘           │                │
    │                  │                     │                │
    │    ┌─────────────┼──────────────┐      │                │
    │    │             │              │      │                │
    │    ▼             ▼              ▼      │                │
    │ ┌──────┐  ┌───────────┐  ┌──────────┐ │                │
    │ │DISPA-│  │ PARTIALLY │  │   NOT    │ │                │
    │ │TCHED │  │OPERATIONAL│  │OPERATION-│ │                │
    │ └──┬───┘  └───────────┘  │   AL     │ │                │
    │    │                     └────┬─────┘ │                │
    │    │                          │       │                │
    │    └───── Close ──────────────┘       │                │
    │          Dispatch                     │                │
    │                                       │                │
    │         ┌────────────────────┐         │                │
    │         │ UNDER PREVENTIVE  │         │                │
    └─────────│   MAINTENANCE     │─────────┘                │
              └────────────────────┘                          │
                        │                                     │
                        └─────────────────────────────────────┘
```

### 8.2 Transition Rules

| From | To | Triggered By | Automatic? |
|---|---|---|---|
| Operational | Dispatched | Admin logs a dispatch | **Yes** — auto-set when dispatch is created |
| Partially Operational | Dispatched | Admin logs a dispatch | **Yes** — auto-set when dispatch is created (Admin sees a warning) |
| Dispatched | Operational | Admin closes the dispatch (was Operational) | **Yes** — auto-set when dispatch is closed |
| Dispatched | Partially Operational | Admin closes the dispatch (was Partially Operational) | **Yes** — auto-set when dispatch is closed |
| Operational | Partially Operational | Admin reviews defect report or manual set | No — Admin decision |
| Operational | Not Operational | Admin decision (major defect, warranty, etc.) | No — Admin decision |
| Partially Operational | Operational | Admin decision (after minor repair) | No — Admin decision |
| Partially Operational | Not Operational | Admin decision (defect worsened) | No — Admin decision |
| Not Operational | Operational | Admin marks repair as completed | No — Admin decision |
| Operational | Under Preventive Maintenance | Admin begins PM service | No — Admin decision |
| Under Preventive Maintenance | Operational | Admin logs PM completion | No — Admin decision |
| Not Operational | Under Preventive Maintenance | Not allowed | — |
| Dispatched | Any (except via dispatch close) | Not allowed while dispatched | — |

### 8.3 Validation (Laravel)

```php
// app/Rules/VehicleStatusTransition.php
//
// This validation rule checks whether a status change is allowed.
// For example, a vehicle that is currently "Dispatched" cannot be
// changed to "Not Operational" directly — it must first be returned
// (dispatch closed) before any other status change.
//
// The allowed transitions map:

$validTransitions = [
    'OPERATIONAL' => [
        'DISPATCHED',
        'PARTIALLY_OPERATIONAL',
        'NOT_OPERATIONAL',
        'UNDER_PREVENTIVE_MAINTENANCE',
    ],
    'DISPATCHED' => [
        'OPERATIONAL',           // Via dispatch close (was Operational)
        'PARTIALLY_OPERATIONAL', // Via dispatch close (was Partially Operational)
    ],
    'PARTIALLY_OPERATIONAL' => [
        'OPERATIONAL',
        'NOT_OPERATIONAL',
        'DISPATCHED',    // Allowed — rescue agencies may dispatch imperfect vehicles; Admin sees a warning
    ],
    'NOT_OPERATIONAL' => [
        'OPERATIONAL',
    ],
    'UNDER_PREVENTIVE_MAINTENANCE' => [
        'OPERATIONAL',
    ],
];
```

---

## 9. WORKFLOW LOGIC

### 9.1 Daily BLOWBAGETS Inspection Workflow

```php
// Step-by-step in plain language:
//
// 1. Driver opens the mobile app and taps "New Inspection".
// 2. The app fetches the BLOWBAGETS items for the driver's agency
//    (14 items — the standardized BLOWBAGETS checklist).
// 3. Driver goes through each item (Battery, Lights, Oil, etc.) and marks
//    it as "OK" or "Has Issue". If "Has Issue", they type a remark.
// 4. Driver enters the current odometer reading and taps "Submit".
// 5. The app sends a POST request to the API with all the data.
// 6. Laravel creates the inspection record with status "Pending Review".
// 7. Laravel updates the vehicle's current_mileage_km if the new reading is higher.
// 8. Laravel sends a notification to the Agency Admin: "Driver X submitted
//    an inspection for vehicle ABC-1234."
// 9. Later, the Admin opens the web dashboard, reviews the inspection,
//    optionally adds review remarks, and marks it as "Reviewed".
// 10. Laravel sends a notification to the driver: "Your inspection has been reviewed."
// 11. Admin reviews the report, writes remarks, and decides the vehicle's
//    new status (Operational / Partially Operational / Not Operational).
```

### 9.2 Defect & Damage Reporting Workflow

```php
// Step-by-step in plain language:
//
// 1. Driver notices damage on the vehicle (e.g., broken taillight).
// 2. Driver opens the mobile app and taps "Report Defect".
// 3. The form auto-fills the vehicle details (type, brand, engine no., etc.).
// 4. Driver describes the damage in detail
//    and optionally attaches photos.
// 5. The app uploads photos to the server and submits the report.
// 6. Laravel creates the defect report with status "Pending Review".
// 7. Notification sent to the Admin.
// 8. Admin reviews the report, writes remarks, and decides the vehicle's
//    new status (Operational / Partially Operational / Not Operational).
// 9. Status "Acknowledged" is set. Notification sent to the driver.
// 10. When the issue is fixed, Admin marks it "Resolved". Notification sent.
```

### 9.3 Repair Logging Workflow

```php
// Step-by-step in plain language:
//
// 1. Admin opens the web dashboard and navigates to Repairs > Log Repair.
// 2. Admin selects the vehicle and describes what needs to be repaired.
// 3. Admin selects who will do the repair:
//    - Internal Station (their own mechanics)
//    - GSO Motorpool (city's central repair facility)
//    - External Shop (private shop — must type the name)
// 4. Admin enters repair start date and any parts to be replaced.
// 5. Laravel creates the repair record with status "In Progress".
// 6. The vehicle's status is set to "Not Operational" (if not already).
// 7. When the repair is done, Admin marks it "Completed" and enters the
//    end date. The vehicle status returns to "Operational".
// 8. Notification sent to the assigned driver: "Repair completed."
// 9. If the repair was linked to a defect report, that report is also
//    automatically marked as "Resolved".
```

### 9.4 Preventive Maintenance Workflow

```php
// Step-by-step in plain language:
//
// 1. Admin opens Preventive Maintenance > Configure Schedule.
// 2. Admin creates a PM schedule for a vehicle, for example:
//    "Oil Change every 5,000 km" (mileage-based) or
//    "Tire Replacement every 6 months" (time-based).
// 3. Laravel calculates when the next service is due based on the interval.
// 4. Every 4 hours, Laravel's scheduler runs the pm:check-status command.
//    This command checks every active PM schedule:
//    - For mileage-based: compares the vehicle's current mileage to the
//      next due mileage. If within the agency's configured pm_alert_km
//      → "Due Soon". If past the due mileage → "Overdue".
//    - For time-based: compares today's date to the next due date.
//      If within the agency's configured pm_alert_days → "Due Soon".
//      If past the due date → "Overdue".
//    Note: Both thresholds are set by the Agency Admin in agency settings.
//    If a threshold is not configured (NULL), the DUE_SOON check is skipped
//    for that interval type. The system will still flag OVERDUE regardless.
// 5. Notifications are sent to the Admin for due-soon and overdue items.
// 6. When the maintenance is done, Admin logs the completion with details
//    (what was done, who did it, parts replaced, mileage at service).
// 7. Laravel recalculates the next due date/mileage and resets to "On Schedule".
// 8. Vehicle status returns to "Operational". Driver is notified.
```

### 9.5 Dispatch Logging & Fleet Monitoring Workflow

```php
// Step-by-step in plain language:
//
// 1. Admin opens Dispatches > Log Dispatch.
// 2. Admin selects a vehicle (must be "Operational" or "Partially Operational").
//    NOTE: Dispatching a "Partially Operational" vehicle is allowed because
//    rescue agencies may need to deploy imperfect vehicles in emergencies.
//    The system displays a warning to the Admin when this occurs.
//    assigns a driver, and fills in mission details:
//    - Mission type: Fire Response / Medical Response / Rescue / Patrol / Other
//    - Location (destination)
//    - Date and time dispatched
// 3. Laravel creates the dispatch record with status "Active".
// 4. The vehicle's status is automatically set to "Dispatched".
// 5. The assigned driver receives a push notification: "You have been dispatched."
// 6. When the vehicle returns, Admin closes the dispatch:
//    - Enters return date/time and optional remarks.
// 7. Laravel sets the dispatch status to "Closed".
// 8. The vehicle's status automatically returns to "Operational".
// 9. The driver is notified: "Your dispatch has been closed."
//
// The Fleet Board page shows all vehicles and their current statuses in
// a card-based layout, allowing the Admin to see at a glance which vehicles
// are available, which are out on missions, and which are grounded.
```

### 9.6 Report Generation Workflow

```php
// Step-by-step in plain language:
//
// 1. Admin opens Reports and selects the type of report to generate.
// 2. Admin sets filters: date range, specific vehicle, etc.
// 3. Laravel queries the database and returns the filtered data.
// 4. The web dashboard displays the report in a formatted HTML table.
// 5. Admin can click "Download PDF" to generate a printable PDF file
//    using Laravel DomPDF.
//
// Available report types:
// - Vehicle Inspection Report (BLOWBAGETS results)
// - Defect and Damage Report
// - Maintenance History Report (repairs + PM combined)
// - Repair Log Records
// - Preventive Maintenance Records
// - Dispatch Log
// - Vehicle Operational Status Report
```

---

## 10. MOBILE APP STRUCTURE

### 10.1 Screen Map (Jetpack Compose)

```
Android Mobile App (Authorized Driver)
├── Auth
│   └── LoginScreen
│
├── Main (Bottom Navigation)
│   ├── Home Tab
│   │   └── HomeScreen (dashboard: vehicle status, quick actions, recent activity)
│   │
│   ├── Inspection Tab
│   │   ├── InspectionListScreen (history of submitted inspections)
│   │   ├── NewInspectionScreen (BLOWBAGETS checklist form)
│   │   └── InspectionDetailScreen (view single inspection + review status)
│   │
│   ├── Report Tab
│   │   ├── DefectReportListScreen (history of submitted reports)
│   │   ├── NewDefectReportScreen (defect form with camera/gallery)
│   │   └── DefectReportDetailScreen (view report + status + admin remarks)
│   │
│   └── Notifications Tab
│       └── NotificationsScreen (list of all notifications)
│
├── Detail Screens (navigated from any tab)
│   ├── VehicleDetailScreen (view assigned vehicle info + status)
│   └── MaintenanceHistoryScreen (view repairs + PM for assigned vehicle)
│
└── Profile
    └── ProfileScreen (view profile, change password, logout)
```

### 10.2 Navigation (Compose Navigation)

```kotlin
// The app uses Jetpack Compose Navigation with a NavHost.
//
// In plain language:
// - When the user opens the app, it checks if they are logged in.
//   - If NOT logged in → show the Login screen.
//   - If logged in → show the Main screen with bottom tabs.
// - The bottom navigation bar has 4 tabs: Home, Inspection, Report, Notifications.
// - Tapping a tab switches the content area to that tab's screen.
// - From any screen, the user can navigate deeper (e.g., tap an inspection
//   to see its details) using a stack-based navigation pattern.

// Navigation graph structure:
// NavHost
//   ├── authGraph (login)
//   └── mainGraph
//       ├── homeGraph (home → vehicle detail → maintenance history)
//       ├── inspectionGraph (list → new → detail)
//       ├── defectReportGraph (list → new → detail)
//       └── notificationGraph (list)
```

### 10.3 MVVM Architecture

```kotlin
// The mobile app follows the MVVM (Model-View-ViewModel) architecture:
//
// In plain language:
// - Model: The data classes and the API service (Retrofit).
//   These handle getting and sending data to the server.
//
// - ViewModel: The "brain" of each screen. It holds the screen's data
//   (like a list of inspections) and handles user actions (like submitting
//   a form). It survives screen rotations.
//
// - View (Composable): The UI that the user sees and interacts with.
//   It reads data from the ViewModel and calls ViewModel functions
//   when the user taps buttons.
//
// Data flow:
// User taps "Submit" → Composable calls viewModel.submitInspection()
//   → ViewModel calls repository.submitInspection()
//     → Repository calls apiService.postInspection()
//       → Retrofit sends HTTP request to Laravel API
//         → Laravel processes and returns response
//       → Repository returns result to ViewModel
//     → ViewModel updates the UI state
//   → Composable automatically re-renders with updated data
```

---

## 11. WEB ADMIN STRUCTURE

### 11.1 Page Map (Laravel Blade + Bootstrap)

```
Web Admin Dashboard (Agency Administrator)
├── Auth
│   ├── /login (login form)
│   └── /logout
│
├── Main Layout (sidebar + header + content area)
│   ├── /dashboard (overview: fleet status cards, PM alerts, recent activity)
│   │
│   ├── Vehicles
│   │   ├── /vehicles (DataTable with search, filter by type/status)
│   │   ├── /vehicles/create (Bootstrap form)
│   │   ├── /vehicles/{id} (detail page with status badge, history tabs)
│   │   └── /vehicles/{id}/edit (edit form)
│   │
│   ├── Drivers
│   │   ├── /drivers (DataTable)
│   │   ├── /drivers/create (form + assign vehicles)
│   │   ├── /drivers/{id} (detail + assigned vehicles)
│   │   └── /drivers/{id}/edit
│   │
│   ├── Inspections
│   │   ├── /inspections (DataTable with date/vehicle/status filters)
│   │   └── /inspections/{id} (detail + review form)
│   │
│   ├── Defect Reports
│   │   ├── /defect-reports (DataTable)
│   │   └── /defect-reports/{id} (detail + photos + review/resolve actions)
│   │
│   ├── Repairs
│   │   ├── /repairs (DataTable)
│   │   ├── /repairs/create (log repair form)
│   │   ├── /repairs/{id} (detail)
│   │   └── /repairs/{id}/edit
│   │
│   ├── Preventive Maintenance
│   │   ├── /preventive-maintenance (DataTable + alert badges)
│   │   ├── /preventive-maintenance/create (configure schedule form)
│   │   ├── /preventive-maintenance/{id} (detail + log history)
│   │   └── /preventive-maintenance/{id}/log (log completion form)
│   │
│   ├── Dispatches
│   │   ├── /dispatches (DataTable with active/closed filter)
│   │   ├── /dispatches/create (dispatch form)
│   │   ├── /dispatches/{id} (detail + close action)
│   │   └── /fleet-board (card-based vehicle status board)
│   │
│   ├── Reports
│   │   ├── /reports (report type selection cards)
│   │   └── /reports/{type} (filtered report + PDF download)
│   │
│   └── Notifications
│       └── /notifications (notification list + mark as read)
```

### 11.2 Dashboard Overview Components

| Component | Content |
|---|---|
| **Fleet Status Cards** | Count of vehicles by status (Operational, Dispatched, etc.) with color-coded Bootstrap cards |
| **PM Alerts Panel** | List of DUE_SOON and OVERDUE preventive maintenance items with Bootstrap alert badges |
| **Recent Inspections** | Latest 5 submitted inspections with status badges |
| **Pending Defect Reports** | Unacknowledged defect reports requiring action |
| **Active Dispatches** | Currently active dispatch missions |
| **Quick Actions** | Bootstrap buttons: Log Dispatch, Log Repair, Create Vehicle, Add Driver |
| **Recent Activity Feed** | Chronological list from activity_logs table |

### 11.3 Blade Layout Structure

```
resources/views/
├── layouts/
│   ├── app.blade.php              -- Main layout (sidebar, header, content yield)
│   └── auth.blade.php             -- Auth layout (centered card, no sidebar)
│
├── components/                     -- Blade components (reusable UI pieces)
│   ├── status-badge.blade.php
│   ├── stat-card.blade.php
│   ├── confirm-modal.blade.php
│   ├── filter-bar.blade.php
│   ├── page-header.blade.php
│   └── empty-state.blade.php
│
├── auth/
│   └── login.blade.php
│
├── dashboard/
│   └── index.blade.php
│
├── vehicles/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   ├── edit.blade.php
│   └── fleet-board.blade.php
│
├── ... (same pattern for drivers, inspections, defect-reports,
│        repairs, preventive-maintenance, dispatches, reports,
│        notifications)
│
└── partials/
    ├── sidebar.blade.php
    ├── header.blade.php
    └── pagination.blade.php
```

---

## 12. FOLDER STRUCTURE

### 12.1 Overview — Two Separate Projects

```
rvms/
├── rvms-mobile/                   # Android Studio project (Kotlin + Compose)
└── rvms-web/                      # Laravel project (Backend + Web Dashboard)
```

### 12.2 Mobile App (`rvms-mobile/`)

```
rvms-mobile/
├── app/
│   ├── build.gradle.kts
│   ├── google-services.json        # Firebase config for FCM
│   └── src/
│       └── main/
│           ├── AndroidManifest.xml
│           ├── java/com/rvms/driver/
│           │   │
│           │   ├── RvmsApplication.kt          # Application class (Hilt entry point)
│           │   │
│           │   ├── di/                          # Dependency Injection modules
│           │   │   ├── AppModule.kt             # Provides Retrofit, SharedPrefs, etc.
│           │   │   └── RepositoryModule.kt      # Binds repositories
│           │   │
│           │   ├── data/
│           │   │   ├── remote/
│           │   │   │   ├── api/                 # Retrofit API interfaces
│           │   │   │   │   ├── AuthApiService.kt
│           │   │   │   │   ├── InspectionApiService.kt
│           │   │   │   │   ├── DefectReportApiService.kt
│           │   │   │   │   ├── VehicleApiService.kt
│           │   │   │   │   └── NotificationApiService.kt
│           │   │   │   ├── dto/                 # Data Transfer Objects (request/response)
│           │   │   │   │   ├── LoginRequest.kt
│           │   │   │   │   ├── LoginResponse.kt
│           │   │   │   │   ├── InspectionRequest.kt
│           │   │   │   │   ├── DefectReportRequest.kt
│           │   │   │   │   └── ApiResponse.kt
│           │   │   │   └── interceptor/
│           │   │   │       └── AuthInterceptor.kt  # Adds Bearer token to requests
│           │   │   │
│           │   │   ├── repository/              # Repository implementations
│           │   │   │   ├── AuthRepository.kt
│           │   │   │   ├── InspectionRepository.kt
│           │   │   │   ├── DefectReportRepository.kt
│           │   │   │   ├── VehicleRepository.kt
│           │   │   │   └── NotificationRepository.kt
│           │   │   │
│           │   │   └── local/
│           │   │       └── TokenManager.kt      # Encrypted DataStore for auth token
│           │   │
│           │   ├── domain/
│           │   │   └── model/                   # Domain models (Kotlin data classes)
│           │   │       ├── User.kt
│           │   │       ├── Vehicle.kt
│           │   │       ├── Inspection.kt
│           │   │       ├── InspectionItem.kt
│           │   │       ├── DefectReport.kt
│           │   │       ├── Notification.kt
│           │   │       └── Enums.kt             # VehicleStatus, VehicleType, etc.
│           │   │
│           │   ├── ui/
│           │   │   ├── navigation/
│           │   │   │   ├── RvmsNavHost.kt       # Main navigation graph
│           │   │   │   ├── Screen.kt            # Route sealed class
│           │   │   │   └── BottomNavBar.kt
│           │   │   │
│           │   │   ├── theme/
│           │   │   │   ├── Color.kt
│           │   │   │   ├── Type.kt
│           │   │   │   └── Theme.kt             # Material 3 theme
│           │   │   │
│           │   │   ├── components/              # Reusable Compose composables
│           │   │   │   ├── LoadingIndicator.kt
│           │   │   │   ├── ErrorMessage.kt
│           │   │   │   ├── EmptyState.kt
│           │   │   │   ├── ConfirmDialog.kt
│           │   │   │   ├── StatusBadge.kt
│           │   │   │   ├── PhotoPicker.kt
│           │   │   │   └── PullToRefreshWrapper.kt
│           │   │   │
│           │   │   ├── screens/
│           │   │   │   ├── auth/
│           │   │   │   │   ├── LoginScreen.kt
│           │   │   │   │   └── LoginViewModel.kt
│           │   │   │   ├── home/
│           │   │   │   │   ├── HomeScreen.kt
│           │   │   │   │   └── HomeViewModel.kt
│           │   │   │   ├── inspection/
│           │   │   │   │   ├── InspectionListScreen.kt
│           │   │   │   │   ├── InspectionListViewModel.kt
│           │   │   │   │   ├── NewInspectionScreen.kt
│           │   │   │   │   ├── NewInspectionViewModel.kt
│           │   │   │   │   ├── InspectionDetailScreen.kt
│           │   │   │   │   └── InspectionDetailViewModel.kt
│           │   │   │   ├── defectReport/
│           │   │   │   │   ├── DefectReportListScreen.kt
│           │   │   │   │   ├── DefectReportListViewModel.kt
│           │   │   │   │   ├── NewDefectReportScreen.kt
│           │   │   │   │   ├── NewDefectReportViewModel.kt
│           │   │   │   │   ├── DefectReportDetailScreen.kt
│           │   │   │   │   └── DefectReportDetailViewModel.kt
│           │   │   │   ├── vehicle/
│           │   │   │   │   ├── VehicleDetailScreen.kt
│           │   │   │   │   └── VehicleDetailViewModel.kt
│           │   │   │   ├── notification/
│           │   │   │   │   ├── NotificationsScreen.kt
│           │   │   │   │   └── NotificationsViewModel.kt
│           │   │   │   └── profile/
│           │   │   │       ├── ProfileScreen.kt
│           │   │   │       └── ProfileViewModel.kt
│           │   │   │
│           │   │   └── util/
│           │   │       ├── DateFormatter.kt
│           │   │       └── StatusColors.kt
│           │   │
│           │   └── fcm/
│           │       └── RvmsFirebaseMessagingService.kt  # Handles incoming push notifications
│           │
│           └── res/
│               ├── values/
│               │   ├── strings.xml
│               │   ├── colors.xml
│               │   └── themes.xml
│               ├── drawable/
│               └── mipmap/
│
├── build.gradle.kts                # Project-level Gradle
├── settings.gradle.kts
└── gradle.properties
```

### 12.3 Laravel Project (`rvms-web/`)

```
rvms-web/
├── app/
│   ├── Console/
│   │   ├── Kernel.php                  # Task scheduler registration
│   │   └── Commands/
│   │       ├── CheckPMStatus.php       # Artisan command: pm:check-status
│   │       └── CheckLicenseExpiry.php  # Artisan command: license:check-expiry
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                    # API controllers (JSON responses for mobile)
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── InspectionController.php
│   │   │   │   ├── DefectReportController.php
│   │   │   │   ├── VehicleController.php
│   │   │   │   └── NotificationController.php
│   │   │   │
│   │   │   └── Web/                    # Web controllers (Blade views for dashboard)
│   │   │       ├── AuthController.php
│   │   │       ├── DashboardController.php
│   │   │       ├── VehicleController.php
│   │   │       ├── DriverController.php
│   │   │       ├── InspectionController.php
│   │   │       ├── DefectReportController.php
│   │   │       ├── RepairController.php
│   │   │       ├── PMController.php
│   │   │       ├── DispatchController.php
│   │   │       ├── ReportController.php
│   │   │       └── NotificationController.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── EnsureRole.php          # Checks user role (DRIVER / ADMIN)
│   │   │   └── ScopeToAgency.php       # Auto-filters queries by agency_id
│   │   │
│   │   ├── Requests/                   # Form Request validation classes
│   │   │   ├── StoreInspectionRequest.php
│   │   │   ├── StoreDefectReportRequest.php
│   │   │   ├── StoreVehicleRequest.php
│   │   │   ├── StoreDispatchRequest.php
│   │   │   └── ...
│   │   │
│   │   └── Resources/                  # API Resource transformers (JSON output)
│   │       ├── VehicleResource.php
│   │       ├── InspectionResource.php
│   │       ├── DefectReportResource.php
│   │       ├── NotificationResource.php
│   │       └── ...
│   │
│   ├── Models/
│   │   ├── Agency.php
│   │   ├── AgencyBlowbagetsItem.php
│   │   ├── User.php
│   │   ├── FcmToken.php
│   │   ├── Vehicle.php
│   │   ├── VehicleDriverAssignment.php
│   │   ├── Inspection.php
│   │   ├── InspectionItem.php
│   │   ├── DefectReport.php
│   │   ├── DefectReportPhoto.php
│   │   ├── Repair.php
│   │   ├── RepairPart.php
│   │   ├── PreventiveMaintenance.php
│   │   ├── PmLog.php
│   │   ├── PmLogPart.php
│   │   ├── Dispatch.php
│   │   ├── Notification.php
│   │   └── ActivityLog.php
│   │
│   ├── Notifications/                  # Laravel Notification classes
│   │   ├── InspectionSubmittedNotification.php
│   │   ├── DefectReportSubmittedNotification.php
│   │   ├── VehicleStatusChangedNotification.php
│   │   ├── DispatchAssignedNotification.php
│   │   ├── PMDueSoonNotification.php
│   │   └── ...
│   │
│   ├── Observers/                      # Model observers for audit logging
│   │   ├── VehicleObserver.php
│   │   ├── InspectionObserver.php
│   │   └── ...
│   │
│   ├── Scopes/
│   │   └── AgencyScope.php             # Global scope: filters by agency_id
│   │
│   ├── Services/
│   │   ├── FcmService.php              # Firebase Cloud Messaging HTTP v1 API
│   │   ├── VehicleStatusService.php    # Status transition validation
│   │   ├── PMCheckerService.php        # PM due/overdue calculation
│   │   ├── ReportService.php           # Report data aggregation
│   │   └── ActivityLogService.php      # Audit trail logging
│   │
│   └── Enums/                          # PHP 8.1 backed enums
│       ├── VehicleStatus.php
│       ├── VehicleType.php
│       ├── MissionType.php
│       ├── UserRole.php
│       ├── PMType.php
│       ├── RepairType.php
│       └── NotificationType.php
│
├── config/
│   └── rvms.php                        # App-specific config (FCM project ID, etc.)
│
├── database/
│   ├── migrations/                     # All table migrations (in order)
│   │   ├── 0001_create_agencies_table.php
│   │   ├── 0002_create_agency_blowbagets_items_table.php
│   │   ├── 0003_create_users_table.php
│   │   ├── 0004_create_fcm_tokens_table.php
│   │   ├── 0005_create_vehicles_table.php
│   │   ├── 0006_create_vehicle_driver_assignments_table.php
│   │   ├── 0007_create_inspections_table.php
│   │   ├── 0008_create_inspection_items_table.php
│   │   ├── 0009_create_defect_reports_table.php
│   │   ├── 0010_create_defect_report_photos_table.php
│   │   ├── 0011_create_repairs_table.php
│   │   ├── 0012_create_repair_parts_table.php
│   │   ├── 0013_create_preventive_maintenances_table.php
│   │   ├── 0014_create_pm_logs_table.php
│   │   ├── 0015_create_pm_log_parts_table.php
│   │   ├── 0016_create_dispatches_table.php
│   │   ├── 0017_create_notifications_table.php
│   │   └── 0018_create_activity_logs_table.php
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── AgencySeeder.php            # Seeds the 4 agencies + BLOWBAGETS items
│       └── TestDataSeeder.php          # Seeds sample vehicles, users for dev/testing
│
├── resources/
│   ├── views/                          # Blade templates (see Section 11.3)
│   ├── css/
│   │   └── app.css                     # Custom CSS (on top of Bootstrap)
│   └── js/
│       └── app.js                      # Custom JS (DataTables init, Chart.js, etc.)
│
├── routes/
│   ├── api.php                         # Mobile API routes
│   └── web.php                         # Web dashboard routes
│
├── storage/
│   └── app/
│       ├── public/
│       │   ├── defect-photos/          # Uploaded defect report photos
│       │   │   └── {agency_id}/{report_id}/
│       │   └── profile-photos/         # Uploaded profile photos
│       │       └── {user_id}/
│       └── firebase/
│           └── service-account.json    # FCM service account key (NEVER commit)
│
├── .env                                # Environment config (DB, FCM, etc.)
├── .env.example                        # Template for .env
├── composer.json
├── artisan
└── README.md
```

---

## 13. NAMING CONVENTIONS

### 13.1 Kotlin (Mobile App)

| Type | Convention | Example |
|---|---|---|
| **Class** | PascalCase | `LoginViewModel`, `InspectionRepository` |
| **Function** | camelCase | `submitInspection()`, `getVehicleById()` |
| **Variable** | camelCase | `currentMileageKm`, `isLoading` |
| **Constant** | SCREAMING_SNAKE_CASE | `MAX_PHOTO_SIZE`, `BASE_URL` |
| **Composable function** | PascalCase | `StatusBadge()`, `InspectionListScreen()` |
| **Package** | lowercase, dot-separated | `com.rvms.driver.ui.screens.inspection` |
| **File** | PascalCase (matches class name) | `LoginScreen.kt`, `AuthRepository.kt` |
| **Enum** | PascalCase class, SCREAMING_SNAKE entries | `VehicleStatus.OPERATIONAL` |
| **Boolean** | Prefixed `is`, `has`, `can` | `isActive`, `hasIssue` |
| **DTO** | Suffixed `Request` / `Response` | `LoginRequest`, `ApiResponse` |

### 13.2 PHP / Laravel (Backend + Web)

| Type | Convention | Example |
|---|---|---|
| **Class** | PascalCase | `VehicleController`, `FcmService` |
| **Method** | camelCase | `storeInspection()`, `getFleetBoard()` |
| **Variable** | camelCase (or snake_case in Blade) | `$vehicleId`, `$currentUser` |
| **Constant** | SCREAMING_SNAKE_CASE | `MAX_PHOTO_SIZE`, `PM_CHECK_INTERVAL` |
| **Model** | Singular PascalCase | `Vehicle`, `DefectReport`, `PmLog` |
| **Migration** | `create_{table}_table` | `create_vehicles_table` |
| **Controller** | Resource suffix | `VehicleController`, `PMController` |
| **Request class** | `Store` / `Update` prefix | `StoreVehicleRequest`, `UpdateDriverRequest` |
| **Notification** | Event + `Notification` suffix | `InspectionSubmittedNotification` |
| **Artisan command** | kebab-case with colon | `pm:check-status`, `license:check-expiry` |
| **Route name** | dot-notation | `vehicles.index`, `inspections.store` |
| **Blade view** | kebab-case folders, snake files | `vehicles/index.blade.php`, `fleet-board.blade.php` |
| **Config key** | snake_case | `rvms.fcm_project_id` |

### 13.3 MySQL (Database)

| Type | Convention | Example |
|---|---|---|
| **Table** | snake_case, plural | `vehicles`, `defect_reports`, `pm_logs` |
| **Column** | snake_case | `plate_number`, `agency_id`, `current_mileage_km` |
| **Primary key** | `id` | `id` |
| **Foreign key** | `{singular_table}_id` | `vehicle_id`, `driver_id`, `agency_id` |
| **Boolean column** | Prefixed `is_` or `has_` | `is_active`, `is_read`, `is_under_warranty` |
| **Timestamp columns** | `*_at` suffix | `created_at`, `reviewed_at`, `dispatched_at` |
| **Date columns** | `*_date` suffix or descriptive | `inspection_date`, `repair_start_date` |
| **Enum** | SCREAMING_SNAKE_CASE values | `'OPERATIONAL'`, `'FIRE_TRUCK'`, `'MAJOR'` |
| **Index** | `idx_{table}_{columns}` | `idx_vehicles_agency_status` |

### 13.4 Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>
```

| Type | Use |
|---|---|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Code style (formatting, no logic change) |
| `refactor` | Code restructuring (no feature/fix) |
| `test` | Adding or updating tests |
| `chore` | Build config, dependencies, tooling |

**Scopes:** `mobile`, `web`, `api`, `db`, `fcm`, `docs`

**Examples:**
```
feat(mobile): add BLOWBAGETS inspection form with Compose
fix(api): correct PM due date calculation for mileage-based schedules
feat(web): implement fleet board page with Bootstrap cards
chore(db): add index on dispatches agency_id and status
docs: update vehicle status transition rules
```

---

## 14. REUSABLE COMPONENTS

### 14.1 Mobile App — Compose Composables

| Composable | Parameters | Usage |
|---|---|---|
| `LoadingIndicator` | `message: String?` | Full-screen or inline loading spinner (CircularProgressIndicator) |
| `ErrorMessage` | `error: String, onRetry: (() -> Unit)?` | Error display with optional retry button |
| `EmptyState` | `icon: ImageVector, title: String, description: String, action: (() -> Unit)?` | Empty list placeholder |
| `ConfirmDialog` | `title, message, onConfirm, onDismiss` | AlertDialog for destructive/important actions |
| `StatusBadge` | `status: VehicleStatus` | Color-coded chip showing vehicle status |
| `PhotoPicker` | `onPhotosSelected: (List<Uri>) -> Unit` | Camera/gallery picker with preview |
| `PullToRefreshWrapper` | `isRefreshing: Boolean, onRefresh: () -> Unit, content: @Composable () -> Unit` | Swipe-to-refresh wrapper |
| `FormField` | `label: String, error: String?, content: @Composable () -> Unit` | Consistent form field with label and error |
| `BlowbagetsItemRow` | `itemName, condition, remarks, onConditionChange, onRemarksChange` | Single inspection item row (toggle + text) |
| `InspectionCard` | `inspection: Inspection, onClick: () -> Unit` | Card for inspection list |
| `DefectReportCard` | `report: DefectReport, onClick: () -> Unit` | Card for defect report list |
| `VehicleInfoCard` | `vehicle: Vehicle` | Card showing vehicle details |
| `NotificationItem` | `notification: Notification, onClick: () -> Unit` | Row in notification list |

### 14.2 Web Dashboard — Blade Components

| Component | Parameters | Usage |
|---|---|---|
| `<x-status-badge>` | `status` | Color-coded Bootstrap badge for vehicle status |
| `<x-stat-card>` | `title, value, icon, color` | Dashboard statistic card (Bootstrap card) |
| `<x-confirm-modal>` | `id, title, message, action, method` | Bootstrap modal with form submission |
| `<x-filter-bar>` | `filters (array)` | Row of filter dropdowns and date pickers |
| `<x-page-header>` | `title, breadcrumbs, actions` | Page title with breadcrumbs and action buttons |
| `<x-empty-state>` | `icon, title, description, actionUrl, actionLabel` | Empty state illustration |
| `<x-photo-viewer>` | `photos (array)` | Bootstrap carousel for defect report photos |
| `<x-vehicle-form>` | `vehicle? (for edit)` | Reusable vehicle create/edit form |
| `<x-repair-form>` | `repair?, vehicles` | Reusable repair log form |
| `<x-dispatch-form>` | `vehicles, drivers` | Reusable dispatch log form |
| `<x-pm-status-indicator>` | `pmStatus` | Color-coded PM status label with icon |

### 14.3 Status Color Mapping

```php
// Used in both mobile (Compose Color) and web (Bootstrap classes / CSS)

// OPERATIONAL       → Green   (#2E7D32 / bg-success)
// DISPATCHED        → Blue    (#1565C0 / bg-primary)
// PARTIALLY_OPERATIONAL → Amber (#F57F17 / bg-warning)
// NOT_OPERATIONAL   → Red     (#C62828 / bg-danger)
// UNDER_PREVENTIVE_MAINTENANCE → Purple (#6A1B9A / custom bg-purple)
```

---

## 15. CODING STANDARDS

### 15.1 Kotlin Standards (Mobile App)

```kotlin
// ✅ DO: Use data classes for models
data class Vehicle(
    val id: Long,
    val plateNumber: String,
    val status: VehicleStatus,
    // ...
)

// ✅ DO: Use sealed classes for UI state
sealed class InspectionUiState {
    object Loading : InspectionUiState()
    data class Success(val inspections: List<Inspection>) : InspectionUiState()
    data class Error(val message: String) : InspectionUiState()
}

// ✅ DO: Use StateFlow in ViewModels for Compose
class InspectionListViewModel @Inject constructor(
    private val repository: InspectionRepository
) : ViewModel() {
    private val _uiState = MutableStateFlow<InspectionUiState>(InspectionUiState.Loading)
    val uiState: StateFlow<InspectionUiState> = _uiState.asStateFlow()
}

// ✅ DO: Keep Composables focused and small
// ✅ DO: Use Hilt @Inject for dependencies
// ✅ DO: Use suspend functions for API calls
// ✅ DO: Handle loading, success, and error states in every screen

// ❌ DON'T: Use var when val works
// ❌ DON'T: Put business logic inside Composables
// ❌ DON'T: Make API calls directly from Composables (use ViewModel)
// ❌ DON'T: Ignore error handling
```

### 15.2 PHP / Laravel Standards (Backend + Web)

```php
// ✅ DO: Follow PSR-12 coding style (enforced by Laravel Pint)
// ✅ DO: Use Eloquent relationships for related data
// ✅ DO: Use Form Requests for validation
// ✅ DO: Use API Resources for consistent JSON output
// ✅ DO: Use PHP 8.1 backed enums for fixed-value fields
// ✅ DO: Use Laravel's built-in features (queues, events, notifications)

// Example: PHP 8.1 Enum
enum VehicleStatus: string
{
    case OPERATIONAL = 'OPERATIONAL';
    case DISPATCHED = 'DISPATCHED';
    case PARTIALLY_OPERATIONAL = 'PARTIALLY_OPERATIONAL';
    case NOT_OPERATIONAL = 'NOT_OPERATIONAL';
    case UNDER_PREVENTIVE_MAINTENANCE = 'UNDER_PREVENTIVE_MAINTENANCE';

    // Returns the human-readable label for display.
    public function label(): string
    {
        return match ($this) {
            self::OPERATIONAL => 'Operational',
            self::DISPATCHED => 'Dispatched',
            self::PARTIALLY_OPERATIONAL => 'Partially Operational',
            self::NOT_OPERATIONAL => 'Not Operational',
            self::UNDER_PREVENTIVE_MAINTENANCE => 'Under Preventive Maintenance',
        };
    }

    // Returns the Bootstrap CSS class for the status badge color.
    public function badgeClass(): string
    {
        return match ($this) {
            self::OPERATIONAL => 'bg-success',
            self::DISPATCHED => 'bg-primary',
            self::PARTIALLY_OPERATIONAL => 'bg-warning text-dark',
            self::NOT_OPERATIONAL => 'bg-danger',
            self::UNDER_PREVENTIVE_MAINTENANCE => 'bg-purple',
        };
    }
}

// Example: Controller (clean, thin)
class VehicleController extends Controller
{
    // Shows the list of all vehicles for the admin's agency.
    public function index(Request $request)
    {
        $vehicles = Vehicle::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->type, fn ($q, $type) => $q->where('vehicle_type', $type))
            ->orderBy('plate_number')
            ->paginate(15);

        return view('vehicles.index', compact('vehicles'));
    }
}

// Example: Form Request (validation)
class StoreVehicleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            // ...
        ];
    }
}

// ❌ DON'T: Put business logic in controllers (use Services)
// ❌ DON'T: Write raw SQL when Eloquent can handle it
// ❌ DON'T: Skip validation
// ❌ DON'T: Return inconsistent API response formats
// ❌ DON'T: Hardcode agency_id in queries (use AgencyScope)
```

### 15.3 Blade + Bootstrap Standards (Web Views)

```php
// ✅ DO: Extend the main layout
@extends('layouts.app')

// ✅ DO: Use Blade components for reusable UI
<x-status-badge :status="$vehicle->status" />

// ✅ DO: Use Bootstrap utility classes for spacing and layout
// ✅ DO: Use @csrf in all forms
// ✅ DO: Use @method('PUT') for update forms
// ✅ DO: Use DataTables for searchable/sortable tables
// ✅ DO: Include ARIA attributes for accessibility

// ❌ DON'T: Write inline CSS when Bootstrap classes exist
// ❌ DON'T: Use raw PHP in Blade (use helpers / directives)
// ❌ DON'T: Skip error message display (use @error directive)
```

### 15.4 Code Formatting Tools

| Tool | Platform | Config |
|---|---|---|
| **Laravel Pint** | PHP/Laravel | PSR-12 preset (default) — `./vendor/bin/pint` |
| **ktlint** | Kotlin/Android | Official Kotlin style — `./gradlew ktlintCheck` |
| **EditorConfig** | Both | `.editorconfig` at project root for consistent indent/encoding |

---

## 16. STATE MANAGEMENT

### 16.1 Mobile App (Kotlin)

```kotlin
// The mobile app uses the MVVM pattern with:
// - ViewModel: holds UI state, survives screen rotation
// - StateFlow: observable data stream that Compose collects
// - Repository: abstracts data source (API calls)
//
// In plain language:
// Each screen has a ViewModel that acts as its "brain". The ViewModel
// fetches data from the server (via Repository → Retrofit), stores it
// in a StateFlow, and the Compose screen automatically updates whenever
// the data changes.

// State management strategy:
// | Data                  | Where it lives          | Why                          |
// |-----------------------|------------------------|------------------------------|
// | Auth token            | Encrypted DataStore    | Persists across app restarts |
// | Current user profile  | AuthViewModel          | Needed globally              |
// | Vehicle list          | VehicleDetailViewModel | Screen-scoped               |
// | Inspection form data  | NewInspectionViewModel | Screen-scoped, lost on back  |
// | Notifications list    | NotificationsViewModel | Screen-scoped               |
// | Notification count    | Shared StateFlow       | Shown on bottom nav badge    |
```

### 16.2 Web Dashboard (Laravel)

```php
// The web dashboard uses server-side rendering (SSR) with Laravel Blade.
// State management is simpler than a SPA:
//
// In plain language:
// - Each page request goes to the server. The server fetches data from
//   MySQL, passes it to a Blade template, and sends back HTML.
// - There is no client-side "state" to manage for most pages.
// - For interactive features (like the Fleet Board or DataTables),
//   we use AJAX calls with jQuery to fetch fresh data without reloading.
// - Session data (logged-in user, flash messages) is managed by Laravel.
//
// | Data                  | Where it lives          | Why                          |
// |-----------------------|------------------------|------------------------------|
// | Auth session          | Laravel session (cookie)| Standard web auth            |
// | Page data             | Controller → Blade view | Server-rendered per request  |
// | Flash messages        | Laravel session         | "Vehicle created!" alerts    |
// | DataTable state       | Client-side (jQuery)    | Sort, search, paginate       |
// | Fleet Board updates   | AJAX polling (30s)      | Near-real-time status view   |
```

---

## 17. SECURITY NOTES

### 17.1 Authentication Security

| Rule | Implementation |
|---|---|
| Passwords | Minimum 8 characters; Bcrypt hashed by Laravel; never stored or transmitted in plain text |
| API token | Sanctum personal access token; transmitted via `Authorization: Bearer <token>` header |
| Web session | Laravel session with CSRF token protection on all forms |
| Token expiry | API tokens expire after 30 days of inactivity (configurable) |
| Account creation | Only Agency Admins can create accounts (no self-registration) |
| Rate limiting | Laravel's built-in rate limiter: 60 requests/minute per user for API; 5 login attempts per minute |

### 17.2 Data Security

| Rule | Implementation |
|---|---|
| Agency isolation | All Eloquent models use a global `AgencyScope` that adds `WHERE agency_id = ?` to every query |
| No cross-agency access | Middleware + global scope enforce isolation at both route and database level |
| Soft delete | All critical tables use Laravel `SoftDeletes`; `deleted_at` is set instead of removing rows |
| Audit logging | Model observers log all create/update/delete operations to `activity_logs` |
| Input validation | Laravel Form Requests validate all inputs before they reach controllers |
| File upload limits | 5 MB max per file; validated MIME type (image/jpeg, image/png); stored outside web root |
| SQL injection | Prevented by Eloquent ORM parameterized queries (never use raw user input in queries) |
| XSS | Blade `{{ }}` escapes output by default; `{!! !!}` only used for trusted HTML |

### 17.3 API Security

| Rule | Implementation |
|---|---|
| CORS | Laravel CORS config allows only the web dashboard origin |
| CSRF | All web forms include `@csrf` token; API routes exempt (token auth instead) |
| Rate limiting | `throttle:api` middleware on all API routes |
| Validation | All API inputs validated via Form Requests |
| Error sanitization | Production mode hides stack traces and internal details; shows generic messages |
| HTTPS | Required in production (enforced by server config) |

### 17.4 Environment Variables

```bash
# .env — NEVER commit this file to Git
# Use .env.example as the template

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rvms
DB_USERNAME=rvms_user
DB_PASSWORD=<strong-password>

# Firebase (FCM only)
FIREBASE_PROJECT_ID=rvms-capstone
FIREBASE_SERVICE_ACCOUNT_PATH=storage/app/firebase/service-account.json

# App
APP_URL=https://your-server.com
APP_ENV=production
APP_DEBUG=false
```

---

## 18. DEPLOYMENT NOTES

### 18.1 Deployment Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    PRODUCTION SERVER                        │
│                    (VPS / Shared Hosting)                   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐    │
│  │              Laravel Application                    │    │
│  │              (Apache/Nginx + PHP 8.2+)              │    │
│  │                                                     │    │
│  │  ├── Web Dashboard (Blade + Bootstrap)              │    │
│  │  ├── REST API (Sanctum-protected)                   │    │
│  │  ├── Task Scheduler (cron → artisan schedule:run)   │    │
│  │  ├── Queue Worker (php artisan queue:work)          │    │
│  │  └── File Storage (storage/app/public/)             │    │
│  └────────────────────┬────────────────────────────────┘    │
│                       │                                     │
│  ┌────────────────────┴────────────────────────────────┐    │
│  │              MySQL 8.0+ Database                    │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                             │
└─────────────────────────────────────────────────────────────┘

                     │  HTTPS
                     ▼

┌─────────────────────────────────────────────────────────────┐
│                   CLIENTS                                   │
│                                                             │
│  ┌──────────────────────┐    ┌───────────────────────────┐  │
│  │  Android Mobile App  │    │  Web Browser (Chrome,     │  │
│  │  (APK distributed    │    │   Firefox, Edge)          │  │
│  │   via USB / Drive)   │    │                           │  │
│  └──────────────────────┘    └───────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘

                     │  FCM HTTP v1 API
                     ▼

┌─────────────────────────────────────────────────────────────┐
│               FIREBASE (FCM Only)                           │
│  Push notifications delivered to Android devices            │
└─────────────────────────────────────────────────────────────┘
```

### 18.2 Server Requirements

| Requirement | Specification |
|---|---|
| OS | Ubuntu 22.04 LTS (or compatible Linux) |
| Web Server | Nginx 1.24+ or Apache 2.4+ |
| PHP | 8.2+ with extensions: mbstring, xml, curl, mysql, gd, zip |
| MySQL | 8.0+ |
| Composer | Latest |
| Node.js | 20 LTS (only for asset compilation if needed) |
| SSL | Let's Encrypt (free) or provided by hosting |
| Cron | Single cron entry for Laravel Scheduler |
| Process Manager | Supervisor (for queue worker) |

### 18.3 Deployment Commands

```bash
# ─── Laravel Deployment ──────────────────────────────────

# Clone and install dependencies
git clone <repo-url> /var/www/rvms-web
cd /var/www/rvms-web
composer install --no-dev --optimize-autoloader

# Environment setup
cp .env.example .env
# Edit .env with production values (DB, FCM, APP_URL, etc.)
php artisan key:generate

# Database setup
php artisan migrate --force
php artisan db:seed --class=AgencySeeder  # Seed the 4 agencies

# Storage and permissions
php artisan storage:link
chmod -R 775 storage bootstrap/cache

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# ─── Cron (Scheduler) ────────────────────────────────────
# Add to crontab:
* * * * * cd /var/www/rvms-web && php artisan schedule:run >> /dev/null 2>&1

# ─── Queue Worker (Supervisor) ────────────────────────────
# /etc/supervisor/conf.d/rvms-worker.conf
# [program:rvms-worker]
# command=php /var/www/rvms-web/artisan queue:work --sleep=3 --tries=3

# ─── Android APK Build ───────────────────────────────────

# Build signed APK from Android Studio:
# Build > Generate Signed Bundle/APK > APK > release
# Distribute APK via USB or Google Drive to testers
```

### 18.4 Environment Strategy

| Environment | Server | Database | Purpose |
|---|---|---|---|
| `development` | `localhost` (XAMPP / Laragon / Herd) | `rvms_dev` (local MySQL) | Local development and testing |
| `staging` | Test server or same server with subdomain | `rvms_staging` | Team testing, adviser demo |
| `production` | VPS or shared hosting | `rvms` | Final pilot deployment |

### 18.5 Pre-Deployment Checklist

- [ ] `APP_DEBUG=false` in production `.env`
- [ ] `APP_ENV=production` in production `.env`
- [ ] All migrations run successfully (`php artisan migrate --force`)
- [ ] Agency seeder run (4 agencies + BLOWBAGETS items)
- [ ] Firebase Service Account JSON in `storage/app/firebase/`
- [ ] `storage/` directory writable by web server
- [ ] Storage link created (`php artisan storage:link`)
- [ ] Cron entry added for scheduler
- [ ] Supervisor running queue worker
- [ ] HTTPS configured with valid SSL certificate
- [ ] CORS configured to allow only the correct origin
- [ ] Rate limiting enabled on API routes
- [ ] All API endpoints tested with Postman / Insomnia
- [ ] Android APK tested on physical device (Android 8.0+)
- [ ] FCM push notifications tested end-to-end
- [ ] PDF report generation tested
- [ ] No `dd()` or `dump()` statements left in code

### 18.6 Android APK Distribution

```
# For pilot testing, the Android APK will be distributed directly to
# test devices — NOT through the Google Play Store.
#
# Distribution methods:
# 1. USB transfer: Copy the signed APK to the device and install manually.
# 2. Google Drive: Upload APK and share the link with testers.
# 3. Firebase App Distribution (optional): Upload APK for managed distribution.
#
# Testers must enable "Install from unknown sources" on their devices.
```

---

## 19. SCHEDULE OF ACTIVITIES

| Phase | Timeline | Key Activities |
|---|---|---|
| **Planning & Data Gathering** | May 2026 | System planning, interviews, Chapters 1–3, scope definition |
| **Proposed Prototype Design** | June 2026 | UI/UX mockups, workflow finalization, database schema design |
| **Software Development** | July – September 2026 | Full development of mobile app, web dashboard, API, database, notifications, reports |
| **Testing & Deployment** | October – November 2026 | Unit testing, integration testing, UAT, pilot testing, Chapters 4–5, defense |

---

*Last updated: May 2026. Aligned with RVMS_PROJECT_CONTEXT_SKILL, Chapter 3 Technical Background, and the finalized capstone manuscript.*

