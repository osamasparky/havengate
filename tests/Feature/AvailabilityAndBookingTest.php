<?php

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\BlockedDate;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Support\StayRequest;

function stay(string $in, string $out, int $adults = 2, int $children = 0): StayRequest
{
    return StayRequest::make($in, $out, $adults, $children);
}

it('holds a unit and prevents double booking once inventory is gone', function () {
    $acc = $this->makeStay(units: 2);
    $svc = app(BookingService::class);
    $in = today()->addDays(10)->toDateString();
    $out = today()->addDays(12)->toDateString();

    $a = $svc->createHold($acc, stay($in, $out), $this->guest('a@x.com'));
    $b = $svc->createHold($acc, stay($in, $out), $this->guest('b@x.com'));

    expect($a->units->first()->unit_id)->not->toBe($b->units->first()->unit_id);
    expect(fn () => $svc->createHold($acc, stay($in, $out), $this->guest('c@x.com')))->toThrow(BookingException::class);
});

it('allows same-day turnover', function () {
    $acc = $this->makeStay(units: 1);
    $svc = app(BookingService::class);
    $svc->createHold($acc, stay(today()->addDays(5)->toDateString(), today()->addDays(7)->toDateString()), $this->guest('a@x.com'));

    $next = $svc->createHold($acc, stay(today()->addDays(7)->toDateString(), today()->addDays(9)->toDateString()), $this->guest('b@x.com'));

    expect($next->status)->toBe(BookingStatus::Pending);
});

it('releases inventory when a hold expires', function () {
    $acc = $this->makeStay(units: 1);
    $svc = app(BookingService::class);
    $in = today()->addDays(3)->toDateString();
    $out = today()->addDays(4)->toDateString();
    $b = $svc->createHold($acc, stay($in, $out), $this->guest());
    $b->update(['expires_at' => now()->subMinute()]);

    // Even before the scheduler runs, an expired hold no longer blocks the unit.
    expect(app(AvailabilityService::class)->availableUnits($acc, today()->addDays(3), today()->addDays(4)))->toHaveCount(1);

    expect($svc->expireHolds())->toBe(1);
    expect($b->fresh()->status)->toBe(BookingStatus::Expired);
});

it('respects blocked dates for a single unit and for the whole stay', function () {
    $acc = $this->makeStay(units: 2);
    $avail = app(AvailabilityService::class);
    $u = $acc->units->first();
    BlockedDate::create(['unit_id' => $u->id, 'starts_on' => today()->addDays(2), 'ends_on' => today()->addDays(2)]);

    expect($avail->availableUnits($acc, today()->addDays(2), today()->addDays(3)))->toHaveCount(1);

    BlockedDate::create(['accommodation_id' => $acc->id, 'starts_on' => today()->addDays(20), 'ends_on' => today()->addDays(21)]);
    expect($avail->availableUnits($acc, today()->addDays(21), today()->addDays(23)))->toHaveCount(0);
});

it('rejects stays over capacity', function () {
    $acc = $this->makeStay();
    app(BookingService::class)->createHold($acc, stay(today()->addDay()->toDateString(), today()->addDays(2)->toDateString(), 4), $this->guest());
})->throws(BookingException::class);

it('cancels with policy refund tiers', function () {
    $acc = $this->makeStay();
    $svc = app(BookingService::class);
    $b = $svc->createHold($acc, stay(today()->addDays(30)->toDateString(), today()->addDays(32)->toDateString()), $this->guest());
    $svc->recordManualPayment($b, (float) $b->amount_due_now, \App\Enums\PaymentProvider::Cash);

    $q = $svc->cancellationQuote($b->fresh());
    expect($q['tier'])->toBe('free')->and($q['refund'])->toEqual((float) $b->total);

    $svc->cancel($b->fresh(), 'test');
    expect($b->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($b->units()->first()->is_active)->toBeFalse();
});
