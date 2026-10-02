<?php

namespace App\Livewire;

use App\Exceptions\BookingException;
use App\Models\Accommodation;
use App\Models\Experience;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Support\StayRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Four-step booking flow:
 *   1 Dates & guests → 2 Choose your stay → 3 Extras & details → hold + checkout.
 * The hold is only created on submit, inside a locked transaction.
 */
#[Layout('layouts.site')]
class BookingWizard extends Component
{
    #[Url(as: 'checkin')]
    public ?string $checkIn = null;

    #[Url(as: 'checkout')]
    public ?string $checkOut = null;

    #[Url]
    public int $adults = 2;

    #[Url]
    public int $children = 0;

    #[Url(as: 'stay')]
    public ?string $staySlug = null;

    public bool $withPet = false;

    /** Units of the chosen type; never below what the group needs. */
    public int $rooms = 1;

    public int $step = 1;

    /** @var array<int,int> experience_id => quantity */
    public array $extras = [];

    public string $promoInput = '';

    public ?string $promoCode = null;

    public array $guest = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'country' => '',
        'marketing_opt_in' => false,
    ];

    public string $arrivalTime = '';

    public string $specialRequests = '';

    public bool $acceptPolicies = false;

    public function mount(): void
    {
        $this->adults = max(1, min(12, $this->adults));
        $this->children = max(0, min(10, $this->children));

        if ($this->validDates()) {
            $this->step = $this->staySlug && $this->selectedStay ? 3 : 2;
            $this->normalizeRooms();
        }
    }

    // ---------------------------------------------------------------- steps

    public function searchStays(): void
    {
        $this->validate([
            'checkIn' => 'required|date|after_or_equal:today',
            'checkOut' => 'required|date|after:checkIn',
            'adults' => 'required|integer|min:1|max:12',
            'children' => 'integer|min:0|max:10',
        ], [], [
            'checkIn' => __('booking.check_in'),
            'checkOut' => __('booking.check_out'),
        ]);
        $this->staySlug = null;
        $this->step = 2;
        unset($this->results);
    }

    public function chooseStay(string $slug): void
    {
        $result = collect($this->results)->first(fn ($r) => $r['accommodation']->slug === $slug);
        if (! $result || ! $result['bookable']) {
            $this->addError('stay', __('booking.errors.sold_out'));

            return;
        }
        $this->staySlug = $slug;
        $this->rooms = $result['rooms_needed'];
        $this->step = 3;
        unset($this->quote, $this->selectedStay);
    }

    public function setRooms(int $rooms): void
    {
        $this->rooms = $rooms;
        $this->normalizeRooms();
        unset($this->quote);
    }

    /** Smallest and largest unit count the guest may pick for the selected stay. */
    #[Computed]
    public function roomRange(): ?array
    {
        $stay = $this->selectedStay;
        $min = $stay?->roomsNeeded($this->adults, $this->children);
        if (! $min || ! $this->validDates()) {
            return null;
        }
        $free = app(AvailabilityService::class)->availableUnits($stay, CarbonImmutable::parse($this->checkIn), CarbonImmutable::parse($this->checkOut))->count();

        return ['min' => $min, 'max' => max($min, min($free, $this->adults, Accommodation::MAX_ROOMS_PER_BOOKING))];
    }

    private function normalizeRooms(): void
    {
        unset($this->roomRange);
        if ($range = $this->roomRange) {
            $this->rooms = max($range['min'], min($range['max'], $this->rooms));
        }
    }

    public function goTo(int $step): void
    {
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    public function toggleExtra(int $id): void
    {
        if (isset($this->extras[$id])) {
            unset($this->extras[$id]);
        } else {
            $exp = Experience::find($id);
            $this->extras[$id] = $exp?->isPerPerson() ? $this->adults + $this->children : 1;
        }
        unset($this->quote);
    }

    public function setExtraQty(int $id, int $qty): void
    {
        if ($qty < 1) {
            unset($this->extras[$id]);
        } else {
            $this->extras[$id] = min(30, $qty);
        }
        unset($this->quote);
    }

    public function applyPromo(): void
    {
        $code = strtoupper(trim($this->promoInput));
        if ($code === '') {
            $this->promoCode = null;
            unset($this->quote);

            return;
        }
        $this->promoCode = $code;
        unset($this->quote);
        if ($this->quote?->promoError) {
            $this->addError('promoInput', __($this->quote->promoError));
            $this->promoCode = null;
            unset($this->quote);
        }
    }

    public function removePromo(): void
    {
        $this->promoCode = null;
        $this->promoInput = '';
        unset($this->quote);
    }

    public function updated($name): void
    {
        if (in_array($name, ['withPet', 'adults', 'children'], true)) {
            unset($this->quote, $this->results);
            $this->normalizeRooms();
        }
    }

    public function submit(BookingService $bookings)
    {
        $this->validate([
            'guest.first_name' => 'required|string|max:80',
            'guest.last_name' => 'required|string|max:80',
            'guest.email' => 'required|email:rfc|max:180',
            'guest.phone' => 'required|string|min:7|max:30',
            'guest.country' => 'nullable|string|size:2',
            'arrivalTime' => 'nullable|string|max:20',
            'specialRequests' => 'nullable|string|max:1500',
            'acceptPolicies' => 'accepted',
        ], [], [
            'guest.first_name' => __('booking.first_name'),
            'guest.last_name' => __('booking.last_name'),
            'guest.email' => __('booking.email'),
            'guest.phone' => __('booking.phone'),
            'acceptPolicies' => __('booking.policies'),
        ]);

        $key = 'hold:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            $this->addError('submit', __('booking.errors.too_many'));

            return null;
        }
        RateLimiter::hit($key, 600);

        try {
            $booking = $bookings->createHold($this->selectedStay, $this->stayRequest(), $this->guest, [
                'arrival_time' => $this->arrivalTime ?: null,
                'special_requests' => $this->specialRequests ?: null,
            ]);
        } catch (BookingException $e) {
            $this->addError('submit', $e->getMessage());
            unset($this->results);

            return null;
        }

        return $this->redirectRoute('booking.checkout', ['booking' => $booking->reference, 'token' => $booking->manage_token]);
    }

    // ------------------------------------------------------------ computed

    #[Computed]
    public function results(): array
    {
        if (! $this->validDates()) {
            return [];
        }
        $stay = $this->stayRequest();
        $pricing = app(PricingService::class);

        return app(AvailabilityService::class)->search($stay)->map(function ($r) use ($stay, $pricing) {
            $quote = $pricing->quote($r['accommodation'], $stay->withRooms($r['rooms_needed'] ?? 1), validatePromo: false);

            return $r + [
                'quote' => $quote,
                'bookable' => $r['enough_units'] && $r['min_nights_ok'],
            ];
        })->sortBy([['bookable', 'desc'], fn ($a, $b) => ($a['rooms_needed'] ?? 99) <=> ($b['rooms_needed'] ?? 99), fn ($a, $b) => $a['quote']->total <=> $b['quote']->total])->values()->all();
    }

    #[Computed]
    public function selectedStay(): ?Accommodation
    {
        return $this->staySlug ? Accommodation::active()->where('slug', $this->staySlug)->first() : null;
    }

    #[Computed]
    public function quote(): ?\App\Support\Quote
    {
        return $this->selectedStay && $this->validDates()
            ? app(PricingService::class)->quote($this->selectedStay, $this->stayRequest())
            : null;
    }

    #[Computed]
    public function addons()
    {
        return Experience::active()->where('is_addon', true)->get();
    }

    public function stayRequest(): StayRequest
    {
        return StayRequest::make($this->checkIn, $this->checkOut, $this->adults, $this->children, $this->extras, $this->promoCode, $this->withPet, $this->rooms);
    }

    private function validDates(): bool
    {
        try {
            $in = CarbonImmutable::parse((string) $this->checkIn);
            $out = CarbonImmutable::parse((string) $this->checkOut);
        } catch (\Throwable) {
            return false;
        }

        return $this->checkIn && $this->checkOut && $in->gte(today()) && $out->gt($in);
    }

    public function render()
    {
        return view('livewire.booking-wizard')->title(__('booking.title'));
    }
}
