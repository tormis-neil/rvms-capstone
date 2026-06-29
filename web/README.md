# RVMS Admin Website (Prototype)

Static, no-backend prototype of the Agency Administrator dashboard for the
Rescue Vehicle Management System. All data is sample/static data defined in
`assets/js/agency.js` and scoped per agency (BFP, PNP, CDRRMO, CHO).

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
