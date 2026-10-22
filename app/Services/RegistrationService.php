<?php

namespace App\Services;

use App\Mail\NewRegistrationMail;
use App\Mail\RegistrationStatusMail;
use App\Models\School;
use App\Models\SchoolRegistration;
use App\Models\Setting;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Turns website registrations into schools: notify, take payment, approve or reject.
 */
class RegistrationService
{
    public function __construct(protected SchoolProvisioner $provisioner) {}

    public static function enabled(): bool
    {
        return (bool) Setting::get('registration_enabled', true);
    }

    public static function autoApprove(): bool
    {
        return (bool) Setting::get('registration_auto_approve', false);
    }

    /** Called right after the form is saved (and again once a Stripe payment is confirmed). */
    public function submitted(SchoolRegistration $registration): void
    {
        // Free trials and finished payments can go live straight away when auto-approve is on.
        if (self::autoApprove() && $registration->isPending() && ! $registration->awaitingPayment() && $registration->billing_choice !== 'request') {
            $this->approve($registration, mode: $registration->isPaid() ? 'paid' : 'trial');

            return;
        }

        $this->notifyAdmins($registration);
        $this->mail($registration->principal_email, new RegistrationStatusMail($registration, 'received'));
    }

    /** A Stripe payment for this registration went through. */
    public function markPaid(SchoolRegistration $registration, string $reference): void
    {
        if ($registration->isPaid()) {
            return;
        }

        $registration->update(['payment_status' => 'paid', 'paid_at' => now(), 'stripe_session_id' => $reference]);
        Activity::log('registration.paid', "{$registration->school_name} paid ".$registration->amount_currency.' '.number_format((float) $registration->amount, 2).' online', $registration);

        $this->submitted($registration->refresh());
    }

    /**
     * Create the school. $mode: "trial" (free trial), "paid" (paid subscription) or "unpaid" (invoice to pay).
     */
    public function approve(SchoolRegistration $registration, string $mode = 'trial', ?int $trialDays = null, ?int $reviewer = null): School
    {
        if (! $registration->isPending()) {
            throw ValidationException::withMessages(['status' => 'This registration was already reviewed.']);
        }

        if (School::where('slug', $registration->slug)->exists()) {
            throw ValidationException::withMessages(['slug' => "The address {$registration->slug} is now taken. Edit the registration's address first."]);
        }

        $plan = $registration->plan ?? throw ValidationException::withMessages(['plan' => 'Choose a plan for this registration first.']);

        $school = DB::transaction(function () use ($registration, $plan, $mode, $trialDays, $reviewer) {
            $school = $this->provisioner->create(
                school: [
                    'name' => $registration->school_name,
                    'slug' => $registration->slug,
                    'email' => $registration->school_email,
                    'phone' => $registration->school_phone,
                    'address' => $registration->school_address,
                    'city' => $registration->city,
                    'country' => $registration->country,
                    'currency' => $registration->currency,
                    'student_count' => $registration->students,
                    'photo_path' => $registration->school_photo_path,
                    'principal_name' => $registration->principal_name,
                    'principal_email' => $registration->principal_email,
                    'principal_phone' => $registration->principal_phone,
                    'principal_national_id' => $registration->principal_national_id,
                    'principal_qualification' => $registration->principal_qualification,
                    'principal_address' => $registration->principal_address,
                    'principal_bio' => $registration->principal_bio,
                    'principal_photo_path' => $registration->principal_photo_path,
                ],
                plan: $plan,
                // The password the principal chose on the form (stored hashed).
                admin: ['name' => $registration->principal_name, 'email' => $registration->principal_email, 'password' => $registration->admin_password],
                billing: [
                    'mode' => $mode === 'trial' ? 'trial' : 'paid',
                    'trial_days' => $trialDays,
                    'student_count' => $registration->students,
                    // Paid online in the billing currency: keep exactly what was charged.
                    'breakdown' => $mode === 'paid' && $registration->isPaid() && $registration->amount_currency === Setting::currency() && $registration->subtotal !== null ? [
                        'subtotal' => (float) $registration->subtotal, 'discount' => (float) $registration->discount, 'coupon_code' => $registration->coupon_code,
                        'tax_name' => (float) $registration->tax_rate > 0 ? InvoiceCalculator::taxName() : null,
                        'tax_rate' => (float) $registration->tax_rate, 'tax_amount' => (float) $registration->tax_amount, 'total' => (float) $registration->amount,
                    ] : null,
                    'amount' => $mode === 'paid' && $registration->amount_currency === Setting::currency() && $registration->subtotal === null ? (float) $registration->amount : null,
                    'coupon_code' => $registration->coupon_code,
                    'strict_coupon' => false,
                    'payment_status' => $mode === 'paid' ? 'paid' : 'unpaid',
                    'payment_method' => $mode === 'paid' && $registration->isPaid() ? 'card' : null,
                    'payment_reference' => $registration->stripe_session_id,
                ],
                createdBy: $reviewer,
            );

            $registration->update([
                'status' => 'approved',
                'school_id' => $school->id,
                'reviewed_by' => $reviewer,
                'reviewed_at' => now(),
            ]);

            return $school;
        });

        Activity::log('registration.approved', "Approved registration of {$school->name}".($reviewer ? '' : ' (automatic)'), $school);
        $this->mail($registration->principal_email, new RegistrationStatusMail($registration->refresh(), 'approved'));

        return $school;
    }

    public function reject(SchoolRegistration $registration, ?string $reason, ?int $reviewer): void
    {
        $registration->update(['status' => 'rejected', 'rejection_reason' => $reason, 'reviewed_by' => $reviewer, 'reviewed_at' => now()]);

        Activity::log('registration.rejected', "Rejected registration of {$registration->school_name}".($reason ? ": {$reason}" : ''), $registration);
        $this->mail($registration->principal_email, new RegistrationStatusMail($registration, 'rejected'));
    }

    protected function notifyAdmins(SchoolRegistration $registration): void
    {
        $to = Setting::get('registration_notify_email') ?: Setting::get('company_email') ?: config('skooline.sales_email');

        $this->mail($to, new NewRegistrationMail($registration));
    }

    /** Email problems must never lose a registration: log and continue. */
    protected function mail(?string $to, $mailable): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
