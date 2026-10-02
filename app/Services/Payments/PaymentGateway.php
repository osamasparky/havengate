<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;

interface PaymentGateway
{
    /** Start a payment and return the URL the guest must be sent to. */
    public function initiate(Booking $booking, Payment $payment): string;

    /** Verify an incoming server callback. */
    public function verifyCallback(array $payload): bool;
}
