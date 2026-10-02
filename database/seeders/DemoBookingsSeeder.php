<?php

namespace Database\Seeders;

use App\Models\Accommodation;
use App\Services\BookingService;
use App\Support\StayRequest;
use Illuminate\Database\Seeder;

/** A handful of realistic bookings so the admin calendar and dashboard aren't empty (local only). */
class DemoBookingsSeeder extends Seeder
{
    public function run(BookingService $bookings): void
    {
        $guests = [
            ['Mariam', 'Hassan', 'mariam.demo@example.com', '+201001112233', 'EG', 'ar'],
            ['Noa', 'Levi', 'noa.demo@example.com', '+972501234567', 'IL', 'he'],
            ['Lukas', 'Becker', 'lukas.demo@example.com', '+491701234567', 'DE', 'en'],
            ['Omar', 'Fathy', 'omar.demo@example.com', '+201112223344', 'EG', 'ar'],
            ['Sara', 'Cohen', 'sara.demo@example.com', '+972521112233', 'IL', 'he'],
        ];
        $stays = Accommodation::all()->keyBy('slug');
        $plan = [
            ['sea-view-chalet', 2, 3, 2, 0],
            ['beach-hut', 5, 2, 2, 0],
            ['family-chalet', 9, 4, 2, 2],
            ['sea-view-chalet', 0, 2, 2, 0],
            ['sea-view-chalet', 14, 3, 2, 1],
        ];

        foreach ($plan as $i => [$slug, $offset, $nights, $adults, $children]) {
            [$first, $last, $email, $phone, $country, $locale] = $guests[$i];
            app()->setLocale($locale);
            $in = today()->addDays($offset);
            $stay = StayRequest::make($in->toDateString(), $in->copy()->addDays($nights)->toDateString(), $adults, $children);

            $booking = $bookings->createHold($stays[$slug], $stay, [
                'first_name' => $first, 'last_name' => $last, 'email' => $email, 'phone' => $phone, 'country' => $country,
            ], ['source' => $i % 2 ? 'phone' : 'website']);

            if ($i !== 1) { // leave one pending
                $bookings->recordManualPayment($booking, (float) $booking->amount_due_now, \App\Enums\PaymentProvider::Cash, 'Demo payment');
            }
        }
        app()->setLocale('en');
    }
}
