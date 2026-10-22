<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\HomeController;
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

    Route::middleware('auth:web')->group(function () {
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::resource('schools', Admin\SchoolController::class);
        Route::post('schools/{school}/suspend', [Admin\SchoolController::class, 'suspend'])->name('schools.suspend');
        Route::post('schools/{school}/activate', [Admin\SchoolController::class, 'activate'])->name('schools.activate');
        Route::post('schools/{school}/extend-trial', [Admin\SchoolController::class, 'extendTrial'])->name('schools.extend-trial');
        Route::post('schools/{school}/reset-password', [Admin\SchoolController::class, 'resetAdminPassword'])->name('schools.reset-password');
        Route::post('schools/{school}/subscriptions', [Admin\SubscriptionController::class, 'store'])->name('schools.subscriptions.store');

        Route::get('subscriptions', [Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('subscriptions/{subscription}/paid', [Admin\SubscriptionController::class, 'markPaid'])->name('subscriptions.paid');

        Route::resource('plans', Admin\PlanController::class)->except('show');

        Route::get('demo-requests', [Admin\DemoRequestController::class, 'index'])->name('demo-requests.index');
        Route::patch('demo-requests/{demoRequest}', [Admin\DemoRequestController::class, 'update'])->name('demo-requests.update');

        Route::get('system', [Admin\SystemController::class, 'index'])->name('system');
        Route::post('system/migrate', [Admin\SystemController::class, 'migrate'])->name('system.migrate');

        Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings');
        Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');

        Route::get('account', [Admin\AccountController::class, 'edit'])->name('account');
        Route::put('account/password', [Admin\AccountController::class, 'updatePassword'])->name('account.password');
    });
});
