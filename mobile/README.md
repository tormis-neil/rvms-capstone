# RVMS — Driver Mobile App (Prototype)

Android driver application for the Rescue Vehicle Management System. Built with
Kotlin + Jetpack Compose following the "Structured Authority" design theme
(navy structure, light surfaces, gold accent). This is a **frontend-only
prototype** — all data is static sample data (no backend), per the prototype plan.

## Requirements
- Android Studio Meerkat (2024.3.1)+
- JDK 21
- Android SDK (compileSdk 36, minSdk 24)

## Build & Run
1. Open the `mobile/` folder in Android Studio (it contains the Gradle project).
2. Let Gradle sync.
3. Run the `app` configuration on an emulator (Android 8.0+) or a physical device.

CLI alternative (with a local Android SDK configured):
```
cd mobile
./gradlew assembleDebug      # build APK -> app/build/outputs/apk/debug/
./gradlew installDebug       # install on a connected device/emulator
```

## What to test (driver workflows)
- **Splash → Sign In**: auto-advances after 2s. Sign In now validates email/password
  (try empty fields and a value without "@"). Sign Up validates all fields,
  agency selection, password length, and password match.
- **Home**: shows the signed-in driver's agency logo and assigned vehicle (static),
  including when it was last inspected. Tapping the vehicle card or the "Vehicle
  Info" quick action opens the Vehicle Information screen directly. Pull down to
  refresh (simulated).
- **BLOWBAGETS Inspection** (Inspect tab): if no inspection was submitted today,
  "Start New Inspection" opens the checklist form. If one was already submitted,
  the tab shows today's result with a "View Today's Inspection" button and a
  "Submit Another Inspection" option behind a confirmation warning. On the form,
  items carry their BLOWBAGETS acronym letter, the progress bar and submit button
  stay pinned at the bottom, and each item is marked **OK** or **Has Issue**
  (48dp touch targets); flagged items require **Remarks**. BFP vehicles include
  the 2 extra items (Hydraulic System, Fire Pump). Submit is blocked until all
  items are marked and all flagged items have remarks, then a confirmation lists
  any flagged items. Tap any history entry to view its full per-item results
  and remarks.
- **Damage Report** (Damage tab): vehicle info is read-only/auto-filled. Nature of
  Damage is required; photo attachment is an optional mock toggle. Submit shows a
  "Pending" confirmation and resets the form. History badges use dedicated report
  colors (slate Pending, navy Reviewed) so the vehicle status palette keeps a
  single meaning.
- **Alerts**: unread count badge on the bottom-nav bell; pull down to refresh
  (simulated).
- **Profile**: driver details + agency logo badge; Sign Out returns to Sign In.

## Per-agency demo data
On the **Sign In** screen, the "Sign in as" chips (BFP / PNP / CDRRMO / CHO)
choose which agency account to enter. The whole app then reflects that agency:
driver, assigned vehicle, logo, inspection history, damage reports, notifications,
and recent activity. Each account is self-contained and exercises the full range
of states:
- **Current vehicle status differs per agency** — BFP Operational (green),
  PNP Dispatched (blue), CDRRMO Under Preventive Maintenance (orange),
  CHO Not Operational (red).
- Inspection history mixes *All OK* and *Has Issues* results. BFP, CDRRMO, and
  CHO have already inspected today; **PNP has not**, so it demos the
  "Start New Inspection" state.
- Damage reports include both *Pending* and *Reviewed*.
- Notifications/recent activity walk the vehicle through all four statuses and
  both driver notification types (PM Reminder, Vehicle Status Update).
- PNP's driver (Mark Santos) has an **expiring license**, shown on Profile.

All data lives in `data/SampleData.kt`; the signed-in account is held in
`data/Session.kt`. To switch agencies, Sign Out and pick a different chip.
