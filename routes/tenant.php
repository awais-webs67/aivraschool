<?php

declare(strict_types=1);

use App\Http\Controllers\School;
use App\Http\Middleware\EnsureSchoolCanAccess;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| School portal routes ({school}.skooline.com)
|--------------------------------------------------------------------------
|
| Every request here is resolved to one school and runs against that
| school's own database.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
    EnsureSchoolCanAccess::class,
])->name('school.')->group(function () {
    Route::get('/', fn () => redirect()->route('school.dashboard'))->name('home');

    Route::middleware('guest:school')->group(function () {
        Route::get('login', [School\LoginController::class, 'create'])->name('login');
        Route::post('login', [School\LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:school')->group(function () {
        Route::post('logout', [School\LoginController::class, 'destroy'])->name('logout');
        Route::get('dashboard', School\DashboardController::class)->name('dashboard');
    });
});
