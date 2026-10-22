<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Activity;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "My account": password, two-factor sign-in, signed-in devices and sign-in history.
 */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Devices signed in to this account (database sessions keep the user id).
        $sessions = config('session.driver') === 'database'
            ? DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->orderByDesc('last_activity')->get()
            : collect();

        return view('admin.account', [
            'user' => $user,
            'pendingSecret' => $user->two_factor_secret && ! $user->two_factor_confirmed_at ? $user->two_factor_secret : null,
            'sessions' => $sessions,
            'currentSession' => $request->session()->getId(),
            'history' => ActivityLog::query()
                ->where(fn ($q) => $q->where(fn ($q) => $q->where('action', 'login.platform')->where('actor_id', $user->id)->where('actor_type', 'platform'))
                    ->orWhere(fn ($q) => $q->where('action', 'login.failed')->where('properties', 'like', '%'.json_encode($user->email).'%')))
                ->latest('id')->limit(15)->get(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $request->user()->update(['password' => $data['password']]);
        Activity::log('login.password', 'Changed own password');

        return back()->with('success', 'Password changed.');
    }

    /** Step 1: create a secret and show it as a QR code. Not active until a code is confirmed. */
    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password:web']]);

        $request->user()->forceFill(['two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => null])->save();

        return back()->with('success', 'Scan the QR code with your authenticator app, then enter the 6-digit code.');
    }

    /** Step 2: the first correct code turns two-factor on and shows the recovery codes once. */
    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if (! $user->two_factor_secret || ! Totp::verify($user->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'That code is not right. Check the time on your phone and try the current code.']);
        }

        $codes = self::recoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();
        Activity::log('login.2fa', 'Turned on two-factor sign-in');

        return back()->with('success', 'Two-factor sign-in is on.')->with('recovery_codes', $codes);
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password:web']]);

        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        Activity::log('login.2fa', 'Turned off two-factor sign-in');

        return back()->with('success', 'Two-factor sign-in is off.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password:web']]);
        abort_unless($request->user()->hasTwoFactor(), 404);

        $codes = self::recoveryCodes();
        $request->user()->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return back()->with('success', 'New recovery codes created. The old ones no longer work.')->with('recovery_codes', $codes);
    }

    /** Sign out every other browser/device using this account. */
    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password:web']]);

        Auth::guard('web')->logoutOtherDevices($data['current_password']);
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        Activity::log('login.devices', 'Signed out all other devices');

        return back()->with('success', 'All other devices were signed out.');
    }

    /** @return list<string> 8 codes like "K7QX-M2PD" */
    protected static function recoveryCodes(): array
    {
        return collect(range(1, 8))->map(fn () => strtoupper(Str::random(4).'-'.Str::random(4)))->all();
    }
}
