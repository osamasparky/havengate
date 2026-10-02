<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * EasyKash Direct Pay (https://www.easykash.net) — cards, Meeza, mobile
 * wallets, Fawry pay-at-outlet, ValU, etc., depending on merchant setup.
 *
 * Flow
 *  1. POST {base}/api/directpayv1/pay (Authorization: <API key>) with amount,
 *     customer and our unique `customerReference` → returns `redirectUrl`.
 *  2. Guest pays on EasyKash, is redirected to our `redirectUrl` (GET, with
 *     status/customerReference in the query). The redirect is NOT proof of
 *     payment — it only shows a "processing" page.
 *  3. EasyKash POSTs a server callback to the URL configured in the
 *     merchant dashboard: ProductCode, Amount, ProductType, PaymentMethod,
 *     status, easykashRef, customerReference, signatureHash.
 *     signatureHash = HMAC-SHA512(concat(those 7 values in that order), secret).
 *     Only a verified callback with status PAID confirms the booking.
 *
 * Confirm field names and option ids against your merchant documentation
 * when activating the account; they are isolated in this class.
 */
class EasyKashGateway implements PaymentGateway
{
    public const SIGNED_FIELDS = ['ProductCode', 'Amount', 'ProductType', 'PaymentMethod', 'status', 'easykashRef', 'customerReference'];

    public function __construct(private readonly array $config) {}

    public function initiate(Booking $booking, Payment $payment): string
    {
        if (empty($this->config['api_key'])) {
            throw new RuntimeException('EasyKash API key is not configured.');
        }

        $guest = $booking->guest;
        $body = array_filter([
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'paymentOptions' => $this->config['payment_options'] ?: null,
            'cashExpiry' => $this->config['cash_expiry_hours'] ?? 12,
            'name' => $guest->full_name,
            'email' => $guest->email,
            'mobile' => preg_replace('/[^0-9+]/', '', (string) $guest->phone),
            'redirectUrl' => route('payments.easykash.return', ['locale' => $booking->locale, 'payment' => $payment->reference]),
            'customerReference' => $payment->reference,
        ], fn ($v) => $v !== null && $v !== '');

        $response = Http::withHeaders(['authorization' => $this->config['api_key']])
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 500, throw: false)
            ->post($this->config['base_url'].'/api/directpayv1/pay', $body);

        $payment->update(['payload' => ['request' => array_diff_key($body, ['email' => 1, 'mobile' => 1]), 'response' => $response->json()]]);

        if (! $response->successful() || ! $response->json('redirectUrl')) {
            Log::error('EasyKash initiate failed', ['payment' => $payment->reference, 'status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Payment gateway unavailable.');
        }

        return $response->json('redirectUrl');
    }

    public function verifyCallback(array $payload): bool
    {
        $secret = $this->config['hmac_secret'] ?? null;
        if (! $secret || empty($payload['signatureHash'])) {
            return false;
        }

        $data = implode('', array_map(fn ($f) => (string) ($payload[$f] ?? ''), self::SIGNED_FIELDS));
        $expected = hash_hmac('sha512', $data, $secret);

        return hash_equals($expected, strtolower((string) $payload['signatureHash']));
    }

    /** Map EasyKash status strings to our handling. */
    public static function normaliseStatus(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            'PAID', 'SUCCESS', 'DELIVERED' => 'paid',
            'NEW', 'PENDING' => 'pending',
            'EXPIRED' => 'expired',
            'FAILED', 'CANCELED', 'CANCELLED', 'REFUSED' => 'failed',
            'REFUNDED' => 'refunded',
            default => 'unknown',
        };
    }
}
