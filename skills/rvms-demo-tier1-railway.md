# Deployment Runbook — TIER 1: Railway (hosted)

> **Read this if:** you want RVMS running on the internet permanently — a real
> `https://…` address that works whether or not your laptop is on. This is what
> you want for the September user-acceptance testing.
>
> **What it costs:** about **$5 a month**, cancellable at any time. Railway has
> no free tier.
>
> **How long:** about **an hour** the first time.
>
> **What gets hosted:** the **`backend/` folder only.** See Part 1 — getting
> this wrong is the one mistake that wastes an afternoon.
>
> **Follow every step in order.** Each says what to do and what you should see.
> If it does not match, go to [Troubleshooting](#troubleshooting) — do not carry
> on and hope.

---

<a name="what-gets-hosted"></a>
## PART 1 — What gets hosted, and what must NOT

The repository has several folders. Only one is the application.

| Folder | What it is | Deploy it? |
|---|---|---|
| **`backend/`** | The real Laravel app — API and admin dashboard | ✅ **This one** |
| `web/` | The **frozen prototype**: hardcoded fake data, dead links, demo JavaScript | 🚫 **Never** |
| `mobile/` | The Android driver app | 🚫 It is an APK, not a website |
| `manuscript/`, `skills/` | Documents | 🚫 No |

> 🚨 **`web/` is a mockup.** Deploying it would put a fake dashboard of invented
> vehicles on the internet. It is also the reference every screen was checked
> against, so it must stay exactly as it is.
>
> In Railway this is one setting: **Root Directory = `backend`**. Do not skip it.

---

## PART 2 — What you need

- [ ] A **GitHub account** with the RVMS repository (you have this)
- [ ] A **payment method** for Railway — about $5/month
- [ ] Your **Firebase service-account JSON** (the file `FIREBASE_CREDENTIALS`
      points at on your laptop). Open it in Notepad; you will paste its contents.
- [ ] About an hour

---

## PART 3 — Create the project

### STEP 3.1 — Sign up

Go to **railway.app** → **Login with GitHub** → authorise it.

Add a payment method under **Account → Billing**, and choose the **Hobby** plan.

> Railway's trial credit runs out quickly. Without a payment method the project
> stops mid-UAT, which is worse than not deploying at all.

---

### STEP 3.2 — Deploy from the repository

1. **New Project** → **Deploy from GitHub repo**
2. Choose **`tormis-neil/rvms-capstone`**
3. Railway creates a service and immediately tries to build. **It will fail.**
   That is expected — it is looking at the repository root, not `backend/`.

---

### STEP 3.3 — Point it at `backend/`  ⚠️ the important one

1. Click the service → **Settings**
2. Find **Root Directory**
3. Set it to:

   ```
   backend
   ```

4. Save

**You should see:** a new build start, and this time it finds `composer.json`.

---

### STEP 3.4 — Add the database

1. In the project canvas: **New** → **Database** → **Add MySQL**
2. Wait for it to finish provisioning

**You should see:** a MySQL service beside your app service.

> Railway exposes it to your app as variables like `MYSQLHOST`, `MYSQLUSER`,
> `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`. Step 4 wires them up.

---

## PART 4 — Environment variables

Click the **app service** → **Variables** → **Raw Editor**, and paste this in.

```
APP_NAME=RVMS
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Manila
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
LOG_CHANNEL=stack
LOG_LEVEL=error

FIREBASE_PROJECT_ID=
FIREBASE_CREDENTIALS=
```

Then fill in the three blanks:

**`APP_KEY`** — on your laptop run:

```powershell
php artisan key:generate --show
```

Copy the whole output, including the `base64:` prefix.

**`APP_DEBUG=false`** — leave it false. With it on, any error page shows your
database credentials to whoever is looking.

**`FIREBASE_PROJECT_ID`** — from your Firebase console.

**`FIREBASE_CREDENTIALS`** — open your service-account JSON in Notepad,
**select all, copy, and paste the whole thing** as the value. On one line is
fine.

> This is why the code accepts JSON here as well as a file path. Railway has
> nowhere to upload a private key to, and anything written to disk is wiped on
> the next deploy. Paste the JSON and the app writes it somewhere safe on boot.
> Never commit that file to the repository.

Save. Railway redeploys automatically.

---

## PART 5 — A disk for the damage photos

Without this, every damage-report photo (FR-11) disappears on the next deploy.

1. App service → **Settings** → **Volumes** → **New Volume**
2. Mount path:

   ```
   /app/storage/app/public
   ```

3. Save and let it redeploy

---

## PART 6 — The scheduler

The licence and PM alerts (FR-08, FR-14) are time-driven. Nothing fires them
unless something asks "is anything due?" once a minute.

1. **New** → **GitHub Repo** → the same repository (a **second service**)
2. **Settings** → **Root Directory** = `backend`
3. **Settings** → **Custom Start Command**:

   ```
   php artisan schedule:work
   ```

4. **Variables** → paste the **same block** as Part 4
5. **Settings** → **Networking** → make sure **no public domain** is generated.
   This service has no website; it is a clock.

> Two services on one plan. The web service serves pages; this one only runs the
> scheduled sweeps. Keeping them apart matters because the web service restarts
> on every deploy, and a restarted clock misses its alarm.

---

## PART 7 — Go live

### STEP 7.1 — Get the address

App service → **Settings** → **Networking** → **Generate Domain**.

**You should see:** something like
`rvms-production-a1b2.up.railway.app`

📝 **Write it down.** This address is permanent — it survives restarts and
redeploys, so you can print it and give it to the agencies.

---

### STEP 7.2 — Set APP_URL

Back in **Variables**, add:

```
APP_URL=https://rvms-production-a1b2.up.railway.app
```

(your own address, with `https://`). Save.

---

### STEP 7.3 — Load the sample data

App service → the **⋮** menu → **Run a command** (or use the Railway CLI):

```
php artisan db:seed --force
```

**You should see:** the four agencies, five administrators and eight drivers
created.

> Migrations already ran on boot — that is in `nixpacks.toml`. This adds the
> seeded accounts and demo records.

---

### STEP 7.4 — Test it

Open your address in a browser.

**You should see:** the RVMS login page with a padlock, looking exactly as it
does locally.

Log in as `bfp.admin@rvms.local` / `password`.

> 🚨 **Change these passwords before real UAT.** The seeded accounts all use
> `password`, and the site is now public.

---

### STEP 7.5 — Point the phone at it

1. Open the **RVMS** app
2. Tap **`Server: …`** at the bottom of the sign-in screen
3. Type the address **without** `https://` and **without** a port:

   ```
   rvms-production-a1b2.up.railway.app
   ```

4. **Save**, then log in as `ramon.villanueva@rvms.local` / `password`

---

### STEP 7.6 — Prove it end to end

1. **Laptop:** Vehicles → circular-arrows on the driver's vehicle → **Not
   Operational** → confirm
2. **Phone:** pull down to refresh

**You should see:** the phone's badge reads **Not Operational**, over the
internet, with your laptop closed.

✅ **That is Tier 1 working.**

---

## PART 8 — Check the deployment is complete

Run this through **Run a command**:

```
php artisan rvms:doctor
```

It checks the things that silently break a handover — scheduler, `storage:link`,
`APP_DEBUG`, timezone.

And for push specifically:

```
php artisan rvms:fcm-doctor
```

**You should see:** `Transport in use — FcmHttpV1Transport (real pushes)`. If it
says pushes are **simulated**, the Firebase variables are wrong — the command
names which one.

---

<a name="troubleshooting"></a>
## TROUBLESHOOTING

### ❌ Build fails: "composer.json not found"

**Root Directory** is not set to `backend`. Part 3.3.

---

### ❌ Build succeeds, site shows 500

Almost always `APP_KEY`. Check it is set and starts with `base64:`.

To see the real error, temporarily set `APP_DEBUG=true`, reload, read it, then
**set it back to false immediately** — it exposes your database password.

---

### ❌ "SQLSTATE[HY000] [2002] Connection refused"

The `DB_*` variables are not resolving. They must use Railway's reference
syntax, exactly as in Part 4:

```
DB_HOST=${{MySQL.MYSQLHOST}}
```

If your MySQL service is named something other than `MySQL`, use that name.

---

### ❌ The page loads but has no styling

The proxy headers are not being trusted. This is **already fixed** in
`bootstrap/app.php` and guarded by `TrustedProxyTest`, so if you see it you are
deploying older code. Make sure the branch with that fix is merged.

---

### ❌ Damage photos show a broken image

Either the volume (Part 5) is missing, or `storage:link` did not run. Run it by
hand:

```
php artisan storage:link
```

---

### ❌ Licence and PM alerts never arrive

The scheduler service (Part 6) is missing, crashed, or has no database
variables. Open its **Logs** — it should print a line every minute.

---

### ❌ Pushes are "simulated"

Run `php artisan rvms:fcm-doctor`; it names the cause. Usually the pasted JSON
lost a character — re-copy the whole file, including both braces.

---

### ❌ It costs more than expected

**Settings → Usage** shows what is being charged. Two small services plus MySQL
should sit near the $5 included in Hobby. Delete the scheduler service when you
are not testing if you need to economise — the alerts stop, but nothing else
does.

---

## ONE-PAGE SUMMARY

```
ROOT DIRECTORY = backend         <- NOT the repo root, NEVER web/

SERVICES        1. app       (root: backend)              -> public domain
                2. MySQL     (Railway plugin)
                3. scheduler (root: backend)              -> NO domain
                   start command: php artisan schedule:work

VARIABLES       APP_KEY       = php artisan key:generate --show
                APP_ENV       = production
                APP_DEBUG     = false          <- never true in public
                APP_TIMEZONE  = Asia/Manila
                APP_URL       = https://<your-domain>
                DB_*          = ${{MySQL.MYSQL...}}
                FIREBASE_CREDENTIALS = paste the whole JSON

VOLUME          /app/storage/app/public        <- or photos vanish

AFTER DEPLOY    php artisan db:seed --force
                php artisan rvms:doctor
                php artisan rvms:fcm-doctor

PHONE           app -> "Server:" -> your-domain.up.railway.app
                no https://   no :8000

FIRST THING     change the seeded passwords. The site is public.
```

---

## Notes

- **For the final defense, use Tier 2 (USB).** It depends on no network at all,
  so nothing in the venue can break it. This tier is for UAT, where the clients
  cannot come to you.
- **What to demonstrate is in `rvms-demo-script.md`**, shared by every tier.
