<?php

use App\Http\Controllers\School;
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
    ->middleware([IdentifySchool::class, EnsureSchoolCanAccess::class, EnsureUserBelongsToSchool::class])
    ->name('school.')
    ->group(function () {
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
