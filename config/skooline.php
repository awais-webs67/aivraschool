<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product
    |--------------------------------------------------------------------------
    */

    'name' => 'Skooline',
    'company' => 'AivraSol',
    'company_url' => 'https://aivrasol.com',
    'support_email' => env('SKOOLINE_SUPPORT_EMAIL', 'info@aivrasol.com'),
    'sales_email' => env('SKOOLINE_SALES_EMAIL', 'sales@aivrasol.com'),
    'currency' => env('SKOOLINE_CURRENCY', 'PKR'),

    /*
    |--------------------------------------------------------------------------
    | School domains
    |--------------------------------------------------------------------------
    |
    | Each school is served on "{subdomain}.{school_domain}". The school domain
    | is normally the first central domain (e.g. skooline.com).
    |
    */

    'school_domain' => env('SCHOOL_DOMAIN', explode(',', (string) env('CENTRAL_DOMAINS', 'localhost'))[0]),

    'reserved_subdomains' => [
        'www', 'admin', 'app', 'api', 'mail', 'smtp', 'ftp', 'cdn', 'static', 'assets',
        'support', 'help', 'docs', 'blog', 'status', 'billing', 'dashboard', 'demo',
        'skooline', 'aivrasol', 'test', 'staging', 'dev',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscriptions
    |--------------------------------------------------------------------------
    */

    // Days a school keeps access after its subscription end date.
    'grace_days' => (int) env('SKOOLINE_GRACE_DAYS', 7),

    'default_trial_days' => (int) env('SKOOLINE_TRIAL_DAYS', 14),

    // Billing model => [label, cycle length in months (null = never renews)]
    'billing_models' => [
        'monthly' => ['label' => 'Monthly', 'months' => 1],
        'quarterly' => ['label' => 'Quarterly', 'months' => 3],
        'half_yearly' => ['label' => 'Half-yearly', 'months' => 6],
        'yearly' => ['label' => 'Yearly', 'months' => 12],
        'lifetime' => ['label' => 'Lifetime', 'months' => null],
        'per_student' => ['label' => 'Per student / month', 'months' => 1],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    |
    | Every school feature belongs to a module. Plans switch modules on/off.
    |
    */

    'modules' => [
        'students' => ['label' => 'Students & SIS', 'icon' => 'users', 'description' => 'Profiles, guardians, documents, promotions'],
        'admissions' => ['label' => 'Admissions & Enquiries', 'icon' => 'clipboard', 'description' => 'Online forms, leads, admission tests'],
        'attendance' => ['label' => 'Attendance', 'icon' => 'check-circle', 'description' => 'Students & staff, absence alerts'],
        'fees' => ['label' => 'Fees & Challans', 'icon' => 'banknotes', 'description' => 'Vouchers, fines, discounts, online payments'],
        'exams' => ['label' => 'Exams & Report Cards', 'icon' => 'academic-cap', 'description' => 'Marks, GPA, positions, report cards'],
        'timetable' => ['label' => 'Timetable', 'icon' => 'calendar', 'description' => 'Class & teacher timetables, substitutions'],
        'homework' => ['label' => 'Homework & Diary', 'icon' => 'book-open', 'description' => 'Daily diary, assignments'],
        'lms' => ['label' => 'LMS & Online Quizzes', 'icon' => 'play', 'description' => 'Study material, online classes, quizzes'],
        'communication' => ['label' => 'SMS, WhatsApp & Notices', 'icon' => 'chat', 'description' => 'Bulk messages, circulars, alerts'],
        'hr_payroll' => ['label' => 'HR & Payroll', 'icon' => 'briefcase', 'description' => 'Staff, leaves, salaries, payslips'],
        'accounts' => ['label' => 'Accounts', 'icon' => 'calculator', 'description' => 'Income, expenses, ledgers, P&L'],
        'library' => ['label' => 'Library', 'icon' => 'library', 'description' => 'Catalog, issue/return, fines'],
        'transport' => ['label' => 'Transport', 'icon' => 'truck', 'description' => 'Routes, vehicles, transport fees'],
        'hostel' => ['label' => 'Hostel', 'icon' => 'home', 'description' => 'Rooms, allotment, mess'],
        'inventory' => ['label' => 'Inventory', 'icon' => 'cube', 'description' => 'Stock, assets, uniform & book sales'],
        'certificates' => ['label' => 'ID Cards & Certificates', 'icon' => 'identification', 'description' => 'Leaving, character, ID cards'],
        'reports' => ['label' => 'Reports & Analytics', 'icon' => 'chart', 'description' => 'Dashboards and exportable reports'],
        'multi_branch' => ['label' => 'Multi-branch', 'icon' => 'building', 'description' => 'Several campuses under one school'],
        'mobile_apps' => ['label' => 'Teacher & Parent Apps', 'icon' => 'phone', 'description' => 'Android & iOS apps'],
        'ai' => ['label' => 'AI Assistant', 'icon' => 'sparkles', 'description' => 'AI remarks, question papers, insights'],
    ],

];
