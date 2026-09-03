# Session Handoff — 28 August 2026

> **What this is.** A compaction of the deployment session so a new chat can pick
> up without re-deriving anything. Read `CLAUDE.md` and
> `skills/rvms-source-of-truth.md` first as always; this file is the *delta*.

---

## STATE OF THE SYSTEM

| | |
|---|---|
| `main` at | `e282db2` (PR #115 merged) |
| Test suite | **734 passed, 17 skipped** (`php artisan test`) |
| Working tree | clean, everything pushed |
| Deployment | **Railway — LIVE.** Web dashboard, MySQL, scheduler service, volume |
| Mobile | APK installed from CI release, authenticating over HTTPS |

### What works, verified on the deployment

- Web dashboard serving, seeded (4 agencies · 13 users · 8 vehicles), logged in
- Scheduler as its own service — heartbeat confirmed, correct Manila time:
  `2026-08-28 14:53:00 Running ['artisan' queue:work …] 3s DONE`
- Volume attached, so photos and receipts survive redeploys
- Driver app installed from the CI-built APK, signing in against the deployed API

### ⚠️ Two builders, and it matters

| Service | Builder | Consequence |
|---|---|---|
| `rvms-capstone` (web) | **Railpack** → FrankenPHP + Caddy | Ignores `nixpacks.toml` **and** `deploy/start.sh` |
| `scheduler` | **Nixpacks** | Reads them both |

This is why the same command reports different things on each service, and why
half a day was spent fixing a file the web service never reads. Check which
builder a service uses before believing any config-based explanation.

---

## ⚠️ DO FIRST TOMORROW — the site is public

1. **Change the 12 seeded passwords.** `rvms:doctor` fails on this, correctly.
   Admins: `php artisan rvms:reset-password <email>` in the Console.
   Drivers: through the dashboard's reset button — it also exercises FR-22 and
   confirms the driver gets a `Password_Reset` notification.
2. **Rotate the Firebase service-account key.** Its full text appeared in a
   screenshot and in a chat transcript. Firebase Console → Project settings →
   Service accounts → Generate new private key, then delete the old one in
   Google Cloud Console → IAM → Service Accounts → Keys.

---

## FCM — the diagnosis, in full

Push notifications were broken at **both ends**. Only one end was visible.

### End 1 — the server (fixed in code, deploy pending verification)

`FIREBASE_CREDENTIALS` was pasted correctly and still refused:

```
length     : 2296          ← full
last 40    : …"universe_domain":"googleapis.com"}   ← complete
json error : Control character error, possibly incorrectly encoded
```

**Cause.** A service-account key holds its PEM private key as one JSON string
whose line breaks are written as the two characters `\` and `n`. Railway's raw
variable editor rewrites those into REAL line breaks on save. A raw newline
cannot legally sit inside a JSON string, so `json_decode` refuses the whole
document. Measured: the clipboard held 2,324 chars with **0** real line breaks
and **28** escaped ones; what came back out of the editor had them expanded.

**Fixed (PR #114).** `FcmTransportFactory::resolveCredentialsPath()` now:
- accepts **base64** (checked first — the only form no editor can damage), and
- retries a `JSON_ERROR_CTRL_CHAR` failure once with control characters inside
  string literals escaped back, re-encoding before writing to disk.

Five tests, including the exact deployment value; the repaired `private_key`
comes back byte-identical.

### End 2 — the app (fixed in CI, needs a new APK)

`mobile/app/google-services.json` is gitignored, so GitHub Actions never saw it
and `build.gradle.kts` took its *"building without Firebase"* branch. **The
installed APK cannot register a device token at all.**

**Fixed (PR #115).** CI writes the file from a `GOOGLE_SERVICES_JSON`
repository secret before Gradle runs, failing loudly on a malformed secret.

### Remaining FCM steps

1. **Verify the deploy landed.** In the `rvms-capstone` Console:
   `grep -c fromBase64 app/Services/Fcm/FcmTransportFactory.php`
   `0` means the fix is still not running.
   **Railway's "Redeploy" re-deploys the SAME commit** — only a new push to
   `main` triggers a build of current code. Auto-deploy was enabled late, which
   is why #114 never deployed on its own.
2. `php artisan rvms:fcm-doctor` → expect a real credentials path and a passing
   access-token check. The value already stored should work without re-pasting.
3. **Create the `GOOGLE_SERVICES_JSON` secret** at
   `github.com/tormis-neil/rvms-capstone/settings/secrets/actions` — paste
   `google-services.json` (project **rvms-28129**, package `com.example.rvms`).
4. Re-run the APK workflow, download from the `apk-latest` release, reinstall.
   Same CI signing key, so it installs over the top.
5. Test: change a vehicle's status on the web → push banner on the phone.

**If base64 is ever needed** (nothing can mangle it):
```powershell
[Convert]::ToBase64String([IO.File]::ReadAllBytes("$env:USERPROFILE\Downloads\<key>.json")) | Set-Clipboard
```

---

## OPEN — the upload limit is UNMEASURED

`rvms:doctor` reports `0M` on the web service and `2M` on the scheduler from
identical code. It reads the **CLI's** configuration, which under FrankenPHP is
not the web server's, so neither figure describes what an upload actually faces.
The hint was corrected in PR #115 to say so.

**Ground truth is still needed:** take a real camera photo (3–6 MB), file a
damage report from the phone, and see whether it saves and displays. Four
outcomes, each meaning something different — the misleading one is *"nature of
damage is required"* on a form that was filled in, which means `post_max_size`
dropped every field.

---

## AGREED CODE CHANGES — investigated, none started

From the end-to-end testing report. Verdicts grounded in the interviews,
Chapter 1, the NFRs and practicality.

| # | Change | Basis | Manuscript |
|---|---|---|---|
| 🔴 1 | **Remove the password field from Edit Driver** | `DriverController::update()` sets a driver's password with **no** admin re-authentication and **no** `passwordWasReset` notification, while `resetPassword()` does both. Two routes, one silently weaker | None — removes a violation |
| 🟠 2 | FCM | above | None |
| 🟠 3 | **Login lockout resets on each failure** | The 60s window starts at the *first* failure, so a paced attacker gets 5 tries/minute indefinitely. Also makes the countdown read as expected. NFR-02 | None |
| 🟡 4 | **Move Frequently Reported Issues to the Dashboard** | Project lead's decision (usability, NFR-03) | **Yes** — see below |
| 🟡 5 | **Block daily inspections on Dispatched vehicles** | Interviews unanimous: *"before deployment"*, *"before vehicle use"*, *"before operations"*, *"while waiting for deployment"*. Use a separate `isAvailableForDailyInspection()` — a dispatched vehicle is not *out of service* | **None** — Ch1 never names the restriction |
| 🟡 6 | **UI-based admin account creation** | Ch1 says accounts are *"provisioned within the system"*, and names a server command only for password recovery. Officers are not sysadmins, and after turnover there are no maintainers | Possibly one Ch1 clause |

**Item 6 safeguards:** own agency only · confirm your own password · notify the
agency's existing admins · keep `rvms:create-admin` for when nobody can sign in.

**Deliberately NOT changed:** damage reports are never blocked by vehicle status
— grounded in the GSO Motorpool interview (*"submitted damage descriptions are
sometimes incomplete… additional defects are often discovered during
inspection"*). Blocking would make a second, unrelated fault unreportable.

---

## MANUSCRIPT DECISIONS (project lead, this session)

### The FR table is withdrawn — not yet executed

New FRs will be drafted once the instructor supplies the format. Until then the
repo's manuscript authority is **Chapter 1–3 + ERD + data dictionary + NFRs**.

**Task:** remove the `#### Functional Requirements` heading, its intro, Table 3
(FR-01–FR-22) and the Table 3 narrative from `skills/rvms-source-of-truth.md`
(lines ~157–190). Leave the NFR section untouched. Replace with a notice that
FRs are withdrawn and that any `FR-nn` still appearing in `CLAUDE.md`, code
comments, migrations or tests refers to the **withdrawn** numbering.

**Decided:** keep the FR references elsewhere rather than stripping them
repo-wide — the numbering will be replaced anyway, and hundreds of edits carry
real risk. Line ~256 (the Ch4 FDD narrative) says the leaf functions
*"correspond directly to the functional requirements"*; soften to "the system's
functions".

**Standing rule from the lead:** where a change contradicts the old FR table but
is proven and more practical, the change wins.

### Frequently Reported Issues moves to the Dashboard

Four edits, and the consequence must be stated:

| Where | Change |
|---|---|
| Scope, inspection sentence | delete `", and monitor frequently reported issues"` |
| Scope, dashboard sentence | append `", together with the most frequently reported inspection issues."` |
| Purpose & Description, dashboard sentence | same append |
| Ch4 FDD narrative | move the leaf from Inspection Management to Dashboard & Report Generation (also Figure 5 — add to the deferred diagram work) |

**Say it plainly in the manuscript:** the dashboard stops being *only counts*.
That was a deliberate line (R8's rule was "no new data claims"). Breaking it is
the lead's call, but the document must describe the screen that will be demoed.

---

## THINGS THAT COST TIME — do not re-derive

- **`PHP_INI_SCAN_DIR` unloads every PHP extension** on this image, leading
  colon or not. Measured: 12 extensions with it set, 45 without. It broke both
  the build (Composer could not start) and the runtime (`DOMDocument not
  found`). Removed from `nixpacks.toml`; PHP config now happens in
  `deploy/start.sh`, at runtime, with a fallback to PHP's defaults rather than
  a crash loop.
- **Nixpacks is multi-stage** and re-copies the source over `/app` after the
  install phase — anything written into `/app` during the build does not survive.
- **Railway's raw ENV editor** reformats values: it escapes quotes and expands
  `\n`. Use the individual variable field, or base64.
- **Railway's "Redeploy" re-deploys the same commit.** Only a new push builds
  current code.
- **The mobile app's server address is runtime-configurable** (`ServerUrlStore`)
  — one APK covers deployed, hotspot and USB. Type it **with `https://`**: a
  bare hostname defaults to `http`, which is wrong for Railway. The runbook's
  Part 7.5 says "without https://" and is wrong; fix it.
- **The USB tier is not unbreakable.** The cable failed mid-consultation. Tier 1
  (deployed) is now the primary path; a hotspot runbook would be a better
  fallback than a spare cable.

---

## KEY STANDING CONSTRAINTS

- `backend/public/assets/css/style.css` is **never edited** (Rule 9);
  backend-only CSS goes in `admin.css`.
- `web/` is **never edited** — it is the prototype and the checkpoint reference.
- Every vehicle status write goes through `App\Services\VehicleStatusWriter`.
- Agency-scope every query · `/api/v1/` · Sanctum bearer · FCM server-side HTTP v1.
- SVG stays **refused** on every upload surface.
- Develop, commit and push only to `claude/file-review-usb-testing-vplyjo`.
- No model identifiers in commit messages, PR bodies, code comments, or any
  pushed artifact.

---

## SUGGESTED ORDER FOR THE NEXT SESSION

1. Passwords + key rotation (above) — the site is public
2. Confirm the FCM deploy landed; finish the APK half
3. The upload-limit measurement
4. Withdraw the FR table (settles the basis before code moves)
5. Code items 1, 3, 5 — small, independent, no manuscript impact
6. Item 4 + its manuscript edits in one commit
7. Item 6 on its own
