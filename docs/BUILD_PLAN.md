# Skooline by AivraSol — Capacity Plan & Build Plan

Companion to [`SCHOOL_ERP_FEATURES.md`](SCHOOL_ERP_FEATURES.md).
Stack: **PHP 8.3 / Laravel 13 + MySQL**, one database with `school_id` (see [`ARCHITECTURE.md`](ARCHITECTURE.md)), Blade + Tailwind, database queue + one cron job. Runs on XAMPP and cPanel/hPanel.

> **Update (v0.2):** the database-per-school design below was replaced by one shared database so Skooline installs on cPanel/hPanel. The capacity numbers still apply per school; at scale, large tables are partitioned by `school_id` and big schools can move to their own DB server.

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
| **Launch** | 1–50 | Good cPanel/hPanel shared or cloud hosting plan | low |
| **Growth** | 50–300 | 2 app servers behind a load balancer, dedicated queue worker server, MySQL 8 vCPU / 32 GB + read replica for reports | ~$250–500 |
| **Scale** | 300–1,500 | 3+ app servers (auto-scale), bigger MySQL server, read replica for reports | ~$800–2,000 |
| **Large** | 1,500+ | Same pattern: add MySQL servers. Big schools can get their own DB server (Enterprise plan) | scales with revenue |

At launch, **one MySQL server can comfortably hold ~500–1,000 school databases** (with `table_open_cache` / `open_files_limit` tuned). After that, add another DB server. No code change is needed, only the tenant's DB host changes.

The estimated monthly costs are rough ballpark ranges and depend on the provider. Get real quotes from the hosting provider before budgeting.

### A.4 Rules that keep it fast (built in from day 1)
1. **Indexes** on every foreign key and on common filters (`student_id + date`, `class_id + session_id`, `status + due_date`).
2. **Pagination everywhere.** Never load "all students" into one page.
3. **Heavy work in the background queue** (run by cron): bulk SMS/WhatsApp, 1,000 vouchers/report cards as PDF, Excel imports, reminders. The user never waits.
4. **Caching** (database cache): dashboards, settings, permissions, timetables.
5. **Reports on a read replica** once on the Growth stage.
6. **Summary tables** for dashboards, e.g. daily attendance % per class and daily collection per head, updated by jobs. Dashboards don't re-count millions of rows.
7. **Files on CDN** (Cloudflare R2/S3), signed URLs.
8. **Monitoring**: `storage/logs/laravel.log`, slow-query log, uptime alerts.
9. **Backups**: nightly per-school DB dump to S3 (30-day retention) + binlog for point-in-time recovery.
10. **Load test** before launch: simulate 200 schools × 50 concurrent users (k6 / JMeter).

---

## Part B — Build Plan (what to build first)

**Principle:** build in the order that lets us **sell early**. The Super Admin and subscription system come first, because without them we can't onboard a school. Next come the modules a school pays for: students, fees, attendance and exams.

Assumed team: 2 Laravel developers, 1 frontend (Blade/Tailwind), 1 QA (part-time), designer (part-time). 1 sprint = 2 weeks.

### Phase 0 — Foundation ✅ done
Laravel 13 project, one-database multi-school layer (`school_id`), platform and school logins, Skooline design system,
SQL file for phpMyAdmin import, first-run Super Admin setup, CI (tests + Pint), Git auto-deploy, XAMPP and cPanel/hPanel support.

### Phase 1 — Super Admin & Subscriptions (Sprints 3–4, weeks 5–8)
| # | Task |
|---|---|
| 1.1 | Super Admin panel layout (ink sidebar), dashboard: schools count, active/trial/expired, MRR, expiring soon |
| 1.2 | **Plan builder**: name, price, billing cycle (monthly / quarterly / yearly / lifetime / per-student / hybrid), limits (students, staff, branches, storage, SMS), included modules |
| 1.3 | **Add school wizard**: school info, portal address, plan, trial days → create school, school admin and subscription; show credentials ✅ |
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
- Teacher screens work well on phones (mobile-first attendance page)

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

## Part C — Step by step
Detailed principal portal plan: [`PRINCIPAL_PORTAL.md`](PRINCIPAL_PORTAL.md).

1. ✅ **School setup:** school profile and logo, academic sessions, classes and sections, subjects
2. ✅ **Staff & roles:** staff records, portal logins with roles, class teachers
3. ✅ **Students & admissions**
4. ✅ **Attendance & staff leave**
5. ✅ **Fees & vouchers**
6. ✅ **Timetable, homework, notices, exams, discipline, certificates, front office, dashboard, reports, parent portal**
   (details in [`PRINCIPAL_PORTAL.md`](PRINCIPAL_PORTAL.md))

## Part D — Definition of Done (every feature)
- Permission checked (policy) + module/plan check
- Validated input (Form Requests), no raw SQL with user input
- Tenant-safe (queued jobs/files scoped to the school)
- PHPUnit feature test for the happy path, permission denial and cross-school isolation
- Works on mobile width, uses design-system components
- Audit-logged if it changes money, marks or attendance
- Code reviewed + CI green before merge
