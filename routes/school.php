<?php

use App\Http\Controllers\School;
use App\Http\Controllers\School\Setup;
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
        Route::get('logo', [Setup\ProfileController::class, 'logo'])->name('logo');

        Route::middleware('guest:school')->group(function () {
            Route::get('login', [School\LoginController::class, 'create'])->name('login');
            Route::post('login', [School\LoginController::class, 'store'])->name('login.store');
        });

        Route::middleware('auth:school')->group(function () {
            Route::post('logout', [School\LoginController::class, 'destroy'])->name('logout');
            Route::get('dashboard', School\DashboardController::class)->name('dashboard');

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
