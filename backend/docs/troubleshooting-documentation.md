# RVMS — Troubleshooting & Deployment Documentation

**What this is.** A practical, symptom-first guide to the RVMS deployment. It captures the
procedures and the dead-ends that cost real time, so nobody has to re-derive them. It contains
**no secrets** — only the know-how. Keep it that way: procedures are safe to commit,
credentials never are.

> Companion documents: `docs/security-audit.md` (findings & disposition),
> `skills/rvms-session-handoff-2026-08-28.md` (deployment session notes),
> `CLAUDE.md` (design decisions & conventions). Read those for the *why* behind the system;
> this file is the *when-it-breaks-do-this*.

---

## 0. Three golden rules (read once, save hours)

1. **`php artisan rvms:doctor` is the scoreboard.** Don't guess whether something is fixed —
   run the doctor and read the line. Green means done. It checks config, schema, storage,
   upload limit, the scheduler, push delivery, and seeded passwords in one pass.
   `php artisan rvms:fcm-doctor` is the deeper, push-only version.

2. **After ANY environment-variable change, clear the config cache** — or the change is
   silently ignored:
   ```
   php artisan config:clear
   ```
   The doctor warns `Config is cached` precisely because this is the #1 "why isn't my change
   taking effect?" trap. A fresh deploy also clears it; a "Redeploy" of the same commit may not.

3. **Railway runs TWO services, each with its OWN variables.** Any Firebase/env change must be
   applied to **both**:
   - `rvms-capstone` (web) — the dashboard + API, built by **Railpack → FrankenPHP + Caddy**.
   - `scheduler` — runs `php artisan schedule:work`, built by **Nixpacks**.
   Setting a variable on one but not the other is a classic cause of "it works on the web but
   the scheduled alerts don't" (or vice versa).

---

## 1. Push notifications (FCM)

### 1.1 The mental model

A push needs **three artifacts that all name the SAME Firebase project**:

| Artifact | Where it lives | How to read its project id |
|---|---|---|
| `google-services.json` (app/client) | GitHub secret `GOOGLE_SERVICES_JSON` → baked into the APK by CI; and `mobile/app/google-services.json` for local builds | `project_info.project_id` |
| Admin service-account key (server) | Railway `FIREBASE_CREDENTIALS` (both services) | `project_id` field |
| Project id (server) | Railway `FIREBASE_PROJECT_ID` (both services) | the value itself |

The current project is **`rvms-28129`**, package **`com.example.rvms`**. A device token issued
under one project can only be sent to by that same project — a mismatch means every send is
refused while the in-app notification row still saves. **That is why "the Alerts screen updates
but no banner arrives" is the signature of a project mismatch, not a broken server.**

Delivery is dispatched **after the HTTP response** (`->afterResponse()` in
`App\Services\NotificationDispatcher`), so no queue worker is required for pushes triggered by a
web action. The stored `notifications` row is written **synchronously** and is the real
guarantee; the push is best-effort on top. A dropped push costs a banner, never a record.

### 1.2 First move for ANY push problem

```
php artisan rvms:fcm-doctor
```

It walks the whole path and stops at the first broken stage. Read these lines:

| Line | Meaning if it fails |
|---|---|
| `Transport in use` | If "SIMULATED" → `FIREBASE_*` vars are blank/mangled, or the code predates the credential-repair fix. |
| `google/auth` | Library missing → `composer install`. |
| `Cache store … usable` | The OAuth token is cached; with `CACHE_STORE=database`, a missing `cache` table breaks it → `php artisan migrate`. |
| `Service-account key … valid` + `project_id in key` | Prints the key's project and compares it to `FIREBASE_PROJECT_ID`. A mismatch is named outright. |
| `Reached Google and authenticated` | The decisive line. `yes` = the key mints tokens. Failure with `invalid_grant` = the key was **revoked or rotated** and the stored copy is stale. |
| `Registered devices` | **The phone-side tell.** "No user has a registered device token" = no phone has signed in on a Firebase-enabled APK; the server is fine. |

Then put a real banner on a phone (background the app first — a foreground app swallows it):
```
php artisan rvms:fcm-doctor --user=<driver email>
```

### 1.3 Symptom → cause → fix

- **Alerts screen updates, but no push banner.**
  - *App/server project mismatch* — verify all three artifacts read `rvms-28129` (§1.1). This
    was the original root cause: the first `google-services.json` and admin key were from two
    different projects.
  - *No registered device* — the installed APK was built **before Firebase was added** (before
    the 2026-09-03 `apk-latest`), or the driver hasn't signed in since. Fix: uninstall, install
    the current `apk-latest`, sign in (registers a token), grant the notification permission.
  - *Token was cleared* — resetting a user's password revokes their device token. They simply
    sign in again and it re-registers. Expected, not a bug.

- **`fcm-doctor` says `invalid_grant`.** The service-account key was rotated/revoked but Railway
  still holds the old one. Paste the new key (§1.4) into **both** services and `config:clear`.

- **`fcm-doctor` shows "Control character error" / the key won't parse.** Railway's raw variable
  editor rewrote the `\n` escapes inside the private key into real newlines, which is illegal
  inside a JSON string. The code repairs this automatically, **but the permanent fix is to store
  the key as base64** (§1.4) — nothing can mangle base64.

- **`cURL error 60`/`77` / "cannot verify Google's TLS certificate"** (usually local XAMPP on
  Windows, not Railway). PHP has no CA bundle. Download <https://curl.se/ca/cacert.pem>, set
  `curl.cainfo`/`openssl.cafile` in the php.ini this PHP actually loads, or point
  `FIREBASE_CA_BUNDLE` at it in `.env`, then `config:clear`. `fcm-doctor` prints which php.ini
  is in use when this happens.

- **Push works on web actions but scheduled PM/license alerts never arrive.** The `scheduler`
  service is missing the `FIREBASE_*` vars, or isn't running — see §4.

### 1.4 Rotating the Firebase admin key (safe order)

1. Firebase Console → Project settings → Service accounts → **Generate new private key**.
   (Keep the old one active for now.)
2. Base64-encode it on your own machine (never through Railway's editor):
   ```powershell
   [Convert]::ToBase64String([IO.File]::ReadAllBytes("C:\path\to\new-key.json")) | Set-Clipboard
   ```
3. Paste as `FIREBASE_CREDENTIALS` on **both** services. Leave `FIREBASE_PROJECT_ID=rvms-28129`.
4. Let the redeploy finish, then on the web service: `php artisan config:clear` and
   `php artisan rvms:fcm-doctor`. Confirm `Reached Google and authenticated: yes`. Repeat on the
   scheduler service.
5. **Only now** delete the old key in Google Cloud Console → IAM → Service Accounts → Keys.
6. Re-run `fcm-doctor` once more to confirm nothing broke when the old key went away.

> **Never commit the admin key.** It belongs in Railway (`FIREBASE_CREDENTIALS`) and one secure
> backup (password manager / private vault) — not in Downloads, not in the repo, not in a chat.
> `google-services.json` is *not* a secret (it ships in every APK) but is still gitignored at
> `mobile/app/google-services.json` and mirrored in the `GOOGLE_SERVICES_JSON` GitHub secret.

---

## 2. Accounts & passwords

- **`rvms:doctor` says "N account(s) still use the seeded demo password."** Every seeded account
  ships with the password `password`, which is an open door on a public site. Reset each:
  ```
  php artisan rvms:reset-password <email>
  ```
  It prompts for a new password (min 8 chars) and revokes that user's device tokens. Re-run
  `rvms:doctor` until the line is green. The check counts **all** users (admins and drivers),
  so drivers must be done too.

- **Find exactly which accounts are still on the seeded password:**
  ```
  php artisan tinker --execute='App\Models\User::all()->filter(fn($u)=>Illuminate\Support\Facades\Hash::check("password",$u->password))->each(fn($u)=>print($u->email.PHP_EOL));'
  ```
  Useful when the count doesn't match the seeder — e.g. a driver **self-registered through the
  app during testing** and won't appear in `UserSeeder.php`. The live database is the authority,
  not the seeder.

- **"No account found for X" but it's in the seeder.** The deployed database was seeded from an
  earlier state, or the account was edited during testing. Trust the database; list it with:
  ```
  php artisan tinker --execute='App\Models\User::orderBy("email")->get()->each(fn($u)=>print($u->email." | ".$u->role." | ".$u->status.PHP_EOL));'
  ```

- **A locked-out administrator.** Admins cannot reset each other in every case; the recovery
  path when nobody can sign in is the same console command:
  `php artisan rvms:reset-password <admin email>`. It needs no authentication precisely because
  it is only reachable by someone already at the server.

- **Records can never be deleted** (Scope decision). A junk test user is retired by setting
  `status = 'rejected'`, not by deleting it:
  ```
  php artisan tinker --execute='App\Models\User::where("email","<email>")->update(["status"=>"rejected"]);'
  ```
  (It still counts as a seeded password until you also reset it.)

---

## 3. File uploads (damage photos & repair receipts)

- **The app accepts up to 5 MB** (`max:5120`) for the damage photo and the repair/PM receipt.
  SVG is refused on every upload surface (XSS); allowed types are JPG/PNG/WEBP/HEIC/HEIF.

- **`rvms:doctor` reports `PHP upload limit is 0M`.** This reads the **CLI's** PHP config, which
  under FrankenPHP is **not** the web server's — so the number is not the real limit. `0M` means
  "the CLI has no value set," not "uploads are blocked." **You must measure with a real upload**,
  not trust the figure.

- **How to measure:** sign in on the phone, file a damage report with a **real 3–6 MB camera
  photo**, and read the outcome:

  | What happens | Meaning | Fix |
  |---|---|---|
  | Saves and the photo displays | Upload limit is fine | none |
  | "The photo must not be larger than 5 MB" | Laravel caught it — working as designed | none |
  | "The photo did not finish uploading…" | PHP `upload_max_filesize` too low | raise it (§3, below) |
  | **"Nature of damage is required"** on a filled-in form | `post_max_size` dropped the ENTIRE body before Laravel saw it | raise `post_max_size` |

  That last outcome is the sneaky one: it looks like a validation bug but is really the server
  discarding the whole request.

- **Raising the limits.** Limits are assembled at runtime by `deploy/start.sh` from
  `deploy/php.ini` (see §5 for why it's done there and not via `PHP_INI_SCAN_DIR`). Set
  `upload_max_filesize` to at least 8M and `post_max_size` higher still (it must exceed the file
  size plus the other form fields). **Caveat:** the web service is built by Railpack/FrankenPHP,
  which **ignores `deploy/start.sh`** — so confirm the change with a real upload on the web
  service, not just the doctor. If FrankenPHP's own limit is the binding one, it's set through the
  web service's runtime config, not this file.

- **Uploaded photo 404s.** `storage:link` wasn't run (or the volume replaced it). The web start
  script runs `php artisan storage:link` on boot; `rvms:doctor` reports the link's presence.

---

## 4. The scheduler & time-driven alerts

- **Two alert families.** Event-driven alerts (damage report, flagged inspection, status change,
  self-registration, password reset) fire from the web request itself — nothing extra needed.
  **Time-driven** alerts (license expiry, PM due) only fire if something periodically asks "is
  anything due now?" — that is the `scheduler` service running `php artisan schedule:work`.

- **License/PM alerts never arrive.** Confirm the `scheduler` service is running and its logs
  show `queue:work` firing every minute (and the correct Manila time). Without the scheduler,
  the licence/PM sweeps never run and the queue is never drained. On a non-platform server the
  equivalent is one cron entry: `* * * * * cd /path && php artisan schedule:run`.

- **Verify what's scheduled:** `php artisan schedule:list` — expect `rvms:recalculate-pm`
  (01:00), `rvms:pm-alerts` (01:15), `rvms:license-alerts` (06:00), and a per-minute
  `queue:work` drain.

- **Force a recompute for a demo** (don't wait for 01:00): `php artisan rvms:recalculate-pm`,
  then `php artisan rvms:pm-alerts` / `php artisan rvms:license-alerts`.

---

## 5. Railway / deployment gotchas (things that cost a day)

- **Two builders, and it matters.** The **web** service uses **Railpack (FrankenPHP + Caddy)**,
  which **ignores `nixpacks.toml` AND `deploy/start.sh`**. The **scheduler** uses **Nixpacks**,
  which reads them. If a config-file change "does nothing" on the web service, this is usually
  why — check which builder the service uses before believing any file-based explanation.

- **"Redeploy" re-deploys the SAME commit.** Only a new push to `main` builds current code.
  If a code fix "isn't deployed," push a commit (or confirm auto-deploy is on) rather than
  hitting Redeploy.

- **Railway's raw variable editor reformats values** — it escapes quotes and expands `\n` into
  real newlines. For any JSON-or-key value, prefer **base64** (§1.4), or use the single-variable
  field carefully.

- **`PHP_INI_SCAN_DIR` unloads every PHP extension on this image** (leading colon or not):
  ~45 extensions drop to ~12, so `pdo_mysql` and `DOMDocument` vanish and the app won't boot.
  That's why upload limits are assembled at runtime in `deploy/start.sh` with a fallback to PHP's
  defaults, rather than by pointing `PHP_INI_SCAN_DIR` at `deploy/php.ini`.

- **Nixpacks re-copies the source over `/app` after the install phase**, so anything written
  into `/app` *during* the build does not survive to runtime — configuration must happen in the
  running container.

- **Mobile server URL must include `https://`.** A bare hostname defaults to `http`, which is
  wrong for Railway. The address is runtime-configurable in the app (`ServerUrlStore`), so one
  APK covers deployed / hotspot / USB. For USB testing: `adb reverse tcp:8000 tcp:8000` and use
  `http://127.0.0.1:8000`.

---

## 6. Local development gotchas

- **`migrate` vs `migrate:fresh`.** `php artisan migrate` applies new migrations and **keeps all
  data** — the routine command. `php artisan migrate:fresh --seed` **DROPS EVERY TABLE** and
  reseeds; it erases every account/record you created by hand. Use `fresh` only for a deliberate
  clean slate.

- **Wrong dates on records before 8:00 AM.** `APP_TIMEZONE` must be `Asia/Manila`. Under the UTC
  default, anything recorded before 8 AM Manila is filed on the previous day. `.env.example`
  already ships Manila; if an older `.env` was copied, set it and `php artisan config:clear`.

- **The mobile app's `SampleData` mock layer is being retired** (R10). If a screen shows fake
  data, confirm it's wired to the repository/API, not `SampleData`.

---

## 7. The one-command health check

Before any demo or handover, on the **web** service:
```
php artisan rvms:doctor        # config, schema, storage, upload, scheduler, push, passwords
php artisan rvms:fcm-doctor    # deeper push check; --user=<email> for a live banner
```
And on the **scheduler** service, the same two — because its variables are separate. Everything
green on both = ready.
