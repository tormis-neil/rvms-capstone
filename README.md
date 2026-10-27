# Rescue Vehicle Management System (RVMS)

The **Rescue Vehicle Management System (RVMS)** is a centralized fleet and maintenance management platform designed to ensure rescue and emergency vehicles remain mechanically sound, compliant, and operational for rapid emergency response. 

The system unifies fleet oversight across four public safety and emergency response agencies:
- **BFP** — Bureau of Fire Protection
- **PNP** — Philippine National Police
- **CDRRMO** — City Disaster Risk Reduction and Management Office
- **CHO** — City Health Office

RVMS operates across two synchronized platforms sharing a single backend API and unified database:
1. **Android Mobile Application** for Authorized Drivers in the field.
2. **Web Administrative Dashboard** for Agency Administrators managing fleet resources.

---

## The Problem

Emergency response agencies frequently manage vehicle readiness, pre-trip safety, and maintenance workflows through manual and disconnected practices:

* **Fragmented and Vulnerable Paper Records:** Vehicle ledgers, logbooks, driver credentials, and repair histories are physically kept on paper or informal logs, leading to lost documentation, lack of audit trails, and slow retrieval during emergencies.
* **Informal Defect Reporting:** Vehicle faults and damage are often communicated verbally or via informal messaging. Crucial mechanical details are easily lost, delaying necessary workshop interventions.
* **Lack of Real-Time Operational Visibility:** Administrators struggle to determine instantaneous fleet readiness. Without centralized status synchronization, it is difficult to identify which rescue units are deployed, operational, grounded, or overdue for routine service.
* **Unmonitored Compliance and Safety Risks:** Driver license and qualification expiries are tracked manually without advance warning, creating regulatory and operational compliance liabilities.
* **Unstructured Preventive Maintenance:** Scheduled servicing (oil changes, brake inspections, tire rotations) is often tracked reactively rather than proactively, increasing the risk of mechanical breakdowns during critical emergency missions.

---

## The Solution

RVMS eliminates fragmented paperwork and informal communication by providing a unified, secure, and role-based digital management environment:

* **Strict Multi-Tenancy Data Isolation:** Agency administrators and drivers access only their respective agency's vehicles, dispatches, and reports through an automated backend global scope.
* **Single Shared Operational Status:** Every vehicle maintains exactly one shared operational state across the entire system:
  * `Operational` — Fully functional and ready for emergency deployment.
  * `Dispatched` — Currently deployed on an active mission.
  * `Not Operational` — Grounded due to reported defects, mechanical failure, or active corrective repairs.
  * `Under Preventive Maintenance` — Undergoing scheduled routine servicing.
* **Digital Inspection & Defect Accountability:** Drivers execute standardized daily pre-trip inspections and photo-verified damage tickets directly from mobile handsets before deployment.
* **Integrated Maintenance & Mission Records:** Connects dispatch departure/arrival odometer readings directly with vehicle cumulative mileage and automated preventive maintenance schedules.

---

## Core Features

### 1. Driver Mobile Application (Android)
* **Assigned Vehicle Overview:** View real-time technical specifications (make, model, plate, chassis/engine numbers) and live operational status of assigned primary and secondary vehicles.
* **Digital BLOWBAGETS Daily Inspection:** Conduct mandatory pre-trip checks covering 12 universal vehicle components (*Battery, Lights, Oil, Water, Brakes, Air, Gas, Engine, Tires, Self-Inspection/Power Steering, Horn/Siren, Directional Signals*) plus 2 specialized agency criteria (*Hydraulic System* and *Fire Pump* for fire engines). Requires mandatory defect remarks on any item flagged as *Has Issue*.
* **Defect & Damage Reporting:** File immediate incident reports documenting the nature of damage, suspected failing components, and supporting raster photo attachments.
* **Automated Notifications:** Receive instant in-app alerts and Firebase Cloud Messaging (FCM) push notifications when vehicle status changes or when preventive maintenance schedules are due.
* **Driver Self-Service Profile:** Update contact information, view personal license and TESDA NC II expiration statuses, and manage account security.

### 2. Agency Web Administration Dashboard
* **Fleet Asset Management:** Register, update, and manage permanent vehicle records, assigning primary and optional backup drivers without risking historical audit deletion.
* **Driver Compliance Monitoring:** Oversee driver rosters, approve/reject pending self-registered driver accounts, and track automated warnings for upcoming or expired professional driver licenses and NC II certifications.
* **Inspection Review & Verification:** Audit daily inspection submissions, examine flagged checklist items, and verify supervisor reviews.
* **Damage Assessment Workflow:** Review driver-submitted incident tickets, inspect uploaded photographic evidence, assess defect severity, and update the vehicle's operational availability.
* **Preventive Maintenance (PM) Engine:** Create mileage-based (kilometer threshold) or time-based (calendar interval) maintenance plans with configurable "Due Soon" alert thresholds, record completion logs, and archive service documentation.
* **Corrective Repair History:** Track historical repair work orders, parts replacement inventories, financial expenditures, and external workshop receipts.
* **Dispatch Logging & Odometer Tracking:** Authorize mission deployments, assign primary and secondary crew drivers, upload travel orders, and close dispatches with return odometer readings that automatically increment the vehicle's live mileage.
* **Executive Summary & Analytics:** Real-time dashboard summarizing fleet readiness distribution, driver totals, impending license expiries, pending damage tickets, and top reported vehicle defects.
* **Printable Operational Reporting:** Filter and export audit-ready reports with computed summary statistics covering inspections, damage incidents, maintenance history, preventive maintenance logs, dispatch records, and fleet availability.
* **Administrative Notifications:** In-app alert drawer notifying administrators of newly filed damage tickets, flagged inspection findings, driver registration requests, expiring credentials, and scheduled maintenance milestones.

---

## Tech Stack

| Component | Technology | Description |
|---|---|---|
| **Backend Framework** | Laravel 11 (PHP 8.2+) | RESTful API at `/api/v1`, Eloquent ORM with multi-tenant global scoping, Form Request validation |
| **Authentication** | Laravel Sanctum & Web Session | Bearer token authentication for mobile clients and secure cookie-based session guards for web administration |
| **Web Dashboard** | Blade & Bootstrap 5.3 | Responsive administrative dashboard, custom modular UI components, server-rendered views |
| **Database** | MySQL 8.0+ | Relational schema with foreign key integrity, InnoDB storage engine, and transactional safety |
| **Mobile Application** | Kotlin (Android SDK 26+) | Native Android application built with Jetpack Compose, Material 3, Navigation component, and Retrofit/OkHttp |
| **Push Notifications** | Firebase Cloud Messaging (FCM) | Server-side notification dispatch via FCM HTTP v1 API targeting Android device tokens |
| **Task Scheduler** | Laravel Scheduler | Automated background workers monitoring time-based PM intervals and credential expiration thresholds |

---

## How to Run Locally

### Prerequisites
* **PHP:** PHP 8.2 or higher (with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `gd` extensions enabled)
* **Composer:** Latest stable Composer release
* **Database:** MySQL 8.0+ or MariaDB 10.4+
* **Mobile Development:** Android Studio (Meerkat 2024.3+ or compatible) and Android SDK 26+ (or a physical Android device running Android 8.0+)

---

### Step 1: Backend Setup

1. **Clone the repository and enter the backend directory:**
   ```bash
   git clone https://github.com/tormis-neil/rvms-capstone.git
   cd rvms-capstone/backend
   ```

2. **Install Composer dependencies:**
   ```bash
   composer install
   ```

3. **Configure Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Open `.env` in a text editor and configure your local MySQL database connection:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rvms_db
   DB_USERNAME=root
   DB_PASSWORD=your_mysql_password
   ```

4. **Run Migrations and Seed Initial Records:**
   ```bash
   php artisan migrate --seed
   ```
   *(Creates all 11 domain tables and populates sample administrative accounts, drivers, vehicles, checklist items, and maintenance schedules).*

5. **Create Storage Symlink:**
   ```bash
   php artisan storage:link
   ```
   *(Essential for serving uploaded damage photos and receipts).*

6. **Start the Development Server:**
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```

7. **Start the Background Scheduler (in a second terminal):**
   ```bash
   cd backend
   php artisan schedule:work
   ```
   *(Simulates cron to evaluate PM due alerts and license expiry notices).*

---

### Step 2: Accessing the Web Dashboard

Navigate to `http://127.0.0.1:8000` in your web browser. You can sign in using any of the pre-seeded agency administrator accounts (default password: `password`):

| Agency | Administrator Email | Default Password |
|---|---|---|
| **BFP** | `bfp.admin@rvms.local` | `password` |
| **PNP** | `pnp.admin@rvms.local` | `password` |
| **CDRRMO** | `cdrrmo.admin@rvms.local` | `password` |
| **CHO** | `cho.admin@rvms.local` | `password` |

---

### Step 3: Mobile App Setup (Android)

#### Option A: Running from Source via Android Studio
1. Launch Android Studio and select **Open** $\rightarrow$ choose the `mobile/` directory.
2. Allow Gradle to synchronize dependencies.
3. If testing on a physical device connected via USB with the local backend running on `localhost:8000`, run this ADB reverse command:
   ```bash
   adb reverse tcp:8000 tcp:8000
   ```
4. Build and deploy the application to your emulator or physical handset by clicking **Run ▶**.

#### Option B: Installing the Pre-built APK
1. Download `app-debug.apk` directly to your Android device from the releases page:
   `https://github.com/tormis-neil/rvms-capstone/releases/tag/apk-latest`
2. Open the APK on the device and allow installation from unknown sources if prompted.
3. On first launch, configure the API server address in the mobile settings screen:
   * **USB tethered with ADB reverse:** `http://127.0.0.1:8000`
   * **Local Wi-Fi Network:** `http://<YOUR_COMPUTER_LOCAL_IP>:8000`
   * **Hosted Server:** `https://your-deployed-domain.com`

#### Sample Driver Login Credentials (Pre-seeded):
* **BFP Driver:** `bfp.driver@rvms.local` / `password`
* **PNP Driver:** `pnp.driver@rvms.local` / `password`
* **CDRRMO Driver:** `cdrrmo.driver@rvms.local` / `password`
* **CHO Driver:** `cho.driver@rvms.local` / `password`

---

## How to Run Online (Production & Cloud Deployment)

RVMS is architected for containerized cloud deployment (e.g., Railway, DigitalOcean, VPS, or AWS).

### 1. Build and Process Architecture
Production containers should be deployed using Nixpacks or Docker with PHP 8.2+ and FrankenPHP / Nginx:
* **Web Service:** Handles HTTP traffic, routes incoming API requests, and serves the administrative dashboard.
* **Scheduler Service:** A lightweight companion worker executing `php artisan schedule:work` to trigger scheduled daily status calculations without restarting web connections.

### 2. Environment Variables Configuration
Set the following production variables in your cloud provider's dashboard:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-rvms-domain.com

# Database Connection
DB_CONNECTION=mysql
DB_HOST=your-production-db-host
DB_PORT=3306
DB_DATABASE=your-production-db-name
DB_USERNAME=your-production-db-user
DB_PASSWORD=your-production-db-password

# Session & Security
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

# File Upload Thresholds
UPLOAD_MAX_FILESIZE=8M
POST_MAX_SIZE=10M

# Firebase Cloud Messaging Credentials
FIREBASE_CREDENTIALS=/path/to/firebase-credentials.json
```

### 3. Persistent Storage Volume
Mount a persistent storage volume to `/storage/app/public` in your container configuration. Uploaded vehicle damage photographs, repair invoices, and travel orders are permanently stored in this volume.

### 4. Container Startup Command
Deployments should initialize migrations and symlinks safely before accepting traffic:
```bash
php artisan migrate --force && php artisan storage:link || true && php artisan serve --host=0.0.0.0 --port=$PORT
```

### 5. Automated Background Cron
On a standard Linux VPS, configure the system crontab to run once every minute:
```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## Verification & Health Check

The backend includes a built-in diagnostics command to verify environmental health, configuration validity, and deployment integrity:

```bash
cd backend
php artisan rvms:doctor
```

To run the automated backend test suite:
```bash
cd backend
php artisan test
```
