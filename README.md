# Rescue Vehicle Management System (RVMS)

A two-platform digital system that helps the rescue agencies of **Calbayog City** keep their
emergency vehicles ready for deployment — an **Android app for drivers** and a **web dashboard
for agency administrators**, sharing one Laravel API and one database.

> **Capstone Project** — College of Computing and Information Sciences, Northwest Samar State
> University, Calbayog City. In partial fulfillment of the BS in Information Technology and
> BS in Information Systems.

---

## Description

The Rescue Vehicle Management System (RVMS) centralizes vehicle and driver records, daily
inspections, damage and defect reporting, repair logging, preventive-maintenance scheduling,
dispatch logging, notifications, and report generation for four government agencies:

- **BFP** — Bureau of Fire Protection
- **PNP** — Philippine National Police
- **CDRRMO** — City Disaster Risk Reduction and Management Office
- **CHO** — City Health Office

Each agency administrator only ever sees and manages their own agency's records. That wall is
enforced on every query by a global scope in the backend, not by the screens.

## The Problem

The four agencies previously tracked vehicles, drivers, inspections, repairs, and dispatches
using **paper forms, logbooks, verbal reports, phone calls, and group chats**. Because the
information was scattered across separate records, it was hard to tell at a glance which
vehicles were ready, which were deployed, which were under repair, or which driver licenses
were about to expire. Damage reports were sometimes incomplete, and records were easy to lose —
all of which slowed emergency readiness.

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
| **Driver Mobile App** (Android) | Authorized Driver | View assigned vehicle(s) and live status, submit BLOWBAGETS inspections, file damage reports with photos, receive push notifications, edit their own profile. |
| **Admin Website** | Agency Administrator | Manage vehicles & drivers, review inspections & damage, log repairs, schedule preventive maintenance, log dispatches, read notifications, generate printable reports. |

Vehicle status is a **single shared field** with exactly four values — **Operational**,
**Dispatched**, **Not Operational**, **Under Preventive Maintenance** — written from every
module through one service, so both platforms always read the same truth.

## Tech Stack

| Area | Technology |
|---|---|
| Backend & API | Laravel 11 (PHP 8.2+), REST at `/api/v1`, Laravel Sanctum bearer tokens |
| Database | MySQL 8.0+ |
| Admin dashboard | Blade + Bootstrap 5.3 (server-rendered, session auth) |
| Mobile app | Kotlin 2.x + Jetpack Compose, Retrofit/OkHttp (Android 8.0+) |
| Push notifications | Firebase Cloud Messaging, HTTP v1, sent server-side from PHP on a queue |
| Tooling | Android Studio, Visual Studio Code, XAMPP |

## Repository Structure

```
rvms-capstone/
├── backend/                    Laravel 11 API + Blade admin dashboard  ← the running system
│   ├── app/                    Controllers, models, services, jobs, console commands
│   ├── resources/views/        The 10 admin screens (copies of the web/ prototype, wired live)
│   ├── database/               Migrations, factories, seeders
│   ├── tests/                  PHPUnit feature + unit suite
│   └── docs/security-audit.md  Security findings and their disposition
├── mobile/                     Android driver app (Kotlin + Jetpack Compose)
├── web/                        The original static prototype — the visual reference the
│                               dashboard is compared against; frozen, never edited
├── skills/                     Capstone manuscript, interview results, source of truth
├── CLAUDE.md                   Approved design decisions, data dictionary, and build plan
└── README.md
```

---

## Running the System

### 1. Backend (required by both platforms)

```bash
cd backend
composer install
cp .env.example .env             # then set DB_USERNAME / DB_PASSWORD for your MySQL
php artisan key:generate
php artisan migrate --seed       # first time only
php artisan storage:link         # without this every damage photo 404s
php artisan serve                # http://127.0.0.1:8000
```

In two more terminals, for the full feature set:

```bash
php artisan queue:work           # sends the queued FCM pushes
php artisan schedule:work        # stands in for cron during development
```

Sign in at <http://127.0.0.1:8000> as `bfp.admin@rvms.local` / `password`. See
[`backend/README.md`](backend/README.md) for the complete account list, the API surface, and
the deployment requirements.

> **`php artisan migrate` is the routine command after an update.** `migrate:fresh --seed`
> **drops every table** and erases any account or record you created by hand.

### 2. Driver Mobile App (Android)

**Install the ready-made APK (easiest for testers):**

1. On an Android 8.0+ phone, open the latest build:
   **https://github.com/tormis-neil/rvms-capstone/releases/tag/apk-latest**
2. Download **`app-debug.apk`** (the raw file — no zip).
3. Tap it to install; allow *"install unknown apps"* if prompted.
4. The app needs the backend reachable — see `mobile/README.md` for the USB setup.

**Build and run it yourself:** open the `mobile/` folder in Android Studio, let Gradle sync,
and press **Run ▶**. See [`mobile/README.md`](mobile/README.md) for the USB (`adb reverse`)
setup that lets the phone reach the backend running on your laptop.

**Using it:** Sign in → see your **Assigned Vehicle** and **License Status** on Home → use the
bottom tabs (**Home, Inspect, Damage, Alerts, Profile**). Submit a daily **BLOWBAGETS**
inspection (remarks are required for any item marked *Has Issue*), file a **Damage Report**,
and check **Alerts** for maintenance and status notifications.

### 3. The static prototype (`web/`)

The `web/` folder is the original click-through prototype the dashboard was built from. It
still runs on its own sample data and is kept as the **visual reference** for side-by-side
comparison — it is not the working system. See [`web/README.md`](web/README.md).

---

## Testing

```bash
cd backend && php artisan test        # the backend suite
cd mobile  && ./gradlew test          # the mobile unit tests
php artisan rvms:doctor               # is this deployment complete and safe to hand over?
```

---

## Scope & Limitations (summary)

- Two roles only: **Agency Administrator** and **Authorized Driver** (no GSO/external mechanic
  logins). Administrator accounts are provisioned, not self-registered; drivers self-register
  and wait for their agency administrator to approve them.
- **Android only** for the mobile app; both platforms require an internet connection (no
  offline mode — the app says so plainly rather than showing stale or empty data).
- No GPS, live tracking, maps, or IoT/telematics diagnostics. Odometer readings are keyed in
  by hand from the vehicle's own odometer.
- Dispatch is **logging and availability monitoring only** — no automatic assignment.
- Records depend on manual entry.
- Generated reports are not official COA/government forms.
- One primary driver per vehicle (rotating shifts are attributed via the dispatch record); a
  driver may be the primary driver of more than one vehicle.

## Authors

Neil Mayo C. Tormis · Jenny Rose C. Monticod · Jhon Lex C. Mahait · Sim Harold J. Doren ·
Christian Jay F. Abarro

*Northwest Samar State University — Calbayog City*
