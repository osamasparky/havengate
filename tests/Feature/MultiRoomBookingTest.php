<?php

use App\Exceptions\BookingException;
use App\Livewire\BookingWizard;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Support\StayRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

it('works out how many units a group needs and splits it by capacity', function () {
    $acc = $this->makeStay(units: 3); // 3 adults, 1 child, 3 guests per unit

    expect($acc->roomsNeeded(2, 0))->toBe(1)
        ->and($acc->roomsNeeded(7, 0))->toBe(3)
        ->and($acc->roomsNeeded(4, 2))->toBe(2)
        ->and($acc->splitParty(7, 0, 3))->toBe([['adults' => 3, 'children' => 0], ['adults' => 2, 'children' => 0], ['adults' => 2, 'children' => 0]])
        ->and($acc->splitParty(4, 2, 2))->toBe([['adults' => 2, 'children' => 1], ['adults' => 2, 'children' => 1]])
        ->and($acc->canSleep(7, 0, 2))->toBeFalse();
});

it('prices every unit and charges extra guests per unit', function () {
    $acc = $this->makeStay(units: 3); // base 1000, extra adult 200, base occupancy 2
    $mon = today()->next('Monday');
    $stay = StayRequest::make($mon->toDateString(), $mon->copy()->addDay()->toDateString(), 7, 0, rooms: 3);

    $q = app(PricingService::class)->quote($acc, $stay);

    expect($q->rooms)->toBe(3)
        ->and($q->roomSubtotal)->toEqual(3000.0)
        ->and($q->extraGuestFees)->toEqual(200.0) // only the unit with 3 adults
        ->and($q->total)->toEqual(3200.0)
        ->and($q->averageNightly())->toEqual(1000.0);
});

it('holds one unit per room and splits the group over them', function () {
    $acc = $this->makeStay(units: 3);
    $in = today()->addDays(10)->toDateString();
    $out = today()->addDays(12)->toDateString();

    $b = app(BookingService::class)->createHold($acc, StayRequest::make($in, $out, 7, rooms: 3), $this->guest());

    expect($b->units)->toHaveCount(3)
        ->and($b->units->pluck('unit_id')->unique())->toHaveCount(3)
        ->and($b->units->sum('adults'))->toBe(7)
        ->and((float) $b->units->sum('subtotal'))->toEqual((float) $b->accommodation_total)
        ->and(app(AvailabilityService::class)->availableUnits($acc, today()->addDays(10), today()->addDays(12)))->toHaveCount(0);
});

it('refuses a group that does not fit the units asked for or left', function () {
    $acc = $this->makeStay(units: 2);
    $svc = app(BookingService::class);
    $in = today()->addDays(10)->toDateString();
    $out = today()->addDays(12)->toDateString();

    expect(fn () => $svc->createHold($acc, StayRequest::make($in, $out, 7, rooms: 2), $this->guest()))->toThrow(BookingException::class)
        ->and(fn () => $svc->createHold($acc, StayRequest::make($in, $out, 7, rooms: 3), $this->guest()))->toThrow(BookingException::class);
});

it('offers a big group several rooms in the booking wizard', function () {
    $this->seed(DatabaseSeeder::class);
    URL::defaults(['locale' => 'en']);

    $wizard = Livewire::withQueryParams([
        'checkin' => today()->addDays(5)->toDateString(),
        'checkout' => today()->addDays(7)->toDateString(),
        'adults' => 7,
    ])->test(BookingWizard::class);

    $result = collect($wizard->instance()->results)->firstWhere('accommodation.slug', 'sea-view-chalet');
    expect($result['rooms_needed'])->toBe(3)->and($result['bookable'])->toBeTrue();

    $wizard->call('chooseStay', 'sea-view-chalet')->assertSet('rooms', 3)
        ->call('setRooms', 1)->assertSet('rooms', 3)  // can't go below what the group needs
        ->call('setRooms', 4)->assertSet('rooms', 4)  // more space is fine
        ->set('guest.first_name', 'Big')->set('guest.last_name', 'Group')
        ->set('guest.email', 'group@example.com')->set('guest.phone', '+201000000000')
        ->set('acceptPolicies', true)
        ->call('submit')->assertHasNoErrors();

    expect(Booking::latest('id')->first()->units)->toHaveCount(4);
});
