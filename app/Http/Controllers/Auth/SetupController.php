<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * First-run screen: after the SQL file is imported there is no Super Admin yet.
 * This page creates the first one, then disappears for good.
 */
class SetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (PlatformUser::query()->exists()) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(PlatformUser::query()->exists(), 403, 'Setup has already been completed.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $user = PlatformUser::create($data + ['role' => 'super_admin']);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'Welcome to Skooline. Your Super Admin account is ready.');
    }
}
