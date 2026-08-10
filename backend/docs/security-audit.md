# Security Audit — findings and disposition (R10 sub-task 2)

**Date:** 2026-07-30 · **Scope:** the whole repository (Laravel backend, Blade dashboard,
Android driver app), attacked from the outside in. · **Focus areas:** auth · agency data
isolation · driver PII. *(The audit prompt's PAYMENTS area does not apply: the system holds no
transactions — `repair_logs.cost` is a number on a form.)*

Severity scale: **Critical** = exploitable now, fixed in this phase · **Ticket** = real but not
dangerous today, listed below with effort · **Clean** = checked and found sound.

---

## 1. Fixed in this phase (criticals)

### C1 — Stored XSS via SVG damage "photo" — FIXED
**Where:** `app/Http/Requests/StoreDamageReportRequest.php:31`
Laravel's bare `image` rule also admits **SVG**, and an SVG is a document that can carry
`<script>`/event handlers. Uploaded as a damage photo it was stored on the public disk and
opened verbatim by the admin's **View** button — executing in the dashboard's own origin, with
the admin's session. A driver-role account could therefore attack an administrator.
**Fix:** the rule is now `image` **plus** `mimes:jpg,jpeg,png,webp`. A phone camera only ever
produces raster formats, so nothing legitimate is lost. Pinned by two new tests
(`test_an_svg_is_refused_as_a_photo`, `test_jpeg_png_and_webp_photos_are_accepted`).

### C2 — CRLF injection through the default `email` rule — MITIGATED
**Where:** every form request accepting an email; advisory GHSA-5vg9-5847-vvmq (high).
`composer audit` reports the framework line in use (Laravel 11.55) affected, and **Laravel 11
left its security-fix window in March 2026**, so no 11.x patch exists. Exploitability *here* is
low — RVMS sends no mail, so an injected CRLF can reach storage but no mail header — but the
mitigation costs one word: every `email` rule is now **`email:strict`**, which rejects
CRLF-bearing addresses at validation. (Framework upgrade itself: ticket T1.)

### C3 — Bearer token printed to logcat — FIXED
**Where:** `mobile/.../data/remote/ApiClient.kt:37`
`HttpLoggingInterceptor` at `Level.BODY` prints request headers, including
`Authorization: Bearer <token>` — a live session credential readable by anyone with adb access
to the handset. Redacted with `redactHeader("Authorization")`, unconditionally: a demo device
is exactly where a curious onlooker plugs in a cable. (Body logging of PII: ticket T2.)

---

## 2. Checked and found sound

| Area | What was checked | Result |
|---|---|---|
| **Secrets in git history** | All 224 commits: paths and blob content for `firebase.json`, `.env`, `google-services.json`, private keys, Google API keys | **Never committed.** Ignore rules predate the files |
| **IDOR / agency isolation** | Every `{id}` route in all nine modules | Scoped models resolve through the `AgencyScope` global scope (cross-agency → 404); `users` (unscoped by design) is guarded by `DriverController::authorizeDriver()` — role **and** agency, 404 not 403, so existence is not leaked; notifications check `user_id`, not just agency (multi-admin rule); drivers reading inspections/damage are pinned to `driver_id = their own`; report filters validate ids against the caller's agency (422). Automated end-to-end proof: `AgencyIsolationSweepTest`, sub-task 3 |
| **SQL injection** | All `DB::raw` / `whereRaw` / `selectRaw` sites | Four sites, all constant aggregate expressions (`COUNT(*)`, `MAX(...)`); no user input reaches raw SQL. Everything else is Eloquent bindings |
| **XSS in Blade** | Both `{!! !!}` sites | `reports.blade.php:124` escapes every element through `e()` before joining with entity separators; the pagination partials render framework translation strings. All other output is `{{ }}`-escaped |
| **File upload surface** | Damage photos (the only upload) | Random `hashName()` filenames (no traversal, no overwrite-by-name), stored under `storage/app/public/damage-photos`, 5 MB cap, and now raster-only (C1) |
| **Mass assignment** | Controllers vs `$fillable` | Writes go through form-request `validated()`/`safe()` data; vehicle status writes are funneled through `VehicleStatusWriter` (design decision 9) |
| **PII in responses/logs** | API resources, `Log::` calls | `password`, `remember_token`, `fcm_token` are `$hidden` on `User` and appear in no resource; the only logging outside FCM is framework-level; FCM logs mask device tokens |
| **Mobile dependencies** | `libs.versions.toml` | Kotlin 2.3.20, OkHttp 4.12, Retrofit 2.11, current Firebase BoM — no applicable known CVEs |
| **Remaining composer advisories** | Signed-URL path confusion; CRLF (C2) | No `signedRoute`/`temporarySignedRoute` usage and no mail sending — no exploit path in this codebase |

---

## 3. Tickets — real, not dangerous today

| # | Finding | Where | Disposition | Effort |
|---|---|---|---|---|
| **T1** | **Laravel 11 is past its security window** (fixes ended Mar 2026); the CRLF advisory has no 11.x patch | `composer.json` | Upgrade to Laravel 12 **after the defense** — a framework major days before a panel is the wrong risk. Interim mitigations (C2) in place; the other advisories have no path here | 0.5–1 day + full regression |
| **T2** | OkHttp `Level.BODY` still logs **response bodies** (driver names, licence numbers) to logcat | `ApiClient.kt:36` | Belongs with the release-APK build variant task (parked per plan): enable `buildConfig`, gate to `Level.NONE` when not debuggable | 1 h |
| **T3** | Sanctum tokens never expire (`'expiration' => null`) — a leaked bearer token is valid forever, bounded only by logout's revoke | `config/sanctum.php:53` | Set an expiry (e.g. 30 days) once the mobile re-auth path is tested; do together with T2's build-variant work | 2 h |
| **T4** | `SESSION_SECURE_COOKIE` unset — fine on localhost, required once HTTPS lands | `config/session.php:172` | Deployment-time setting; noted in the deployment docs. `http_only` and `same_site=lax` already sound | 15 min at deploy |
| **T5** | Password policy is `min:8` only — no letter/number mix, no breached-password check | all password rules | A policy change the agencies should agree to (drivers type these on phones in the field); `Password::min(8)->letters()->numbers()` when agreed | 1 h |
| ~~**T6**~~ | ~~No rate limiting on login or report generation~~ | routes | **CLOSED (R10.5).** `LoginThrottle` — 5 failures per account+IP, shared by web and API; `/reports` capped at 20/min on both surfaces. `LoginThrottleTest` | done |
| ~~**T7**~~ | ~~`APP_DEBUG=true` on a demo machine exposes DB credentials in stack traces~~ | `.env.example:4` | **CLOSED (R10.6).** `php artisan rvms:doctor` fails with a non-zero exit when `APP_DEBUG` is on outside a development environment, alongside eight other handover checks | done |

---

## 3b. Closed since the audit

| # | Closed by | What changed |
|---|---|---|
| T6 | R10.5 | Login throttling on both front doors + a cap on report generation |
| T7 | R10.6 | `rvms:doctor` fails loudly on `APP_DEBUG` outside development |

**Still open and deliberately deferred:** T1 (Laravel 12 upgrade — a framework major days
before a defense is the wrong risk; the CRLF advisory is mitigated by `email:strict`), T2 and
T3 (OkHttp response-body logging and Sanctum token expiry — both belong with the release-APK
build-variant work), T4 (`SESSION_SECURE_COOKIE`, a deployment-day setting), and T5 (password
policy, which the agencies should agree to rather than have imposed).

---

## 4. What this audit did NOT cover

Penetration of the *deployed* environment (server OS, MySQL exposure, HTTPS/TLS termination) —
the system is not yet deployed; those checks belong to the deployment day, and `rvms:doctor`
carries the software-side half of them. Physical device security of shared handsets is a
procedural matter for the agencies, noted in the manuscript's scope.

---

## Accepted risk — cleartext HTTP to a local server (2026-08)

`network_security_config.xml` previously permitted cleartext only to
`127.0.0.1`, `10.0.2.2` and `localhost`. It now permits it generally, because
the server address became switchable at runtime so one APK could serve all
three demo tiers, and a laptop's LAN address differs at every venue. Android's
network security config matches domains, not ranges, so the hotspot tier could
not be allow-listed in advance — it failed with "cannot reach the server"
against a server that was running.

**What it costs:** traffic to a local server is unencrypted and readable by
anyone already on that network.

**Why it is accepted:** the network in question is a hotspot the laptop creates
for the demo, with one phone on it, in the same room. The alternative — a
self-signed certificate the handset must be told to trust — is more to configure
and more to fail, for a threat model of one laptop and one phone.

**Where NFR-02 is actually met:** the deployed configuration is HTTPS and never
uses this exemption. A production APK pointed at an `https://` origin encrypts
everything; the exemption only ever applies when someone has deliberately typed
a plain-HTTP address.

**If this were shipped beyond the capstone**, the fix is to permit cleartext
only in a debug build variant and require HTTPS in release.
