<?php

use App\Http\Middleware\EnsureSchoolRole;
use App\Http\Middleware\IdentifySchool;
use App\Http\Middleware\PlatformMaintenance;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // School portals (/{school}/...) are registered last so fixed paths like /admin win.
        then: fn () => Route::middleware('web')->group(base_path('routes/school.php')),
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Stripe signs its webhook calls instead of sending a CSRF token.
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);

        // The school must be known before authentication looks up the user.
        $middleware->prependToPriorityList(AuthenticatesRequests::class, IdentifySchool::class);

        $middleware->alias(['school.role' => EnsureSchoolRole::class]);

        // "Back soon" page for visitors and schools while maintenance mode is on (Admin → System).
        $middleware->web(append: [PlatformMaintenance::class]);

        $isPlatform = fn (Request $request) => $request->is('admin', 'admin/*');

        $middleware->redirectGuestsTo(fn (Request $request) => $isPlatform($request) ? route('admin.login') : route('school.login'));
        $middleware->redirectUsersTo(fn (Request $request) => $isPlatform($request) ? route('admin.dashboard') : route('school.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
