<?php

use App\Enums\AdjustmentType;
use App\Models\Promotion;
use App\Models\SeasonalRate;
use App\Services\PricingService;
use App\Support\StayRequest;
use Carbon\CarbonImmutable;

it('charges weekend nights (Thu, Fri) at the weekend price', function () {
    $acc = $this->makeStay();
    $wed = CarbonImmutable::parse('next wednesday');
    $q = app(PricingService::class)->quote($acc, StayRequest::make($wed->toDateString(), $wed->addDays(3)->toDateString(), 2));

    // Wed 1000 + Thu 1500 + Fri 1500
    expect($q->roomSubtotal)->toEqual(4000.0);
});

it('applies the highest-priority seasonal rate', function () {
    $acc = $this->makeStay(['weekend_price' => null]);
    $mon = CarbonImmutable::parse('next monday');
    SeasonalRate::create(['name' => ['en' => 'High'], 'starts_on' => $mon, 'ends_on' => $mon->addDays(6), 'adjustment_type' => AdjustmentType::Percent, 'value' => 50, 'priority' => 10]);
    SeasonalRate::create(['name' => ['en' => 'Event'], 'accommodation_id' => $acc->id, 'starts_on' => $mon, 'ends_on' => $mon, 'adjustment_type' => AdjustmentType::Fixed, 'value' => 3000, 'priority' => 20]);

    $q = app(PricingService::class)->quote($acc, StayRequest::make($mon->toDateString(), $mon->addDays(2)->toDateString(), 2));

    expect(array_column($q->nights, 'rate'))->toEqual([3000.0, 1500.0]);
});

it('adds extra guest fees above base occupancy', function () {
    $acc = $this->makeStay(['weekend_price' => null]);
    $mon = CarbonImmutable::parse('next monday');
    $q = app(PricingService::class)->quote($acc, StayRequest::make($mon->toDateString(), $mon->addDays(2)->toDateString(), 2, 1));

    expect($q->extraGuestFees)->toEqual(200.0); // 1 child × 100 × 2 nights
});

it('validates and applies promo codes', function () {
    $acc = $this->makeStay(['weekend_price' => null]);
    Promotion::create(['code' => 'TEN', 'name' => ['en' => 'Ten'], 'type' => 'percent', 'value' => 10, 'min_nights' => 2]);
    $mon = CarbonImmutable::parse('next monday');

    $ok = app(PricingService::class)->quote($acc, StayRequest::make($mon->toDateString(), $mon->addDays(2)->toDateString(), 2, 0, [], 'ten'));
    $short = app(PricingService::class)->quote($acc, StayRequest::make($mon->toDateString(), $mon->addDay()->toDateString(), 2, 0, [], 'TEN'));

    expect($ok->discount)->toEqual(200.0)->and($ok->total)->toEqual(1800.0)
        ->and($short->discount)->toEqual(0.0)->and($short->promoError)->toBe('booking.promo.min_nights');
});
