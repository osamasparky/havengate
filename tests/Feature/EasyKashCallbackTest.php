<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Services\BookingService;
use App\Services\Payments\EasyKashGateway;
use App\Support\StayRequest;

function signed(array $p): array
{
    $data = implode('', array_map(fn ($f) => (string) ($p[$f] ?? ''), EasyKashGateway::SIGNED_FIELDS));

    return $p + ['signatureHash' => hash_hmac('sha512', $data, 'test-secret')];
}

beforeEach(function () {
    $acc = $this->makeStay();
    $this->booking = app(BookingService::class)->createHold($acc, StayRequest::make(today()->addDays(5)->toDateString(), today()->addDays(6)->toDateString(), 2), $this->guest());
    $this->payment = $this->booking->payments()->create(['provider' => PaymentProvider::EasyKash, 'amount' => $this->booking->amount_due_now, 'status' => PaymentStatus::Initiated]);
});

it('confirms the booking on a valid PAID callback, idempotently', function () {
    $payload = signed(['ProductCode' => 'X1', 'Amount' => (string) $this->payment->amount, 'ProductType' => 'Direct Pay', 'PaymentMethod' => 'Credit & Debit Card', 'status' => 'PAID', 'easykashRef' => '123', 'customerReference' => $this->payment->reference]);

    $this->postJson('/payments/easykash/callback', $payload)->assertOk();
    $this->postJson('/payments/easykash/callback', $payload)->assertOk();

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Confirmed)
        ->and((float) $this->booking->fresh()->amount_paid)->toEqual((float) $this->payment->amount)
        ->and($this->booking->payments()->where('status', PaymentStatus::Paid)->count())->toBe(1);
});

it('rejects a tampered signature', function () {
    $payload = signed(['ProductCode' => 'X1', 'Amount' => (string) $this->payment->amount, 'ProductType' => 'Direct Pay', 'PaymentMethod' => 'Card', 'status' => 'PAID', 'easykashRef' => '123', 'customerReference' => $this->payment->reference]);
    $payload['Amount'] = '1';

    $this->postJson('/payments/easykash/callback', $payload)->assertForbidden();
    expect($this->booking->fresh()->status)->toBe(BookingStatus::Pending);
});

it('rejects a PAID callback whose amount does not match', function () {
    $payload = signed(['ProductCode' => 'X1', 'Amount' => '1.00', 'ProductType' => 'Direct Pay', 'PaymentMethod' => 'Card', 'status' => 'PAID', 'easykashRef' => '123', 'customerReference' => $this->payment->reference]);

    $this->postJson('/payments/easykash/callback', $payload)->assertStatus(422);
    expect($this->booking->fresh()->status)->toBe(BookingStatus::Pending);
});

it('extends the hold when a Fawry voucher is pending', function () {
    $before = $this->booking->expires_at;
    $payload = signed(['ProductCode' => 'X1', 'Amount' => (string) $this->payment->amount, 'ProductType' => 'Direct Pay', 'PaymentMethod' => 'Fawry', 'status' => 'PENDING', 'easykashRef' => '123', 'customerReference' => $this->payment->reference]);

    $this->postJson('/payments/easykash/callback', $payload)->assertOk();
    expect($this->booking->fresh()->expires_at->gt($before))->toBeTrue()
        ->and($this->payment->fresh()->status)->toBe(PaymentStatus::Pending);
});
