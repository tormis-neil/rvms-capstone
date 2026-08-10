# Demo Runbook — TIER 2: Laptop Wi-Fi Hotspot (Fallback)

> **Read this if:** the deployed site is unreachable — no venue internet, the
> host is down, or the Wi-Fi will not let you on — but you still want the phone
> untethered.
>
> **Why this tier works when the venue does not:** your laptop *is* the network.
> It creates its own Wi-Fi, the phone joins it, and nothing else is involved. No
> venue Wi-Fi password, no internet, no IT desk.
>
> **The trade:** the phone must be told your laptop's address once, and push
> notification banners need the phone's mobile data (see [Part 6](#push)).
>
> **Follow every step in order.** Each says what to type and what you should
> see. If it does not match, go to [Troubleshooting](#troubleshooting) — do not
> carry on and hope.

---

## PART 1 — What you need

- [ ] Laptop with the RVMS project, and Wi-Fi
- [ ] Android phone with the RVMS app installed
- [ ] The phone's mobile data working *(only for push banners — everything else works without it)*
- [ ] This document

**No cable needed.** No internet needed.

---

## PART 2 — One-time setup: the laptop's hotspot

Do this **once**, days before the demo.

1. Press the **Windows key**, type `mobile hotspot`, press Enter
2. Set **Share my Internet connection from**: Wi-Fi *(or anything — it does not
   matter, because we are not sharing internet)*
3. Click **Edit** and set a network name and password you can type on a phone.
   Something short: `RVMS-DEMO` / `rvmsdemo123`
4. Turn **Mobile hotspot** **On**

> **"It says it cannot set up the hotspot."** Some laptops refuse when there is
> no internet connection to share. If so, use the alternative in
> [Troubleshooting](#no-hotspot) — an ordinary Wi-Fi network works just as well,
> you simply depend on the venue for it.

---

## PART 3 — Demo day setup

**Start 20 minutes before.** It takes about 7 minutes.

You will open **two windows** and keep them open.

---

### STEP 1 — Turn on the hotspot

Windows key → type `mobile hotspot` → switch it **On**.

**You should see:** *Mobile hotspot: On*, with the network name you set.

---

### STEP 2 — Find your laptop's address  🔎 **write this down**

Open **PowerShell** and type:

```powershell
ipconfig
```

Look for the section named something like **Wireless LAN adapter Local Area
Connection\* 2** — the one belonging to the hotspot. Read its **IPv4 Address**.

**You should see** something like:

```
   IPv4 Address. . . . . . . . . . . : 192.168.137.1
```

> 📝 **Write it down.** The Windows hotspot address is almost always
> **192.168.137.1**. You will type it into the phone in Step 6.

If several sections have an IPv4 address, the hotspot one usually starts
`192.168.137.` — see [Troubleshooting](#which-ip) if unsure.

---

### STEP 3 — Check the database is running

```
sc query MySQL80
```

**You should see:** `STATE : 4 RUNNING`. If it says STOPPED, run
`net start MySQL80` as Administrator.

---

### STEP 4 — Start the website, open to the network  🪟 **Window 1**

⚠️ **This command is different from Tier 3.** The `--host=0.0.0.0` part is what
lets the phone reach it; without it the server only answers itself.

```powershell
cd "C:\rescue vehicle management system\rvms\backend"
php artisan serve --host=0.0.0.0 --port=8000
```

**You should see:**

```
   INFO  Server running on [http://0.0.0.0:8000].
```

⚠️ Leave this window open for the whole demo.

**Windows will probably show a firewall pop-up the first time.** Tick **Private
networks** and click **Allow access**. If you miss it, see
[Troubleshooting](#firewall).

---

### STEP 5 — Start the alerts  🪟 **Window 2**

```powershell
cd "C:\rescue vehicle management system\rvms\backend"
php artisan schedule:work
```

**You should see:** `INFO  Running scheduled tasks every minute.`

⚠️ Leave this window open too.

---

### STEP 6 — Point the phone at the laptop

1. On the phone: **Settings → Wi-Fi** → connect to your hotspot (`RVMS-DEMO`)
2. Android may warn *"This network has no internet access"* — tap **Keep
   connection** / **Stay connected**. That warning is expected and correct.
3. Open the **RVMS** app. At the bottom of the sign-in screen you will see:

   ```
   Server: http://127.0.0.1:8000
   ```

4. **Tap it.** A box opens.
5. Type your laptop's address from Step 2, including the port:

   ```
   192.168.137.1:8000
   ```

   *(You may leave off `http://` — the app adds it.)*

6. Tap **Save**.

**You should see:** the line at the bottom now reads
`Server: http://192.168.137.1:8000`.

> This is saved. The phone will still be pointed here after a restart, so you
> only do this once per venue.

---

### STEP 7 — Log in on both

**Laptop** — browser → **http://127.0.0.1:8000**
`bfp.admin@rvms.local` / `password`

**Phone** — the RVMS app
`ramon.villanueva@rvms.local` / `password`

✅ **Both logged in? You are ready.**

---

## PART 4 — Final check before people arrive

1. On the **laptop**: Vehicles → circular-arrows on the driver's vehicle → set
   **Not Operational** → confirm
2. On the **phone**: pull down to refresh the home screen

**You should see:** the phone's status badge now reads **Not Operational**.

✅ That proves the hotspot, the server and the database are all working.

Set it back to **Operational** so the demo starts clean.

---

## PART 5 — Running the demo

Go to **`rvms-demo-script.md`**. Everything in it works in this tier, with the
one exception below.

---

<a name="push"></a>
## PART 6 — Push notifications need mobile data

The pop-up banners come from Google's servers, so the *phone* needs internet.
Your hotspot deliberately has none.

**The fix is simple: leave the phone's mobile data switched ON.** Android uses
Wi-Fi for the app's own traffic and mobile data for the internet, so banners
arrive normally.

**If there is no mobile data either:** banners will not appear, but every alert
still lands in the app's **Alerts** tab, because that comes from your own
database. Open Alerts and show it there. Say out loud:

> *"The alert is delivered and stored. The pop-up banner needs Google's servers,
> which needs internet this room does not have."*

That is accurate and it is a better answer than being surprised.

---

## PART 7 — Packing up

1. **Ctrl + C** in Window 1 and Window 2
2. Turn the hotspot off
3. Reconnect the phone to its normal Wi-Fi

**Leave the app's server address as it is** unless you are switching tiers — it
is already correct for next time.

---

<a name="troubleshooting"></a>
## TROUBLESHOOTING

---

<a name="which-ip"></a>
### ❌ `ipconfig` shows several addresses and I do not know which

The hotspot adapter's address almost always starts **192.168.137.**

Narrow it down:

```powershell
ipconfig | Select-String -Pattern "IPv4" -Context 0,0
```

Or list only what is actually listening:

```powershell
Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -like "192.168.*" } | Select IPAddress, InterfaceAlias
```

The one whose `InterfaceAlias` mentions *Local Area Connection\** is the hotspot.

**Quickest test of all:** try one, and if the phone cannot reach it, try the
next. You cannot break anything.

---

### ❌ The app says "Cannot reach the server"

Work through these in order.

1. **Is the phone actually on your hotspot?** Settings → Wi-Fi. Android
   sometimes drops a network with no internet and silently rejoins another.
   Reconnect and choose **Keep connection**.
2. **Is the address right?** Tap `Server:` at the bottom of the sign-in screen
   and compare it, digit for digit, with `ipconfig`.
3. **Did you include the port?** `192.168.137.1` alone will not work — it must
   be `192.168.137.1:8000`.
4. **Did you start the server with `--host=0.0.0.0`?** Window 1 must say
   `Server running on [http://0.0.0.0:8000]`. If it says `127.0.0.1`, stop it
   with Ctrl+C and start it again with the full command in Step 4.
5. **Firewall** — see below.
6. **Test from the laptop's own browser** using the LAN address:
   `http://192.168.137.1:8000`. If that fails too, it is the server or the
   firewall, not the phone.

---

<a name="firewall"></a>
### ❌ Everything looks right but the phone still cannot connect

Windows Firewall is blocking port 8000. Open **PowerShell as Administrator**:

```powershell
New-NetFirewallRule -DisplayName "RVMS demo 8000" -Direction Inbound -LocalPort 8000 -Protocol TCP -Action Allow -Profile Private
```

**You should see** the rule printed back. Try the phone again.

To remove it afterwards:
```powershell
Remove-NetFirewallRule -DisplayName "RVMS demo 8000"
```

---

<a name="no-hotspot"></a>
### ❌ Windows will not start the mobile hotspot

Some laptops refuse without an internet connection to share, and some Wi-Fi
adapters do not support it at all.

**Alternative — an ordinary Wi-Fi network.** Put the laptop and the phone on the
same Wi-Fi (the venue's, or a phone's hotspot with the laptop joined to it), then
run `ipconfig` and use that adapter's IPv4 address instead. Everything else is
identical.

> The only thing you lose is independence from the venue — which is the whole
> point of this tier. If neither works, drop to **Tier 3 (USB cable)**, which
> depends on no network at all.

---

### ❌ The phone connects but everything is very slow

Almost always the phone is trying to reach the internet through a hotspot that
has none, and timing out.

1. Turn **mobile data on** — Android will then use data for internet and Wi-Fi
   for the app.
2. Or, on the Wi-Fi network's settings, turn off *"Auto-switch to mobile
   data"* / *"Switch to mobile data when Wi-Fi has no internet"*.

---

### ❌ I typed the wrong address and want to start over

Tap `Server:` at the bottom of the sign-in screen and press **Reset**. It goes
back to `http://127.0.0.1:8000` (the USB-cable default). Then type the correct
one.

---

### ❌ MySQL, login, photo or alert problems

These are not tier-specific. See the matching sections of
**`rvms-demo-tier3-usb.md`** — the fixes are identical.

---

### ❌ Something is badly wrong and there is no time

⚠️ **Erases all records and restores the sample data.**

```powershell
php artisan migrate:fresh --seed
php artisan rvms:doctor
```

Expect `2 warning(s)` and no failures. Then redo Step 7.

**If it is the network rather than the data, drop to Tier 3.** Plug in the
cable, run `adb reverse tcp:8000 tcp:8000`, and set the app's server back to
`http://127.0.0.1:8000`. About 45 seconds.

---

## ONE-PAGE SUMMARY

Print this part.

```
BEFORE:   hotspot ON · MySQL running · laptop IP written down

FIND IP   ipconfig                       → 192.168.137.1  (usually)

WINDOW 1  cd "C:\rescue vehicle management system\rvms\backend"
          php artisan serve --host=0.0.0.0 --port=8000
                                         → "Server running on [http://0.0.0.0:8000]"
                                         → Allow through the firewall (Private)

WINDOW 2  cd "C:\rescue vehicle management system\rvms\backend"
          php artisan schedule:work      → "Running scheduled tasks..."

PHONE     join the hotspot · "Keep connection" when it warns of no internet
          app → tap "Server:" → 192.168.137.1:8000 → Save
          leave MOBILE DATA ON so push banners still arrive

LAPTOP    http://127.0.0.1:8000
          bfp.admin@rvms.local / password

PHONE     ramon.villanueva@rvms.local / password

TEST      change a vehicle status on the laptop
          → refresh the phone → the status changed

IF THE PHONE CANNOT CONNECT:
          1. is it on the hotspot?
          2. does the address match ipconfig, WITH :8000?
          3. did Window 1 say 0.0.0.0 and not 127.0.0.1?
          4. firewall rule (see troubleshooting)

FALL BACK TO TIER 3:
          plug in cable · adb reverse tcp:8000 tcp:8000
          app → Server → http://127.0.0.1:8000
```

---

## Notes

- The switchable server address is what makes this tier possible at all. Before
  it, the address was fixed inside the APK and only the USB tier worked.
- **Tier 1 (deployed)** uses the same app: tap `Server:` and enter the site's
  `https://…` address. No other difference.
- Setup is in this document; **what to demonstrate is in `rvms-demo-script.md`**,
  which is shared by all three tiers.
