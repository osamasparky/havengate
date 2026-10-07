<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** $kind: 'received' (awaiting bank transfer) or 'requested' (pay at property, awaiting staff approval). */
    public function __construct(public Booking $booking, public string $kind = 'received') {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __("mail.{$this->kind}.subject", ['ref' => $this->booking->reference]));
    }

    public function content(): Content
    {
        $this->booking->loadMissing('guest', 'units.unit', 'units.accommodation', 'extras');

        return new Content(view: 'mail.booking', with: ['kind' => $this->kind, 'booking' => $this->booking]);
    }
}
