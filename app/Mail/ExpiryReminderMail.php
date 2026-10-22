<?php

namespace App\Mail;

use App\Models\School;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "Your trial / subscription ends in N days." */
class ExpiryReminderMail extends Mailable
{
    public function __construct(public School $school, public int $days, public bool $trial) {}

    public function envelope(): Envelope
    {
        $what = $this->trial ? 'free trial' : 'subscription';
        $when = $this->days === 1 ? 'tomorrow' : "in {$this->days} days";

        return new Envelope(subject: "Your Skooline {$what} ends {$when}");
    }

    public function content(): Content
    {
        $ends = $this->trial ? $this->school->trial_ends_at : $this->school->subscription_ends_at;

        return new Content(view: 'emails.message', with: [
            'heading' => 'Keep '.$this->school->name.' running',
            'lines' => [
                'The '.($this->trial ? 'free trial' : 'subscription').' for '.$this->school->name.' ends on '.$ends?->format('d M Y').'.',
                'Renew from Billing in your school portal so your staff keep access without a break. Your data stays safe either way.',
            ],
            'button' => ['label' => 'Open billing', 'url' => route('school.billing', $this->school->slug)],
        ]);
    }
}
