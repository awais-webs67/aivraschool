# AivraSchool ERP — Product Research & Full Feature Specification

> Multi-tenant, subscription-based School Management SaaS by **Aivrasol**.
> One platform → many schools. Aivrasol is the **Super Admin**; every school gets its own isolated workspace managed by its own Principal/Admin.

---

## 1. Market Research — Who We Compete With

| Competitor | Region | Strengths | Gaps we can exploit |
|---|---|---|---|
| **Fedena / Uzity** | Global / India | Very broad module list, open-source roots | Dated UI, weak mobile apps, expensive custom work |
| **Teachmint** | India | Great teacher mobile app, live classes, free tier | Weak finance/accounts, limited customization |
| **Edunext / Entab CampusCare** | India | Strong fees + accounts, big-school ready | Heavy, complex, slow onboarding |
| **Classe365** | Global | LMS + CRM + SIS in one | Expensive (USD), not localized for PK |
| **PowerSchool / Blackbaud** | US | Enterprise-grade SIS, analytics | Unaffordable & irrelevant for South Asia |
| **OpenEduCat / Gibbon** | Open-source | Free, extensible | Needs technical staff, no SaaS billing |
| **Local PK tools** (various desktop / small SaaS) | Pakistan | Cheap, Urdu-friendly, bank challans | Mostly single-tenant, desktop-era UX, no real parent app, no WhatsApp/AI |

**Common market pain points (from reviews / sales conversations in this segment):**
1. Fee collection & defaulters is the #1 reason schools buy software.
2. Parents want a mobile app + WhatsApp, not emails.
3. Report cards in the school's *own* format (board-style, Cambridge, Montessori) are hard in most tools.
4. Owners with multiple branches want one consolidated dashboard.
5. Onboarding pain: importing existing student/fee data from Excel.
6. Poor/expensive SMS, no offline support when internet is weak.

**Our positioning:** *"Enterprise-grade school ERP at local price — WhatsApp-first, mobile-first, AI-assisted, multi-branch, with flexible subscriptions."*

---

## 2. Role Hierarchy & Access Model

```
AIVRASOL (Platform)
└── Super Admin  (owner of the platform – full access to everything)
    ├── Platform Staff roles: Sales, Support, Finance, Onboarding (limited, customizable)
    └── Resellers / Partners (optional – manage only their own schools)

SCHOOL (Tenant)  — may have multiple Branches/Campuses
└── School Owner / Chairman   (all branches, financial overview)
    └── Principal              (one or more branches, academic + admin control)
        └── School Admin / Administrator (day-to-day operations)
            ├── Staff: Accountant, Cashier, Receptionist/Front-desk, Librarian,
            │          Transport Manager, Hostel Warden, Store Keeper, HR,
            │          Exam Controller, Nurse, Counselor, IT Admin
            ├── Teachers: Class Teacher, Subject Teacher, Coordinator/HOD
            ├── Students
            └── Parents / Guardians (one parent ↔ many children, across branches)
```

### Access-control rules
- **RBAC + permissions matrix**: every module × action (view / create / edit / delete / approve / export / print).
- **Custom roles**: school admin can clone a role and tick/untick permissions (e.g. "Senior Accountant").
- **Scope limits**: per-branch, per-class, per-section (a class teacher only sees own section).
- **Super Admin impersonation**: "Login as school admin" for support, with full audit log + school's consent setting.
- **Data isolation**: every record carries `tenant_id` (+ `branch_id`); enforced at DB level (Postgres Row-Level Security) not just in code.
- **2FA** (OTP SMS / email / authenticator) mandatory for Super Admin, optional per school role.
- **Session/device management**: see active devices, force logout, IP allow-list for admin roles.

---

## 3. Super Admin (Aivrasol) Panel

### 3.1 Dashboard
- Total schools (active / trial / suspended / expired), total students, total users
- **MRR / ARR**, revenue this month, collections vs. due, churn rate, LTV, trial→paid conversion
- Expiring subscriptions (next 7/15/30 days), overdue invoices
- Usage heatmap: which modules schools actually use, last login per school
- SMS/WhatsApp credit consumption, storage usage per tenant
- System health: queue status, failed jobs, error rate, backup status

### 3.2 School (Tenant) Management
- Add school wizard: name, logo, address, city, contact, owner info, **subdomain** (`schoolname.aivraschool.com`), optional **custom domain**
- Auto-create school admin account + send welcome credentials (SMS/WhatsApp/email)
- Assign plan, trial days, branches limit, student limit
- Enable/disable individual modules per school (feature flags)
- Suspend / reactivate / archive / delete (with data export before delete)
- Impersonate (login-as) with audit
- Per-school settings override (SMS sender ID, payment gateways, storage quota)
- Data import service: Excel/CSV templates for students, staff, fees, opening balances
- School notes, onboarding checklist & status, assigned account manager

### 3.3 Subscription & Billing Engine (fully customizable)
**Billing models (combine freely):**
| Model | Example |
|---|---|
| Monthly | PKR 5,000 / month |
| Quarterly / Half-yearly | discounted bundles |
| Yearly | 2 months free |
| **Lifetime** | one-time fee + optional yearly AMC (maintenance) |
| **Per-student** | PKR 50 / active student / month (min. charge) |
| **Tiered per-student** | 0–300 @ 60, 301–1000 @ 45, 1000+ @ 35 |
| **Per-branch** | base + per extra campus |
| **Hybrid** | base fee + per-student + add-ons |
| Custom/Enterprise | negotiated price per school |

**Plan builder:**
- Create plans (Basic / Standard / Premium / Enterprise / Custom) with: price, cycle, included modules, limits (students, staff, branches, storage GB, SMS credits, admins)
- **Add-ons**: extra modules (Transport GPS, Hostel, LMS, AI pack), extra SMS bundles, extra storage, WhatsApp API
- Free trial (X days, configurable), freemium tier option
- Coupons & discounts (%, fixed, first-N-months, referral)
- Setup / onboarding fee, data migration fee
- Proration on upgrade/downgrade mid-cycle
- Grace period → read-only mode → suspension (all configurable)
- Auto-renewal + reminders (15/7/3/1 days before, on expiry)
- Invoices & receipts (PDF, tax/GST/sales-tax fields), credit notes, refunds
- Payment methods: bank transfer (manual verify), JazzCash, EasyPaisa, cards (Stripe/PayFast/Safepay), cheque — with manual payment recording by Super Admin
- Student-count auto-sync for per-student billing (snapshot at billing date)
- Billing history, ledger per school, export to Excel

### 3.4 Platform Operations
- Global announcements / in-app banners to all or selected schools
- Support ticket system (schools raise tickets, SLA, chat)
- Knowledge base / help center & video tutorials
- Email/SMS/WhatsApp template manager (global defaults; schools can override)
- SMS gateway & WhatsApp Business API provider config, credit pricing & resale
- Global master data: countries, cities, boards (Federal, Punjab, Sindh, KPK boards, Cambridge, Aga Khan), religions, blood groups, etc.
- Platform staff & reseller management with commission tracking
- Audit logs (who did what, when, from which IP) across platform
- Backup & restore per tenant, data export (GDPR-style "download all my data")
- Release notes / changelog, maintenance-mode scheduler
- Leads CRM for Aivrasol sales (demo requests from website → trial)

---

## 4. School-Level Modules (Tenant)

Every module below is toggleable by plan. ✅ = MVP, 🔷 = Phase 2, 🔶 = Phase 3.

### 4.1 School Setup & Configuration ✅
- School profile, logo, letterhead, stamp, principal signature (used on all prints)
- **Multi-branch/campus** with consolidated owner view 🔷
- Academic sessions/years (e.g. 2026-27), session rollover / promotion
- Classes, sections, streams/groups (Science, Arts, Computer, Pre-Medical, O/A Level)
- Subjects (compulsory/optional, theory/practical, credit hours), subject-teacher mapping
- Shifts (morning/evening), houses, periods & bell schedule
- Holidays & weekend config, working days
- Grading systems (percentage, GPA, letter grades, custom bands)
- Number formats: roll no., admission no., invoice no., receipt no. patterns
- Language (English / **Urdu RTL**), currency, timezone, date format
- Custom fields on any entity (student, staff, etc.)

### 4.2 Front Office & Admissions (CRM) ✅/🔷
- Online admission form (public link / school website embed), with document upload
- Enquiry / lead management: source tracking, follow-ups, status pipeline (Kanban)
- Admission test scheduling, result entry, interview, merit list
- Admission approval workflow → auto-generate student + parent accounts + first fee voucher
- Visitor log (with photo/CNIC), gate pass, call log, postal dispatch/receive
- Complaint & suggestion register

### 4.3 Student Information System (SIS) ✅
- Complete profile: personal, B-Form/CNIC, photo, medical, previous school, siblings, guardians, address, emergency contacts, documents
- Bulk import/export (Excel), bulk photo upload
- Student timeline (admission → attendance → fees → exams → discipline → certificates)
- Promotion / demotion / class transfer / section change (bulk)
- Branch transfer, struck-off / left / alumni status with reason
- Sibling linking (for sibling discounts & single parent login)
- **ID cards** (designer, QR/barcode, bulk print)
- **Certificates**: School Leaving Certificate, Character, Bonafide, Birth, Provisional, custom templates with auto-numbering
- Student categories (scholarship, staff child, orphan, etc.)

### 4.4 Attendance ✅
- Daily / period-wise student attendance (web + teacher app, one-tap "all present")
- Statuses: Present, Absent, Late, Leave, Half-day, Holiday
- **Auto SMS/WhatsApp/push to parents** on absence / late
- Biometric / RFID card / QR / **face recognition** integration 🔷
- Staff attendance with check-in/out, geofenced mobile attendance 🔷
- Leave applications (student by parent app, staff by staff app) with approval flow
- Reports: monthly register, defaulters (<75%), class-wise %, continuous absentees alert

### 4.5 Timetable 🔷
- Period/bell setup, class timetable builder (drag-drop)
- **Auto timetable generator** with constraints (teacher availability, max periods/day, labs)
- Teacher timetable, room/lab allocation, clash detection
- **Substitution / proxy management** when a teacher is absent (suggests free teachers)
- Exam timetable / date sheet

### 4.6 Academics, Homework & LMS 🔷
- Syllabus & lesson plans, curriculum progress tracking per class/subject
- Daily diary / homework (text, images, PDF, voice note) → parent app
- Assignments with submission, marking, feedback, plagiarism-light check
- Study material library (notes, videos, links) per class/subject
- Online classes (Zoom / Google Meet / Jitsi integration), recordings
- Online quizzes with MCQ auto-grading, question bank, timer, randomization
- Student progress analytics per subject

### 4.7 Examinations & Report Cards ✅
- Exam types (monthly test, mid-term, final, unit test, pre-board), weightage-based terms
- Marks entry (teacher web/app, Excel import), absent/medical flags, moderation & lock
- Grade/GPA/CGPA calculation, positions (class/section), pass/fail rules, grace marks
- **Report card designer** — drag-drop template builder (board format, Cambridge, Montessori/skills-based, co-scholastic: discipline, punctuality, arts)
- Teacher & principal remarks (with **AI suggested remarks** 🔶)
- Admit cards / roll-no slips, seating plan, invigilation duty roster
- Mark sheets, tabulation sheet, result analytics (subject-wise, teacher-wise, topper lists, comparison vs. last term)
- Online result publishing to parent app with "fee clearance required" option
- Question paper generator from question bank 🔶

### 4.8 Fees & Finance (most important for sales) ✅
- Fee heads (tuition, admission, annual, exam, lab, transport, hostel, sports, fine…)
- Fee structures per class / category, one-time vs. recurring (monthly/quarterly/yearly)
- **Discounts & concessions**: sibling, staff child, merit scholarship, custom %, with approval
- **Fee vouchers / challans**: bank-format 3-copy challan (bank/school/student), bulk generation, 1-Bill / bank barcode
- Due dates, **late fine** (fixed/per-day/slab), auto-apply
- Partial payments, installments, advance payments, arrears carry-forward
- Collection at counter (cash/cheque/bank/online) with instant receipt print (thermal + A4)
- **Online payments**: JazzCash, EasyPaisa, Raast, cards, bank 1-Link/1-Bill integration with auto-reconciliation
- Defaulter lists & automated reminders (SMS/WhatsApp/push) with escalation
- Fee freeze/struck-off on non-payment (configurable)
- Refunds, security deposit tracking
- Reports: daily collection, cashier-wise, head-wise, class-wise, outstanding, aging, projected vs. collected

### 4.9 Accounts (Accounting) 🔷
- Chart of accounts, double-entry ledger (auto-posted from fees, payroll, expenses)
- Income & expense entries, vouchers (payment/receipt/journal/contra)
- Bank accounts, bank reconciliation, cash book, petty cash
- Budgeting per department
- Trial balance, P&L, balance sheet, cash-flow
- Vendor payments & purchase bills
- Export to Excel / Tally-like formats

### 4.10 HR & Payroll 🔷
- Staff profiles, qualifications, documents, contracts, CNIC
- Recruitment: job posting, applicants, interviews 🔶
- Departments & designations, joining/leaving, experience letters
- Leave types, balances, carry-forward, approval chain
- Salary structure (basic, allowances, deductions), increments, arrears
- Attendance-based deductions, late policy, overtime
- Advances & loans with installments
- EOBI / provident fund / income-tax fields
- Payslips (PDF, sent to staff app), bank transfer sheet
- Staff appraisal / performance reviews, teacher observation forms 🔶

### 4.11 Communication Hub ✅
- **SMS** (bulk, class-wise, defaulters, custom groups) with credit tracking
- **WhatsApp Business API** (official) — fee reminders, results, absence alerts, voucher PDF
- Push notifications (mobile apps), email
- Notice board / circulars with read receipts
- Two-way chat: parent ↔ teacher (with admin moderation & time windows)
- Event & PTM (parent-teacher meeting) scheduling with slot booking 🔷
- Surveys & feedback forms, polls 🔷
- Automated triggers: birthday wishes, fee due, absence, results published, exam schedule

### 4.12 Transport 🔷
- Routes, stops, vehicles, drivers & conductors, fees per stop
- Student ↔ route assignment
- **Live GPS tracking** in parent app (driver app or GPS device integration) 🔶
- Pickup/drop alerts, vehicle maintenance, fuel log, document expiry (fitness, insurance)

### 4.13 Hostel / Boarding 🔶
- Hostels, rooms, beds, allotment, hostel fee
- Mess menu, attendance, visitor log, leave/outing passes

### 4.14 Library 🔷
- Book catalog (ISBN lookup), barcode/QR, multiple copies
- Issue/return, fines, reservations, member cards
- E-library (PDF/eBooks) 🔶

### 4.15 Inventory & Assets 🔷
- Item categories, stock in/out, suppliers, purchase orders
- Uniform & book shop sales to students (linked to fee/POS)
- Fixed assets register, depreciation, maintenance

### 4.16 Health, Discipline & Wellbeing 🔶
- Medical records, vaccinations, infirmary visits, allergies alerts
- Discipline incidents, merit/demerit points, counselor notes (confidential)
- Behaviour reports to parents

### 4.17 Events, Calendar & Activities 🔷
- Academic calendar (holidays, exams, events) synced to apps
- Co-curricular activities, clubs, sports, competitions, house points
- Photo/video gallery per event (parent app)

### 4.18 School Website & Online Presence 🔶
- Built-in website builder (templates) on subdomain/custom domain
- Auto-sync: news, events, gallery, admissions form, results lookup

### 4.19 Alumni 🔶
- Alumni directory, events, donations

### 4.20 Reports & Analytics ✅/🔷
- 100+ ready reports with filters, Excel/PDF export, print
- Custom report builder (pick fields/filters) 🔷
- Principal/owner dashboard: strength, attendance %, collection %, defaulters, exam performance, staff attendance
- Branch comparison dashboard (multi-campus owners)
- Scheduled reports by email/WhatsApp (e.g. daily collection to owner at 8 PM)

### 4.21 AI Features (differentiator) 🔶
- AI report-card remarks generator
- AI question paper / quiz generator from syllabus
- At-risk student prediction (attendance + marks + fee pattern)
- Fee default prediction & smart reminder timing
- AI chat assistant for parents ("what's my child's homework / fee due?") over WhatsApp
- Natural-language reports for principal ("show class 8 defaulters above 10k")
- Lesson plan generator for teachers

---

## 5. Portals & Mobile Apps

| App | Key features |
|---|---|
| **Super Admin web** | Everything in §3 |
| **School Admin / Principal web** | All modules, dashboards, settings |
| **Owner app** 🔷 | Collections, strength, alerts across branches — read-mostly |
| **Teacher app** ✅ | Attendance, homework/diary, marks entry, timetable, leave, chat, payslip |
| **Parent app** ✅ | Multiple children, attendance, homework, fee vouchers + **pay online**, results, notices, chat, transport tracking, leave request |
| **Student app** 🔷 | Timetable, homework, LMS, quizzes, results, library |
| **Driver app** 🔶 | Start trip → GPS share, mark pickup/drop |
| **Staff / Accountant** | Fee collection on mobile, receipts |

All apps: white-label option (school's own name/logo in Play Store) as a premium add-on.

---

## 6. Non-Functional Requirements (production level)

- **Multi-tenancy:** shared DB + `tenant_id` + Postgres RLS (cost-efficient); option to move large schools to dedicated DB.
- **Security:** OWASP top-10, encrypted passwords (argon2/bcrypt), encryption at rest for sensitive docs, signed URLs for files, rate limiting, audit logs, 2FA.
- **Performance:** paginated APIs, caching (Redis), background jobs for bulk SMS/vouchers/report cards, CDN for files.
- **Reliability:** daily automated backups + point-in-time recovery, per-tenant restore, monitoring & alerting (Sentry, uptime).
- **Offline/Weak internet:** PWA + mobile apps with offline attendance/marks sync.
- **Localization:** English + Urdu (RTL), PKR first, multi-currency later.
- **Printing:** pixel-accurate PDFs (vouchers, report cards, ID cards, certificates).
- **Scalability target:** 1,000+ schools, 1M+ students.
- **Compliance:** data ownership with school, export anytime, privacy policy, consent for photos.

---

## 7. Recommended Tech Stack

| Layer | Choice | Why |
|---|---|---|
| Backend | **NestJS (TypeScript)** or Laravel (PHP) | Modular, RBAC friendly, large talent pool in PK |
| Database | **PostgreSQL** (+ RLS) | Multi-tenant isolation, reporting |
| Cache / Queue | Redis + BullMQ | Bulk SMS, PDFs, reminders |
| Web frontend | **Next.js + React + Tailwind + shadcn/ui** | Fast, SEO for school websites |
| Mobile | **Flutter** or React Native (one codebase, Android + iOS) | Parent/Teacher apps |
| Files | S3-compatible (AWS S3 / Cloudflare R2 / Wasabi) | Cheap storage |
| PDFs | Puppeteer/Playwright HTML→PDF | Designer-friendly templates |
| Auth | JWT + refresh tokens, OTP | Mobile + web |
| Payments | JazzCash, EasyPaisa, Safepay/PayFast, Stripe | Local + international |
| Messaging | Local SMS aggregator + Meta WhatsApp Cloud API + FCM | |
| Infra | Docker, CI/CD (GitHub Actions), VPS/AWS | |
| Monitoring | Sentry, Grafana/Prometheus, UptimeRobot | |

---

## 8. Core Data Model (high level)

```
platform: plans, plan_features, add_ons, coupons, tenants, tenant_subscriptions,
          platform_invoices, platform_payments, platform_users, tickets, audit_logs

tenant:   branches, academic_sessions, classes, sections, subjects, class_subjects,
          users, roles, permissions, role_permissions, user_roles(scope)
          students, guardians, student_guardians, enrollments(session, class, section)
          staff, departments, designations
          attendance_students, attendance_staff, leaves
          timetables, periods, substitutions
          homework, assignments, submissions, materials, quizzes, questions
          exams, exam_subjects, marks, grade_scales, report_card_templates, results
          fee_heads, fee_structures, fee_discounts, invoices(vouchers), invoice_items,
          payments, receipts, fines, refunds
          accounts, journal_entries, expenses, vendors
          salary_structures, payrolls, payslips, loans
          messages, notices, notification_logs, sms_credits
          routes, stops, vehicles, transport_assignments
          books, book_copies, issues, inventory_items, stock_moves
          documents, certificates, custom_fields, settings
```
Every tenant table: `id, tenant_id, branch_id?, created_by, created_at, updated_at, deleted_at`.

---

## 9. Phased Roadmap

### Phase 1 — MVP (sellable product) ~3–4 months
Super Admin (schools, plans, subscriptions, billing, impersonation) · School setup · Roles/permissions · Admissions (basic) · SIS · Attendance · Fees & vouchers & online payment · Exams & report cards · SMS/WhatsApp · Notices · Teacher & Parent apps · Core reports & dashboards · Excel import

### Phase 2 — Competitive parity ~3 months
Multi-branch · Timetable + substitution · Homework/LMS/quizzes · Accounts · HR & Payroll · Transport (basic) · Library · Inventory · Events/calendar/gallery · PTM booking · Student app · Biometric integration · Custom report builder

### Phase 3 — Market leader
AI pack · GPS live tracking & driver app · Hostel · Website builder · Alumni · Health & discipline · White-label apps · Reseller program · Recruitment · Question paper generator · Face-recognition attendance

---

## 10. Suggested Pricing (Pakistan market, adjustable in plan builder)

| Plan | Price idea | Includes |
|---|---|---|
| Starter | PKR 3,000–5,000/mo up to 200 students | SIS, attendance, fees, exams, notices, parent app |
| Standard | PKR 40–60 per student/mo | + timetable, homework, HR/payroll, accounts, library |
| Premium | PKR 70–90 per student/mo | + transport, LMS, multi-branch, WhatsApp, custom report cards |
| Enterprise / Lifetime | Custom (e.g. one-time + 15–20% yearly AMC) | Everything + white-label, dedicated DB, AI pack |

Add-ons: SMS bundles, WhatsApp API, GPS, extra storage, white-label app, data migration.

---

## 11. Our Differentiators (why schools switch to us)
1. **WhatsApp-first** — vouchers, results, absence alerts, AI assistant on WhatsApp.
2. **Bank challan + JazzCash/EasyPaisa auto-reconciliation** — fees marked paid automatically.
3. **Drag-drop report card & voucher designer** — any school's format in minutes.
4. **Multi-branch owner app** — live collection and strength across all campuses.
5. **1-day onboarding** — Excel import wizard + Aivrasol onboarding team.
6. **Offline-capable** teacher app for weak internet areas.
7. **AI features** competitors don't have locally.
8. **Flexible billing** — monthly, yearly, lifetime, per-student, hybrid.

---

## 12. Open Decisions (to finalize before development)
1. Target market first: Pakistan only, or also Middle East / India?
2. Backend: NestJS vs. Laravel? Mobile: Flutter vs. React Native?
3. Shared DB with RLS for all, or dedicated DB for enterprise schools?
4. Which SMS aggregator & WhatsApp BSP to partner with?
5. Will we offer resellers/franchise partners in Phase 1?
6. Branding: will parent app be "AivraSchool" or white-labeled per school by default?
