# Manual testing guide — R7 → R9 (one sitting)

Covers **R7 Notifications + FCM**, **R8 Dashboard + Reports**, and **R9 Profile**.
Work top to bottom. Anything that does not match its **Expected**, write down the exact
message and move on — collect everything, then report it all at once.

Legend: **[AUTO]** one command · **[WEB]** browser · **[MOBILE]** phone · **[BOTH]** cross-platform.

---

## 0. Setup (once, before you start)

| # | Do this | Expected |
|---|---|---|
| 0.1 | `git pull origin claude/r7-notification-fcm-fix-9tnvum` | Files update, no conflicts |
| 0.2 | `php artisan migrate` | `Nothing to migrate.` — R8 and R9 add no tables. **A migration running here is a red flag** |
| 0.3 | `php artisan config:clear` | Config cache cleared |
| 0.4 | `php artisan rvms:fcm-doctor` | Every stage green, ending `Reached Google and authenticated: yes` |
| 0.5 | `php artisan serve` — leave running | Serving on 127.0.0.1:8000 |
| 0.6 | **Second terminal:** `php artisan queue:work` — leave running | Waits for jobs. **Nothing pushes without this** |
| 0.7 | **Third terminal:** `php artisan schedule:work` — leave running | Stands in for cron so the PM/licence sweeps fire |
| 0.8 | `adb reverse tcp:8000 tcp:8000`, then run the app on the phone | App opens |
| 0.9 | In Android Studio: `./gradlew test` | All green (includes the new `LicenseUiTest` + `UpdateProfileTest`) |

Accounts: web `bfp.admin@rvms.local` / `password` · second admin `bfp.admin2@rvms.local` ·
phone: the driver you registered (`ramon.bfp@email.com`).

**[AUTO] 0.10** — `php artisan test` → **442 passed, 10 skipped**. Paste the first red line if not.

---

## 1. R7 — Notifications + FCM

### 1a. The bell and the notifications page [WEB]

| # | Do this | Expected |
|---|---|---|
| 1.1 | Open the bell dropdown, then the notifications page, beside `web/pages/notifications.html` | Identical layout; rows grouped **Today / Yesterday / Earlier** |
| 1.2 | Click one notification | Marks it read and jumps to the module it concerns |
| 1.3 | Click **Mark All as Read** | Red bell count drops to 0; button goes disabled |
| 1.4 | Click **Clear Read** | Modal names the **exact count**; unread count is stated separately |
| 1.5 | Confirm the clear | Read rows disappear; **unread rows survive** |
| 1.6 | With nothing read, look at **Clear Read** | Greyed out, not hidden |
| 1.7 | Sign in as `bfp.admin2@rvms.local` | **Their** notifications are still there — 1.5 emptied only your own list |

### 1b. Who gets rung [WEB]

Each row: make it happen, then check *both* BFP admin accounts.

| # | Event | Who should be notified |
|---|---|---|
| 1.8 | Change a vehicle's status | **The assigned driver only** — no admin |
| 1.9 | Driver files a damage report on the phone | **Both** BFP admins |
| 1.10 | Driver submits an inspection **with an item flagged** | **Both** BFP admins |
| 1.11 | Driver submits an **all-OK** inspection | **Nobody.** A new alert here is a bug |
| 1.12 | Driver self-registers on the phone | **Both** BFP admins |
| 1.13 | `php artisan rvms:license-alerts` | **Both** BFP admins |
| 1.14 | `php artisan rvms:pm-alerts` | Both admins **and** the assigned driver |
| 1.15 | Sign in as the CHO admin | Bell shows **only** CHO's news |

### 1c. Push delivery [BOTH]

| # | Do this | Expected |
|---|---|---|
| 1.16 | `php artisan rvms:fcm-doctor --user=ramon.bfp@email.com`, app **backgrounded** | Banner on the handset |
| 1.17 | With the app backgrounded, change that driver's vehicle status on the web | `queue:work` shows **DONE in 200–700 ms**; banner appears |
| 1.18 | Watch `queue:work` during 1.17 | **No `FAIL` line.** A `FAIL` + `DONE` pair means a config fault — grab it from `php artisan queue:failed` |
| 1.19 | Tap the banner | App opens **already showing the new status** — no restart needed |

> Foreground app = no banner by design (the app handles it in code). Background it first.

### 1d. Mobile alerts inbox [MOBILE]

| # | Do this | Expected |
|---|---|---|
| 1.20 | Open the **Alerts** tab | The driver's own notifications, grouped like the web |
| 1.21 | Look at the tab badge | Unread count matches the list |
| 1.22 | Tap one notification | Marks read; badge drops by one |
| 1.23 | **Mark all read** with an empty inbox | Button **visible but greyed out** — not missing |
| 1.24 | Pull down to refresh | List reloads |

---

## 2. R7 fixes — licence colours & live refresh [MOBILE]

| # | Do this | Expected |
|---|---|---|
| 2.1 | In tinker set the driver's `license_expiry_date` to **10 days out**; reopen the app | Licence card **amber**, "Expiring Soon", chip "Action Needed" |
| 2.2 | Set it to **yesterday**; return to the app | Licence card **red**, "Expired", chip "Renewal Required" |
| 2.3 | Read the sentence under 2.2 | Says "**Expired** \<date\>" — past tense. "Expires" here is a bug |
| 2.4 | Compare against the web Drivers page | Same three colours: green / amber / red |
| 2.5 | Background the app; change the vehicle's status on the web; reopen | Status is **already correct** without a restart |
| 2.6 | Pull to refresh on **Vehicle Info**, **Inspections**, **Damage** | All three refresh — none did before |

---

## 3. R8 — Dashboard + Reports [WEB]

### 3a. Fleet Overview

| # | Do this | Expected |
|---|---|---|
| 3.1 | Open the dashboard beside `web/pages/dashboard.html` | Identical cards, tiles, and the two Action Required panels |
| 3.2 | Read the date line, top right | **Today's** date, not "June 8, 2026" |
| 3.3 | `php artisan tinker` → `App\Models\Vehicle::where('status','Operational')->count()` | Matches the **OPERATIONAL** card exactly |
| 3.4 | Add the four status cards together | Equals the **TOTAL VEHICLES** card |
| 3.5 | Compare **EXPIRING LICENSES** with the Drivers page's *Expiring Soon* card | **Same number.** They are meant to match |
| 3.6 | Look at the Expiring Licenses panel below | Shows expiring **and** expired; expired badged **red** and listed first |
| 3.7 | Compare **Pending Inspections & Damage** pill with the Inspections page's pending pill + pending damage | Adds up |
| 3.8 | Click each of the 6 Quick Action tiles | All open real pages — no 404, no `.html` |
| 3.9 | Click a row in either Action Required panel | Opens the right module |
| 3.10 | Sign in as the CHO admin | Every figure is CHO's; no BFP plate anywhere |
| 3.11 | Sign in as `bfp.admin2@rvms.local` | **Identical** figures to `bfp.admin` |

### 3b. Reports

| # | Do this | Expected |
|---|---|---|
| 3.12 | Open Reports beside `web/pages/reports.html` | Identical 6 cards and Configure Report modal |
| 3.13 | Click **Configure Report** on each card in turn | Modal title changes to that report's name |
| 3.14 | Open it on **Vehicle Status Summary** | **Filter block hidden** — it is a snapshot |
| 3.15 | Open it on **Preventive Maintenance Records** | **Driver filter hidden** (a PM schedule has no driver) |
| 3.16 | Generate all six with **All Dates** | Each renders a table below the cards |
| 3.17 | Check each report's columns | Every field that module records is present |
| 3.18 | Generate one with a **Vehicle** filter | Only that vehicle's rows |
| 3.19 | Generate one with **Last 7 Days** | Older rows drop out |
| 3.20 | Pick a range with no matching records | "No records match the selected filters." — not an empty page |
| 3.21 | Read the report header | Agency logo + name + location, report type, **Generated: \<today\>**, **Generated by: \<your name\>** |
| 3.22 | **Ctrl+P** on a report | Clean printout; the Print/Clear buttons **do not** appear on paper |
| 3.23 | Click **Clear** | Report disappears, the 6 cards remain |
| 3.24 | Sign in as the CHO admin, generate all six | Not one BFP record anywhere |
| 3.25 | Edit the URL to `?type=inspections&range=whenever` | Refused with a validation error, not a wrong report |

---

## 4. R9 — Profile

### 4a. Web [WEB]

| # | Do this | Expected |
|---|---|---|
| 4.1 | Open Profile (sidebar **and** the user dropdown) beside `web/pages/profile.html` | Identical layout; both links work |
| 4.2 | Read the card and form | **Your** name, email and agency — not "Admin User" / "admin@bfp.gov.ph" |
| 4.3 | Try to type in **Agency Name / Location / Contact** | **Not editable.** "Change Agency Logo" is greyed out |
| 4.4 | Change your **name**, Save | Success message; the topbar and card show the new name |
| 4.5 | Type a new password but a different confirmation, Save | Refused — "confirmation does not match"; password unchanged |
| 4.6 | Type a password shorter than 8 characters, Save | Refused |
| 4.7 | Change your email to another account's email | Refused — already exists |
| 4.8 | Change your password properly, Save | Success — **and you are still signed in.** Being logged out here is a bug |
| 4.9 | Sign out, sign in with the **new** password | Works |
| 4.10 | Sign in with the old password | Refused |

### 4b. Mobile [MOBILE]

| # | Do this | Expected |
|---|---|---|
| 4.11 | Profile tab → **Edit Profile** | Dialog with Full Name, Email, New Password, Confirm |
| 4.12 | Change your name only, leave passwords blank, Save | Saves; name updates on Profile **and** the Home greeting |
| 4.13 | Enter mismatched passwords | Blocked in the app before sending |
| 4.14 | Enter a password under 8 characters | Blocked with "At least 8 characters." |
| 4.15 | Enter an email already used by another account | Server's message shown in the dialog |
| 4.16 | Change your password properly, Save | Saves; **you stay signed in** |
| 4.17 | Sign out, sign in with the new password | Works |
| 4.18 | Look for licence fields in the dialog | **Not there** — the licence is the admin's record (FR-06/FR-08) |

### 4c. Cross-platform [BOTH]

| # | Do this | Expected |
|---|---|---|
| 4.19 | Change the driver's name on the **phone**, then open the web Drivers page | New name shown |
| 4.20 | Admin edits that driver's **licence expiry** on the web; background/reopen the app | Licence card updates without a restart |

---

## 5. Report back

For each failure: **which number**, what you did, what you saw (exact message), and a
screenshot if it is visual. Also worth noting:

- **0.10** — the automated suite: pass, or the first red line.
- **0.9** — `./gradlew test`: pass, or the first error. *(If it complains about
  `androidx.compose.ui.graphics.Color` in `LicenseUiTest`, tell me — I'll split those
  assertions out rather than have you fight it.)*
- **1.18** — any `FAIL` in `queue:work`, plus `php artisan queue:failed`.
- **3.3–3.7** — any dashboard figure that disagrees with the page it should match. These
  are the ones a panel will check by hand.
