<?php

use App\Http\Controllers\School;
use App\Http\Controllers\School\Academics;
use App\Http\Controllers\School\Daily;
use App\Http\Controllers\School\Money;
use App\Http\Controllers\School\People;
use App\Http\Controllers\School\Setup;
use App\Http\Middleware\EnsureDatabaseIsUpToDate;
use App\Http\Middleware\EnsureSchoolCanAccess;
use App\Http\Middleware\EnsureUserBelongsToSchool;
use App\Http\Middleware\IdentifySchool;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| School portal routes (skooline.com/{school}/...)
|--------------------------------------------------------------------------
|
| {school} is the school's slug. IdentifySchool makes that school current,
| so every query below only sees its own data.
|
*/

Route::prefix('{school}')
    ->where(['school' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->middleware([EnsureDatabaseIsUpToDate::class.':school', IdentifySchool::class, EnsureSchoolCanAccess::class, EnsureUserBelongsToSchool::class])
    ->name('school.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('school.dashboard'))->name('home');
        Route::get('logo', [Setup\ProfileController::class, 'logo'])->name('logo');

        // Renew online (opens without signing in, also when the subscription has expired)
        Route::get('renew', [School\BillingController::class, 'renew'])->name('renew');
        Route::post('renew', [School\BillingController::class, 'checkout'])->middleware('throttle:10,1')->name('renew.checkout');
        Route::get('renew/done', [School\BillingController::class, 'done'])->name('renew.done');

        // Public "Apply for admission" form
        Route::get('apply', [People\EnquiryController::class, 'apply'])->name('apply');
        Route::post('apply', [People\EnquiryController::class, 'submit'])->middleware('throttle:10,1')->name('apply.submit');

        Route::middleware('guest:school')->group(function () {
            Route::get('login', [School\LoginController::class, 'create'])->name('login');
            Route::post('login', [School\LoginController::class, 'store'])->name('login.store');
            Route::get('forgot-password', [School\PasswordResetController::class, 'request'])->name('password.request');
            Route::post('forgot-password', [School\PasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
            Route::get('reset-password/{token}', [School\PasswordResetController::class, 'edit'])->name('password.reset');
            Route::post('reset-password', [School\PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
        });

        Route::middleware('auth:school')->group(function () {
            Route::post('logout', [School\LoginController::class, 'destroy'])->name('logout');
            Route::post('impersonation/leave', [School\ImpersonationController::class, 'leave'])->name('impersonation.leave');
            Route::get('dashboard', School\DashboardController::class)->name('dashboard');

            // AI assistant: principals analyse, admins can also confirm suggested changes
            Route::middleware('school.role:admin,principal')->group(function () {
                Route::get('ai', [School\AiAssistantController::class, 'index'])->name('ai.index');
                Route::post('ai/ask', [School\AiAssistantController::class, 'ask'])->middleware('throttle:20,1')->name('ai.ask');
                Route::post('ai/actions/{action}', [School\AiAssistantController::class, 'confirm'])->name('ai.confirm');
            });

            // Invoices from AivraSol
            Route::get('billing', [School\BillingController::class, 'index'])->middleware('school.role:admin,principal')->name('billing');

            // Help desk with AivraSol
            Route::middleware('school.role:admin,principal')->group(function () {
                Route::get('support', [School\SupportController::class, 'index'])->name('support.index');
                Route::post('support', [School\SupportController::class, 'store'])->name('support.store');
                Route::get('support/{ticket}', [School\SupportController::class, 'show'])->name('support.show');
                Route::post('support/{ticket}/reply', [School\SupportController::class, 'reply'])->name('support.reply');
            });

            // Step 8: exams, marks, results and report cards
            Route::prefix('exams')->name('exams.')->group(function () {
                Route::middleware('school.can:marks.enter')->group(function () {
                    Route::get('/', [Academics\ExamController::class, 'index'])->name('index');
                    Route::get('{exam}', [Academics\ExamController::class, 'show'])->name('show');
                    Route::get('{exam}/papers/{paper}/sections/{section}', [Academics\MarksController::class, 'edit'])->name('marks');
                    Route::put('{exam}/papers/{paper}/sections/{section}', [Academics\MarksController::class, 'update'])->name('marks.update');
                    Route::get('{exam}/sections/{section}/results', [Academics\ResultController::class, 'section'])->name('results');
                    Route::get('{exam}/sections/{section}/report-cards', [Academics\ResultController::class, 'reportCards'])->name('report-cards');
                });
                Route::middleware('school.can:exams.manage')->group(function () {
                    Route::post('/', [Academics\ExamController::class, 'store'])->name('store');
                    Route::put('settings/grading', [Academics\ExamController::class, 'saveSettings'])->name('settings');
                    Route::put('{exam}/classes/{class}/papers', [Academics\ExamController::class, 'savePapers'])->name('papers');
                    Route::post('{exam}/toggle', [Academics\ExamController::class, 'toggle'])->name('toggle');
                    Route::delete('{exam}', [Academics\ExamController::class, 'destroy'])->name('destroy');
                    Route::get('{exam}/analysis', [Academics\ResultController::class, 'analysis'])->name('analysis');
                });
            });

            // Step 7: homework / diary and notices
            Route::middleware('school.can:homework')->group(function () {
                Route::get('homework', [Academics\HomeworkController::class, 'index'])->name('homework.index');
                Route::post('homework', [Academics\HomeworkController::class, 'store'])->name('homework.store');
                Route::delete('homework/{homework}', [Academics\HomeworkController::class, 'destroy'])->name('homework.destroy');
                Route::get('homework/{homework}/file', [Academics\HomeworkController::class, 'attachment'])->name('homework.file');
            });
            Route::get('notices', [Academics\NoticeController::class, 'index'])->name('notices.index');
            Route::get('notices/{notice}/file', [Academics\NoticeController::class, 'attachment'])->name('notices.file');
            Route::middleware('school.can:notices')->group(function () {
                Route::post('notices', [Academics\NoticeController::class, 'store'])->name('notices.store');
                Route::delete('notices/{notice}', [Academics\NoticeController::class, 'destroy'])->name('notices.destroy');
            });

            // Step 6: timetable and substitution
            Route::get('timetable', [Academics\TimetableController::class, 'index'])->middleware('school.can:timetable.view')->name('timetable.index');
            Route::prefix('timetable')->name('timetable.')->middleware('school.can:timetable.manage')->group(function () {
                Route::get('periods', [Academics\TimetableController::class, 'periods'])->name('periods');
                Route::post('periods', [Academics\TimetableController::class, 'storePeriod'])->name('periods.store');
                Route::post('periods/standard', [Academics\TimetableController::class, 'standardPeriods'])->name('periods.standard');
                Route::put('days', [Academics\TimetableController::class, 'saveDays'])->name('days');
                Route::delete('periods/{period}', [Academics\TimetableController::class, 'destroyPeriod'])->name('periods.destroy');
                Route::get('sections/{section}/edit', [Academics\TimetableController::class, 'edit'])->name('edit');
                Route::put('sections/{section}', [Academics\TimetableController::class, 'update'])->name('update');
                Route::get('substitutions', [Academics\SubstitutionController::class, 'index'])->name('substitutions');
                Route::post('substitutions', [Academics\SubstitutionController::class, 'store'])->name('substitutions.store');
            });

            // Step 5: fees
            Route::prefix('fees')->name('fees.')->middleware('school.can:fees.manage')->group(function () {
                Route::get('/', [Money\FeeController::class, 'index'])->name('index');
                Route::get('setup', [Money\FeeController::class, 'setup'])->name('setup');
                Route::put('setup', [Money\FeeController::class, 'saveSetup'])->name('setup.save');
                Route::post('heads', [Money\FeeController::class, 'storeHead'])->name('heads.store');
                Route::delete('heads/{head}', [Money\FeeController::class, 'destroyHead'])->name('heads.destroy');
                Route::get('generate', [Money\FeeController::class, 'generateForm'])->name('generate');
                Route::post('generate', [Money\FeeController::class, 'generate'])->name('generate.store');
                Route::get('vouchers', [Money\FeeController::class, 'invoices'])->name('invoices');
                Route::get('vouchers/print', [Money\FeeController::class, 'print'])->name('print');
                Route::post('vouchers/{invoice}/cancel', [Money\StudentFeeController::class, 'cancel'])->name('cancel');
                Route::get('defaulters', [Money\FeeController::class, 'defaulters'])->name('defaulters');
                Route::get('collect', [Money\StudentFeeController::class, 'search'])->name('search');
                Route::get('students/{student}', [Money\StudentFeeController::class, 'show'])->name('student');
                Route::post('students/{student}/collect', [Money\StudentFeeController::class, 'collect'])->name('collect');
                Route::post('students/{student}/voucher', [Money\StudentFeeController::class, 'custom'])->name('custom');
                Route::get('receipts/{receipt}', [Money\StudentFeeController::class, 'receipt'])->name('receipt');
            });

            // Step 4: attendance and leave
            Route::middleware('school.can:attendance.take')->group(function () {
                Route::get('attendance', [Daily\AttendanceController::class, 'index'])->name('attendance.index');
                Route::get('attendance/sections/{section}', [Daily\AttendanceController::class, 'mark'])->name('attendance.mark');
                Route::post('attendance/sections/{section}', [Daily\AttendanceController::class, 'save'])->name('attendance.save');
                Route::get('attendance/sections/{section}/register', [Daily\AttendanceController::class, 'register'])->name('attendance.register');
            });
            Route::middleware('school.can:staff.attendance')->group(function () {
                Route::get('attendance/staff', [Daily\StaffAttendanceController::class, 'index'])->name('attendance.staff');
                Route::post('attendance/staff', [Daily\StaffAttendanceController::class, 'save'])->name('attendance.staff.save');
            });
            Route::get('leave', [Daily\LeaveController::class, 'index'])->name('leave.index');
            Route::post('leave', [Daily\LeaveController::class, 'store'])->name('leave.store');
            Route::delete('leave/{leave}', [Daily\LeaveController::class, 'destroy'])->name('leave.destroy');
            Route::post('leave/{leave}/review', [Daily\LeaveController::class, 'review'])->middleware('school.can:approvals')->name('leave.review');

            // Step 3: students and admissions
            Route::middleware('school.can:students.manage')->group(function () {
                Route::get('students/create', [People\StudentController::class, 'create'])->name('students.create');
                Route::post('students', [People\StudentController::class, 'store'])->name('students.store');
                Route::get('students/import', [People\StudentToolsController::class, 'importForm'])->name('students.import');
                Route::get('students/import/template', [People\StudentToolsController::class, 'template'])->name('students.import.template');
                Route::post('students/import', [People\StudentToolsController::class, 'import'])->name('students.import.store');
                Route::get('students/promote', [People\StudentToolsController::class, 'promoteForm'])->name('students.promote');
                Route::post('students/promote', [People\StudentToolsController::class, 'promote'])->name('students.promote.store');
                Route::get('students/{student}/edit', [People\StudentController::class, 'edit'])->name('students.edit');
                Route::put('students/{student}', [People\StudentController::class, 'update'])->name('students.update');
                Route::delete('students/{student}', [People\StudentController::class, 'destroy'])->name('students.destroy');
            });
            Route::middleware('school.can:students.view')->group(function () {
                Route::get('students', [People\StudentController::class, 'index'])->name('students.index');
                Route::get('students/export', [People\StudentController::class, 'export'])->name('students.export');
                Route::get('students/{student}', [People\StudentController::class, 'show'])->name('students.show');
                Route::get('students/{student}/photo', [People\StudentController::class, 'photo'])->name('students.photo');
            });
            Route::middleware('school.can:admissions')->group(function () {
                Route::get('enquiries', [People\EnquiryController::class, 'index'])->name('enquiries.index');
                Route::post('enquiries', [People\EnquiryController::class, 'store'])->name('enquiries.store');
                Route::put('enquiries/{enquiry}', [People\EnquiryController::class, 'update'])->name('enquiries.update');
                Route::delete('enquiries/{enquiry}', [People\EnquiryController::class, 'destroy'])->name('enquiries.destroy');
            });

            // Step 2: staff and their portal logins — admins and principals only
            Route::middleware('school.can:staff.manage')->group(function () {
                Route::get('staff', [People\StaffController::class, 'index'])->name('staff.index');
                Route::get('staff/create', [People\StaffController::class, 'create'])->name('staff.create');
                Route::post('staff', [People\StaffController::class, 'store'])->name('staff.store');
                Route::get('staff/export', [People\StaffController::class, 'export'])->name('staff.export');
                Route::get('staff/class-teachers', [People\StaffController::class, 'classTeachers'])->name('staff.class-teachers');
                Route::put('staff/class-teachers', [People\StaffController::class, 'saveClassTeachers'])->name('staff.class-teachers.save');
                Route::get('staff/{member}', [People\StaffController::class, 'show'])->name('staff.show');
                Route::get('staff/{member}/edit', [People\StaffController::class, 'edit'])->name('staff.edit');
                Route::put('staff/{member}', [People\StaffController::class, 'update'])->name('staff.update');
                Route::delete('staff/{member}', [People\StaffController::class, 'destroy'])->name('staff.destroy');
                Route::get('staff/{member}/photo', [People\StaffController::class, 'photo'])->name('staff.photo');
            });

            // Step 1: school setup — admins and principals only
            Route::prefix('setup')->name('setup.')->middleware('school.role:admin,principal')->group(function () {
                Route::get('profile', [Setup\ProfileController::class, 'edit'])->name('profile');
                Route::put('profile', [Setup\ProfileController::class, 'update'])->name('profile.update');

                Route::get('sessions', [Setup\SessionController::class, 'index'])->name('sessions');
                Route::post('sessions', [Setup\SessionController::class, 'store'])->name('sessions.store');
                Route::put('sessions/{session}', [Setup\SessionController::class, 'update'])->name('sessions.update');
                Route::post('sessions/{session}/current', [Setup\SessionController::class, 'makeCurrent'])->name('sessions.current');
                Route::delete('sessions/{session}', [Setup\SessionController::class, 'destroy'])->name('sessions.destroy');

                Route::get('classes', [Setup\ClassController::class, 'index'])->name('classes');
                Route::post('classes', [Setup\ClassController::class, 'store'])->name('classes.store');
                Route::post('classes/standard', [Setup\ClassController::class, 'addStandard'])->name('classes.standard');
                Route::put('classes/{class}', [Setup\ClassController::class, 'update'])->name('classes.update');
                Route::delete('classes/{class}', [Setup\ClassController::class, 'destroy'])->name('classes.destroy');
                Route::post('classes/{class}/sections', [Setup\ClassController::class, 'storeSection'])->name('sections.store');
                Route::delete('sections/{section}', [Setup\ClassController::class, 'destroySection'])->name('sections.destroy');

                Route::get('subjects', [Setup\SubjectController::class, 'index'])->name('subjects');
                Route::post('subjects', [Setup\SubjectController::class, 'store'])->name('subjects.store');
                Route::post('subjects/standard', [Setup\SubjectController::class, 'addStandard'])->name('subjects.standard');
                Route::put('subjects/{subject}', [Setup\SubjectController::class, 'update'])->name('subjects.update');
                Route::delete('subjects/{subject}', [Setup\SubjectController::class, 'destroy'])->name('subjects.destroy');
            });
        });
    });
