<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * "Forgot password" for the Super Admin panel (Laravel's password broker, tokens expire in 60 minutes).
 */
class AdminPasswordResetController extends Controller
{
    public function request(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        // Same answer whether or not the email exists, so accounts can't be discovered.
        if (PlatformUser::where('email', strtolower($data['email']))->where('is_active', true)->exists()) {
            Password::broker('users')->sendResetLink(['email' => strtolower($data['email'])]);
        }

        return back()->with('success', 'If that email belongs to an account, a reset link is on its way. Check your inbox and spam folder.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        $status = Password::broker('users')->reset($data, function (PlatformUser $user, string $password) {
            $user->forceFill(['password' => $password])->setRememberToken(null);
            $user->save();
            Activity::log('login.reset', "Password reset by email for {$user->email}", $user);
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('success', 'Password changed. Sign in with your new password.')
            : back()->withInput(['email' => $data['email']])->withErrors(['email' => 'This reset link is invalid or has expired. Ask for a new one.']);
    }
}
