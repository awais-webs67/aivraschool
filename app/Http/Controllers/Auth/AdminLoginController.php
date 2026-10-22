<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        // Fresh install (SQL just imported): create the first Super Admin instead.
        if (! PlatformUser::query()->exists()) {
            return redirect()->route('admin.setup');
        }

        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'admin-login:'.Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        if (! Auth::guard('web')->validate($credentials + ['is_active' => true])) {
            RateLimiter::hit($key, 60);
            Activity::log('login.failed', 'Failed sign-in for '.Str::lower($credentials['email']), properties: ['email' => Str::lower($credentials['email'])]);

            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);
        $user = PlatformUser::where('email', Str::lower($credentials['email']))->firstOrFail();

        // Two-factor on: ask for the app code before signing in.
        if ($user->hasTwoFactor()) {
            $request->session()->put('two_factor', ['user_id' => $user->id, 'remember' => $request->boolean('remember')]);

            return redirect()->route('admin.two-factor');
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return self::signedIn($request, $user);
    }

    /** Shared by the password and two-factor steps once the person is fully signed in. */
    public static function signedIn(Request $request, PlatformUser $user): RedirectResponse
    {
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        Activity::log('login.platform', 'Signed in to the Super Admin panel');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
