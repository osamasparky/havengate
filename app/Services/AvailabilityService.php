<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Accommodation;
use App\Models\BlockedDate;
use App\Models\BookingUnit;
use App\Models\Unit;
use App\Support\StayRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Answers "which units are free?" A night is the date you sleep: a stay
 * 10→12 Oct occupies the nights of the 10th and 11th. Two stays overlap when
 * a.check_in < b.check_out AND a.check_out > b.check_in, so same-day
 * turnover (check-out 12th, check-in 12th) is allowed.
 */
class AvailabilityService
{
    /**
     * Booking-unit rows that currently hold inventory: active rows whose
     * booking is confirmed / checked-in, or pending with a live hold.
     */
    public function occupyingQuery(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return BookingUnit::query()
            ->where('booking_units.is_active', true)
            ->whereDate('booking_units.check_in', '<', $to->toDateString())
            ->whereDate('booking_units.check_out', '>', $from->toDateString())
            ->whereHas('booking', function (Builder $q) {
                $q->whereIn('status', [BookingStatus::Confirmed, BookingStatus::CheckedIn])
                    ->orWhere(fn (Builder $p) => $p
                        ->where('status', BookingStatus::Pending)
                        ->where(fn (Builder $e) => $e->whereNull('expires_at')->orWhere('expires_at', '>', now())));
            });
    }

    /** Blocks touching any night in [from, to). */
    public function blocksQuery(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return BlockedDate::query()
            ->whereDate('starts_on', '<=', $to->copy()->subDay()->toDateString())
            ->whereDate('ends_on', '>=', $from->toDateString());
    }

    /**
     * Free units of an accommodation for a stay.
     *
     * @param  Collection<int,Unit>|null  $units  pre-locked units (inside a transaction)
     * @return Collection<int,Unit>
     */
    public function availableUnits(Accommodation $accommodation, CarbonInterface $checkIn, CarbonInterface $checkOut, ?Collection $units = null, ?int $ignoreBookingId = null): Collection
    {
        $units ??= $accommodation->activeUnits()->get();
        if ($units->isEmpty()) {
            return $units;
        }

        $busy = $this->occupyingQuery($checkIn, $checkOut)
            ->whereIn('unit_id', $units->pluck('id'))
            ->when($ignoreBookingId, fn ($q) => $q->where('booking_id', '!=', $ignoreBookingId))
            ->pluck('unit_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $blocks = $this->blocksQuery($checkIn, $checkOut)->get();
        $campClosed = $blocks->contains(fn ($b) => ! $b->unit_id && ! $b->accommodation_id);
        $typeClosed = $blocks->contains(fn ($b) => ! $b->unit_id && (int) $b->accommodation_id === (int) $accommodation->id);
        if ($campClosed || $typeClosed) {
            return collect();
        }
        $blockedUnits = $blocks->whereNotNull('unit_id')->pluck('unit_id')->map(fn ($v) => (int) $v)->all();

        return $units->reject(fn (Unit $u) => in_array((int) $u->id, $busy, true) || in_array((int) $u->id, $blockedUnits, true))->values();
    }

    public function isUnitAvailable(Unit $unit, CarbonInterface $checkIn, CarbonInterface $checkOut, ?int $ignoreBookingId = null): bool
    {
        return $this->availableUnits($unit->accommodation, $checkIn, $checkOut, collect([$unit]), $ignoreBookingId)->isNotEmpty();
    }

    /**
     * Search all accommodation types for a stay request.
     *
     * A group too big for one unit is offered as many units of the type as it needs.
     *
     * @return Collection<int, array{accommodation:Accommodation, available:int, rooms_needed:?int, fits:bool, enough_units:bool, min_nights_ok:bool}>
     */
    public function search(StayRequest $stay): Collection
    {
        return Accommodation::active()->with('activeUnits')->get()->map(function (Accommodation $a) use ($stay) {
            $free = $this->availableUnits($a, $stay->checkIn, $stay->checkOut, $a->activeUnits)->count();
            $needed = $a->roomsNeeded($stay->adults, $stay->children);

            return [
                'accommodation' => $a,
                'available' => $free,
                'rooms_needed' => $needed,
                'fits' => $needed !== null,
                'enough_units' => $needed !== null && $free >= $needed,
                'min_nights_ok' => $stay->nights >= $a->min_nights,
            ];
        });
    }

    /**
     * Night-by-night free-unit count for one accommodation (date picker,
     * admin calendar). One query for bookings and one for blocks.
     *
     * @return array<string,int> 'Y-m-d' => free units
     */
    public function calendar(Accommodation $accommodation, CarbonInterface $from, CarbonInterface $to): array
    {
        $from = CarbonImmutable::parse($from)->startOfDay();
        $to = CarbonImmutable::parse($to)->startOfDay();
        $unitIds = $accommodation->activeUnits()->pluck('id')->all();
        $total = count($unitIds);

        $rows = $this->occupyingQuery($from, $to)->whereIn('unit_id', $unitIds)->get(['unit_id', 'check_in', 'check_out']);
        $blocks = $this->blocksQuery($from, $to)
            ->where(fn ($q) => $q->whereNull('accommodation_id')->whereNull('unit_id')
                ->orWhere(fn ($q) => $q->where('accommodation_id', $accommodation->id)->whereNull('unit_id'))
                ->orWhereIn('unit_id', $unitIds))
            ->get();

        $out = [];
        for ($d = $from; $d->lt($to); $d = $d->addDay()) {
            $key = $d->toDateString();
            if ($blocks->contains(fn ($b) => ! $b->unit_id && $b->starts_on->lte($d) && $b->ends_on->gte($d))) {
                $out[$key] = 0;

                continue;
            }
            $taken = $rows->filter(fn ($r) => $r->check_in->lte($d) && $r->check_out->gt($d))->pluck('unit_id')
                ->merge($blocks->filter(fn ($b) => $b->unit_id && $b->starts_on->lte($d) && $b->ends_on->gte($d))->pluck('unit_id'))
                ->unique()->count();
            $out[$key] = max(0, $total - $taken);
        }

        return $out;
    }

    /**
     * Camp-wide calendar for the first booking step: free units and the
     * lowest nightly base price among types with a free unit.
     *
     * @return array<string, array{free:int, from:?float}>
     */
    public function campCalendar(CarbonInterface $from, CarbonInterface $to, PricingService $pricing): array
    {
        $out = [];
        foreach (Accommodation::active()->get() as $a) {
            $cal = $this->calendar($a, $from, $to);
            $stay = new StayRequest(CarbonImmutable::parse($from)->startOfDay(), CarbonImmutable::parse($to)->startOfDay(), 1);
            $rates = collect($pricing->nightlyRates($a, $stay))->pluck('rate', 'date');
            foreach ($cal as $date => $free) {
                $out[$date]['free'] = ($out[$date]['free'] ?? 0) + $free;
                if ($free > 0) {
                    $rate = (float) ($rates[$date] ?? $a->base_price);
                    $out[$date]['from'] = isset($out[$date]['from']) && $out[$date]['from'] !== null ? min($out[$date]['from'], $rate) : $rate;
                } else {
                    $out[$date]['from'] ??= null;
                }
            }
        }

        return $out;
    }

    /**
     * Unit × night occupancy grid for the admin calendar.
     *
     * @return array<int, array<string, array{type:string, booking_id?:int, reference?:string, guest?:string, status?:string}>>
     */
    public function grid(CarbonInterface $from, int $days, ?int $accommodationId = null): array
    {
        $from = CarbonImmutable::parse($from)->startOfDay();
        $to = $from->addDays($days);
        $units = Unit::query()->where('is_active', true)
            ->when($accommodationId, fn ($q) => $q->where('accommodation_id', $accommodationId))
            ->pluck('accommodation_id', 'id'); // unit_id => accommodation_id

        $rows = BookingUnit::query()
            ->with('booking.guest')
            ->where('is_active', true)
            ->whereIn('unit_id', $units->keys())
            ->whereDate('check_in', '<', $to->toDateString())
            ->whereDate('check_out', '>', $from->toDateString())
            ->get();
        $blocks = $this->blocksQuery($from, $to)->get();

        $grid = [];
        foreach ($units->keys() as $unitId) {
            for ($d = $from; $d->lt($to); $d = $d->addDay()) {
                $grid[$unitId][$d->toDateString()] = ['type' => 'free'];
            }
        }
        foreach ($rows as $r) {
            if (! $r->booking || ! $r->booking->status->isActive() || $r->booking->isHoldExpired()) {
                continue;
            }
            for ($d = CarbonImmutable::parse(max($r->check_in, $from)); $d->lt($r->check_out) && $d->lt($to); $d = $d->addDay()) {
                $grid[$r->unit_id][$d->toDateString()] = [
                    'type' => 'booking',
                    'booking_id' => $r->booking_id,
                    'reference' => $r->booking->reference,
                    'guest' => $r->booking->guest?->full_name,
                    'status' => $r->booking->status->value,
                    'start' => $d->equalTo(CarbonImmutable::parse($r->check_in)) || $d->equalTo($from),
                ];
            }
        }
        foreach ($blocks as $b) {
            foreach ($grid as $unitId => $nights) {
                $applies = ! $b->unit_id && ! $b->accommodation_id
                    || (int) $b->unit_id === (int) $unitId
                    || (! $b->unit_id && $b->accommodation_id && (int) $units[$unitId] === (int) $b->accommodation_id);
                if (! $applies) {
                    continue;
                }
                foreach ($nights as $date => $cell) {
                    if ($cell['type'] === 'free' && $b->starts_on->toDateString() <= $date && $b->ends_on->toDateString() >= $date) {
                        $grid[$unitId][$date] = ['type' => 'blocked', 'reason' => $b->reason];
                    }
                }
            }
        }

        return $grid;
    }
}
