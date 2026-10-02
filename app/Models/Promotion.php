<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Promotion extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'bookable_from' => 'date',
            'bookable_until' => 'date',
            'stay_from' => 'date',
            'stay_until' => 'date',
            'accommodation_ids' => 'array',
            'value' => 'decimal:2',
            'show_on_site' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** Can this promotion apply to a stay? Returns a translation key on failure, null if OK. */
    public function ineligibilityReason(CarbonInterface $checkIn, CarbonInterface $checkOut, array $accommodationIds, ?CarbonInterface $today = null): ?string
    {
        $today ??= now()->startOfDay();
        $nights = $checkIn->diffInDays($checkOut);

        return match (true) {
            ! $this->is_active => 'booking.promo.invalid',
            $this->max_uses !== null && $this->used_count >= $this->max_uses => 'booking.promo.used_up',
            $this->bookable_from && $today->lt($this->bookable_from),
            $this->bookable_until && $today->gt($this->bookable_until) => 'booking.promo.expired',
            $this->stay_from && $checkIn->lt($this->stay_from),
            $this->stay_until && $checkOut->copy()->subDay()->gt($this->stay_until) => 'booking.promo.dates',
            $this->min_nights && $nights < $this->min_nights => 'booking.promo.min_nights',
            ! empty($this->accommodation_ids) && empty(array_intersect($this->accommodation_ids, $accommodationIds)) => 'booking.promo.not_applicable',
            default => null,
        };
    }

    public function discountFor(float $amount): float
    {
        $d = $this->type === 'percent' ? $amount * ((float) $this->value / 100) : (float) $this->value;

        return round(min($d, $amount), 2);
    }
}
