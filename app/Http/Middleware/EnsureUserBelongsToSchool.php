<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defence in depth: all schools share one domain and one session cookie. If a user
 * of school A ever reaches a school B page, sign them out and send them to B's login.
 */
class EnsureUserBelongsToSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('school')->user();

        if ($user && $user->school_id !== app(CurrentSchool::class)->id()) {
            Auth::guard('school')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('school.login');
        }

        return $next($request);
    }
}
