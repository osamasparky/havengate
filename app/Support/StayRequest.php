<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** Immutable description of what a guest is asking for. */
final class StayRequest
{
    public readonly int $nights;

    /**
     * @param  array<int,int>  $extras  experience_id => quantity
     */
    public function __construct(
        public readonly CarbonImmutable $checkIn,
        public readonly CarbonImmutable $checkOut,
        public readonly int $adults,
        public readonly int $children = 0,
        public readonly array $extras = [],
        public readonly ?string $promoCode = null,
        public readonly bool $withPet = false,
        public readonly int $rooms = 1,
    ) {
        $this->nights = (int) $checkIn->diffInDays($checkOut);
    }

    public static function make(string $checkIn, string $checkOut, int $adults, int $children = 0, array $extras = [], ?string $promo = null, bool $withPet = false, int $rooms = 1): self
    {
        return new self(
            CarbonImmutable::parse($checkIn)->startOfDay(),
            CarbonImmutable::parse($checkOut)->startOfDay(),
            max(1, $adults),
            max(0, $children),
            array_filter(array_map('intval', $extras)),
            $promo ? strtoupper(trim($promo)) : null,
            $withPet,
            max(1, $rooms),
        );
    }

    /** Same request, split over a different number of units. */
    public function withRooms(int $rooms): self
    {
        return new self($this->checkIn, $this->checkOut, $this->adults, $this->children, $this->extras, $this->promoCode, $this->withPet, max(1, $rooms));
    }

    public function guests(): int
    {
        return $this->adults + $this->children;
    }

    /** @return CarbonImmutable[] each night (the date you sleep) */
    public function nightDates(): array
    {
        $dates = [];
        for ($d = $this->checkIn; $d->lt($this->checkOut); $d = $d->addDay()) {
            $dates[] = $d;
        }

        return $dates;
    }
}
