<?php

namespace App\Mail;

use App\Models\SchoolRegistration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** To the principal: registration received, approved or not approved. */
class RegistrationStatusMail extends Mailable
{
    public function __construct(public SchoolRegistration $registration, public string $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->event) {
            'approved' => 'Your school portal is ready',
            'rejected' => 'About your Skooline registration',
            default => 'We received your registration',
        });
    }

    public function content(): Content
    {
        $r = $this->registration;

        $data = match ($this->event) {
            'approved' => [
                'heading' => "Welcome to Skooline, {$r->principal_name}",
                'lines' => ["{$r->school_name} is ready. Sign in with your email and the password you chose when registering."],
                'details' => ['Portal' => $r->school?->displayUrl() ?? $r->slug, 'Email' => $r->principal_email],
                'button' => ['label' => 'Open your school portal', 'url' => $r->school?->url('login') ?? url('/')],
            ],
            'rejected' => [
                'heading' => 'Your registration was not approved',
                'lines' => array_values(array_filter([
                    "We could not approve the registration for {$r->school_name}.",
                    $r->rejection_reason ? "Reason: {$r->rejection_reason}" : null,
                    'Reply to this email if you have questions.',
                ])),
            ],
            default => [
                'heading' => 'Thank you for registering',
                'lines' => ["We received the registration for {$r->school_name}. Our team will review it and email you as soon as your portal is ready."],
                'details' => ['Reference' => $r->number(), 'Requested address' => $r->slug],
            ],
        };

        return new Content(view: 'emails.message', with: $data);
    }
}
