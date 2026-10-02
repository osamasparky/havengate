<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.cancelled.subject', ['ref' => $this->booking->reference]));
    }

    public function content(): Content
    {
        $this->booking->loadMissing('guest', 'units.unit', 'units.accommodation', 'extras');

        return new Content(view: 'mail.booking', with: ['kind' => 'cancelled', 'booking' => $this->booking]);
    }
}
