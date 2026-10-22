<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a team member as soon as an owner deactivates them.
 */
class EnsurePlatformUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->user()?->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors(['email' => 'Your account has been deactivated.']);
        }

        return $next($request);
    }
}
