<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'currencies' => config('currencies'),
            'base' => Setting::currency(),
            'rates' => (array) Setting::get('website_currencies', []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $codes = array_keys(config('currencies'));

        $data = $request->validate([
            'currency' => ['required', Rule::in($codes)],
            'website' => ['array'],
            'website.*' => ['nullable', 'numeric', 'gt:0', 'max:100000000'],
        ]);

        // Keep only currencies that were ticked and given a rate.
        $enabled = (array) $request->input('enabled', []);
        $rates = collect($data['website'] ?? [])
            ->filter(fn ($rate, $code) => in_array($code, $codes, true) && in_array($code, $enabled, true) && $rate !== null && $code !== $data['currency'])
            ->map(fn ($rate) => (float) $rate)
            ->all();

        Setting::put(['currency' => $data['currency'], 'website_currencies' => $rates]);

        return back()->with('success', 'Settings saved.');
    }
}
