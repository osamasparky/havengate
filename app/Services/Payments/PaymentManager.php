<?php

namespace App\Services\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use Illuminate\Support\Facades\Log;

/** Orchestrates online payments for bookings. */
class PaymentManager
{
    public function __construct(
        private readonly EasyKashGateway $easykash,
        private readonly BookingService $bookings,
    ) {}

    public function onlineEnabled(): bool
    {
        return (bool) config('services.easykash.enabled') && (bool) setting('online_payment_enabled', true);
    }

    public function offlineEnabled(): bool
    {
        return (bool) setting('offline_payment_enabled', true);
    }

    /** Create a payment row for the amount due now and get the gateway URL. */
    public function startOnline(Booking $booking): string
    {
        if ($booking->status !== BookingStatus::Pending || $booking->isHoldExpired()) {
            throw new BookingException('booking.errors.hold_expired');
        }

        $amount = max(0, (float) $booking->amount_due_now - (float) $booking->amount_paid);
        if ($amount <= 0) {
            throw new BookingException('booking.errors.nothing_due');
        }

        $payment = $booking->payments()->create([
            'provider' => PaymentProvider::EasyKash,
            'amount' => $amount,
            'currency' => $booking->currency,
            'status' => PaymentStatus::Initiated,
        ]);

        // Give the guest a little more time while on the gateway.
        $booking->update(['expires_at' => now()->addMinutes(max(15, (int) config('heavengate.hold_minutes')))]);
        $booking->log('payment_started', 'EasyKash '.money($amount), ['payment' => $payment->reference]);

        return $this->easykash->initiate($booking, $payment);
    }

    /** Handle EasyKash server callback. Returns HTTP status to answer with. */
    public function handleEasyKashCallback(array $payload): int
    {
        if (! $this->easykash->verifyCallback($payload)) {
            Log::warning('EasyKash callback with invalid signature', ['ref' => $payload['customerReference'] ?? null]);

            return 403;
        }

        $payment = Payment::where('reference', $payload['customerReference'] ?? '')->first();
        if (! $payment) {
            Log::warning('EasyKash callback for unknown payment', ['ref' => $payload['customerReference'] ?? null]);

            return 404;
        }

        $status = EasyKashGateway::normaliseStatus($payload['status'] ?? null);
        $reported = round((float) ($payload['Amount'] ?? 0), 2);

        if ($status === 'paid' && abs($reported - (float) $payment->amount) > 0.01) {
            Log::critical('EasyKash amount mismatch', ['payment' => $payment->reference, 'expected' => $payment->amount, 'got' => $reported]);
            $payment->booking->log('needs_attention', "Gateway amount {$reported} ≠ expected {$payment->amount}");

            return 422;
        }

        $common = [
            'method' => $payload['PaymentMethod'] ?? null,
            'provider_reference' => $payload['easykashRef'] ?? null,
            'payload' => $payload,
        ];

        switch ($status) {
            case 'paid':
                $this->bookings->markPaymentPaid($payment, $common);
                break;

            case 'pending': // e.g. Fawry voucher issued — keep the unit until the voucher expires
                if ($payment->status !== PaymentStatus::Paid) {
                    $payment->update(['status' => PaymentStatus::Pending, 'method' => $common['method'], 'provider_reference' => $common['provider_reference'], 'voucher' => $payload['voucher'] ?? $payment->voucher]);
                    $payment->booking->update(['expires_at' => now()->addHours((int) config('services.easykash.cash_expiry_hours', 12))]);
                    $payment->booking->log('payment_pending', 'Awaiting cash payment ('.($common['method'] ?? 'voucher').')');
                }
                break;

            case 'failed':
            case 'expired':
                if ($payment->status !== PaymentStatus::Paid) {
                    $payment->update(['status' => $status === 'failed' ? PaymentStatus::Failed : PaymentStatus::Expired, 'payload' => array_merge($payment->payload ?? [], ['callback' => $payload])]);
                    $payment->booking->log('payment_failed', $payload['status'] ?? $status);
                }
                break;

            case 'refunded':
                $payment->update(['status' => PaymentStatus::Refunded]);
                $this->bookings->refreshPaymentTotals($payment->booking);
                $payment->booking->log('refunded', money($payment->amount));
                break;
        }

        return 200;
    }
}
