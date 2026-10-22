# Principal Portal: research and build plan

The school portal (`skooline.com/{school}`) is the school's main workspace. The **principal** runs it:
they watch the whole school, approve things and make decisions. The **school admin** does most of the
data entry. Teachers, accountants and front-office staff each get only their own screens.

Sources: our feature spec ([`SCHOOL_ERP_FEATURES.md`](SCHOOL_ERP_FEATURES.md)) and the principal
dashboards of other school ERPs (Fedena, Entab CampusCare, Odoo school dashboards and
leave/substitution tools). All of them centre the principal on:
**one morning dashboard, approvals, staff, students, attendance, fees, results and communication.**

---

## 1. What a principal does every day (and the screen for it)

| When | Principal's job | Skooline screen |
|---|---|---|
| Morning | How many students and staff are in today? Who is absent? | Dashboard: attendance today, absent staff, alerts |
| Morning | Who covers an absent teacher's classes? | Staff leave → substitution |
| Daily | Approve or reject requests | **Approvals inbox**: staff leave, admissions, fee concessions, certificates, marks unlock |
| Daily | Visitors, complaints, parent calls | Front office: visitor log, complaints |
| Weekly | Are fees coming in? Who hasn't paid? | Fee collection %, defaulters |
| Weekly | Which classes/subjects are weak? | Results analysis, class heatmap |
| Weekly | Are teachers giving homework and taking attendance? | Teacher activity report |
| Monthly | Staff performance, salaries | Staff records, observations, payroll |
| Term | Exams, report cards, promotions | Exams, publish results, promote students |
| Any time | Send a notice to parents/staff | Notices & messages |

## 2. Roles inside a school

| Role | Sees |
|---|---|
| **Principal** | Everything, plus approvals and analytics. Read-only on money unless also admin. |
| **School Admin** | Everything (setup, users, fees). The account created with the school. |
| **Vice Principal / Coordinator** | Academics for chosen classes: attendance, timetable, exams, homework |
| **Teacher** | Own classes: attendance, homework/diary, marks, timetable, own leave |
| **Accountant** | Fees, expenses, payroll |
| **Receptionist** | Admissions enquiries, visitors, complaints, student lookup |
| Parent / Student | Separate portal (later): child's attendance, fees, results, notices |

## 3. Full feature map

Each area is a module; schools see only the modules in their plan.

### 3.1 Dashboard (principal's morning view)
- Today: students present / absent %, staff present / on leave, new admissions, fees collected today
- Alerts: classes without attendance by 10 am, absent teachers without cover, fee defaulters, low results
- Charts: attendance trend (30 days), fee collection vs target, strength by class
- Approvals waiting (count, one tap to open)
- Birthdays today (students and staff), upcoming events and holidays

### 3.2 People
- **Staff:** profiles, documents, designation, department, joining date, salary, subjects they teach,
  login accounts with roles, activate/deactivate
- **Students:** full profile, guardians, photo, documents, siblings, history; search and filter;
  promote / transfer / left; Excel import and export
- **Admissions:** enquiries with follow-up, admission form (online link too), test and interview,
  approve → student created with admission number
- **Certificates & ID cards:** leaving, character, bonafide; ID cards with photo and QR

### 3.3 Academics
- Class teacher per section, subject teacher per class/section
- Timetable builder with free-period check; daily substitution for absent teachers
- Homework / daily diary, lesson plans (principal reviews)
- Exams: terms, marks entry, grading, positions, report cards, result analysis
- Academic calendar: holidays, events, PTMs

### 3.4 Attendance
- Student daily attendance (one tap "all present"), late/leave/half-day
- Staff attendance and leave requests with principal approval
- Reports: monthly register, below 75%, continuous absentees

### 3.5 Fees & money
- Fee structure per class, discounts (sibling, staff child, scholarship) needing principal approval
- Monthly vouchers, collection, receipts, defaulters, reminders
- Expenses and income summary; payroll (later)

### 3.6 Communication
- Notices / circulars to staff, parents, a class or one student
- SMS / WhatsApp / email (credits per school), message history
- Complaints and suggestions register with status

### 3.7 Discipline & wellbeing
- Incident log per student (warning, detention, suspension), parent informed
- Teacher observations: principal's classroom visit notes and rating

### 3.8 Reports & AI
- Exportable reports for every module (Excel / PDF)
- AI assistant (already built): questions about school data, suggested actions to confirm

## 4. Build order (one step at a time, each one usable on its own)

| Step | What | Status |
|---|---|---|
| 1 | School setup: profile, sessions, classes & sections, subjects | ✅ done |
| 2 | Staff & roles: staff profiles, login accounts, roles, class teachers | ✅ done |
| 3 | Students & admissions: profiles, guardians, siblings, enquiries, online form, import, promote | ✅ done |
| 4 | Attendance: students + staff, staff leave with approval, monthly register | ✅ done |
| 5 | Fees: structure, vouchers, collection, receipts, defaulters | ✅ done |
| 6 | Timetable + substitution | ✅ done |
| 7 | Homework / diary, notices | ✅ done |
| 8 | Exams, marks, results, report cards, result analysis | ✅ done |
| 9 | Discipline, observations, certificates & ID cards, front office | ✅ done |
| 10 | Principal dashboard, approvals inbox, reports | ✅ done |
| 11 | Parent portal (sign in with phone) | ✅ done |

**Next ideas:** SMS / WhatsApp alerts to parents (absence, fees, results), online fee payment for parents,
student portal, HR payroll, library, transport and hostel modules.

## 5. Who can open what

Defined in `app/Support/SchoolAccess.php` (role + plan module). The sidebar (`app/Support/SchoolMenu.php`)
only shows pages a person can open.

| Area | Admin | Principal | Vice principal | Teacher | Accountant | Receptionist | Parent |
|---|---|---|---|---|---|---|---|
| Setup, staff, approvals | ✔ | ✔ | | | | | |
| Students (view) | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | own children |
| Students (add / edit), admissions | ✔ | ✔ | | | | ✔ | |
| Attendance | all | all | all | own classes | | | own children |
| Fees | ✔ | ✔ | | | ✔ | | own children |
| Timetable (edit) | ✔ | ✔ | ✔ | view own | | | own child's class |
| Homework / diary | ✔ | ✔ | ✔ | own classes | | | own children |
| Exams (setup / marks) | ✔ | ✔ | ✔ | marks for own classes | | | published results |
| Discipline | ✔ | ✔ | ✔ | add | | | |
| Observations | ✔ | ✔ | ✔ | | | | |
| Certificates, ID cards, front office | ✔ | ✔ | | | | ✔ | |
| Reports | ✔ | ✔ | ✔ | | ✔ | | |

Every step follows the Definition of Done in [`BUILD_PLAN.md`](BUILD_PLAN.md): role checks, school
isolation, tests, mobile layout and audit logging.
