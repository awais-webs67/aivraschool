<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /** End a "view as school admin" session and go back to the Super Admin panel. */
    public function leave(Request $request): RedirectResponse
    {
        $school = current_school();

        Auth::guard('school')->logout();
        $request->session()->forget('impersonator');
        Activity::log('impersonation.ended', "Left {$school->name}'s portal", $school);

        return auth('web')->check()
            ? redirect()->route('admin.schools.show', $school)
            : redirect()->route('admin.login');
    }
}
