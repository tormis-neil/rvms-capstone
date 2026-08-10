# RVMS — Driver Mobile App

Android driver application for the Rescue Vehicle Management System. Built with Kotlin +
Jetpack Compose following the "Structured Authority" design theme (navy structure, light
surfaces, gold accent).

The app runs on **live data from the Laravel backend** — every screen reads and writes through
the REST API at `/api/v1` with a Sanctum bearer token. The backend must be running for the app
to do anything; there is no offline mode, and the app says so plainly rather than showing
stale or empty data.

## Requirements

- Android Studio Meerkat (2024.3.1)+
- JDK 21
- Android SDK — `compileSdk` 36, `minSdk` 26 (Android 8.0+)

## Build & Run

1. Open the `mobile/` folder in Android Studio (it contains the Gradle project).
2. Let Gradle sync.
3. Run the `app` configuration on a device or emulator.

CLI alternative (with a local Android SDK configured):

```
cd mobile
./gradlew assembleDebug      # build APK -> app/build/outputs/apk/debug/
./gradlew installDebug       # install on a connected device/emulator
./gradlew test               # unit tests
```

**`ERROR: JAVA_HOME is set to an invalid directory`** — Gradle is pointed at a
JDK that is not there, usually because Android Studio was installed to a
different folder than the one the variable names. Point it at the JDK that ships
inside your own Android Studio install and re-run, e.g.:

```powershell
$env:JAVA_HOME = "C:\Program Files\Android\Android Studio1\jbr"
```

That lasts for the open terminal only. To make it permanent: Windows key →
`environment variables` → Environment Variables → New, `JAVA_HOME`, with the
path to the `jbr` folder.

**No Android Studio at all?** GitHub Actions builds the APK on every push to
`main` (`.github/workflows/android-build.yml`) and publishes it as a direct
download under the `apk-latest` release. Nothing needs to be installed locally.

## Connecting to the backend

**The server address is chosen in the app, not compiled into it** (2026-08).
At the bottom of the sign-in screen a line reads `Server: …` — tap it and type
an address. It is saved, so it survives restarts.

```
deployed    https://your-site
hotspot     192.168.137.1:8000     (the laptop's own Wi-Fi)
USB cable   127.0.0.1:8000         (with adb reverse tcp:8000 tcp:8000)
```

Type the origin only — the app adds `/api/v1/`, and strips it if you paste it
anyway. A missing `http://` is added for you. **Reset** in the same dialog goes
back to the USB-cable default, which is what a fresh install starts on.

`BASE_URL` in `data/remote/ApiClient.kt` is now only a placeholder Retrofit
needs at build time; `ServerUrlInterceptor` rewrites the scheme, host and port
on every request from `ServerUrlStore`. Changing that constant no longer changes
where the app talks to.

**Real phone over USB (the method used in testing).** No Wi-Fi, no IP address, no firewall
changes: `adb reverse` forwards the phone's own `127.0.0.1:8000` through the cable to the
laptop.

1. On the phone: enable **Developer Options** (tap Build Number 7 times) → **USB Debugging**.
   Plug in and tap **Allow**.
2. On the laptop: `php artisan serve --port=8000` in `backend/`.
3. Once per session: `adb reverse tcp:8000 tcp:8000` (check with `adb devices`).
4. Leave `BASE_URL` at `http://127.0.0.1:8000/api/v1/` and run the app.

**Emulator:** change `BASE_URL` to `http://10.0.2.2:8000/api/v1/` — the emulator's alias for
the host's localhost. No `adb reverse` needed. Push notifications require an emulator image
**with Google Play**.

**Same Wi-Fi:** serve with `--host=0.0.0.0`, point `BASE_URL` at the laptop's IPv4 address, and
allow port 8000 through the firewall.

Cleartext traffic is permitted to `127.0.0.1` and `10.0.2.2` only, via
`network_security_config` — a development affordance, not a production setting.

Sign in with a seeded driver, e.g. `ramon.villanueva@rvms.local` / `password`. A driver who
self-registers from the Sign Up screen starts **pending** and cannot sign in until their agency
administrator approves them on the web dashboard (FR-03).

## Structure

```
app/src/main/java/com/example/rvms/
├── data/
│   ├── remote/          Retrofit ApiService, ApiClient, AuthInterceptor, error mapping, DTOs
│   ├── *Repository.kt   Auth, Vehicle, Inspection, Damage, Notification
│   ├── SessionManager   The signed-in driver, backed by /me + the token store
│   ├── TokenStore       DataStore-backed token persistence
│   ├── FetchResult      Success / Failure — how "the server said none" is kept distinct
│   │                    from "the server could not be reached"
│   └── ServiceLocator   Wiring
├── push/                FCM device-token registration + the messaging service
├── ui/                  Compose screens (auth, home, vehicle, inspection, damage,
│                        notification, profile, shell, splash) + shared UI in ui/common
└── theme/               Colors, typography
```

## Driver workflows

- **Splash → Sign In**: a saved token routes straight into the app; otherwise Sign In. Sign In
  surfaces the API's own refusal reasons (bad credentials, pending approval, rejected).
- **Home**: agency logo, greeting, the driver's **assigned vehicle** with its live four-value
  status badge, **License Status**, and recent inspections. Pull to refresh; it also refreshes
  every time the app returns to the foreground, so a status change announced by a push is
  already applied when the driver opens the app.
  **Quick Actions** carries two cards, not three (2026-08). The first reports whether today's
  inspection has been **submitted or is still outstanding** — the question a driver actually
  opens the app to answer, and one the bottom nav can never answer. The third card was dropped
  because the Assigned Vehicle card above it already navigates to the same screen.
- **Vehicle Info**: every vehicle assigned to this driver — a driver may be the primary driver
  of more than one.
- **BLOWBAGETS Inspection** (Inspect tab): the checklist is built from the live catalog, so a
  **BFP driver gets 14 items** (the standard 12 plus Hydraulic System and Fire Pump) and other
  agencies get 12. Each item is marked **OK** or **Has Issue**; a flagged item **requires
  remarks**, blocked client-side to match the API's 422. History and per-item detail come from
  the driver's own real submissions.
- **Damage Report** (Damage tab): vehicle details auto-filled, nature of damage required, photo
  optional, submitted as multipart. History shows each report's real review status.
- **Alerts**: the driver's notification inbox with an unread badge on the bottom nav; tap to
  mark read, or mark all read. Push notifications arrive via FCM even when the app is closed.
- **Profile**: the driver's own details and licence status; self-edit name, email, and password
  (FR-04). Leaving the password blank leaves it unchanged.

## Offline behaviour

Every repository returns a `FetchResult`, so a failed call is never mistaken for an empty
result. A driver in the field with no signal sees **"Cannot reach the server"** with a retry —
not "No Vehicle Assigned", which would be a confident wrong answer. Records already on screen
stay there; only the banner is added.

## Tests

```bash
./gradlew test
```

Covers DTO serialization, the auth interceptor attaching the bearer token, each repository's
success/failure mapping (including 401/403/422), `SessionManager`'s offline behaviour, the
licence-state calculation shared with the dashboard, and formatting helpers.
