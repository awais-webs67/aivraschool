# Skooline by AivraSol

Multi-tenant, subscription-based **school management system (School ERP)**.
AivraSol (Super Admin) adds schools on subscription; every school gets its own portal
(`skooline.com/{school}`). All schools share one database, and every school's data is kept apart by `school_id`.
Installs on **cPanel / hPanel** like any PHP script: upload, import `database/skooline.sql`, edit `.env`.

- Product & feature spec: [`docs/SCHOOL_ERP_FEATURES.md`](docs/SCHOOL_ERP_FEATURES.md)
- Capacity & build plan: [`docs/BUILD_PLAN.md`](docs/BUILD_PLAN.md)
- Architecture & request flow: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- Hosting (localhost, cPanel/hPanel): [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)

## What's built so far

| Area | Status |
|---|---|
| Public website (home, modules, pricing from DB, FAQ, demo request form) | ✅ |
| Super Admin login (throttled, inactive users blocked) | ✅ |
| Super Admin dashboard (schools by status, MRR, collections, renewals due, leads) | ✅ |
| Schools: add (portal address + admin login + subscription in one step), list/search/filter, edit, suspend/reactivate, extend trial, reset admin password, delete (removes all its data) | ✅ |
| Plans: monthly / quarterly / half-yearly / yearly / lifetime / per-student, limits, modules per plan | ✅ |
| Subscriptions: assign/renew, custom amount & dates, payment status, mark paid, daily auto-expiry with grace period | ✅ |
| School portal: login at `/{school}`, dashboard showing plan modules, blocked when suspended/expired | ✅ |
| Install without SSH: SQL file import, first-run Super Admin setup, auto app key, one-click database updates (Super Admin → System) | ✅ |
| **Step 1 — School setup:** school profile + logo, academic sessions (one current), classes & sections (Playgroup–Class 10 in one click), subjects assigned to classes, setup checklist on the dashboard | ✅ |
| New design: logo, animations, slide-in mobile menus, every page checked at phone to desktop widths | ✅ |
| Currencies: billing currency, fee currency per school, currency switcher on website pricing (Super Admin → Currencies) | ✅ |
| SEO: titles, descriptions, canonical, Open Graph image, structured data, `robots.txt`, `sitemap.xml`, private pages `noindex` | ✅ |
| **Super Admin tools:** dashboard with revenue and growth charts, reports, invoices (numbered, printable/PDF, CSV), leads CRM with follow-ups and convert-to-school, support tickets with schools, announcements, team members with roles (Owner, Manager, Accountant, Support), login as a school, activity log, notification bell, company settings | ✅ |
| **Self-registration:** "Register your school" with principal profile (CNIC, photo, bio), plan or free trial, Stripe payment, admin approval or auto-approve, emails | ✅ |
| **Integrations:** SMTP email with test, Stripe with test, secrets in `.env` shown masked (PIN to reveal) | ✅ |
| **AI:** 23 providers + custom (OpenAI-compatible / Anthropic), test & error help, failover order, AI quota per plan/school, school AI assistant (principal: analysis; admin: confirmed actions) — see [`docs/AI.md`](docs/AI.md) | ✅ |
| Step 2 — Students & admissions | Next |

## Tech stack

Laravel 13 (PHP 8.3+), one MySQL/MariaDB database with `school_id` scoping (`app/Support/Tenancy`),
Blade + Tailwind CSS 4 + small vanilla JS (built assets committed). SQLite for local dev/tests.

## Local setup

**XAMPP (htdocs + phpMyAdmin):** clone into `htdocs/aivraschool`, run `composer install`, import
`database/skooline.sql` in phpMyAdmin, copy `.env.example` to `.env` and set `APP_URL` + `DB_*`,
then open `http://localhost/aivraschool/admin`.

**Or with PHP's built-in server (SQLite, no database setup):**
```bash
composer run setup      # .env, database, plans, Super Admin and 2 demo schools
php artisan serve       # http://localhost:8000
```

Step-by-step guide with logins: [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md#c-localhost-windows-macos-linux).

## Hosting (cPanel / hPanel)

See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md). Recommended: **git push to `main` deploys automatically** (tests → SSH → `scripts/deploy.sh`) once the `DEPLOY_*` secrets are set. Without SSH: upload `skooline-<version>.zip`
(built by `bash scripts/make-release.sh` or the *Release ZIP* GitHub Action), import
`database/skooline.sql` in phpMyAdmin, set `APP_URL` and `DB_*` in `.env`, open `/admin`,
and add one cron job (`php artisan schedule:run` every minute).

## Tests

```bash
php artisan test
vendor/bin/pint --test
```
