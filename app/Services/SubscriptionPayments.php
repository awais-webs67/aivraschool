<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Plan;
use App\Models\School;
use App\Models\Setting;
use App\Models\Subscription;
use App\Support\Activity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Online card payments (Stripe Checkout) for school invoices and renewals.
 *
 * client_reference_id tells the payments apart:
 *   "inv_{subscription id}"        pay an existing invoice
 *   "renew_{school id}_{plan id}"  buy the next period; the invoice is created once paid
 */
class SubscriptionPayments
{
    public function __construct(protected Stripe $stripe, protected SubscriptionManager $manager) {}

    /** Stripe Checkout page for an unpaid invoice. */
    public function checkoutInvoice(Subscription $subscription, string $successUrl, string $cancelUrl): string
    {
        $school = $subscription->school;

        $checkout = $this->stripe->createCheckout(
            name: "Skooline invoice {$subscription->invoice_number} · {$school->name}",
            amount: (float) $subscription->amount,
            currency: Setting::currency(),
            email: $school->billingEmails()[0] ?? '',
            reference: 'inv_'.$subscription->id,
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
        );

        return $checkout['url'];
    }

    /**
     * Price of the next period on a plan (discount code and tax included).
     *
     * @return array<string, mixed>
     */
    public function renewalBill(School $school, Plan $plan, ?string $coupon): array
    {
        return InvoiceCalculator::calculate($plan->priceFor((int) $school->student_count), $coupon, $plan);
    }

    /** Stripe Checkout page to renew. The breakdown travels in the metadata so the invoice matches what was paid. */
    public function checkoutRenewal(School $school, Plan $plan, ?string $coupon, string $successUrl, string $cancelUrl): string
    {
        $bill = $this->renewalBill($school, $plan, $coupon);

        $checkout = $this->stripe->createCheckout(
            name: "Skooline {$plan->name} plan · {$school->name}",
            amount: $bill['total'],
            currency: Setting::currency(),
            email: $school->billingEmails()[0] ?? '',
            reference: "renew_{$school->id}_{$plan->id}",
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
            metadata: collect($bill)->only(['subtotal', 'discount', 'coupon_code', 'tax_rate', 'tax_amount', 'total'])->map(fn ($v) => $v ?? '')->all(),
        );

        return $checkout['url'];
    }

    /** Looks up a finished Checkout session (back from Stripe) and records it. */
    public function confirm(string $sessionId): ?Subscription
    {
        return $this->complete($this->stripe->retrieveCheckout($sessionId));
    }

    /**
     * Records a paid Checkout session. Safe to call twice (return page and webhook both do).
     *
     * @param  array<string, mixed>  $session
     */
    public function complete(array $session): ?Subscription
    {
        if (($session['payment_status'] ?? null) !== 'paid') {
            return null;
        }

        $ref = (string) ($session['client_reference_id'] ?? '');
        $sessionId = (string) ($session['id'] ?? '');

        if (preg_match('/^inv_(\d+)$/', $ref, $m)) {
            $subscription = Subscription::find((int) $m[1]);

            if ($subscription && $subscription->isOpen()) {
                $subscription->update(['payment_status' => 'paid', 'paid_at' => now(), 'payment_method' => 'card', 'payment_reference' => $sessionId]);
                Activity::log('payment.received', "Online payment for {$subscription->invoice_number} (".money($subscription->amount).')', $subscription, school: $subscription->school);
                $this->sendInvoice($subscription, 'receipt');
            }

            return $subscription;
        }

        if (preg_match('/^renew_(\d+)_(\d+)$/', $ref, $m)) {
            if ($existing = Subscription::where('payment_reference', $sessionId)->first()) {
                return $existing;
            }

            $school = School::find((int) $m[1]);
            $plan = Plan::find((int) $m[2]);
            if (! $school || ! $plan) {
                return null;
            }

            $meta = $session['metadata'] ?? [];
            $subscription = $this->manager->assign($school, $plan, [
                'starts_at' => $this->nextStart($school)->toDateString(),
                'student_count' => $school->student_count,
                'breakdown' => [
                    'subtotal' => (float) ($meta['subtotal'] ?? 0),
                    'discount' => (float) ($meta['discount'] ?? 0),
                    'coupon_code' => ($meta['coupon_code'] ?? '') ?: null,
                    'tax_name' => (float) ($meta['tax_rate'] ?? 0) > 0 ? InvoiceCalculator::taxName() : null,
                    'tax_rate' => (float) ($meta['tax_rate'] ?? 0),
                    'tax_amount' => (float) ($meta['tax_amount'] ?? 0),
                    'total' => (float) ($meta['total'] ?? 0),
                ],
                'payment_status' => 'paid',
                'payment_method' => 'card',
                'payment_reference' => $sessionId,
            ]);
            Activity::log('subscription.renewed', "{$school->name} renewed {$plan->name} online (".money($subscription->amount).')', $subscription, school: $school);
            $this->sendInvoice($subscription, 'receipt');

            return $subscription;
        }

        return null;
    }

    /** A renewal starts the day after the current period ends, or today if it already ended. */
    public function nextStart(School $school): Carbon
    {
        $ends = $school->subscription_ends_at;

        return $ends && $ends->isFuture() && $school->trial_ends_at === null ? $ends->copy()->addDay()->startOfDay() : now()->startOfDay();
    }

    /** Emails the invoice to the school. Returns false when there is no address or sending failed. */
    public function sendInvoice(Subscription $subscription, string $kind = 'new'): bool
    {
        $to = $subscription->school?->billingEmails() ?? [];

        if (! $to) {
            return false;
        }

        try {
            Mail::to($to)->send(new InvoiceMail($subscription, $kind));
            $subscription->forceFill($kind === 'reminder' ? ['reminded_at' => now()] : ['sent_at' => $subscription->sent_at ?? now()])->saveQuietly();

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
