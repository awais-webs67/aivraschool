# Skooline by AivraSol

Multi-tenant, subscription-based **school management system (School ERP)**.
AivraSol (Super Admin) adds schools on subscription; every school gets its own portal
(`{school}.skooline.com`) and its **own separate database**, while all schools run on one platform.

- Product & feature spec: [`docs/SCHOOL_ERP_FEATURES.md`](docs/SCHOOL_ERP_FEATURES.md)
- Capacity & build plan: [`docs/BUILD_PLAN.md`](docs/BUILD_PLAN.md)

## What's built so far

| Area | Status |
|---|---|
| Public website (home, modules, pricing from DB, FAQ, demo request form) | ✅ |
| Super Admin login (throttled, inactive users blocked) | ✅ |
| Super Admin dashboard (schools by status, MRR, collections, renewals due, leads) | ✅ |
| Schools: add (auto database + subdomain + admin login), list/search/filter, edit, suspend/reactivate, extend trial, reset admin password, delete (drops DB) | ✅ |
| Plans: monthly / quarterly / half-yearly / yearly / lifetime / per-student, limits, modules per plan | ✅ |
| Subscriptions: assign/renew, custom amount & dates, payment status, mark paid, daily auto-expiry with grace period | ✅ |
| School portal: login on own subdomain, dashboard showing plan modules, blocked when suspended/expired | ✅ |
| School modules (students, attendance, fees, exams…) | Next — see build plan |

## Tech stack

Laravel 13 (PHP 8.3+), [stancl/tenancy](https://tenancyforlaravel.com) (database per school),
Blade + Tailwind CSS 4 + small vanilla JS, MySQL 8 in production (SQLite for local dev/tests).

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed          # default plans + first Super Admin (password printed)
npm install && npm run build
php artisan serve
```

- Website: http://localhost:8000
- Super Admin: http://localhost:8000/admin
- A school created with subdomain `greenfield`: http://greenfield.localhost:8000/login
  (`*.localhost` resolves to your machine in modern browsers)

Create more Super Admins with `php artisan skooline:create-admin you@aivrasol.com`.

## Production notes

- Point `skooline.com` **and** `*.skooline.com` (wildcard DNS + wildcard SSL) to the server.
- `CENTRAL_DOMAINS=skooline.com` and `APP_URL=https://skooline.com`.
- Use MySQL; the DB user must be allowed to `CREATE DATABASE` / `DROP DATABASE` (one DB per school).
- Run the scheduler (`* * * * * php artisan schedule:run`) — it expires overdue subscriptions daily.
- Use Redis for cache/queue/session at scale.

## Tests

```bash
php artisan test
vendor/bin/pint --test
```
