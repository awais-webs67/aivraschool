<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "Reset your password" link (Super Admin panel and school portals). */
class PasswordResetMail extends Mailable
{
    public function __construct(public string $name, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your Skooline password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.message', with: [
            'heading' => "Hi {$this->name}, reset your password",
            'lines' => ['Someone (hopefully you) asked to reset the password for this account. The link works for 60 minutes.', 'If you did not ask for this, ignore this email; your password stays the same.'],
            'button' => ['label' => 'Choose a new password', 'url' => $this->url],
        ]);
    }
}
