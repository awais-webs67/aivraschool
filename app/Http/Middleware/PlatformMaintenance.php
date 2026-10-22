<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance mode switched on in Super Admin → System. The public website and every school
 * portal show a "back soon" page; the Super Admin panel, Stripe webhooks and signed-in
 * Super Admins (also while viewing a school as its admin) keep working.
 */
class PlatformMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = (array) Setting::get('maintenance', []);

        if (empty($mode['on']) || $request->is('admin', 'admin/*', 'stripe/webhook', 'up') || auth('web')->check() || $request->session()->has('impersonator')) {
            return $next($request);
        }

        return response()->view('maintenance', ['message' => $mode['message'] ?? null], 503)->header('Retry-After', '1800');
    }
}
