<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * "Forgot password" for school portal users. Tokens are stored hashed per school
 * (the same email may exist in two schools) and expire after 60 minutes.
 */
class PasswordResetController extends Controller
{
    private const EXPIRES_MINUTES = 60;

    public function request(): View
    {
        return view('school.auth.forgot-password', ['school' => current_school()]);
    }

    public function send(Request $request): RedirectResponse
    {
        $email = Str::lower($request->validate(['email' => ['required', 'email']])['email']);
        $school = current_school();
        $user = User::where('email', $email)->where('is_active', true)->first();

        if ($user) {
            $token = Str::random(64);
            DB::table('school_password_resets')->where('school_id', $school->id)->where('email', $email)->delete();
            DB::table('school_password_resets')->insert([
                'school_id' => $school->id, 'email' => $email, 'token' => Hash::make($token), 'created_at' => now(),
            ]);

            $url = route('school.password.reset', ['school' => $school->slug, 'token' => $token, 'email' => $email]);
            rescue(fn () => Mail::to($email)->send(new PasswordResetMail($user->name, $url)), report: true);
        }

        // Same answer whether or not the email exists, so accounts can't be discovered.
        return back()->with('success', 'If that email belongs to an account here, a reset link is on its way. Check your inbox and spam folder.');
    }

    public function edit(Request $request): View
    {
        return view('school.auth.reset-password', ['school' => current_school(), 'token' => (string) $request->route('token'), 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);
        $school = current_school();
        $email = Str::lower($data['email']);

        $row = DB::table('school_password_resets')->where('school_id', $school->id)->where('email', $email)->first();
        $valid = $row
            && now()->subMinutes(self::EXPIRES_MINUTES)->lt($row->created_at)
            && Hash::check($data['token'], $row->token);
        $user = $valid ? User::where('email', $email)->where('is_active', true)->first() : null;

        if (! $user) {
            return back()->withInput(['email' => $email])->withErrors(['email' => 'This reset link is invalid or has expired. Ask for a new one.']);
        }

        $user->forceFill(['password' => $data['password']])->setRememberToken(Str::random(60));
        $user->save();
        DB::table('school_password_resets')->where('id', $row->id)->delete();
        Activity::log('login.reset', "Password reset by email for {$user->email}", $user);

        return redirect()->route('school.login', $school->slug)->with('success', 'Password changed. Sign in with your new password.');
    }
}
