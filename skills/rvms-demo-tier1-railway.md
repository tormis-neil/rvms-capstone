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
| `skills/` | Documents | 🚫 No |

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

### STEP 3.4 — Set the builder to Nixpacks  ⚠️ the other important one

1. Still in **Settings** → find **Build** → **Builder**
2. Change it from **Railpack** to **Nixpacks**
3. Save

**Why this is not optional.** Railway's default builder is now Railpack, and
Railpack **ignores `backend/nixpacks.toml` completely**. That file is where the
deploy's migrations, `storage:link`, upload limits and server workers live, so a
Railpack build produces an app that starts against a database with no tables and
404s every photo — with nothing in the log saying why.

**How to tell which one ran.** Open the build log. The third line says either
`using build driver railpack-…` (wrong — go back and change it) or
`using build driver nixpacks-…` (right).

---

### STEP 3.5 — Add the database

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

## PART 5 — A disk for the uploaded files

Three things are uploaded and all three live in the same folder: damage-report
photos (FR-11), repair receipts (FR-13) and PM completion documents (FR-14).
Without this volume every one of them disappears on the next deploy — Railway
rebuilds the container from the repository each time, and files written after
the build are not in the repository.

1. App service → **Settings** → **Volumes** → **New Volume**
2. Mount path:

   ```
   /app/storage/app/public
   ```

3. Save and let it redeploy

> **Nothing to do about upload SIZE.** The repository carries `backend/deploy/php.ini`
> (8M upload / 10M post) and `backend/nixpacks.toml` loads it, so the deployed
> app already accepts the 5 MB the forms allow. It is described here only so you
> know where the setting lives if `rvms:doctor` ever complains about it — see the
> note at the end of Part 8.

---

## PART 6 — The scheduler

The licence and PM alerts (FR-08, FR-14) are time-driven. Nothing fires them
unless something asks "is anything due?" once a minute.

1. **New** → **GitHub Repo** → the same repository (a **second service**)
2. **Settings** → **Root Directory** = `backend`
3. **Settings** → **Build** → **Builder** = **Nixpacks** (same reason as Part 3.4)
4. **Settings** → **Custom Start Command**:

   ```
   php artisan schedule:work
   ```

5. **Variables** → paste the **same block** as Part 4
6. **Settings** → **Networking** → make sure **no public domain** is generated.
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
`APP_DEBUG`, timezone, upload limits.

> **If it reports the PHP upload limit is below 5M**, the `deploy/php.ini` file
> is not being read. The variable that loads it is in `backend/nixpacks.toml`:
>
> ```
> PHP_INI_SCAN_DIR = ":/app/deploy"
> ```
>
> **The leading colon is not a typo and must not be removed.** Without it PHP
> stops reading its own default configuration directory, which unloads the
> database driver — the app would then fail with a connection error rather than
> an upload one. With the colon, our file is read *in addition to* the defaults.
>
> If the variable is somehow missing on the service, add it under **Variables**
> with exactly that value, including the colon.

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

### ❌ Build fails: "Your lock file does not contain a compatible set of packages"

The log names `symfony/…` packages that require `php >=8.4.1` while the builder
installed PHP 8.2. `composer.json` declares `"php": "^8.2"`, so the builder
installs 8.2 — but if `composer update` was ever run on a machine with a newer
PHP, the lock recorded packages that 8.2 cannot run.

Fixed in the repository: `composer.json` now carries

```json
"config": { "platform": { "php": "8.2.0" } }
```

which forces Composer to resolve for 8.2 no matter what PHP the developer has.
If you ever hit this again, run `composer update` locally (that block makes it
resolve correctly), commit the new `composer.lock`, and redeploy.

> **Do not "fix" it by raising `composer.json` to `^8.4`.** Chapter 3 states the
> system runs on PHP 8.2+, so that would put the manuscript and the system out
> of step to solve a problem the platform pin already solves.

---

### ❌ Deploy succeeds but every page 500s, and no tables exist

The build ran under **Railpack**, not Nixpacks, so `nixpacks.toml` was ignored
and the migrations never ran. Check the third line of the build log for
`using build driver railpack-…` and fix it in Part 3.4.

The same cause explains photos 404ing (no `storage:link`) and uploads silently
failing (no `PHP_INI_SCAN_DIR`) — one setting, three symptoms.

---

### ❌ Build fails: "The iconv OR mbstring extension is required and both are missing"

Composer itself cannot start, because the PHP the builder produced has no
`mbstring`. The builder decides which extensions to install by reading
`composer.json`'s `require` block for `ext-*` entries — and for a long time this
project declared none, so it got a bare PHP.

Fixed in the repository: `composer.json` now declares the nine extensions the
system actually uses (`ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`,
`pdo_mysql`, `tokenizer`, `zip`). Nothing to do unless you add a library that
needs another one — in which case declare it there and both builders will pick
it up.

> **`ext-pdo_mysql` is the one that matters most**, and nothing declared it
> before. Without it the app builds perfectly and then cannot reach MySQL at
> all, failing with "could not find driver" — which reads like a password
> problem and is not one.

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

### ❌ Damage photos or repair receipts show a broken image

Either the volume (Part 5) is missing, or `storage:link` did not run. Run it by
hand:

```
php artisan storage:link
```

---

### ❌ Uploading a photo or receipt does nothing, or errors on a big file

PHP is rejecting it before Laravel sees it. Run `php artisan rvms:doctor` and
read the upload-limit line, then follow the note in Part 8 about
`PHP_INI_SCAN_DIR` — including why the leading colon matters.

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
BUILDER        = Nixpacks        <- NOT Railpack; Railpack ignores nixpacks.toml

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

VOLUME          /app/storage/app/public        <- or photos + receipts vanish

UPLOAD LIMITS   already in the repo: backend/deploy/php.ini, loaded by
                nixpacks.toml via PHP_INI_SCAN_DIR = ":/app/deploy"
                the leading colon is required — without it pdo_mysql unloads

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
