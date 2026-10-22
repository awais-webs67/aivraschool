<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('school.auth.login', ['school' => current_school()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ], [], ['email' => 'email or phone']);

        // Staff sign in with their email; parents may use their phone number instead.
        $login = trim($data['email']);
        $credentials = str_contains($login, '@')
            ? ['email' => Str::lower($login), 'password' => $data['password']]
            : ['phone' => User::normalizePhone($login) ?? $login, 'password' => $data['password']];

        $key = 'school-login:'.current_school()->id.'|'.Str::lower($login).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        if (! Auth::guard('school')->attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        Auth::guard('school')->user()->forceFill(['last_login_at' => now()])->save();
        Activity::log('login.school', 'Signed in to the school portal');

        return redirect()->intended(route('school.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($request->session()->has('impersonator')) {
            return app(ImpersonationController::class)->leave($request);
        }

        Auth::guard('school')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('school.login');
    }
}
