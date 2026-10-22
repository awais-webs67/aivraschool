<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $isCentral = fn (Request $request) => in_array($request->getHost(), config('tenancy.central_domains'), true);

        $middleware->redirectGuestsTo(fn (Request $request) => $isCentral($request) ? route('admin.login') : route('school.login'));
        $middleware->redirectUsersTo(fn (Request $request) => $isCentral($request) ? route('admin.dashboard') : route('school.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
