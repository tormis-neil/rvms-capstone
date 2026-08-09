# Demo Runbook — TIER 3: USB Cable (Last Fallback)

> **Read this if:** there is no internet, no usable WiFi, or the deployed site is
> down — and the demo still has to happen.
>
> **Why this tier is the floor:** it uses **no network at all**. Not the venue's
> WiFi, not mobile data, not the internet. The phone talks to the laptop through
> the USB cable and nothing else. Nothing the venue does can break it.
>
> **The trade:** the phone is physically tied to the laptop by a cable, so
> whoever holds it cannot walk around. Everything else works normally.
>
> **Follow every step in order.** Each one says what to type and what you should
> see. If what you see does not match, stop and go to
> [Troubleshooting](#troubleshooting) — do not carry on and hope.

---

## PART 1 — What you need

Tick all of these before demo day.

- [ ] Laptop with the RVMS project on it
- [ ] Android phone with the RVMS app installed
- [ ] **USB cable that carries data** — see the warning below
- [ ] The phone's screen unlock code
- [ ] This document, printed or on a second screen

> ⚠️ **The cable is the most common cause of failure.** Many charging cables
> carry power only and no data. A power-only cable will charge the phone
> perfectly and still be completely invisible to the laptop. **Use the cable that
> came with the phone**, and test it once using Part 3 below. If `adb devices`
> shows nothing, suspect the cable first.

---

## PART 2 — One-time setup

Do this **once**, days before the demo — never on demo day.

### 2.1 Turn on Developer Options (on the phone)

1. Open **Settings**
2. Go to **About Phone**
3. Find **Build Number** and **tap it 7 times**
4. Enter your unlock code if asked
5. You should see: *"You are now a developer!"*

### 2.2 Turn on USB Debugging (on the phone)

1. Open **Settings**
2. Go to **Developer Options** — usually under *System* or *Additional Settings*
3. Switch on **USB Debugging**
4. Confirm on the warning pop-up

### 2.3 Install `adb` on the laptop

`adb` is the small program that lets the laptop talk to the phone through the
cable.

**If the laptop has Android Studio:** `adb` is already installed. Skip to 2.4.

**If it does not** (this applies to the personally-owned laptop after the
borrowed one is returned):

1. Go to: **https://developer.android.com/tools/releases/platform-tools**
2. Download **SDK Platform-Tools for Windows**
3. Unzip it to exactly this folder: `C:\platform-tools`
4. Add it to Windows' PATH so the command works from anywhere:
   - Press the **Windows key**, type `environment variables`, press Enter
   - Click **Environment Variables…**
   - Under *System variables*, select **Path**, click **Edit**
   - Click **New**, type `C:\platform-tools`, click **OK** on all windows
5. **Close every open Command Prompt / PowerShell window** — PATH changes only
   apply to new ones.

### 2.4 Test the connection now, not on demo day

1. Plug the phone into the laptop
2. On the phone, a pop-up asks *"Allow USB debugging?"* — tick **Always allow
   from this computer**, then tap **Allow**
3. On the laptop, open **Command Prompt** and type:

```
adb devices
```

**You should see:**

```
List of devices attached
R58M12ABCDE     device
```

The long code will differ — that's your phone's serial number. What matters is
the word **`device`** beside it.

✅ If you see `device`, setup is complete.
❌ Anything else — go to [Troubleshooting](#troubleshooting) now, while there is
time to fix it.

---

## PART 3 — Demo day setup

**Start 20 minutes before the demo.** It takes about 5 minutes; the rest is
your safety margin.

You will open **three windows**. Keep all three open for the whole demo.

---

### STEP 1 — Check the database is running

Open **Command Prompt** and type:

```
sc query MySQL80
```

**You should see:** `STATE : 4 RUNNING`

If it says `STOPPED`, type this (as Administrator):

```
net start MySQL80
```

> MySQL starts automatically with Windows, so this is usually just a check.

---

### STEP 2 — Start the website  🪟 **Window 1**

Open **PowerShell** and type these two lines:

```powershell
cd "C:\rescue vehicle management system\rvms\backend"
php artisan serve
```

**You should see:**

```
   INFO  Server running on [http://127.0.0.1:8000].

  Press Ctrl+C to stop the server
```

⚠️ **Leave this window open.** Closing it shuts down the whole system.

---

### STEP 3 — Start the alerts  🪟 **Window 2**

Open a **second** PowerShell window and type:

```powershell
cd "C:\rescue vehicle management system\rvms\backend"
php artisan schedule:work
```

**You should see:** `INFO  Running scheduled tasks every minute.`

⚠️ **Leave this window open too.**

> **What this does:** it makes the automatic alerts work — preventive
> maintenance reminders and licence expiry warnings. Without it those never
> fire, and part of the demo goes quiet.

---

### STEP 4 — Connect the phone  🪟 **Window 3**

1. Plug the phone into the laptop with the data cable
2. Unlock the phone screen
3. If *"Allow USB debugging?"* appears, tap **Allow**
4. Open a **third** PowerShell window and type:

```powershell
adb devices
```

**You should see:** your phone listed with the word **`device`**.

5. Now type:

```powershell
adb reverse tcp:8000 tcp:8000
```

**You should see:** `8000` — just the number, on its own line. That is success.

> **What this does, in plain words:** it tells the phone *"whenever you look for
> a server on yourself, look at the laptop through this cable instead."* This is
> the trick that makes the whole tier work.

⚠️ **This resets if the cable is unplugged.** Unplug and re-plug at any point →
run `adb reverse tcp:8000 tcp:8000` again. Nothing else needs redoing.

---

### STEP 5 — Log in on the laptop (the admin)

1. Open a browser
2. Go to: **http://127.0.0.1:8000**
3. Log in:
   - Email: `bfp.admin@rvms.local`
   - Password: `password`

**You should see:** the dashboard, with the blue sidebar and "Bureau of Fire
Protection" in the top bar.

---

### STEP 6 — Log in on the phone (the driver)

1. Open the **RVMS** app
2. Log in:
   - Email: `ramon.villanueva@rvms.local`
   - Password: `password`

**You should see:** the home screen with a greeting and the assigned vehicle
card.

✅ **If both logins worked, you are ready. Do not touch anything else.**

---

## PART 4 — Final check before people arrive

Run this one test. It proves the cable, the server and the database are all
working together:

1. On the **laptop**: go to **Vehicles**, click the circular-arrows button on the
   driver's vehicle, set it to **Not Operational**, confirm
2. On the **phone**: pull down to refresh the home screen

**You should see:** the vehicle's status on the phone now reads **Not
Operational**.

✅ That one test proves the whole chain works.

Then set it back to **Operational** so the demo starts clean.

---

## PART 5 — What works and what does not in this tier

Say this out loud during the demo if it comes up — it is a limitation of the
room, not of the system.

| Feature | Works? |
|---|---|
| Web dashboard, all pages | ✅ Yes |
| Mobile app, all screens | ✅ Yes |
| Inspections, damage reports, photos | ✅ Yes |
| Dispatch, repairs, preventive maintenance | ✅ Yes |
| Reports and printing | ✅ Yes |
| Alerts inbox on the phone | ✅ Yes |
| **Pop-up push notifications** | ⚠️ **Only if the phone has mobile data** |

> **About push notifications.** These come from Google's servers, so the *phone*
> needs internet — the cable cannot supply it. **Leave mobile data switched on**
> and pushes will still arrive normally.
>
> If there is no mobile data either, the pop-up banners will not appear, but
> **every alert still lands in the app's Alerts inbox**, because that is stored
> in your own database. Open Alerts and show the notification there instead.
> Nothing is lost — the alert exists, it just does not buzz.

---

## PART 6 — Packing up

1. On the phone: sign out of the app (optional)
2. Close **Window 1** (the website) with **Ctrl + C**
3. Close **Window 2** (the alerts) with **Ctrl + C**
4. Unplug the phone

MySQL can stay running — it does no harm.

---

<a name="troubleshooting"></a>
## TROUBLESHOOTING

Find your symptom. Each fix is in order of likelihood — try the first one first.

---

### ❌ `adb` is not recognized as an internal or external command

**Meaning:** Windows cannot find the `adb` program.

1. Did you **close and reopen** the Command Prompt after editing PATH? PATH
   changes only apply to new windows. → Close every window and open a fresh one.
2. Check the folder really exists: open `C:\platform-tools` and confirm
   `adb.exe` is inside it.
3. Quick workaround that always works — run it by full path:
   ```
   C:\platform-tools\adb.exe devices
   C:\platform-tools\adb.exe reverse tcp:8000 tcp:8000
   ```
4. On the borrowed laptop with Android Studio, `adb` lives at:
   ```
   %LOCALAPPDATA%\Android\Sdk\platform-tools\adb.exe
   ```

---

### ❌ `adb devices` shows nothing under "List of devices attached"

The laptop cannot see the phone at all.

1. **Try a different cable.** This is the most common cause — the cable is
   charge-only. Use the one that came with the phone.
2. Try a **different USB port** on the laptop.
3. **Unlock the phone screen.** A locked phone often will not appear.
4. Check **USB Debugging** is still on (Settings → Developer Options). Some
   phones switch it off after a system update.
5. On the phone, pull down the notification shade, tap the **USB** notification,
   and change the mode to **File Transfer / MTP**. "Charging only" can hide the
   device.
6. Restart the connection:
   ```
   adb kill-server
   adb start-server
   adb devices
   ```

---

### ❌ `adb devices` shows `unauthorized`

The phone has not been given permission.

1. Look at the phone's screen — there is an **"Allow USB debugging?"** pop-up
   waiting. Tick *Always allow* and tap **Allow**.
2. No pop-up? Unplug, re-plug, and watch the screen.
3. Still nothing — revoke and retry: Settings → Developer Options → **Revoke USB
   debugging authorisations**, then unplug and re-plug.

---

### ❌ `adb devices` shows `offline`

1. Unplug and re-plug the cable.
2. ```
   adb kill-server
   adb start-server
   ```
3. Restart the phone if it persists.

---

### ❌ The app says "Cannot reach the server"

Work through these in order — the fix is almost always number 1 or 2.

1. **Is Window 1 still open** and showing `Server running on
   [http://127.0.0.1:8000]`? If it closed, redo STEP 2.
2. **Re-run the reverse command.** It resets whenever the cable is unplugged:
   ```
   adb reverse tcp:8000 tcp:8000
   ```
   You should see `8000`.
3. Confirm the phone is still connected: `adb devices` → must say `device`.
4. Check the reverse rule is registered:
   ```
   adb reverse --list
   ```
   You should see a line containing `tcp:8000 tcp:8000`. If empty, redo step 2.
5. On the laptop's browser, open **http://127.0.0.1:8000** — if that fails too,
   the problem is the server, not the phone. Redo STEP 2.

---

### ❌ `php artisan serve` says "Address already in use" / "Failed to listen"

Another copy of the server is already running.

1. You may already have Window 1 open from earlier — check your other windows,
   and use that one.
2. Otherwise close it and start again:
   ```
   taskkill /F /IM php.exe
   ```
   then redo STEP 2.

---

### ❌ "SQLSTATE[HY000] [2002]" or "Connection refused"

The database is not running.

1. Open Command Prompt **as Administrator**:
   ```
   net start MySQL80
   ```
2. Confirm: `sc query MySQL80` → `STATE : 4 RUNNING`
3. If it refuses to start, read the error file at
   `C:\ProgramData\MySQL\MySQL Server 8.0\Data\` — the file ending `.err`, last
   20 lines.

> **Do not start XAMPP's MySQL.** The system does not use it. Starting it can
> take the port MySQL 8 needs.

---

### ❌ Login fails with "These credentials do not match our records"

1. Check the spelling: `bfp.admin@rvms.local`, password `password` (all
   lowercase).
2. Did someone change that password during testing? Reset it:
   ```powershell
   php artisan rvms:reset-password bfp.admin@rvms.local
   ```
3. If the database looks empty, re-seed it — ⚠️ **this erases all current
   records**:
   ```powershell
   php artisan migrate:fresh --seed
   ```

---

### ❌ Login is refused with "use the mobile app"

You are logging into the **website** with a **driver** account. The website is
for administrators only.

- Website → `bfp.admin@rvms.local`
- Phone app → `ramon.villanueva@rvms.local`

---

### ❌ Damage report photos do not display

The public storage link is missing.

```powershell
php artisan storage:link
```

Then refresh the page.

---

### ❌ Push notifications do not pop up on the phone

Expected in this tier unless the phone has internet — see **Part 5**.

1. Switch **mobile data on**.
2. Check notifications are allowed: Settings → Apps → RVMS → Notifications.
3. Sign out and back in on the phone — this re-registers the device.
4. If there is no internet at all, open the **Alerts** tab instead. Every alert
   is there, because it is stored in your own database. Say so out loud: *"the
   alert is delivered and stored; the pop-up needs internet, which this room
   does not have."*

---

### ❌ Preventive maintenance / licence alerts never appear

**Window 2 is not running.** Redo STEP 3.

To trigger them immediately rather than waiting:

```powershell
php artisan rvms:license-alerts
php artisan rvms:recalculate-pm
php artisan rvms:pm-alerts
```

---

### ❌ Something is badly wrong and there is no time to diagnose

Full reset, about 60 seconds. ⚠️ **Erases all records and restores the sample
data.**

```powershell
php artisan migrate:fresh --seed
php artisan rvms:doctor
```

`rvms:doctor` should end with `2 warning(s)` and **no failures**. Then redo
STEP 5 and STEP 6.

---

## ONE-PAGE SUMMARY

Print this part.

```
BEFORE:   phone unlocked · data cable · MySQL running

WINDOW 1  cd "C:\rescue vehicle management system\rvms\backend"
          php artisan serve                  → "Server running on ...:8000"

WINDOW 2  cd "C:\rescue vehicle management system\rvms\backend"
          php artisan schedule:work          → "Running scheduled tasks..."

WINDOW 3  adb devices                        → your phone, saying "device"
          adb reverse tcp:8000 tcp:8000      → "8000"

LAPTOP    http://127.0.0.1:8000
          bfp.admin@rvms.local / password

PHONE     open RVMS app
          ramon.villanueva@rvms.local / password

TEST      change a vehicle status on the laptop
          → refresh the phone → the status changed

IF THE APP LOSES THE SERVER:
          adb reverse tcp:8000 tcp:8000      ← fixes it 90% of the time
```

---

## Notes

- Tiers 1 (deployed) and 2 (laptop hotspot) are documented separately. This tier
  needs no internet and no code changes — it works with the app exactly as it is
  today, which is why it is the fallback that cannot be taken away.
- **This document only covers getting the system running.** Once both logins
  work, go to **`rvms-demo-script.md`** for what to actually show — the running
  order, who does what, and the expected result of every step. That script is
  shared by all three tiers; the only things that differ here are that the phone
  is tethered by the cable, and that push banners need the phone's mobile data.
