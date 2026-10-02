<?php

namespace Tests;

use App\Models\Accommodation;
use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function makeStay(array $attrs = [], int $units = 2): Accommodation
    {
        Setting::putMany(['weekend_nights' => [4, 5], 'deposit_percent' => 100, 'service_charge_percent' => 0, 'vat_percent' => 0]);

        $a = Accommodation::create(array_merge([
            'slug' => 'chalet-'.uniqid(),
            'name' => ['en' => 'Chalet', 'ar' => 'شاليه', 'he' => 'בקתה'],
            'base_occupancy' => 2, 'max_adults' => 3, 'max_children' => 1, 'max_guests' => 3,
            'base_price' => 1000, 'weekend_price' => 1500, 'extra_adult_fee' => 200, 'extra_child_fee' => 100,
        ], $attrs));
        for ($i = 1; $i <= $units; $i++) {
            $a->units()->create(['code' => $a->id.'-'.$i, 'sort_order' => $i]);
        }

        return $a;
    }

    protected function guest(string $email = 'guest@example.com'): array
    {
        return ['first_name' => 'Test', 'last_name' => 'Guest', 'email' => $email, 'phone' => '+201000000000', 'country' => 'EG'];
    }
}
