<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Plan;
use App\Models\School;
use App\Models\SchoolRegistration;
use App\Models\Setting;
use App\Services\InvoiceCalculator;
use App\Services\RegistrationService;
use App\Services\Stripe;
use App\Support\Activity;
use App\Support\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Public "Register your school" flow: form → (optional Stripe payment) → thank-you page.
 * The Super Admin can switch this off under Settings → Registration.
 */
class RegistrationController extends Controller
{
    public function create(Request $request): View
    {
        $plans = $this->plans();

        return view('register.create', [
            'enabled' => RegistrationService::enabled(),
            'plans' => $plans,
            'selected' => $plans->firstWhere('slug', $request->query('plan'))?->id ?? $plans->first()?->id,
            'stripeOn' => self::stripeOn(),
            'currencies' => Setting::websiteCurrencies(),
            'appHost' => preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')),
        ]);
    }

    /** Live "is this address free?" check for the form. */
    public function checkAddress(Request $request): JsonResponse
    {
        $slug = strtolower(trim((string) $request->query('slug')));

        return response()->json(['available' => self::addressAvailable($slug)]);
    }

    public function store(Request $request, RegistrationService $registrations, Stripe $stripe): RedirectResponse
    {
        abort_unless(RegistrationService::enabled(), 403, 'School registration is closed.');

        // Honeypot: real people never fill this hidden field.
        if (filled($request->input('website'))) {
            return redirect()->route('home');
        }

        $key = 'register:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput()->withErrors(['school_name' => 'Too many registrations from this network. Please try again later.']);
        }

        $request->merge(['slug' => strtolower(trim((string) $request->input('slug')))]);
        $data = $request->validate($this->rules(), [
            'slug.regex' => 'Use only lowercase letters, numbers and single hyphens (e.g. "city-grammar").',
            'slug.not_in' => 'This address is reserved. Please choose another.',
            'terms.accepted' => 'Please accept the terms to continue.',
        ]);

        if (! self::addressAvailable($data['slug'])) {
            return back()->withInput()->withErrors(['slug' => 'This address is already taken. Please choose another.']);
        }

        $plan = $this->plans()->firstWhere('id', (int) $data['plan_id']) ?? abort(422);
        // The plan decides what happens next, never the browser.
        $choice = match ($plan->cta_mode) {
            'stripe' => self::stripeOn() ? 'pay' : 'request',
            'register' => 'request',
            default => 'trial',
        };

        [$amount, $currency] = $choice === 'trial' ? [null, null] : self::chargeFor($plan);
        $bill = self::billFor($plan, $amount, $currency, $data['coupon_code'] ?? null);
        RateLimiter::hit($key, 3600);

        $registration = SchoolRegistration::create([
            'status' => 'pending',
            'school_name' => $data['school_name'],
            'slug' => $data['slug'],
            'school_email' => $data['school_email'] ?? null,
            'school_phone' => $data['school_phone'] ?? null,
            'school_address' => $data['school_address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'currency' => $data['currency'],
            'students' => $data['students'] ?? null,
            'school_photo_path' => Uploads::store($request->file('school_photo'), 'registrations'),
            'principal_name' => $data['principal_name'],
            'principal_email' => strtolower($data['principal_email']),
            'principal_phone' => $data['principal_phone'],
            'principal_national_id' => $data['principal_national_id'] ?? null,
            'principal_qualification' => $data['principal_qualification'] ?? null,
            'principal_address' => $data['principal_address'] ?? null,
            'principal_bio' => $data['principal_bio'] ?? null,
            'principal_photo_path' => Uploads::store($request->file('principal_photo'), 'registrations'),
            'admin_password' => Hash::make($data['password']),
            'plan_id' => $plan->id,
            'billing_choice' => $choice,
            'amount' => $bill ? $bill['total'] : null,
            'amount_currency' => $currency,
            'subtotal' => $bill['subtotal'] ?? null,
            'discount' => $bill['discount'] ?? 0,
            'coupon_code' => $bill['coupon_code'] ?? (filled($data['coupon_code'] ?? null) ? strtoupper(trim($data['coupon_code'])) : null),
            'tax_rate' => $bill['tax_rate'] ?? 0,
            'tax_amount' => $bill['tax_amount'] ?? 0,
            'payment_status' => $choice === 'pay' ? 'pending' : 'none',
            'ip_address' => $request->ip(),
        ]);

        Activity::log('registration.submitted', "{$registration->school_name} registered on the website ({$plan->name})", $registration);

        if ($choice === 'pay') {
            try {
                $checkout = $stripe->createCheckout(
                    name: "Skooline {$plan->name} plan · {$registration->school_name}",
                    amount: (float) $bill['total'],
                    currency: $currency,
                    email: $registration->principal_email,
                    reference: (string) $registration->id,
                    successUrl: self::thanksUrl($registration).'&session_id={CHECKOUT_SESSION_ID}',
                    cancelUrl: self::thanksUrl($registration).'&cancelled=1',
                );
                $registration->update(['stripe_session_id' => $checkout['id']]);

                return redirect()->away($checkout['url']);
            } catch (Throwable $e) {
                report($e);
                // Keep the registration; AivraSol will invoice it instead.
                $registration->update(['billing_choice' => 'request', 'payment_status' => 'none']);
            }
        }

        $registrations->submitted($registration);

        return redirect()->to(self::thanksUrl($registration));
    }

    public function thanks(Request $request, SchoolRegistration $registration, RegistrationService $registrations, Stripe $stripe): View
    {
        // Signed link; Stripe adds session_id (and we add "cancelled") after signing, so those are ignored.
        abort_unless($request->hasValidSignatureWhileIgnoring(['session_id', 'cancelled']), 403);

        // Back from Stripe: confirm the payment directly (works even where webhooks can't reach, e.g. localhost).
        $sessionId = (string) $request->query('session_id');
        if ($sessionId !== '' && $registration->awaitingPayment() && $sessionId === $registration->stripe_session_id) {
            try {
                $session = $stripe->retrieveCheckout($sessionId);
                if (($session['payment_status'] ?? null) === 'paid' && (string) ($session['client_reference_id'] ?? '') === (string) $registration->id) {
                    $registrations->markPaid($registration, $sessionId);
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return view('register.thanks', [
            'registration' => $registration->refresh()->load('school', 'plan'),
            'cancelled' => $request->boolean('cancelled'),
            'retryUrl' => URL::temporarySignedRoute('register.pay', now()->addDays(30), $registration),
        ]);
    }

    /** "Try paying again" after cancelling on Stripe. */
    public function pay(SchoolRegistration $registration, Stripe $stripe): RedirectResponse
    {
        abort_unless($registration->isPending() && $registration->awaitingPayment() && self::stripeOn(), 404);

        $checkout = $stripe->createCheckout(
            name: 'Skooline '.($registration->plan?->name ?? '').' plan · '.$registration->school_name,
            amount: (float) $registration->amount,
            currency: $registration->amount_currency,
            email: $registration->principal_email,
            reference: (string) $registration->id,
            successUrl: self::thanksUrl($registration).'&session_id={CHECKOUT_SESSION_ID}',
            cancelUrl: self::thanksUrl($registration).'&cancelled=1',
        );
        $registration->update(['stripe_session_id' => $checkout['id']]);

        return redirect()->away($checkout['url']);
    }

    /** Signed link so only the person who registered can see the status page. */
    public static function thanksUrl(SchoolRegistration $registration): string
    {
        return URL::temporarySignedRoute('register.thanks', now()->addDays(30), $registration);
    }

    public static function stripeOn(): bool
    {
        return (bool) Setting::get('stripe_enabled', false) && Stripe::configured();
    }

    public static function addressAvailable(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) === 1
            && strlen($slug) >= 3
            && ! in_array($slug, config('skooline.reserved_slugs'), true)
            && ! School::where('slug', $slug)->exists()
            && ! SchoolRegistration::where('slug', $slug)->where('status', 'pending')->exists();
    }

    /**
     * Amount and currency to charge for the first period: the plan's own price in the Stripe
     * currency when set, otherwise converted with the website rates, otherwise the billing currency.
     *
     * @return array{0: float, 1: string}
     */
    public static function chargeFor(Plan $plan): array
    {
        $currency = Setting::get('stripe_currency', Setting::currency());

        if (($price = $plan->priceIn($currency)) !== null) {
            return [$price, $currency];
        }

        $rate = collect(Setting::websiteCurrencies())->firstWhere('code', $currency)['rate'] ?? null;

        return $rate ? [round($plan->headlinePrice() * $rate, 2), $currency] : [$plan->headlinePrice(), Setting::currency()];
    }

    /**
     * Price after the discount code and tax, or null for a free trial. A wrong code is reported
     * on the form; for trials the code is kept and used on the first invoice.
     *
     * @return array<string, mixed>|null
     */
    protected static function billFor(Plan $plan, ?float $amount, ?string $currency, ?string $code): ?array
    {
        if ($amount === null) {
            if (filled($code)) {
                InvoiceCalculator::calculate(0, $code, $plan); // only checks the code
            }

            return null;
        }

        $coupon = Coupon::findByCode($code);
        if ($coupon?->type === 'fixed' && $currency !== Setting::currency()) {
            throw ValidationException::withMessages(['coupon_code' => 'This discount code works only for payments in '.Setting::currency().'.']);
        }

        return InvoiceCalculator::calculate($amount, $code, $plan);
    }

    /** Plans a school can sign up for on the website. */
    protected function plans()
    {
        return Plan::query()->active()->where('show_on_website', true)->where('cta_mode', '!=', 'contact')->ordered()->get();
    }

    protected function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::notIn(config('skooline.reserved_slugs'))],
            'school_email' => ['nullable', 'email', 'max:150'],
            'school_phone' => ['nullable', 'string', 'max:30'],
            'school_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'currency' => ['required', Rule::in(array_keys(config('currencies')))],
            'students' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'school_photo' => Uploads::PHOTO_RULES,
            'principal_name' => ['required', 'string', 'max:120'],
            'principal_email' => ['required', 'email', 'max:150'],
            'principal_phone' => ['required', 'string', 'max:30'],
            'principal_national_id' => ['nullable', 'string', 'max:40'],
            'principal_qualification' => ['nullable', 'string', 'max:150'],
            'principal_address' => ['nullable', 'string', 'max:255'],
            'principal_bio' => ['nullable', 'string', 'max:1000'],
            'principal_photo' => Uploads::PHOTO_RULES,
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'plan_id' => ['required', 'integer'],
            'terms' => ['accepted'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ];
    }
}
