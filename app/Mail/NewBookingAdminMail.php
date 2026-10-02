<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewBookingAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param string $context new|offline|cancelled|attention */
    public function __construct(public Booking $booking, public string $context = 'new') {}

    public function envelope(): Envelope
    {
        $prefix = match ($this->context) {
            'offline' => 'Awaiting transfer',
            'cancelled' => 'Cancelled',
            'attention' => 'ACTION NEEDED',
            default => 'New booking',
        };

        return new Envelope(subject: "{$prefix} · {$this->booking->reference} · {$this->booking->check_in->format('d M')}");
    }

    public function content(): Content
    {
        $this->booking->loadMissing('guest', 'units.unit', 'units.accommodation', 'extras');

        return new Content(view: 'mail.admin-booking', with: ['booking' => $this->booking, 'context' => $this->context]);
    }
}
