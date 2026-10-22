<?php

use App\Http\Controllers\School;
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
