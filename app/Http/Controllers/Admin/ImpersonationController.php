<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets AivraSol staff open a school's portal as that school's admin, to help with support.
 * The Super Admin session stays signed in; "Return to Super Admin" ends the school session.
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, School $school): RedirectResponse
    {
        if (! $school->canAccess()) {
            return back()->with('error', 'This school\'s portal is closed (suspended or expired), so it can\'t be opened.');
        }

        $admin = $school->users()->where('is_active', true)->where('role', 'admin')->orderBy('id')->first();

        if (! $admin) {
            return back()->with('error', 'This school has no active admin account to sign in as.');
        }

        Auth::guard('school')->login($admin);
        $request->session()->put('impersonator', [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'school_id' => $school->id,
        ]);

        Activity::log('impersonation.started', "Opened {$school->name}'s portal as {$admin->email}", $school);

        return redirect()->to($school->url('dashboard'));
    }
}
