<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Support\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the school from the URL (skooline.com/{school}/...) and makes it current.
 */
class IdentifySchool
{
    public function __construct(protected CurrentSchool $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('school');

        $school = School::query()->with('plan')->where('slug', $slug)->first();

        abort_if($school === null, 404);

        $this->current->set($school);

        // Controllers don't receive {school}; school routes can be generated without passing it.
        $request->route()->forgetParameter('school');
        URL::defaults(['school' => $school->slug]);

        return $next($request);
    }

    public function terminate(): void
    {
        $this->current->set(null);
    }
}
