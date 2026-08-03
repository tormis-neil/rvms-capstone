# RVMS Admin Website (Prototype)

Static, no-backend prototype of the Agency Administrator dashboard for the
Rescue Vehicle Management System. All data is sample/static data defined in
`assets/js/agency.js` and scoped per agency (BFP, PNP, CDRRMO, CHO).

> ## This folder is the reference, not the system
>
> The working admin dashboard is the Laravel/Blade application in **`backend/`**, and these ten
> pages are what it was built from. Every screen there was copied here verbatim first, then
> wired to live data — and each one is checked side by side against the page in `pages/` before
> it is accepted.
>
> That makes this folder a **frozen reference**: it is deliberately never edited, because the
> moment it changes, the comparison it exists for stops meaning anything. Fixes belong in
> `backend/resources/views/`. The one recorded prototype defect — `repairs.html` declaring nine
> table headers over eight body cells — is corrected in the Blade copy and documented there,
> not here.
>
> Run this folder when you want to see the original design intent, demonstrate a screen without
> starting MySQL, or compare a dashboard page against its source. For the real thing, see
> [`backend/README.md`](../backend/README.md).

## Folder structure

```
web/
├── index.html            Splash → redirects to login
├── login.html            Sign in (+ prototype agency switcher)
├── pages/                Authenticated admin pages
│   ├── dashboard.html
│   ├── vehicles.html
│   ├── drivers.html
│   ├── inspections-damage.html
│   ├── pm.html
│   ├── repairs.html
│   ├── dispatch.html
│   ├── reports.html
│   ├── notifications.html
│   └── profile.html
└── assets/
    ├── css/style.css     Theme tokens + custom styles
    ├── js/agency.js      Per-agency sample data + all rendering/wiring
    └── img/              Logos (rvms-logo.svg + img/agency/* agency logos)
```

Pages link to each other with relative paths (`dashboard.html` within
`pages/`, `../login.html`, `../assets/...`), so the site works both when
served and when opened from the file system.

## Run locally

Serve the `web/` folder with any static server, then open the URL:

```
cd web
python3 -m http.server 8080
# or: npx serve .
```

Open http://localhost:8080 — pick an agency on the login screen.

## Deploy to Vercel

Deploy this `web/` folder as the project root (no build step — it is a static
site). Set the Vercel project's **Root Directory** to `web`.

## Agency scoping

The active agency comes from the `?agency=` query parameter (set by the login
chips) and is remembered in `localStorage`. Every page renders only that
agency's records.
