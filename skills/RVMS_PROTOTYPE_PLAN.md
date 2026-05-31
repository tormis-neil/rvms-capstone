---
name: RVMS_PROTOTYPE_PLAN
description: Official Prototype Development Plan for the Rescue Vehicle Management System (UI/UX Design System)
---

# RVMS Prototype Development Plan (UI/UX)

*Design system grounded in the RVMS Project Context design philosophy*

This document defines the exact visual architecture, design system, and user experience rules for both the **Android Mobile App** (Jetpack Compose) and the **Web Admin Dashboard** (Laravel + Bootstrap). 

All frontend development MUST adhere strictly to the variables and styles defined in this document.

---

## 1. Design Philosophy

Grounded in the RVMS Project Context design philosophy (§11), the system adopts a **"Mission-Critical Management Dashboard"** style, prioritizing the operational realities of Philippine rescue agencies.

**Core Principles:**
- **High-Contrast Precision:** Uses high-contrast typography and distinct color-coding to prevent misreading critical vehicle statuses during emergencies.
- **Action-Oriented Prominence:** Key actions (Log Dispatch, Report Defect) use high-visibility contrasting colors to guide user attention immediately.
- **Cognitive Ease:** Layouts rely on ample whitespace (`gap-4` to `gap-6` in Tailwind/Bootstrap equivalents) and card-based grouping to reduce cognitive load on dispatchers and drivers.

---

## 2. Global Color System

Designed for rescue vehicle management contexts — prioritizing trust, clarity, and status visibility.

### Brand & Interface Colors
| Name | Hex Code | Usage |
|---|---|---|
| **Primary (Trust Blue)** | `#2563EB` | Top app bars, active tabs, primary outlines, brand identity |
| **Secondary (Soft Blue)** | `#3B82F6` | Secondary buttons, hovered states, subtle highlights |
| **Call-to-Action** | `#2563EB` | "Log Dispatch", "New Inspection" primary action buttons (uses Primary Blue for consistency; avoids conflict with amber/orange status colors) |
| **Background (Light Mode)** | `#F8FAFC` | App backgrounds, minimizing glare compared to pure white |
| **Surface (Cards)** | `#FFFFFF` | Card backgrounds, modals, dropdowns |
| **Text Primary** | `#1E293B` | Main headings, body text, form labels |
| **Text Secondary** | `#64748B` | Subtitles, helper text, timestamps |

### RVMS Status Colors (CRITICAL)
These colors must be used consistently across both the Android app and Web Dashboard whenever a vehicle status is displayed.
| Status | Hex Code | Bootstrap Equiv. | Usage |
|---|---|---|---|
| **OPERATIONAL** | `#22C55E` | `bg-success` | Ready for dispatch |
| **DISPATCHED** | `#2563EB` | `bg-primary` | Currently on a mission |
| **PARTIALLY_OPERATIONAL** | `#F59E0B` | `bg-warning` | Has minor defects but usable |
| **NOT_OPERATIONAL** | `#EF4444` | `bg-danger` | Grounded / Major defects |
| **UNDER_MAINTENANCE** | `#8B5CF6` | `bg-purple` (custom) | Preventive maintenance in progress |

---

## 3. Typography System

Chosen for technical precision and high readability for field personnel — clear numbers (mileage, license plates, dates) and simple body text for inspection remarks.

- **Google Fonts Import:**
  ```css
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap');
  ```

### Font Pairings
1. **Headings & Body Text:** `Inter` (Sans-Serif)
   - *Why?* Inter is highly legible at all sizes, designed for screens, and professional enough for government agency use. Clean and simple for field personnel (drivers) and administrators alike.
2. **Data Fields (Plate Numbers, Odometers, License Numbers):** `JetBrains Mono` (Monospace)
   - *Why?* Monospace ensures digits align perfectly in tables, fleet boards, and data fields — making it easy to scan plate numbers, odometer readings, and dates. Used ONLY for specific data display, not for headings.

### Font Hierarchy
- **H1 (Page Titles):** `Inter`, 24px (Mobile) / 32px (Web), Semi-Bold (600)
- **H2 (Card Titles):** `Inter`, 18px (Mobile) / 20px (Web), Medium (500)
- **Body Text:** `Inter`, 16px, Regular (400)
- **Small/Caption:** `Inter`, 14px, Light (300) (used for timestamps: `2 mins ago`)
- **Data Fields:** `JetBrains Mono`, 14-18px, Regular (400) (plate numbers, odometers, license numbers)

---

## 4. UI Components & Styling

### 4.1 Buttons
- **Primary Action (Log Dispatch / Submit Form):** 
  - Background: `#2563EB` (Primary Blue)
  - Text: White, Medium weight
  - Border Radius: `8px` (Web) / `RoundedCornerShape(8.dp)` (Compose)
  - *No drop shadows* (flat, modern aesthetic).
- **Secondary Action (Cancel / Edit):**
  - Background: Transparent
  - Border: 1px solid `#CBD5E1`
  - Text: `#1E293B`

### 4.2 Status Badges
- Used everywhere a vehicle or report status is shown.
- **Style:** Pill shape (fully rounded ends).
- **Padding:** `4px` top/bottom, `12px` left/right.
- **Font:** `JetBrains Mono`, 12px, Bold, Uppercase.
- **Colors:** Background uses the Status Color at 15% opacity; Text uses the Status Color at 100% opacity. 
  *(Example: Operational badge is light green background with dark green text).*

### 4.3 Forms (Inspections & Defect Reports)
- **Inputs:** `1px` solid border (`#CBD5E1`), background `#FFFFFF`.
- **Focus State:** `2px` solid border (`#2563EB`), no glowing box shadows.
- **Labels:** `Inter`, 14px, `#64748B`, positioned above the input.

---

## 5. Navigation Architecture

### 5.1 Mobile App (Authorized Drivers)
- **Type:** Bottom Navigation Bar.
- **Background:** `#FFFFFF` with a subtle top border (`#E2E8F0`).
- **Active Tab:** Icon and text colored `#2563EB`.
- **Inactive Tab:** Icon and text colored `#94A3B8`.
- **Tabs:** 
  1. Home (Dashboard)
  2. Inspections (Checklist icon)
  3. Defects (Warning shield icon — for submitting defect/damage reports)
  4. Notifications (Bell icon with unread notification badge: `#EF4444` dot)

### 5.2 Web Dashboard (Agency Administrators)
- **Type:** Persistent Left Sidebar.
- **Width:** `250px` (Collapsible to icon-only on tablets).
- **Background:** Dark slate `#0F172A` (creates contrast between navigation and the light `#F8FAFC` main content area).
- **Text:** Light grey `#CBD5E1`.
- **Active Item:** Background `#1E293B` with a left border accent `#3B82F6` (Secondary Blue) of `4px`.
- **Navigation Items (in order):**
  1. Dashboard (Grid/Home icon)
  2. Vehicles (Truck icon)
  3. Fleet Board (Layout/Board icon)
  4. Drivers (Users icon)
  5. Inspections (Clipboard Check icon)
  6. Defect Reports (Alert Triangle icon)
  7. Repairs (Wrench icon)
  8. Preventive Maintenance (Calendar Clock icon)
  9. Dispatches (Send/Radio icon)
  10. Reports (File Text icon)
  11. Notifications (Bell icon with unread count badge)

---

## 6. Dashboard Layout & Visualization

### Web Fleet Board
- **Layout:** CSS Grid / Bootstrap rows.
- **Cards:** Each vehicle is a card. Background `#FFFFFF`, padding `20px`, border radius `12px`.
- **Card Content:** 
  - Top Left: Plate Number (`JetBrains Mono`, 18px).
  - Top Right: Status Badge.
  - Middle: Vehicle Type & Model (`Inter`, 14px, `#64748B`).
  - Bottom: Assigned Driver & Odometer.

### Mobile Home Screen
- **Header:** "Welcome, [Driver Name]" + Agency Logo.
- **Assigned Vehicle Card:** A prominent card at the top displaying the current status of the vehicle assigned to them.
- **Quick Actions Grid:** 2x2 grid of large, tap-friendly buttons: "New Inspection", "Report Defect", "View History", "My Vehicle" (view assigned vehicle status and maintenance history).

### Admin Dashboard Overview (`/dashboard`)
- **Layout:** Responsive grid — 4-column on desktop, 2-column on tablet, 1-column on mobile.
- **Row 1 — Fleet KPI Cards:** Four stat cards showing:
  - Total Vehicles (with breakdown by status using status colors)
  - Active Dispatches (count of currently dispatched vehicles)
  - Pending Reviews (count of unreviewed inspections + unacknowledged defect reports)
  - PM Alerts (count of DUE_SOON + OVERDUE preventive maintenance items)
- **Row 2 — Action Panels (2 columns):**
  - **Left: Recent Activity Feed** — Chronological list of recent system actions from `activity_logs` (e.g., "Admin logged a dispatch for ABC-1234", "Driver submitted inspection for XYZ-5678").
  - **Right: Quick Actions** — Bootstrap buttons: Log Dispatch, Log Repair, Create Vehicle, Add Driver.
- **Row 3 — Monitoring Panels (2 columns):**
  - **Left: Pending Defect Reports** — List of unacknowledged defect reports requiring Admin review.
  - **Right: Upcoming PM Schedule** — List of DUE_SOON and OVERDUE preventive maintenance items with vehicle plate number and PM type.
- **Card Style:** Background `#FFFFFF`, padding `20px`, border radius `12px`, subtle `1px` border (`#E2E8F0`).
- **Numbers/Counts:** `JetBrains Mono`, bold, large size for emphasis.

---

## 7. Implementation Checklist

- [ ] Create `theme.xml` and `Color.kt` in Android Studio matching the exact hex codes.
- [ ] Add `Inter` and `JetBrains Mono` to Android project resources.
- [ ] Override Bootstrap variables in `app.scss` (e.g., `$primary: #2563EB;`, `$success: #22C55E;`).
- [ ] Create a custom Blade component `<x-status-badge>` that automatically handles the 15% opacity background logic.
- [ ] Create a reusable Compose `@Composable fun StatusBadge()` with identical logic.

---

## 8. Mobile App Prototype Development Plan (4 Phases)

This phased approach specifically targets the Authorized Driver (Mobile App) module, ensuring a systematic and structured development process aligned with the RVMS design philosophy.

### Phase 1: Foundation & Navigation Architecture
**Goal:** Set up the Android project, implement the global design system, and establish the bottom navigation.
- **Tasks:**
  - Initialize Jetpack Compose project.
  - Implement Global Color System (`theme.xml`, `Color.kt`) and Typography System (Inter & JetBrains Mono).
  - Create reusable UI components (Primary/Secondary Buttons, Status Badges).
  - Implement Bottom Navigation Bar (Home, Inspections, Defects, Notifications).
  - Set up dummy data models for Driver and Vehicle.

### Phase 2: Home Dashboard & Vehicle Status
**Goal:** Build the driver's main interface and assigned vehicle status view.
- **Tasks:**
  - Develop Home Screen Header (Welcome message, Agency Logo).
  - Create the Assigned Vehicle Card (displaying current status using official Status Colors).
  - Implement Quick Actions Grid (New Inspection, Report Defect, View History, My Vehicle).
  - Build the "My Vehicle" detailed view (vehicle information, basic maintenance history).

### Phase 3: Core Operations (Inspections & Defect Reporting)
**Goal:** Implement the primary data entry forms for the driver.
- **Tasks:**
  - Build the Digital BLOWBAGETS Inspection form (14 items, OK/Has Issue, Remarks).
  - Build the Defect & Damage Reporting form (Vehicle details, description, photo upload UI).
  - Implement form validation and submission feedback (success/error states).
  - Apply standard input styling (1px border, focus states).

### Phase 4: Notifications & Polish
**Goal:** Add alerts, refine user experience, and finalize prototype flow.
- **Tasks:**
  - Build the Notifications screen (list view of alerts, read/unread states).
  - Implement UI mockups for In-App Notifications (simulated FCM payloads for PM due, status changes).
  - Conduct full UI/UX review against the Design Philosophy (High-Contrast Precision, Action-Oriented Prominence).
  - Final prototype testing and bug fixing.

---
*Design system aligned with the RVMS Project Context skill and capstone manuscript.*
