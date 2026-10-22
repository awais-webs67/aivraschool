<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Skooline test email');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.message', with: [
            'heading' => 'Your email settings work',
            'lines' => ['This is a test email from the Skooline Super Admin panel. If you can read it, schools and admins will receive registration and login emails.'],
        ]);
    }
}
