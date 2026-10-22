<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\EnsurePlatformUserIsActive;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform routes
|--------------------------------------------------------------------------
|
| Public website and the AivraSol Super Admin panel. School portals
| (/{school}/...) are defined in routes/school.php.
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('robots.txt', [HomeController::class, 'robots']);
Route::get('sitemap.xml', [HomeController::class, 'sitemap']);
Route::post('/demo-request', [HomeController::class, 'requestDemo'])->name('demo-request');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');

        // One-time screen to create the first Super Admin after importing the SQL file.
        Route::get('setup', [SetupController::class, 'create'])->name('setup');
        Route::post('setup', [SetupController::class, 'store'])->name('setup.store');
    });

    Route::middleware(['auth:web', EnsurePlatformUserIsActive::class])->group(function () {
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        // Schools
        Route::middleware('can:schools.manage')->group(function () {
            Route::get('schools/create', [Admin\SchoolController::class, 'create'])->name('schools.create');
            Route::post('schools', [Admin\SchoolController::class, 'store'])->name('schools.store');
            Route::get('schools/{school}/edit', [Admin\SchoolController::class, 'edit'])->name('schools.edit');
            Route::put('schools/{school}', [Admin\SchoolController::class, 'update'])->name('schools.update');
            Route::delete('schools/{school}', [Admin\SchoolController::class, 'destroy'])->name('schools.destroy');
            Route::post('schools/{school}/suspend', [Admin\SchoolController::class, 'suspend'])->name('schools.suspend');
            Route::post('schools/{school}/activate', [Admin\SchoolController::class, 'activate'])->name('schools.activate');
            Route::post('schools/{school}/extend-trial', [Admin\SchoolController::class, 'extendTrial'])->name('schools.extend-trial');
            Route::post('schools/{school}/reset-password', [Admin\SchoolController::class, 'resetAdminPassword'])->name('schools.reset-password');
        });
        Route::middleware('can:schools.view')->group(function () {
            Route::get('schools', [Admin\SchoolController::class, 'index'])->name('schools.index');
            Route::get('schools/export', [Admin\ExportController::class, 'schools'])->name('schools.export');
            Route::get('schools/{school}', [Admin\SchoolController::class, 'show'])->name('schools.show');
        });
        Route::post('schools/{school}/impersonate', [Admin\ImpersonationController::class, 'start'])->middleware('can:impersonate')->name('schools.impersonate');

        // Billing
        Route::middleware('can:billing')->group(function () {
            Route::post('schools/{school}/subscriptions', [Admin\SubscriptionController::class, 'store'])->name('schools.subscriptions.store');
            Route::get('subscriptions', [Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::get('subscriptions/export', [Admin\ExportController::class, 'subscriptions'])->name('subscriptions.export');
            Route::get('subscriptions/{subscription}/invoice', [Admin\SubscriptionController::class, 'invoice'])->name('subscriptions.invoice');
            Route::post('subscriptions/{subscription}/paid', [Admin\SubscriptionController::class, 'markPaid'])->name('subscriptions.paid');
            Route::patch('subscriptions/{subscription}', [Admin\SubscriptionController::class, 'update'])->name('subscriptions.update');
        });

        Route::resource('plans', Admin\PlanController::class)->except('show')->middleware('can:plans');

        // Leads
        Route::middleware('can:leads')->group(function () {
            Route::get('demo-requests', [Admin\DemoRequestController::class, 'index'])->name('demo-requests.index');
            Route::get('demo-requests/export', [Admin\ExportController::class, 'leads'])->name('demo-requests.export');
            Route::get('demo-requests/{demoRequest}', [Admin\DemoRequestController::class, 'show'])->name('demo-requests.show');
            Route::patch('demo-requests/{demoRequest}', [Admin\DemoRequestController::class, 'update'])->name('demo-requests.update');
            Route::delete('demo-requests/{demoRequest}', [Admin\DemoRequestController::class, 'destroy'])->name('demo-requests.destroy');
        });

        Route::resource('announcements', Admin\AnnouncementController::class)->except('show')->middleware('can:announcements');

        // Support
        Route::middleware('can:tickets')->group(function () {
            Route::get('tickets', [Admin\TicketController::class, 'index'])->name('tickets.index');
            Route::get('tickets/{ticket}', [Admin\TicketController::class, 'show'])->name('tickets.show');
            Route::post('tickets/{ticket}/reply', [Admin\TicketController::class, 'reply'])->name('tickets.reply');
            Route::patch('tickets/{ticket}', [Admin\TicketController::class, 'update'])->name('tickets.update');
        });

        Route::get('reports', Admin\ReportController::class)->middleware('can:reports')->name('reports');
        Route::get('activity', Admin\ActivityController::class)->middleware('can:activity')->name('activity');

        // Team
        Route::middleware('can:team')->group(function () {
            Route::get('team', [Admin\TeamController::class, 'index'])->name('team.index');
            Route::post('team', [Admin\TeamController::class, 'store'])->name('team.store');
            Route::get('team/{member}/edit', [Admin\TeamController::class, 'edit'])->name('team.edit');
            Route::put('team/{member}', [Admin\TeamController::class, 'update'])->name('team.update');
            Route::post('team/{member}/reset-password', [Admin\TeamController::class, 'resetPassword'])->name('team.reset-password');
        });

        // Settings & system
        Route::middleware('can:settings')->group(function () {
            Route::get('settings', [Admin\SettingsController::class, 'general'])->name('settings');
            Route::put('settings', [Admin\SettingsController::class, 'updateGeneral'])->name('settings.update');
            Route::get('settings/currencies', [Admin\SettingsController::class, 'edit'])->name('settings.currencies');
            Route::put('settings/currencies', [Admin\SettingsController::class, 'update'])->name('settings.currencies.update');
        });
        Route::middleware('can:system')->group(function () {
            Route::get('system', [Admin\SystemController::class, 'index'])->name('system');
            Route::post('system/migrate', [Admin\SystemController::class, 'migrate'])->name('system.migrate');
        });

        Route::get('account', [Admin\AccountController::class, 'edit'])->name('account');
        Route::put('account/password', [Admin\AccountController::class, 'updatePassword'])->name('account.password');
    });
});
