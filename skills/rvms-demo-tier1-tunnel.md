# Demo Runbook — TIER 1: Public HTTPS Link (Cloudflare Tunnel)

> **Read this if:** the clients need to open RVMS from **their own phones, in
> their own offices**, over the internet — user-acceptance testing, a remote
> consultation, or a demo where you cannot be in the same room.
>
> **What it does:** turns your laptop into a public website for as long as you
> leave it running. One command prints an `https://…` address that anyone in the
> world can open.
>
> **What it costs:** nothing. No account, no credit card, no time limit, no
> deployment. This is why it is here instead of Railway or Render.
>
> **The trade:** your laptop must stay on and connected, and the free address
> changes every time you restart the tunnel. That suits scheduled UAT sessions —
> it is not a 24/7 website.
>
> **Follow every step in order.** Each says what to type and what you should see.
> If it does not match, go to [Troubleshooting](#troubleshooting) — do not carry
> on and hope.

---

## PART 1 — What you need

- [ ] Laptop with the RVMS project **and a working internet connection**
- [ ] The clients' phones or laptops — anywhere, on any network
- [ ] The RVMS app installed on any phone that will be tested
- [ ] This document

**No cable. No hotspot. No same-room requirement.**

---

## PART 2 — One-time setup: install cloudflared

Do this **once**, days before you need it.

### Option A — one command (easiest)

Open **PowerShell** and run:

```powershell
winget install --id Cloudflare.cloudflared
```

**You should see:** `Successfully installed`.

Close PowerShell and open a new one, so it picks up the new command.

### Option B — download it by hand

If `winget` is not available:

1. Go to `https://github.com/cloudflare/cloudflared/releases/latest`
2. Download **`cloudflared-windows-amd64.exe`**
3. Rename it to **`cloudflared.exe`**
4. Move it into your backend folder, next to `artisan`

Then everywhere below, type `.\cloudflared.exe` instead of `cloudflared`.

### Check it worked

```powershell
cloudflared --version
```

**You should see:** a version number, e.g. `cloudflared version 2026.x.x`.

> ⚠️ **Do not run `cloudflared tunnel login`.** That is for named tunnels, which
> need a Cloudflare account and a domain name. You need neither.

---

## PART 3 — Session day setup

**Start 15 minutes before.** It takes about 5 minutes.

You will open **three windows** and keep them open.

---

### STEP 1 — Check the database is running

```powershell
sc query MySQL80
```

**You should see:** `STATE : 4 RUNNING`. If it says STOPPED, run
`net start MySQL80` as Administrator.

---

### STEP 2 — Start the website  🪟 **Window 1**

```powershell
cd "C:\rescue vehicle management system\rvms\backend"
php artisan serve --port=8000
```

**You should see:**

```
   INFO  Server running on [http://127.0.0.1:8000].
```

⚠️ Leave this window open for the whole session.

> Plain `127.0.0.1` is correct here — unlike Tier 2, you do **not** need
> `--host=0.0.0.0`. The tunnel connects to your laptop from the inside, so no
> firewall rule is needed either.

---

### STEP 3 — Start the alerts  🪟 **Window 2**

```powershell
cd "C:\rescue vehicle management system\rvms\backend"
php artisan schedule:work
```

**You should see:** `INFO  Running scheduled tasks every minute.`

⚠️ Leave this window open too.

---

### STEP 4 — Open the tunnel  🪟 **Window 3**  🔎 **write the address down**

```powershell
cloudflared tunnel --url http://localhost:8000
```

Wait about 10 seconds. Among the log lines you will see a box like this:

```
+------------------------------------------------------------+
|  Your quick Tunnel has been created! Visit it at:           |
|  https://formal-tuesday-rice-harvest.trycloudflare.com      |
+------------------------------------------------------------+
```

> 📝 **Write that address down.** It is different every time. That is the link
> you give the clients.

⚠️ Leave this window open. Closing it kills the link instantly.

---

### STEP 5 — Test it yourself FIRST

Open the address in your **own** browser before sending it to anyone.

**You should see:** the RVMS login page, with the padlock in the address bar,
looking exactly as it does on localhost.

Log in as `bfp.admin@rvms.local` / `password`.

> 🚨 **If the page loads but looks broken — no colours, no layout —** stop and
> see [Troubleshooting](#mixed-content). Do not send the link out in that state.

---

### STEP 6 — Point a phone at it

1. Open the **RVMS** app
2. At the bottom of the sign-in screen, tap **`Server: …`**
3. Type the tunnel address, **without** `https://` and **without** a port:

   ```
   formal-tuesday-rice-harvest.trycloudflare.com
   ```

4. Tap **Save**

**You should see:** the line now reads your tunnel address.

Log in as `ramon.villanueva@rvms.local` / `password`.

> The phone needs its own internet (mobile data or any Wi-Fi). It does **not**
> need to be near your laptop.

---

### STEP 7 — Send the link to the clients

Give them:

- **The address** from Step 4
- **Their login** (agency admin account for the web dashboard)
- **The app**, if they are testing the driver side:
  `https://github.com/tormis-neil/rvms-capstone/releases/tag/apk-latest`

✅ **You are live.**

---

## PART 4 — Final check before the clients start

1. On the **laptop**: Vehicles → circular-arrows on a driver's vehicle → set
   **Not Operational** → confirm
2. On the **phone**: pull down to refresh the home screen

**You should see:** the phone's status badge now reads **Not Operational** —
over the internet, on a different network.

✅ That proves the tunnel, the server and the database are all working.

Set it back to **Operational** so the session starts clean.

---

## PART 5 — Running the session

Go to **`rvms-demo-script.md`**. Everything in it works in this tier — including
push notification banners, which work better here than in Tier 2, because
everyone already has internet.

---

## PART 6 — Packing up

1. **Ctrl + C** in Window 3 (the tunnel) — **the link dies immediately**
2. **Ctrl + C** in Windows 1 and 2

> ⚠️ **The link stops working the moment you close Window 3.** Tell the clients
> when the session ends, or they will think the system crashed.

---

## PART 7 — Things to know before you rely on this

**The address changes every restart.** Free quick tunnels get a random name each
time. Send the new link at the start of each session — do not print it on
anything.

**Your laptop is the server.** Sleep, a closed lid, or losing Wi-Fi takes the site
down for everyone. Plug the laptop in and set it to never sleep during a session.

**Anyone with the link can reach the login page.** They still cannot get in
without an account, and agency scoping (FR-02) still applies. But do not post the
link publicly, and stop the tunnel when the session ends.

**Real accounts, real data.** This is your actual database. Anything the clients
create during UAT is really there — which is the point, but remember it when you
reset for the defense.

---

<a name="troubleshooting"></a>
## TROUBLESHOOTING

---

### ❌ `cloudflared` is not recognised

Either the install did not finish, or you are in an old PowerShell window.

1. Close **every** PowerShell window and open a fresh one
2. Run `cloudflared --version` again
3. Still failing? Use **Option B** in Part 2 and type `.\cloudflared.exe`

---

### ❌ The tunnel starts but no address appears

Scroll up in Window 3 — the box is easy to miss among the log lines. Look for
`trycloudflare.com`.

If there is genuinely no address, your internet is down or blocked. Check that
you can open a normal website, then Ctrl + C and run the command again.

---

<a name="mixed-content"></a>
### ❌ The page loads but has no colours or layout

This is the classic reverse-proxy failure: the browser is on HTTPS, but the app
generated its stylesheet links as HTTP, so the browser blocked them.

**This is already fixed in the code** — `bootstrap/app.php` trusts the proxy
headers, and `TrustedProxyTest` guards it.

So if you see it, you are running **older code**. Pull the latest and try again:

```powershell
git pull
php artisan config:clear
```

---

### ❌ "419 Page Expired" when logging in

A stale session cookie from a previous, different tunnel address.

Clear your browser cookies for `trycloudflare.com`, or open the link in a private
window.

---

### ❌ The phone says "Cannot reach the server"

1. **Does the phone have internet?** Open any website on it.
2. **Is the address right?** Tap `Server:` and compare it, character for
   character, with Window 3. The words are random and easy to mistype.
3. **Did you add a port?** Do **not** type `:8000` for a tunnel — that is only
   for Tier 2 and Tier 3.
4. **Is Window 3 still open?** If you closed it, the link is dead. Start it
   again and send the new address.

---

### ❌ It worked, then everyone got disconnected at once

Almost always the laptop slept, closed, or dropped Wi-Fi. Wake it and restart the
tunnel — the address will be **new**, so send the new link.

To prevent it: Settings → System → Power → set sleep to **Never** while plugged
in.

---

### ❌ It is very slow

Every request now travels to Cloudflare and back to your laptop, so it will never
be as quick as Tier 3. If it is unusable, check your upload speed — a weak
connection is the usual cause.

For a defense, use **Tier 3 (USB)** anyway. It depends on no network at all.

---

### ❌ MySQL, login, photo or alert problems

These are not tier-specific. See the matching sections of
**`rvms-demo-tier3-usb.md`** — the fixes are identical.

---

## ONE-PAGE SUMMARY

Print this part.

```
ONCE      winget install --id Cloudflare.cloudflared
          cloudflared --version    -> a version number
          (do NOT run "cloudflared tunnel login")

BEFORE    MySQL running - laptop plugged in - sleep set to Never

WINDOW 1  cd "C:\rescue vehicle management system\rvms\backend"
          php artisan serve --port=8000
                                   -> "Server running on [http://127.0.0.1:8000]"

WINDOW 2  cd "C:\rescue vehicle management system\rvms\backend"
          php artisan schedule:work
                                   -> "Running scheduled tasks..."

WINDOW 3  cloudflared tunnel --url http://localhost:8000
                                   -> https://SOMETHING.trycloudflare.com
                                      WRITE IT DOWN - it changes every time

TEST      open that address in YOUR browser first
          login page + padlock + normal colours = good

PHONE     app -> tap "Server:" -> type the address
          NO https://   NO :8000
          needs its own internet - any network, anywhere

CLIENTS   send: the address + their login + the APK link
          github.com/tormis-neil/rvms-capstone/releases/tag/apk-latest

END       Ctrl+C in Window 3 -> the link dies at once. Tell them.

IF IT BREAKS MID-SESSION:
          restart Window 3, send the NEW address
```

---

## Notes

- **Why not Railway or Render?** Render's free tier has no MySQL, no persistent
  disk for damage photos, and no cron for the licence and PM alerts — three
  separate breakages. Railway fits the shape but is no longer free. A tunnel
  costs nothing and needs no code changes, because the app already carries a
  switchable server address.
- **This tier exists for UAT, not for the defense.** For 26 October use
  **Tier 3 (USB)** — it cannot be broken by a venue's network.
- The three tiers share one app and one script. Setup differs; **what to
  demonstrate is in `rvms-demo-script.md`** for all of them.
