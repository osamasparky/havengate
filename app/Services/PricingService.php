<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\Experience;
use App\Models\Promotion;
use App\Models\SeasonalRate;
use App\Support\Quote;
use App\Support\StayRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Turns a stay request into an itemised quote.
 *
 * Nightly rate resolution, per night:
 *   1. base_price, or weekend_price on weekend nights (Thu & Fri nights by default — Egyptian weekend);
 *   2. the highest-priority active seasonal rate covering that night (accommodation-specific wins ties
 *      over camp-wide), whose weekday filter and min-nights condition match, adjusts/replaces it.
 * A large group may take several units of one type (StayRequest::rooms): nightly rates are
 * charged per unit and the group is split over the units by capacity. Extra guests above a
 * unit's base_occupancy add per-night fees (adults fill the base first).
 * Promotion → on accommodation + extras. Service charge and VAT → on the discounted subtotal.
 */
class PricingService
{
    /** @var array<string, Collection> */
    private array $seasonCache = [];

    /** @return array<int, array{date:string, rate:float, label:?string}> */
    public function nightlyRates(Accommodation $accommodation, StayRequest $stay): array
    {
        $weekendNights = array_map('intval', (array) setting('weekend_nights', [4, 5])); // Carbon: 4=Thu, 5=Fri
        $seasons = $this->seasonsFor($accommodation, $stay->checkIn, $stay->checkOut);

        return array_map(function (CarbonImmutable $night) use ($accommodation, $weekendNights, $seasons, $stay) {
            $isWeekend = in_array($night->dayOfWeek, $weekendNights, true);
            $rate = (float) ($isWeekend && $accommodation->weekend_price ? $accommodation->weekend_price : $accommodation->base_price);
            $label = $isWeekend && $accommodation->weekend_price ? __('booking.weekend') : null;

            $season = $seasons->first(fn (SeasonalRate $s) => $s->starts_on->lte($night)
                && $s->ends_on->gte($night)
                && (empty($s->weekdays) || in_array($night->dayOfWeek, array_map('intval', $s->weekdays), true))
                && (! $s->min_nights || $stay->nights >= $s->min_nights));

            if ($season) {
                $rate = $season->adjustment_type->apply($rate, (float) $season->value);
                $label = $season->name;
            }

            return ['date' => $night->toDateString(), 'rate' => round($rate, 2), 'label' => $label];
        }, $stay->nightDates());
    }

    public function quote(Accommodation $accommodation, StayRequest $stay, bool $validatePromo = true): Quote
    {
        $q = new Quote;
        $q->nights = $this->nightlyRates($accommodation, $stay);
        $q->rooms = $stay->rooms;
        $perUnit = round(array_sum(array_column($q->nights, 'rate')), 2);
        $q->roomSubtotal = round($perUnit * $stay->rooms, 2);

        // Extra occupants beyond what the base price includes, unit by unit.
        $base = $accommodation->base_occupancy;
        foreach ($accommodation->splitParty($stay->adults, $stay->children, $stay->rooms) as $party) {
            $extraAdults = max(0, $party['adults'] - $base);
            $extraChildren = max(0, $party['children'] - max(0, $base - $party['adults']));
            $fee = round($stay->nights * ($extraAdults * (float) $accommodation->extra_adult_fee + $extraChildren * (float) $accommodation->extra_child_fee), 2);
            $q->roomParty[] = $party + ['extra_fee' => $fee, 'subtotal' => round($perUnit + $fee, 2)];
            $q->extraGuestFees += $fee;
        }
        $q->extraGuestFees = round($q->extraGuestFees, 2);

        if ($stay->withPet) {
            $q->petFee = round((float) setting('pet_fee_per_night', 0) * $stay->nights, 2);
        }

        // Add-on experiences.
        if ($stay->extras) {
            $experiences = Experience::active()->where('is_addon', true)->whereIn('id', array_keys($stay->extras))->get();
            foreach ($experiences as $exp) {
                $qty = max(1, (int) $stay->extras[$exp->id]);
                if ($exp->isPerPerson() && $exp->max_people) {
                    $qty = min($qty, $exp->max_people);
                }
                $total = round($exp->price * $qty, 2);
                $q->extras[] = ['experience_id' => $exp->id, 'name' => $exp->name, 'quantity' => $qty, 'unit_price' => (float) $exp->price, 'total' => $total];
                $q->extrasTotal += $total;
            }
            $q->extrasTotal = round($q->extrasTotal, 2);
        }

        // Promotion: explicit code, or the best automatic one.
        $subtotal = $q->accommodationTotal() + $q->extrasTotal;
        if ($validatePromo) {
            [$promo, $error] = $this->resolvePromotion($accommodation, $stay, $subtotal);
            if ($promo) {
                $q->promotionId = $promo->id;
                $q->promoCode = $promo->code;
                $q->discount = $promo->discountFor($subtotal);
            }
            $q->promoError = $error;
        }

        // Taxes on the discounted amount.
        $taxable = max(0, $subtotal - $q->discount);
        $q->serviceCharge = round($taxable * (float) setting('service_charge_percent', 0) / 100, 2);
        $q->vat = round(($taxable + $q->serviceCharge) * (float) setting('vat_percent', 0) / 100, 2);
        $q->total = round($taxable + $q->serviceCharge + $q->vat, 2);

        $q->depositPercent = (int) min(100, max(0, (int) setting('deposit_percent', 100)));
        $q->dueNow = $q->depositPercent >= 100 ? $q->total : round($q->total * $q->depositPercent / 100, 2);

        return $q;
    }

    /** @return array{0: ?Promotion, 1: ?string} promotion and error translation key */
    public function resolvePromotion(Accommodation $accommodation, StayRequest $stay, float $subtotal): array
    {
        if ($stay->promoCode) {
            $promo = Promotion::whereRaw('UPPER(code) = ?', [$stay->promoCode])->first();
            if (! $promo) {
                return [null, 'booking.promo.invalid'];
            }
            $reason = $promo->ineligibilityReason($stay->checkIn, $stay->checkOut, [$accommodation->id]);

            return $reason ? [null, $reason] : [$promo, null];
        }

        $best = Promotion::whereNull('code')->where('is_active', true)->get()
            ->filter(fn (Promotion $p) => $p->ineligibilityReason($stay->checkIn, $stay->checkOut, [$accommodation->id]) === null)
            ->sortByDesc(fn (Promotion $p) => $p->discountFor($subtotal))
            ->first();

        return [$best, null];
    }

    /** Lowest nightly rate over the next N days — "from EGP X" on cards. */
    public function fromPrice(Accommodation $accommodation): float
    {
        $min = (float) $accommodation->base_price;
        if ($accommodation->weekend_price) {
            $min = min($min, (float) $accommodation->weekend_price);
        }

        return $min;
    }

    private function seasonsFor(Accommodation $accommodation, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $key = $accommodation->id.'|'.$from->toDateString().'|'.$to->toDateString();

        return $this->seasonCache[$key] ??= SeasonalRate::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('accommodation_id')->orWhere('accommodation_id', $accommodation->id))
            ->whereDate('starts_on', '<=', $to->toDateString())
            ->whereDate('ends_on', '>=', $from->toDateString())
            ->get()
            ->sortBy([
                ['priority', 'desc'],
                fn ($a, $b) => ($b->accommodation_id ? 1 : 0) <=> ($a->accommodation_id ? 1 : 0),
                ['id', 'desc'],
            ])
            ->values();
    }
}
