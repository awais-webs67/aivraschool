<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()->active()->where('show_on_website', true)->ordered()->get();

        return view('home', [
            'plans' => $plans,
            'modules' => config('skooline.modules'),
        ]);
    }

    public function requestDemo(Request $request): RedirectResponse
    {
        $key = 'demo-request:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return redirect()->to(route('home').'#demo')
                ->withErrors(['phone' => 'Too many requests. Please try again later.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'school_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'city' => ['nullable', 'string', 'max:80'],
            'students' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        RateLimiter::hit($key, 3600);

        DemoRequest::create($data + ['ip_address' => $request->ip()]);

        return redirect()->to(route('home').'#demo')
            ->with('demo_success', 'Thank you! Our team will contact you within one working day.');
    }
}
