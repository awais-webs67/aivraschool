<?php

namespace App\Http\Controllers\School;

use App\Enums\SchoolStatus;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\InvoiceController;
use App\Models\Plan;
use App\Services\SubscriptionPayments;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * The school's own billing: invoices from AivraSol, paying them by card, and renewing.
 * The renew page also opens without signing in, so an expired school can pay to come back.
 */
class BillingController extends Controller
{
    public function index(): View
    {
        $school = current_school();

        return view('school.billing.index', [
            'school' => $school,
            'invoices' => $school->subscriptions()->paginate(15),
            'canPay' => InvoiceController::canPayOnline(),
            'company' => SettingsController::values(),
        ]);
    }

    public function renew(Request $request, SubscriptionPayments $payments): View
    {
        $school = current_school();
        $plans = $this->plans();
        $plan = $plans->firstWhere('id', (int) $request->query('plan', $school->plan_id)) ?? $plans->first();

        return view('school.billing.renew', [
            'school' => $school,
            'plans' => $plans,
            'plan' => $plan,
            'bill' => $plan ? $payments->renewalBill($school, $plan, null) : null,
            'starts' => $payments->nextStart($school),
            'canPay' => InvoiceController::canPayOnline(),
            'company' => SettingsController::values(),
        ]);
    }

    public function checkout(Request $request, SubscriptionPayments $payments): RedirectResponse
    {
        abort_unless(InvoiceController::canPayOnline(), 404);

        $school = current_school();
        $data = $request->validate([
            'plan_id' => ['required', Rule::in($this->plans()->pluck('id'))],
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ]);
        $plan = Plan::findOrFail($data['plan_id']);

        try {
            $url = $payments->checkoutRenewal(
                $school, $plan, $data['coupon_code'] ?? null,
                successUrl: URL::signedRoute('school.renew.done', ['school' => $school->slug]).'&session_id={CHECKOUT_SESSION_ID}',
                cancelUrl: route('school.renew', ['school' => $school->slug, 'plan' => $plan->id]),
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Online payment is not available right now. Please try again later or contact us.');
        }

        Activity::log('subscription.checkout', "{$school->name} started an online renewal ({$plan->name})", school: $school);

        return redirect()->away($url);
    }

    /** Back from Stripe. */
    public function done(Request $request, SubscriptionPayments $payments): RedirectResponse
    {
        abort_unless($request->hasValidSignatureWhileIgnoring(['session_id']), 403);

        $school = current_school();
        $subscription = null;

        try {
            $subscription = $payments->confirm((string) $request->query('session_id'));
        } catch (Throwable $e) {
            report($e);
        }

        if (! $subscription || $subscription->school_id !== $school->id) {
            return redirect()->route('school.renew', ['school' => $school->slug])
                ->with('error', 'We could not confirm the payment yet. If you were charged, your plan will update within a few minutes.');
        }

        $message = 'Thank you! Your plan is renewed until '.($subscription->ends_at?->format('d M Y') ?? 'forever').'.';

        return Auth::guard('school')->check()
            ? redirect()->route('school.billing', ['school' => $school->slug])->with('success', $message)
            : redirect()->route('school.login', ['school' => $school->slug])->with('success', $message.' Sign in to continue.');
    }

    /** Plans a school can renew on: its own plan plus the public ones. */
    protected function plans(): Collection
    {
        $school = current_school();

        return Plan::query()->active()
            ->where(fn ($q) => $q->where('id', $school->plan_id)->orWhere(fn ($q) => $q->where('show_on_website', true)->where('cta_mode', '!=', 'contact')))
            ->ordered()->get()
            ->reject(fn (Plan $p) => $school->status === SchoolStatus::Active && $p->priceFor((int) $school->student_count) <= 0 && $p->id !== $school->plan_id);
    }
}
