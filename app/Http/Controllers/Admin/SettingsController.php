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
    public const GENERAL = ['company_name', 'company_email', 'company_phone', 'company_website', 'company_address', 'tax_id', 'invoice_note', 'tax_name', 'tax_rate', 'reminders_enabled', 'trial_days', 'grace_days'];

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
            'tax_name' => Setting::get('tax_name'),
            'tax_rate' => (float) Setting::get('tax_rate', 0),
            'reminders_enabled' => (bool) Setting::get('reminders_enabled', true),
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
            'tax_name' => ['nullable', 'string', 'max:20'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:365'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:90'],
        ]);

        $data['tax_rate'] = (float) ($data['tax_rate'] ?? 0);
        Setting::put($data + ['reminders_enabled' => $request->boolean('reminders_enabled')]);
        Activity::log('settings.updated', 'Updated general settings');

        return back()->with('success', 'Settings saved.');
    }

    public function registration(): View
    {
        return view('admin.settings.registration', [
            'enabled' => (bool) Setting::get('registration_enabled', true),
            'autoApprove' => (bool) Setting::get('registration_auto_approve', false),
            'notifyEmail' => Setting::get('registration_notify_email'),
            'defaultCurrency' => Setting::get('website_default_currency', 'auto'),
            'currencies' => Setting::websiteCurrencies(),
        ]);
    }

    public function updateRegistration(Request $request): RedirectResponse
    {
        $codes = array_column(Setting::websiteCurrencies(), 'code');

        $data = $request->validate([
            'registration_notify_email' => ['nullable', 'email', 'max:150'],
            'website_default_currency' => ['required', Rule::in(array_merge(['auto'], $codes))],
        ]);

        Setting::put($data + [
            'registration_enabled' => $request->boolean('registration_enabled'),
            'registration_auto_approve' => $request->boolean('registration_auto_approve'),
        ]);
        Activity::log('settings.registration', 'Updated website registration settings ('.($request->boolean('registration_enabled') ? 'open' : 'closed').')');

        return back()->with('success', 'Registration settings saved.');
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
