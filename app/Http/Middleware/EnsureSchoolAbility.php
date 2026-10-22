<?php

namespace App\Http\Middleware;

use App\Support\SchoolAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows a school page only when the user's role and the school's plan allow it,
 * e.g. school.can:students.view (see App\Support\SchoolAccess).
 */
class EnsureSchoolAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        if (! SchoolAccess::allows($request->user('school'), $ability)) {
            abort(403, SchoolAccess::lockedByPlan($ability)
                ? 'This feature is not in your school\'s plan. Ask AivraSol to upgrade.'
                : 'You do not have access to this page.');
        }

        return $next($request);
    }
}
