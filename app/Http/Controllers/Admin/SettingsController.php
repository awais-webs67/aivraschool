<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public const GENERAL = ['company_name', 'company_email', 'company_phone', 'company_website', 'company_address', 'tax_id', 'invoice_note', 'trial_days', 'grace_days'];

    /** @return array<string, mixed> */
    public static function values(): array
    {
        return [
            'company_name' => Setting::get('company_name') ?: config('skooline.company'),
            'company_email' => Setting::get('company_email') ?: config('skooline.sales_email'),
            'company_phone' => Setting::get('company_phone'),
            'company_website' => Setting::get('company_website') ?: config('skooline.company_url'),
            'company_address' => Setting::get('company_address'),
            'tax_id' => Setting::get('tax_id'),
            'invoice_note' => Setting::get('invoice_note'),
            'trial_days' => config('skooline.default_trial_days'),
            'grace_days' => config('skooline.grace_days'),
        ];
    }

    public function general(): View
    {
        return view('admin.settings.general', ['s' => self::values()]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_phone' => ['nullable', 'string', 'max:40'],
            'company_website' => ['nullable', 'url', 'max:150'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:60'],
            'invoice_note' => ['nullable', 'string', 'max:500'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:365'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:90'],
        ]);

        Setting::put($data);
        Activity::log('settings.updated', 'Updated general settings');

        return back()->with('success', 'Settings saved.');
    }

    public function edit(): View
    {
        return view('admin.settings.currencies', [
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
        Activity::log('settings.currencies', 'Updated currencies (billing in '.$data['currency'].')');

        return back()->with('success', 'Settings saved.');
    }
}
