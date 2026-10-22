<?php

namespace App\Http\Middleware;

use App\Support\DatabaseStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After new code is uploaded, pages that need new tables would crash until "Run updates"
 * is pressed. Send the Super Admin to the System page instead, and show schools a
 * short "being updated" notice.
 */
class EnsureDatabaseIsUpToDate
{
    public function handle(Request $request, Closure $next, string $area = 'admin'): Response
    {
        if (! DatabaseStatus::isOutdated()) {
            return $next($request);
        }

        if ($area === 'school') {
            return response()->view('school.updating', [], 503);
        }

        if ($request->routeIs('admin.system', 'admin.system.migrate', 'admin.logout')) {
            return $next($request);
        }

        return redirect()->route('admin.system')
            ->with('error', 'A new version was installed. Press "Run updates" to update the database before using the panel.');
    }
}
