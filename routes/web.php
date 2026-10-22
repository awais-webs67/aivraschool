<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\AdminPasswordResetController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\EnsureDatabaseIsUpToDate;
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

// "Register your school" (can be switched off in Super Admin → Settings → Registration)
Route::get('register', [RegistrationController::class, 'create'])->name('register');
Route::post('register', [RegistrationController::class, 'store'])->name('register.store');
Route::get('register/check-address', [RegistrationController::class, 'checkAddress'])->middleware('throttle:60,1')->name('register.check');
Route::get('register/status/{registration}', [RegistrationController::class, 'thanks'])->name('register.thanks');
Route::get('register/pay/{registration}', [RegistrationController::class, 'pay'])->middleware('signed')->name('register.pay');
Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');

        // Forgot password and the two-factor step of signing in
        Route::get('forgot-password', [AdminPasswordResetController::class, 'request'])->name('password.request');
        Route::post('forgot-password', [AdminPasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
        Route::get('reset-password/{token}', [AdminPasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('reset-password', [AdminPasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
        Route::get('two-factor', [TwoFactorChallengeController::class, 'create'])->name('two-factor');
        Route::post('two-factor', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:10,1')->name('two-factor.store');

        // One-time screen to create the first Super Admin after importing the SQL file.
        Route::get('setup', [SetupController::class, 'create'])->name('setup');
        Route::post('setup', [SetupController::class, 'store'])->name('setup.store');
    });

    Route::middleware(['auth:web', EnsurePlatformUserIsActive::class, EnsureDatabaseIsUpToDate::class])->group(function () {
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
        // Website sign-ups
        Route::middleware('can:schools.manage')->group(function () {
            Route::get('registrations', [Admin\RegistrationController::class, 'index'])->name('registrations.index');
            Route::get('registrations/{registration}', [Admin\RegistrationController::class, 'show'])->name('registrations.show');
            Route::patch('registrations/{registration}', [Admin\RegistrationController::class, 'update'])->name('registrations.update');
            Route::post('registrations/{registration}/approve', [Admin\RegistrationController::class, 'approve'])->name('registrations.approve');
            Route::post('registrations/{registration}/reject', [Admin\RegistrationController::class, 'reject'])->name('registrations.reject');
            Route::get('registrations/{registration}/photo/{kind}', [Admin\RegistrationController::class, 'photo'])->whereIn('kind', ['school', 'principal'])->name('registrations.photo');
        });

        Route::middleware('can:schools.view')->group(function () {
            Route::get('schools', [Admin\SchoolController::class, 'index'])->name('schools.index');
            Route::get('schools/export', [Admin\ExportController::class, 'schools'])->name('schools.export');
            Route::get('schools/{school}', [Admin\SchoolController::class, 'show'])->name('schools.show');
            Route::get('schools/{school}/photo/{kind}', [Admin\SchoolController::class, 'photo'])->whereIn('kind', ['school', 'principal'])->name('schools.photo');
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
            Route::get('settings/registration', [Admin\SettingsController::class, 'registration'])->name('settings.registration');
            Route::put('settings/registration', [Admin\SettingsController::class, 'updateRegistration'])->name('settings.registration.update');
            Route::get('settings/currencies', [Admin\SettingsController::class, 'edit'])->name('settings.currencies');
            Route::put('settings/currencies', [Admin\SettingsController::class, 'update'])->name('settings.currencies.update');

            Route::get('settings/email', [Admin\IntegrationController::class, 'email'])->name('settings.email');
            Route::put('settings/email', [Admin\IntegrationController::class, 'updateEmail'])->name('settings.email.update');
            Route::post('settings/email/test', [Admin\IntegrationController::class, 'testEmail'])->name('settings.email.test');
            Route::get('settings/payments', [Admin\IntegrationController::class, 'payments'])->name('settings.payments');
            Route::put('settings/payments', [Admin\IntegrationController::class, 'updatePayments'])->name('settings.payments.update');
            Route::post('settings/payments/test', [Admin\IntegrationController::class, 'testStripe'])->name('settings.payments.test');
            Route::get('settings/security', [Admin\IntegrationController::class, 'security'])->name('settings.security');
            Route::put('settings/security/pin', [Admin\IntegrationController::class, 'updatePin'])->name('settings.pin');
            // AI setup
            Route::get('ai', [Admin\AiSetupController::class, 'index'])->name('ai.index');
            Route::post('ai/edit-mode', [Admin\AiSetupController::class, 'editMode'])->name('ai.edit-mode');
            Route::post('ai/custom', [Admin\AiSetupController::class, 'storeCustom'])->name('ai.custom.store');
            Route::delete('ai/custom/{provider}', [Admin\AiSetupController::class, 'destroyCustom'])->name('ai.custom.destroy');
            Route::put('ai/{provider}', [Admin\AiSetupController::class, 'update'])->name('ai.update');
            Route::post('ai/{provider}/test', [Admin\AiSetupController::class, 'test'])->middleware('throttle:30,1')->name('ai.test');
            Route::get('ai/{provider}/models', [Admin\AiSetupController::class, 'models'])->middleware('throttle:30,1')->name('ai.models');

            Route::post('settings/reveal', [Admin\IntegrationController::class, 'reveal'])->middleware('throttle:20,1')->name('settings.reveal');
        });
        Route::middleware('can:system')->group(function () {
            Route::get('system', [Admin\SystemController::class, 'index'])->name('system');
            Route::post('system/migrate', [Admin\SystemController::class, 'migrate'])->name('system.migrate');
        });

        Route::get('account', [Admin\AccountController::class, 'edit'])->name('account');
        Route::put('account/password', [Admin\AccountController::class, 'updatePassword'])->name('account.password');
        Route::post('account/two-factor', [Admin\AccountController::class, 'enableTwoFactor'])->name('account.two-factor.enable');
        Route::post('account/two-factor/confirm', [Admin\AccountController::class, 'confirmTwoFactor'])->middleware('throttle:10,1')->name('account.two-factor.confirm');
        Route::delete('account/two-factor', [Admin\AccountController::class, 'disableTwoFactor'])->name('account.two-factor.disable');
        Route::post('account/recovery-codes', [Admin\AccountController::class, 'regenerateRecoveryCodes'])->name('account.recovery-codes');
        Route::post('account/logout-others', [Admin\AccountController::class, 'logoutOtherDevices'])->name('account.logout-others');
    });
});
