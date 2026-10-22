<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Support\Activity;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Second step of signing in when two-factor is on: the 6-digit app code, or a recovery code.
 * The password step stores who is signing in; nobody is logged in until the code is right.
 */
class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        return $request->session()->has('two_factor.user_id')
            ? view('admin.auth.two-factor')
            : redirect()->route('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $user = PlatformUser::find($request->session()->get('two_factor.user_id'));

        if (! $user || ! $user->hasTwoFactor() || ! $user->is_active) {
            $request->session()->forget('two_factor');

            return redirect()->route('admin.login');
        }

        $key = 'two-factor:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => 'Too many wrong codes. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        $code = trim($data['code']);
        $valid = Totp::verify($user->two_factor_secret, $code) || (strlen($code) > 6 && $user->useRecoveryCode($code));

        if (! $valid) {
            RateLimiter::hit($key, 300);
            Activity::log('login.failed', "Wrong two-factor code for {$user->email}", properties: ['email' => $user->email]);

            throw ValidationException::withMessages(['code' => 'That code is not right. Use the current code from your authenticator app.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('two_factor.remember');
        $request->session()->forget('two_factor');

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        return AdminLoginController::signedIn($request, $user);
    }
}
