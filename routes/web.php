<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central routes (skooline.com)
|--------------------------------------------------------------------------
|
| Public website and the AivraSol Super Admin panel. School portals live on
| subdomains and are defined in routes/tenant.php.
|
*/

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::post('/demo-request', [HomeController::class, 'requestDemo'])->name('demo-request');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::middleware('guest:web')->group(function () {
                Route::get('login', [AdminLoginController::class, 'create'])->name('login');
                Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');
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

                Route::get('account', [Admin\AccountController::class, 'edit'])->name('account');
                Route::put('account/password', [Admin\AccountController::class, 'updatePassword'])->name('account.password');
            });
        });
    });
}
