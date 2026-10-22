<?php

namespace App\Mail;

use App\Models\SchoolRegistration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** To AivraSol: a school registered on the website. */
class NewRegistrationMail extends Mailable
{
    public function __construct(public SchoolRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New school registration: '.$this->registration->school_name);
    }

    public function content(): Content
    {
        $r = $this->registration;

        return new Content(view: 'emails.message', with: [
            'heading' => 'A new school registered',
            'lines' => ['Review the details and approve it to create the school\'s portal.'],
            'details' => [
                'School' => $r->school_name,
                'Principal' => $r->principal_name,
                'Email' => $r->principal_email,
                'Phone' => $r->principal_phone,
                'City' => collect([$r->city, $r->country])->filter()->join(', ') ?: '—',
                'Plan' => ($r->plan?->name ?? '—').' · '.(SchoolRegistration::CHOICES[$r->billing_choice] ?? $r->billing_choice),
            ],
            'button' => ['label' => 'Review registration', 'url' => route('admin.registrations.show', $r)],
        ]);
    }
}
