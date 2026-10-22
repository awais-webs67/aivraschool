<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows a school page only for the listed roles, e.g. school.role:admin,principal
 */
class EnsureSchoolRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(in_array($request->user('school')?->role, $roles, true), 403, 'You do not have access to this page.');

        return $next($request);
    }
}
