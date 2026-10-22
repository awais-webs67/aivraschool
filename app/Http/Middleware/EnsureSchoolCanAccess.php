<?php

namespace App\Http\Middleware;

use App\Enums\SchoolStatus;
use App\Support\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a school's portal when it is suspended or its trial/subscription has run out.
 */
class EnsureSchoolCanAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = app(CurrentSchool::class)->get();

        if ($school->canAccess()) {
            return $next($request);
        }

        Auth::guard('school')->logout();

        return response()->view('school.unavailable', [
            'school' => $school,
            'suspended' => $school->status === SchoolStatus::Suspended,
        ], 403);
    }
}
