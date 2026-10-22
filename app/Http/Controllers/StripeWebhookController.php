<?php

namespace App\Http\Controllers;

use App\Models\SchoolRegistration;
use App\Services\RegistrationService;
use App\Services\Stripe;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Stripe calls this when a payment finishes (event checkout.session.completed).
 * The signature is checked with STRIPE_WEBHOOK_SECRET before anything is trusted.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, RegistrationService $registrations): Response
    {
        $event = Stripe::verifyWebhook($request->getContent(), $request->header('Stripe-Signature'));

        if ($event === null) {
            return response('Invalid signature', 400);
        }

        $session = $event['data']['object'] ?? [];

        if (($event['type'] ?? '') === 'checkout.session.completed' && ($session['payment_status'] ?? '') === 'paid') {
            $registration = SchoolRegistration::find((int) ($session['client_reference_id'] ?? 0));

            if ($registration && $registration->awaitingPayment()) {
                $registrations->markPaid($registration, (string) $session['id']);
            }
        }

        return response('ok');
    }
}
