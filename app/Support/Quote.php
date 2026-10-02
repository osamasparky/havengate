<?php

namespace App\Support;

/** Fully itemised price for a stay. All amounts in config('heavengate.currency'). */
final class Quote
{
    public function __construct(
        /** @var array<int, array{date:string, rate:float, label:?string}> */
        public array $nights = [],
        public float $roomSubtotal = 0,
        public float $extraGuestFees = 0,
        /** @var array<int, array{experience_id:int, name:string, quantity:int, unit_price:float, total:float}> */
        public array $extras = [],
        public float $extrasTotal = 0,
        public float $petFee = 0,
        public float $discount = 0,
        public ?int $promotionId = null,
        public ?string $promoCode = null,
        public ?string $promoError = null,
        public float $serviceCharge = 0,
        public float $vat = 0,
        public float $total = 0,
        public float $dueNow = 0,
        public int $depositPercent = 100,
        public int $rooms = 1,
        /** @var array<int, array{adults:int, children:int, extra_fee:float, subtotal:float}> per unit */
        public array $roomParty = [],
    ) {}

    public function accommodationTotal(): float
    {
        return round($this->roomSubtotal + $this->extraGuestFees + $this->petFee, 2);
    }

    public function taxTotal(): float
    {
        return round($this->serviceCharge + $this->vat, 2);
    }

    /** Average nightly rate of one unit. */
    public function averageNightly(): float
    {
        return count($this->nights) ? round($this->roomSubtotal / count($this->nights) / max(1, $this->rooms), 2) : 0;
    }

    public function toArray(): array
    {
        return get_object_vars($this) + [
            'accommodation_total' => $this->accommodationTotal(),
            'tax_total' => $this->taxTotal(),
            'average_nightly' => $this->averageNightly(),
        ];
    }
}
