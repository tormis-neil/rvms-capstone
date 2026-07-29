# FCM push delivery — setup and troubleshooting (FR-21, phase R7)

Notification **rows** (the bell, the notifications page, the mobile inbox) are written
synchronously and never depend on Firebase. Only the **push banner** on the driver's phone goes
through Google. So a broken push never breaks a review, a dispatch, or a status change — it just
means the phone stays quiet.

## The one command

```bash
php artisan rvms:fcm-doctor
```

It walks the whole path in order and stops at the first stage that breaks, with the fix for that
stage:

1. **Configuration** — is the app even trying to send real pushes, or silently simulating them?
2. **Library** — is `google/auth` installed, and are the `curl` / `openssl` extensions loaded?
3. **Cache** — the OAuth token is cached, so an unusable cache store fails every push.
4. **Service-account key** — readable, valid JSON, a real service-account key, private key parses,
   and its `project_id` matches `FIREBASE_PROJECT_ID`.
5. **Google** — mints a real access token and calls FCM.
6. **Devices** — which users have a registered handset.

To put a real banner on a real phone:

```bash
php artisan rvms:fcm-doctor --user=ramon.villanueva@rvms.local
```

## Configuration

In `backend/.env`:

```
FIREBASE_PROJECT_ID=rvms-28129
FIREBASE_CREDENTIALS=storage/app/firebase.json
FIREBASE_CA_BUNDLE=
```

Keep both paths **relative to `backend/`**. An absolute Windows path breaks dotenv twice over: left
unquoted, the spaces in a folder name end the value early; wrapped in double quotes, the backslashes
are read as escape sequences (`\r` in `\rvms` becomes a carriage return). Single quotes are the
escape hatch if you genuinely need an absolute path — `FIREBASE_CA_BUNDLE='C:\path\to\cacert.pem'`.

`storage/app/firebase.json` is the service-account key from Firebase console → Project settings →
Service accounts → Generate new private key. **It is a private key — it is gitignored and must
never be committed.**

Run `php artisan config:clear` after any `.env` edit. If `bootstrap/cache/config.php` exists, the
compiled config wins and your edit is ignored until you clear it — the doctor warns about this.

Leaving both values blank is the normal local state: pushes are written to `storage/logs/laravel.log`
instead of being sent, and every trigger still works.

## Reading a queue-worker failure

`php artisan queue:work` reports a failed push as `FAIL` and a duration, which tells you nothing.
The reason is in `storage/logs/laravel.log` and in the `failed_jobs` table:

```bash
php artisan queue:failed
```

Failures are now split in two:

- **`FcmConfigurationException`** — nothing a retry can fix (bad key, wrong project, API switched
  off, no CA bundle). Logged once, plainly, and the job fails immediately instead of burning three
  attempts on the same wall.
- **Everything else** (429, 5xx, a dropped connection) — genuinely transient, retried three times
  with backoff.

### The failures we have actually hit

| Symptom | Cause | Fix |
|---|---|---|
| Job fails in 7–48 ms, `Class "Google\Auth\...ServiceAccountCredentials" not found` | `vendor/` predates the commit that added `google/auth` | `composer install` in `backend/`, then restart the worker |
| `cURL error 60: SSL certificate problem` | PHP on Windows ships with no CA bundle | Download <https://curl.se/ca/cacert.pem> into `storage/app/` and set `FIREBASE_CA_BUNDLE=storage/app/cacert.pem`. Or set `curl.cainfo` **and** `openssl.cafile` to its full path in php.ini — in the php.ini the **CLI** loads, which `rvms:fcm-doctor` names for you and which is often NOT XAMPP's |
| `cURL error 77: error setting certificate file` | A CA bundle is configured at a path holding nothing | Fix the path, or download the bundle to exactly that path |
| `403 PERMISSION_DENIED` | The "Firebase Cloud Messaging API" is not enabled for the project, or the key belongs to another project | Enable it in the Google Cloud console for that project, or download the right key |
| `invalid_grant` | The server clock is minutes out of step, or the key was revoked | Fix the system clock, or generate a new key |
| `401 UNAUTHENTICATED` | The cached access token was refused | Handled automatically: the cache is dropped and the retry mints a new one |
| One alert type never arrives while others do | Its data payload uses an FCM-reserved key (`from`, `to`, `notification`, `message_type`, anything starting with `google`/`gcm`). Google rejects that message type and only that one | Rename the key. `FcmMessage` now refuses reserved keys before sending, naming the offender |
| `404 UNREGISTERED` / `SENDER_ID_MISMATCH` | The handset uninstalled, or its token came from a different Firebase project | Not an error. The stale token is cleared; sign in on the phone again to register a fresh one |

## The job went DONE but no banner appeared

That is device-side, not server-side:

- **Android 13+** requires the runtime notification permission — check it is granted for the app.
- **Background the app first.** A foreground app receives the message in code rather than as a
  banner.
- The APK must be built against the **same Firebase project** as the service-account key.
- Battery optimisation / Doze can hold back a push on some handsets.
- A reinstall issues a **new** token — sign in again so the app re-registers via
  `POST /api/v1/fcm-token`.

## Running the delivery path at all

Pushes are queued, so something must drain the queue:

```bash
php artisan queue:work
```

and time-driven alerts (`rvms:pm-alerts`, `rvms:license-alerts`) need the scheduler — one cron entry
in deployment, or `php artisan schedule:work` in a spare terminal for a demo:

```
* * * * * cd /path/to/rvms/backend && php artisan schedule:run >> /dev/null 2>&1
```
