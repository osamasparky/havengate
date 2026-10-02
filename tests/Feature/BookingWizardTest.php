<?php

use App\Livewire\BookingWizard;
use App\Models\Booking;
use App\Models\Experience;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

it('opens the wizard with a stay and dates already in the link', function (string $locale) {
    $this->seed(DatabaseSeeder::class);
    $in = today()->addDays(5)->toDateString();
    $out = today()->addDays(20)->toDateString();

    $this->get("/{$locale}/book?stay=family-chalet&checkin={$in}&checkout={$out}")->assertOk();
})->with(['en', 'ar', 'he']);

it('walks the whole booking flow to checkout', function (string $locale) {
    $this->seed(DatabaseSeeder::class);
    app()->setLocale($locale);
    URL::defaults(['locale' => $locale]); // the site sets this in SetLocale middleware

    $wizard = Livewire::withQueryParams([
        'checkin' => today()->addDays(5)->toDateString(),
        'checkout' => today()->addDays(8)->toDateString(),
    ])->test(BookingWizard::class)
        ->call('searchStays')->assertHasNoErrors()
        ->call('chooseStay', 'sea-view-chalet')->assertSet('step', 3)
        ->call('toggleExtra', Experience::first()->id)
        ->set('promoInput', 'WELCOME10')->call('applyPromo')
        ->set('promoInput', 'NOPE')->call('applyPromo')->assertHasErrors('promoInput')
        ->set('guest.first_name', 'Test')->set('guest.last_name', 'Guest')
        ->set('guest.email', 'guest@example.com')->set('guest.phone', '+201000000000')
        ->set('acceptPolicies', true)
        ->call('submit')->assertHasNoErrors();

    $booking = Booking::latest('id')->first();
    $wizard->assertRedirect(route('booking.checkout', ['locale' => $locale, 'booking' => $booking->reference, 'token' => $booking->manage_token]));
    $this->get("/{$locale}/booking/{$booking->reference}/checkout?token={$booking->manage_token}")->assertOk();
})->with(['en', 'ar', 'he']);
