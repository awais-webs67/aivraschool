<?php

namespace App\Mail;

use App\Http\Controllers\InvoiceController;
use App\Models\Subscription;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Invoice to a school: "new", "reminder" (still unpaid) or "receipt" (paid). */
class InvoiceMail extends Mailable
{
    public function __construct(public Subscription $subscription, public string $kind = 'new') {}

    public function envelope(): Envelope
    {
        $number = $this->subscription->invoice_number;

        return new Envelope(subject: match ($this->kind) {
            'reminder' => "Reminder: invoice {$number} is unpaid",
            'receipt' => "Payment received for invoice {$number}",
            default => "Your Skooline invoice {$number}",
        });
    }

    public function content(): Content
    {
        $s = $this->subscription;
        $school = $s->school;

        $lines = match ($this->kind) {
            'reminder' => ["This is a friendly reminder that invoice {$s->invoice_number} for {$school?->name} is still unpaid."],
            'receipt' => ["Thank you! We received your payment for {$school?->name}."],
            default => ["Here is the invoice for {$school?->name}'s Skooline subscription."],
        };
        if ($s->isOpen()) {
            $lines[] = 'Open the invoice to view or print it'.(InvoiceController::canPayOnline() ? ', or pay online by card.' : '. Payment details are on the invoice.');
        }

        return new Content(view: 'emails.message', with: [
            'heading' => $this->kind === 'receipt' ? 'Payment received' : 'Invoice '.$s->invoice_number,
            'lines' => $lines,
            'details' => array_filter([
                'Plan' => $s->plan_name,
                'Period' => $s->starts_at->format('d M Y').' – '.($s->ends_at?->format('d M Y') ?? 'Lifetime'),
                'Total' => money($s->amount, true),
                'Status' => ucfirst($s->payment_status),
            ]),
            'button' => ['label' => $s->isOpen() ? 'View and pay invoice' : 'View invoice', 'url' => $s->publicUrl()],
        ]);
    }
}
