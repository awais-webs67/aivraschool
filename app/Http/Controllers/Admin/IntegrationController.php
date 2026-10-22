<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\Setting;
use App\Services\Stripe;
use App\Support\Activity;
use App\Support\EnvFile;
use App\Support\MailSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Email (SMTP), payments (Stripe) and the PIN that protects viewing saved secrets.
 * Secrets are written to .env; the panel only ever shows them masked unless the PIN is entered.
 */
class IntegrationController extends Controller
{
    /** .env keys whose full value may be revealed with the PIN. */
    public const REVEALABLE = ['MAIL_PASSWORD', 'STRIPE_KEY', 'STRIPE_SECRET', 'STRIPE_WEBHOOK_SECRET'];

    public function email(): View
    {
        return view('admin.settings.email', [
            'env' => collect(MailSettings::KEYS)->mapWithKeys(fn ($k) => [$k => EnvFile::get($k)])->all(),
            'configured' => MailSettings::configured(),
        ]);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'MAIL_HOST' => ['required', 'string', 'max:150'],
            'MAIL_PORT' => ['required', 'integer', 'between:1,65535'],
            'MAIL_ENCRYPTION' => ['required', Rule::in(['tls', 'ssl'])],
            'MAIL_USERNAME' => ['nullable', 'string', 'max:150'],
            'MAIL_PASSWORD' => ['nullable', 'string', 'max:255'],
            'MAIL_FROM_ADDRESS' => ['required', 'email', 'max:150'],
            'MAIL_FROM_NAME' => ['required', 'string', 'max:100'],
        ]);

        // A blank password field means "keep the saved one".
        if (blank($data['MAIL_PASSWORD'])) {
            unset($data['MAIL_PASSWORD']);
        }

        $this->saveEnv(['MAIL_MAILER' => 'smtp'] + $data);
        Activity::log('settings.email', 'Updated SMTP email settings ('.$data['MAIL_HOST'].')');

        return back()->with('success', 'Email settings saved. Send a test email to check them.');
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $data = $request->validate(['to' => ['required', 'email']]);

        MailSettings::apply();

        try {
            Mail::to($data['to'])->send(new TestMail);
        } catch (Throwable $e) {
            return back()->with('error', 'Test email failed: '.MailSettings::explain($e->getMessage()))
                ->with('error_detail', $e->getMessage());
        }

        return back()->with('success', "Test email sent to {$data['to']}. Check the inbox (and spam folder).");
    }

    public function payments(): View
    {
        return view('admin.settings.payments', [
            'publishable' => EnvFile::get('STRIPE_KEY'),
            'secret' => EnvFile::get('STRIPE_SECRET'),
            'webhookSecret' => EnvFile::get('STRIPE_WEBHOOK_SECRET'),
            'currency' => Setting::get('stripe_currency', 'USD'),
            'enabled' => (bool) Setting::get('stripe_enabled', false),
        ]);
    }

    public function updatePayments(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'STRIPE_KEY' => ['nullable', 'string', 'starts_with:pk_test_,pk_live_', 'max:255'],
            'STRIPE_SECRET' => ['nullable', 'string', 'starts_with:sk_test_,sk_live_,rk_test_,rk_live_', 'max:255'],
            'STRIPE_WEBHOOK_SECRET' => ['nullable', 'string', 'starts_with:whsec_', 'max:255'],
            'stripe_currency' => ['required', Rule::in(array_keys(config('currencies')))],
            'stripe_enabled' => ['boolean'],
        ]);

        $this->saveEnv(array_filter(
            ['STRIPE_KEY' => $data['STRIPE_KEY'] ?? null, 'STRIPE_SECRET' => $data['STRIPE_SECRET'] ?? null, 'STRIPE_WEBHOOK_SECRET' => $data['STRIPE_WEBHOOK_SECRET'] ?? null],
            fn ($v) => filled($v),
        ));

        $enabled = $request->boolean('stripe_enabled');
        if ($enabled && ! Stripe::configured()) {
            throw ValidationException::withMessages(['STRIPE_SECRET' => 'Add the secret key before turning Stripe on.']);
        }

        Setting::put(['stripe_currency' => $data['stripe_currency'], 'stripe_enabled' => $enabled]);
        Activity::log('settings.payments', 'Updated Stripe settings ('.($enabled ? 'on' : 'off').')');

        return back()->with('success', 'Payment settings saved.');
    }

    public function testStripe(Stripe $stripe): RedirectResponse
    {
        if (! Stripe::configured()) {
            return back()->with('error', 'Add the Stripe secret key first.');
        }

        try {
            [$ok, $message] = $stripe->test();
        } catch (Throwable $e) {
            [$ok, $message] = [false, 'Could not reach Stripe: '.$e->getMessage()];
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    /** Create or change the PIN that unlocks "Show" on saved secrets. */
    public function updatePin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'pin' => ['required', 'digits_between:4,8', 'confirmed'],
        ]);

        Setting::put(['reveal_pin' => Hash::make($data['pin'])]);
        Activity::log('settings.pin', 'Changed the secret-viewing PIN');

        return back()->with('success', 'PIN saved. Use it to view saved API keys and passwords.');
    }

    /** Returns the full value of one saved secret after checking the PIN. */
    public function reveal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'pin' => ['required', 'string', 'max:20'],
        ]);

        $allowed = in_array($data['name'], self::REVEALABLE, true) || preg_match('/^AI_[A-Z0-9_]+_API_KEY$/', $data['name']);
        abort_unless($allowed, 404);

        $hash = Setting::get('reveal_pin');
        if (! $hash) {
            return response()->json(['error' => 'No PIN has been set yet. Set one under Settings → Security.'], 422);
        }

        $key = 'reveal-pin:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'Too many wrong PINs. Try again in '.RateLimiter::availableIn($key).' seconds.'], 429);
        }

        if (! Hash::check($data['pin'], $hash)) {
            RateLimiter::hit($key, 300);

            return response()->json(['error' => 'Wrong PIN.'], 422);
        }

        RateLimiter::clear($key);
        Activity::log('settings.reveal', "Viewed the saved value of {$data['name']}");

        return response()->json(['value' => EnvFile::get($data['name']) ?? '']);
    }

    public function security(): View
    {
        return view('admin.settings.security', ['hasPin' => (bool) Setting::get('reveal_pin')]);
    }

    /** @param  array<string, string|int|null>  $values */
    protected function saveEnv(array $values): void
    {
        try {
            EnvFile::set($values);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['env' => $e->getMessage()]);
        }
    }
}
