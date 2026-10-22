<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\SettingsController;
use App\Models\Subscription;
use App\Services\SubscriptionPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Throwable;

/**
 * Invoice a school opens from its email (signed link, no sign-in needed) and can pay by card.
 */
class InvoiceController extends Controller
{
    public static function canPayOnline(): bool
    {
        return RegistrationController::stripeOn();
    }

    public function show(Subscription $subscription): View
    {
        return view('admin.subscriptions.invoice', [
            'subscription' => $subscription->load('school'),
            'company' => SettingsController::values(),
            'public' => true,
            'payUrl' => $subscription->isOpen() && self::canPayOnline() ? URL::signedRoute('invoice.pay', $subscription) : null,
        ]);
    }

    public function pay(Subscription $subscription, SubscriptionPayments $payments): RedirectResponse
    {
        abort_unless($subscription->isOpen() && self::canPayOnline() && $subscription->school, 404);

        try {
            return redirect()->away($payments->checkoutInvoice(
                $subscription,
                successUrl: URL::signedRoute('invoice.paid', $subscription).'&session_id={CHECKOUT_SESSION_ID}',
                cancelUrl: $subscription->publicUrl(),
            ));
        } catch (Throwable $e) {
            report($e);

            return redirect()->to($subscription->publicUrl())->with('error', 'Online payment is not available right now. Please try again later or use the payment details on the invoice.');
        }
    }

    /** Back from Stripe: confirm right away (the webhook also does this, whichever comes first). */
    public function paid(Request $request, Subscription $subscription, SubscriptionPayments $payments): RedirectResponse
    {
        abort_unless($request->hasValidSignatureWhileIgnoring(['session_id']), 403);

        try {
            $payments->confirm((string) $request->query('session_id'));
        } catch (Throwable $e) {
            report($e);
        }

        $paid = ! $subscription->refresh()->isOpen();

        return redirect()->to($subscription->publicUrl())->with($paid ? 'success' : 'error', $paid
            ? 'Thank you! Your payment was received.'
            : 'We could not confirm the payment yet. If you were charged it will show here within a few minutes.');
    }
}
