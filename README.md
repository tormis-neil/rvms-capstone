# Rescue Vehicle Management System (RVMS)

A two-platform digital system that helps the rescue agencies of **Calbayog City** keep their
emergency vehicles ready for deployment — an **Android app for drivers** and a **web dashboard
for agency administrators**.

> **Capstone Project** — College of Computing and Information Sciences, Northwest Samar State
> University, Calbayog City. In partial fulfillment of the BS in Information Technology and
> BS in Information Systems.

📄 **New here?** Read the per-platform user guides — **[Driver Mobile App Guide](RVMS-Mobile-App-User-Guide.pdf)**
and **[Admin Website Guide](RVMS-Admin-Website-User-Guide.pdf)** — plain-language tours of every
screen, button, colour and label, with objective mapping, written for the team and the client
users who will test the prototype.

---

## Description

The Rescue Vehicle Management System (RVMS) centralizes vehicle and driver records, daily
inspections, damage and defect reporting, repair logging, preventive-maintenance scheduling,
dispatch logging, and report generation for four government agencies:

- **BFP** — Bureau of Fire Protection
- **PNP** — Philippine National Police
- **CDRRMO** — City Disaster Risk Reduction and Management Office
- **CHO** — City Health Office

Each agency administrator only sees and manages their own agency's records.

## The Problem

The four agencies currently track vehicles, drivers, inspections, repairs, and dispatches using
**paper forms, logbooks, verbal reports, phone calls, and group chats**. Because the information
is scattered across separate records, it is hard to tell at a glance which vehicles are ready,
which are deployed, which are under repair, or which driver licenses are about to expire. Damage
reports are sometimes incomplete, and records are easy to lose — all of which slows emergency
readiness.

## Purpose

RVMS replaces those fragmented, paper-based processes with **one shared system**. Drivers submit
daily inspections and damage reports from their phones; administrators manage vehicles, drivers,
maintenance, dispatch, and reports from the web — with consistent records that are easy to find.

## Objectives

The main objective is to develop a Rescue Vehicle Management System. Specifically:

1. **To input vehicle and driver information.**
2. **To track vehicle maintenance.**
3. **To provide an admin module for dispatch and vehicle management.**
4. **To generate relevant reports.**

## The Two Platforms

| Platform | User | What they do |
|---|---|---|
| **Driver Mobile App** (Android) | Authorized Driver | View assigned vehicle, submit BLOWBAGETS inspections, file damage reports, receive notifications. |
| **Admin Website** | Agency Administrator | Manage vehicles & drivers, review inspections & damage, log repairs, schedule preventive maintenance, log dispatches, generate reports. |

Vehicle status is shared across both platforms with four values: **Operational**, **Dispatched**,
**Not Operational**, and **Under Preventive Maintenance**.

## Tech Stack

**Prototype (this repository)** — runs on built-in sample data; there is no live backend yet.

| Area | Technology |
|---|---|
| Mobile app | Kotlin + Jetpack Compose (Android 8.0+) |
| Admin website | HTML, CSS, Bootstrap 5, JavaScript (static, sample data) |
| Tooling | Android Studio, Visual Studio Code |

**Planned backend (development phase)**

| Area | Technology |
|---|---|
| Backend & API | Laravel 11 (PHP 8.2+) |
| Database | MySQL 8.0+ |
| Push notifications | Firebase Cloud Messaging (FCM) |

## Repository Structure

```
rvms-capstone/
├── mobile/                     Android driver app (Kotlin + Jetpack Compose)
├── web/                        Admin website (static prototype)
│   ├── index.html / login.html / signup.html
│   ├── pages/                  Authenticated admin pages
│   └── assets/                 css / js / img
├── skills/                     Capstone manuscript, interview results, prototype plan
├── RVMS-Mobile-App-User-Guide.pdf   Driver app user guide (per-screen walkthrough)
├── RVMS-Admin-Website-User-Guide.pdf Admin website user guide (per-page walkthrough)
└── README.md
```

---

## User Guide

### A. Driver Mobile App (Android)

**Install the ready-made APK (easiest for testers):**

1. On an Android 7.0+ phone, open the latest build:
   **https://github.com/tormis-neil/rvms-capstone/releases/tag/apk-latest**
2. Download **`app-debug.apk`** (the raw file — no zip).
3. Tap it to install; allow *"install unknown apps"* if prompted.
4. Open the app and pick an agency on the Sign In screen (prototype demo selector).

**Build and run it yourself (Android Studio):**

1. Open the `mobile/` folder in Android Studio (Meerkat 2024.3.1+).
2. Let Gradle sync, connect a device/emulator (Android 8.0+), and press **Run ▶**.
3. To produce a shareable file: **Build → Build App Bundle(s) / APK(s) → Build APK(s)**;
   the file appears at `mobile/app/build/outputs/apk/debug/app-debug.apk`.

**Using it:** Sign in → see your **Assigned Vehicle** and **License Status** on Home → use the
bottom tabs (**Home, Inspect, Damage, Alerts, Profile**). Submit a daily **BLOWBAGETS** inspection
(remarks are required for any item marked *Has Issue*), file a **Damage Report**, and check
**Alerts** for maintenance and status notifications.

### B. Admin Website

**Run it locally:** it is a static site — serve the `web/` folder with any static server:

```bash
cd web
python3 -m http.server 8080      # or: npx serve .
```

Open <http://localhost:8080>, then pick an agency (BFP / PNP / CDRRMO / CHO) on the login page.

**Deploy (e.g., Vercel):** deploy the `web/` folder as the project root (no build step — set the
project's **Root Directory** to `web`). See `web/README.md` for details.

**Using it:** From the **Dashboard**, monitor the 8 readiness cards and *Action Required* panels.
Manage **Vehicles** and **Drivers** (with license-expiry alerts), review **Inspections & Damage**,
schedule **PM**, keep **Repair Logs**, record **Dispatch Logs**, and produce printable **Reports**.

---

## Scope & Limitations (summary)

- Two roles only: **Agency Administrator** and **Authorized Driver** (no GSO/external mechanic logins).
- **Android only** for the mobile app; both platforms require an internet connection (no offline mode).
- No GPS, live tracking, maps, or IoT/telematics diagnostics.
- Dispatch is **logging and availability monitoring only** — no automatic assignment.
- Records depend on manual entry; mileage is updated by the administrator.
- Generated reports are not official COA/government forms.
- One primary driver per vehicle (rotating shifts are attributed via the dispatch record).

> **Prototype note:** the apps currently use sample data to demonstrate every screen and flow.
> Nothing is permanently stored until the backend is implemented.

## Authors

Neil Mayo C. Tormis · Jenny Rose C. Monticod · Jhon Lex C. Mahait · Sim Harold J. Doren ·
Christian Jay F. Abarro

*Northwest Samar State University — Calbayog City*
