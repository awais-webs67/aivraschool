# Skooline by AivraSol — Capacity Plan & Build Plan

Companion to [`SCHOOL_ERP_FEATURES.md`](SCHOOL_ERP_FEATURES.md).
Stack: **Laravel 12 (PHP 8.3+) + MySQL 8**, database-per-school (stancl/tenancy), Redis, Horizon, Livewire + Tailwind, Flutter apps.

---

## Part A — Can it handle many schools? (Capacity Plan)

### A.1 How much data does one school create?
Estimate for a **large school: 1,000 students, 80 staff**, one academic year:

| Data | Calculation | Rows / year |
|---|---|---|
| Student attendance | 1,000 × ~220 days | 220,000 |
| Staff attendance | 80 × 260 days | 21,000 |
| Fee vouchers + line items | 1,000 × 12 months × ~4 heads | ~60,000 |
| Payments / receipts | ~1 per voucher | ~12,000 |
| Exam marks | 1,000 × 8 subjects × 6 exams | 48,000 |
| Homework / diary | 30 sections × 6 subjects × 200 days | 36,000 |
| SMS / WhatsApp / push logs | ~100 per student | 100,000 |
| Audit log | all create/update actions | ~150,000 |
| **Total** | | **≈ 650,000 rows ≈ 300–500 MB/year** |

Photos, documents and PDFs go to **S3/R2 object storage**, not into MySQL. That keeps the database small. Plan on about 1–3 GB of files per school per year.

An average school (300–500 students) creates roughly a third of this.

### A.2 Why this is easy for MySQL
- **Every school has its own database.** School A's 650k rows never slow down School B's queries, because each school only searches its own small tables.
- A single MySQL table handles **tens of millions of rows** with correct indexes. One school reaches about 1M attendance rows only after ~5 years.
- At **500 schools** the platform holds about 300M rows and 150–250 GB in total. That is spread over 500 small databases, which is a normal workload for MySQL.
- Old sessions can be archived (read-only) after 3–5 years to keep the active tables small.

### A.3 Server growth path (scale only when needed)

| Stage | Schools | Setup | Est. cost/month |
|---|---|---|---|
| **Launch** | 1–50 | 1 app server (4 vCPU / 8 GB) + MySQL on a separate server (4 vCPU / 8 GB) + Redis. Files on R2/S3 | ~$60–120 |
| **Growth** | 50–300 | 2 app servers behind a load balancer, dedicated queue worker server, MySQL 8 vCPU / 32 GB + read replica for reports | ~$250–500 |
| **Scale** | 300–1,500 | 3+ app servers (auto-scale), 2–3 MySQL servers with schools distributed across them (stancl/tenancy lets each school point to a different DB host), Redis cluster | ~$800–2,000 |
| **Large** | 1,500+ | Same pattern: add MySQL servers. Big schools can get their own DB server (Enterprise plan) | scales with revenue |

At launch, **one MySQL server can comfortably hold ~500–1,000 school databases** (with `table_open_cache` / `open_files_limit` tuned). After that, add another DB server. No code change is needed, only the tenant's DB host changes.

The estimated monthly costs are rough ballpark ranges and depend on the provider. Get real quotes from the hosting provider before budgeting.

### A.4 Rules that keep it fast (built in from day 1)
1. **Indexes** on every foreign key and on common filters (`student_id + date`, `class_id + session_id`, `status + due_date`).
2. **Pagination everywhere.** Never load "all students" into one page.
3. **Heavy work in queues** (Horizon): bulk SMS/WhatsApp, 1,000 vouchers/report cards as PDF, Excel imports, reminders. The user never waits.
4. **Caching** (Redis): dashboards, settings, permissions, timetables.
5. **Reports on a read replica** once on the Growth stage.
6. **Summary tables** for dashboards, e.g. daily attendance % per class and daily collection per head, updated by jobs. Dashboards don't re-count millions of rows.
7. **Files on CDN** (Cloudflare R2/S3), signed URLs.
8. **Monitoring**: Sentry for errors, slow-query log, Horizon metrics, uptime alerts.
9. **Backups**: nightly per-school DB dump to S3 (30-day retention) + binlog for point-in-time recovery.
10. **Load test** before launch: simulate 200 schools × 50 concurrent users (k6 / JMeter).

---

## Part B — Build Plan (what to build first)

**Principle:** build in the order that lets us **sell early**. The Super Admin and subscription system come first, because without them we can't onboard a school. Next come the modules a school pays for: students, fees, attendance and exams.

Assumed team: 2 Laravel developers, 1 frontend (Livewire/Tailwind), 1 Flutter developer from Sprint 7, 1 QA (part-time), designer (part-time). 1 sprint = 2 weeks.

### Phase 0 — Foundation (Sprints 1–2, weeks 1–4)
| # | Task | Output |
|---|---|---|
| 0.1 | Laravel 12 project, Git flow, `.env` per environment (local / staging / production) | Repo skeleton |
| 0.2 | CI: GitHub Actions running Pest tests, Larastan, Pint on every push | Quality gate |
| 0.3 | **stancl/tenancy** setup: central DB + tenant DB per school, subdomain routing `{school}.skooline.com` | Multi-tenant core |
| 0.4 | Auth: login, logout, forgot password (OTP via SMS/email), 2FA, rate limiting, device sessions | Secure login |
| 0.5 | RBAC with spatie/laravel-permission: roles, permissions, custom roles | Access control |
| 0.6 | **Design system**: Tailwind config with AivraSol tokens (`#FF5B35`, `#333F48`, `#191F24`…), Bebas Neue + Inter, components (buttons, inputs, tables, modals, cards, badges, alerts), app layout (sidebar, top bar, breadcrumbs) | UI kit |
| 0.7 | Audit log (spatie/activitylog), file storage on S3/R2, queue + Horizon, Sentry | Infra |
| 0.8 | Staging server + wildcard SSL (Cloudflare) | Live staging |

### Phase 1 — Super Admin & Subscriptions (Sprints 3–4, weeks 5–8)
| # | Task |
|---|---|
| 1.1 | Super Admin panel layout (ink sidebar), dashboard: schools count, active/trial/expired, MRR, expiring soon |
| 1.2 | **Plan builder**: name, price, billing cycle (monthly / quarterly / yearly / lifetime / per-student / hybrid), limits (students, staff, branches, storage, SMS), included modules |
| 1.3 | **Add school wizard**: school info, logo, subdomain, plan, trial days → auto-create tenant DB, run migrations, seed defaults, create school admin, send credentials (email/SMS/WhatsApp) |
| 1.4 | Module access middleware: a module not in the plan is hidden from the menu and its routes are blocked. Limits are enforced (e.g. can't add student #301 on a 300 plan) |
| 1.5 | Subscription lifecycle: trial → active → grace period → read-only → suspended; renew, upgrade/downgrade with proration; scheduler for expiry + reminders |
| 1.6 | Platform invoices & payments (manual bank/JazzCash entry first, online gateway later), invoice PDF, school ledger |
| 1.7 | Login-as-school (impersonation) with audit; suspend/activate; per-school settings |
| 1.8 | Coupons/discounts, platform staff accounts with limited roles |

**Milestone:** we can create a school, give it credentials, and control its subscription.

### Phase 2 — School Setup & Users (Sprint 5, weeks 9–10)
- School profile, logo, letterhead, principal signature, stamp
- Branches (multi-campus ready from the start, even if one branch is used)
- Academic sessions, classes, sections, subjects, class-subject-teacher mapping, shifts
- Grading scales, holidays, number formats (admission no., voucher no.)
- School roles: Principal, Admin, Accountant, Teacher, Receptionist… + custom roles
- Staff basic profiles + user accounts

### Phase 3 — Students & Admissions (Sprint 6, weeks 11–12)
- Student profile (personal, guardians, documents, photo, B-Form), sibling linking → one parent account for many children
- Admission form + approval → auto student + parent login
- **Excel import wizard** (students, guardians, opening fee balances): critical for onboarding
- Promotion / transfer / struck-off / left, bulk actions
- ID cards & certificates (Leaving, Character), PDF templates

### Phase 4 — Attendance (Sprint 7, weeks 13–14)
- Student daily attendance (web), one-tap all present, statuses
- Staff attendance (manual first; biometric in later phase)
- Leave requests & approval
- Absence alert job (SMS/WhatsApp/push to parent)
- Reports: monthly register, class %, defaulters
- **Flutter developer starts:** API (Sanctum) + Teacher app shell (login, attendance)

### Phase 5 — Fees (Sprints 8–9, weeks 15–18) ★ most important for sales
- Fee heads, fee structures per class, discounts (sibling, staff child, scholarship), approval
- Monthly voucher generation (bulk, queued), **3-copy bank challan PDF**, family voucher
- Late fine rules (fixed / per-day), arrears carry-forward, partial payments, advance
- Counter collection + receipt (A4 + thermal)
- Online payment: JazzCash / EasyPaisa first, then 1Bill/1GO integration with auto-reconcile
- Defaulter list + automatic reminders
- Reports: daily collection, cashier-wise, head-wise, outstanding, aging

### Phase 6 — Exams & Report Cards (Sprint 10, weeks 19–20)
- Exam terms, exam types, weightage, subject max marks
- Marks entry (web + teacher app + Excel), lock/unlock
- Grade/GPA, positions, pass/fail rules
- Report card templates (2–3 ready formats + basic customization; full drag-drop designer in Phase 9)
- Admit cards, tabulation sheet, result publishing (with fee-clearance option)

### Phase 7 — Communication & Dashboards (Sprint 11, weeks 21–22)
- SMS gateway + WhatsApp Cloud API + push (FCM), templates, credits per school
- Notice board / circulars, events calendar
- Homework / daily diary (text + attachment)
- Principal dashboard (strength, attendance %, collection %, defaulters, results)
- Core reports with Excel/PDF export

### Phase 8 — Mobile Apps & Launch (Sprint 12, weeks 23–24)
- **Teacher app**: attendance, homework, marks, timetable view, leave, notices
- **Parent app**: children switcher, attendance, homework, fee vouchers + pay online, results, notices
- Security review, load test, backup/restore drill, bug fixing
- Help center (basic) + onboarding videos
- **Beta with 3–5 pilot schools** → fix feedback → **public launch (v1.0)**

**~6 months to v1.0: a sellable product.**

### Phase 9 — v1.5 (months 7–9): competitive parity
Timetable + auto-generator + substitution · LMS (assignments, materials, online quizzes) · HR & Payroll · Accounts (double-entry, P&L) · Library · Transport (routes, fees) · Inventory · Front office (visitors, enquiries CRM) · PTM booking · Student app · Biometric integration · Drag-drop report card & voucher designer · Custom report builder

### Phase 10 — v2.0 (months 10–12+): market leader
AI pack (remarks, question papers, at-risk students, WhatsApp assistant) · GPS live bus tracking + driver app · Hostel · Website builder · Health & discipline · Alumni · Urdu RTL interface · Owner multi-branch app · Face-recognition attendance

---

## Part C — What we build THIS WEEK (Sprint 1 start)
1. Create Laravel 12 project in this repo, push to `main` via PR
2. Install & configure: stancl/tenancy, spatie/laravel-permission, spatie/laravel-activitylog, Livewire 3, Tailwind, Pest, Larastan, Pint
3. Central DB migrations: `plans`, `plan_features`, `tenants`, `domains`, `subscriptions`, `platform_users`
4. Tailwind theme with AivraSol tokens + Skooline app layout (sidebar/top bar) + login page
5. GitHub Actions CI
6. Super Admin login + empty dashboard on staging

---

## Part D — Definition of Done (every feature)
- Permission checked (policy) + module/plan check
- Validated input (Form Requests), no raw SQL with user input
- Tenant-safe (queued jobs/files scoped to the school)
- Pest feature test for the happy path + permission denial
- Works on mobile width, uses design-system components
- Audit-logged if it changes money, marks or attendance
- Code reviewed + CI green before merge
