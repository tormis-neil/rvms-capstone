# RVMS Backend (Laravel 11)

REST API (`/api/v1`, Sanctum bearer tokens) + Blade admin dashboard for the
Rescue Vehicle Management System. See the repo root `CLAUDE.md` and
`skills/rvms-source-of-truth.md` for scope and requirements.

## Setup

```bash
cd backend
composer install
cp .env.example .env          # MySQL 8 connection (DB_DATABASE=rvms)
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

`php artisan test` runs the suite against an in-memory SQLite database
(configured in `phpunit.xml`) — no MySQL needed for tests.

## Seeded accounts (R0)

Password is `password` for every account.

| Role | Email |
|---|---|
| BFP admin | `bfp.admin@rvms.local` |
| BFP deputy admin (2nd admin sample) | `bfp.admin2@rvms.local` |
| PNP admin | `pnp.admin@rvms.local` |
| CDRRMO admin | `cdrrmo.admin@rvms.local` |
| CHO admin | `cho.admin@rvms.local` |
| Sample drivers (2/agency) | e.g. `ramon.villanueva@rvms.local` |

An agency may have more than one administrator account (per the interviews);
the BFP deputy admin demonstrates this. Admins sign in at `/login` (web
dashboard, from R1). Drivers authenticate via `POST /api/v1/login` (mobile app).

## API surface so far (R0)

| Method | URI | Auth | Purpose |
|---|---|---|---|
| POST | `/api/v1/login` | public | Issue Sanctum token (active accounts only) |
| POST | `/api/v1/register` | public | Driver self-registration → `pending` (FR-03) |
| POST | `/api/v1/logout` | token | Revoke current token |
| GET | `/api/v1/me` | token | Authenticated user + agency |
| PATCH | `/api/v1/me/profile` | token | Self-edit name/email/password (FR-04) |

Status codes: 200/201 success · 401 unauthenticated · 403 wrong role /
non-active account · 422 validation.
