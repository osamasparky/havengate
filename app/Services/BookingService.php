<?php

namespace App\Services;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingException;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingReceivedMail;
use App\Mail\NewBookingAdminMail;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Unit;
use App\Support\StayRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {}

    /** Throws BookingException if a stay request breaks a rule. */
    public function validateStay(Accommodation $accommodation, StayRequest $stay): void
    {
        $today = now()->startOfDay();
        $minLead = (int) setting('min_lead_days', 0);

        match (true) {
            $stay->nights < 1 => throw new BookingException('booking.errors.dates'),
            $stay->checkIn->lt($today->copy()->addDays($minLead)) => throw new BookingException('booking.errors.too_soon', ['days' => $minLead]),
            $stay->checkIn->gt($today->copy()->addDays(config('heavengate.max_advance_days'))) => throw new BookingException('booking.errors.too_far'),
            $stay->nights > (int) setting('max_nights', 30) => throw new BookingException('booking.errors.max_nights', ['n' => setting('max_nights', 30)]),
            $stay->nights < $accommodation->min_nights => throw new BookingException('booking.errors.min_nights', ['n' => $accommodation->min_nights]),
            ! $accommodation->canSleep($stay->adults, $stay->children, $stay->rooms) => throw new BookingException('booking.errors.capacity', ['n' => $accommodation->max_guests * $stay->rooms]),
            $stay->withPet && ! setting('pets_allowed', true) => throw new BookingException('booking.errors.pets'),
            default => null,
        };
    }

    /**
     * Create a pending booking that holds $stay->rooms units while the guest pays.
     * Units of the accommodation are row-locked for the duration of the
     * transaction so two guests can never be given the same unit.
     * `unit_id` pins the first unit (staff); the rest are auto-assigned.
     *
     * @param  array{first_name:string,last_name:string,email:string,phone:?string,country:?string,marketing_opt_in?:bool}  $guestData
     * @param  array{special_requests?:?string, arrival_time?:?string, source?:string, unit_id?:?int, hold_minutes?:int, payment_method?:string}  $options
     */
    public function createHold(Accommodation $accommodation, StayRequest $stay, array $guestData, array $options = []): Booking
    {
        $this->validateStay($accommodation, $stay);

        return DB::transaction(function () use ($accommodation, $stay, $guestData, $options) {
            $units = Unit::where('accommodation_id', $accommodation->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->lockForUpdate()
                ->get();

            $free = $this->availability->availableUnits($accommodation, $stay->checkIn, $stay->checkOut, $units);
            if (! empty($options['unit_id'])) {
                $pinned = $free->firstWhere('id', (int) $options['unit_id']) ?? throw new BookingException('booking.errors.sold_out');
                $free = $free->reject(fn (Unit $u) => $u->id === $pinned->id)->prepend($pinned);
            }
            if ($free->count() < $stay->rooms) {
                throw new BookingException('booking.errors.sold_out');
            }
            $picked = $free->take($stay->rooms)->values();

            $quote = $this->pricing->quote($accommodation, $stay);
            if ($stay->promoCode && $quote->promoError) {
                throw new BookingException($quote->promoError);
            }

            $guest = Guest::updateOrCreate(
                ['email' => strtolower(trim($guestData['email']))],
                [
                    'first_name' => $guestData['first_name'],
                    'last_name' => $guestData['last_name'],
                    'phone' => $guestData['phone'] ?? null,
                    'country' => $guestData['country'] ?? null,
                    'preferred_locale' => app()->getLocale(),
                    'marketing_opt_in' => (bool) ($guestData['marketing_opt_in'] ?? false),
                ],
            );

            $holdMinutes = $options['hold_minutes'] ?? config('heavengate.hold_minutes');

            $booking = Booking::create([
                'guest_id' => $guest->id,
                'status' => BookingStatus::Pending,
                'payment_status' => BookingPaymentStatus::Unpaid,
                'source' => $options['source'] ?? 'website',
                'check_in' => $stay->checkIn,
                'check_out' => $stay->checkOut,
                'nights' => $stay->nights,
                'adults' => $stay->adults,
                'children' => $stay->children,
                'with_pet' => $stay->withPet,
                'currency' => config('heavengate.currency'),
                'accommodation_total' => $quote->accommodationTotal(),
                'extras_total' => $quote->extrasTotal,
                'discount_total' => $quote->discount,
                'tax_total' => $quote->taxTotal(),
                'total' => $quote->total,
                'amount_due_now' => $quote->dueNow,
                'promotion_id' => $quote->promotionId,
                'promo_code' => $quote->promoCode,
                'locale' => app()->getLocale(),
                'arrival_time' => $options['arrival_time'] ?? null,
                'special_requests' => $options['special_requests'] ?? null,
                'expires_at' => $holdMinutes ? now()->addMinutes($holdMinutes) : null,
                'ip_address' => request()?->ip(),
                'created_by' => auth()->id(),
            ]);

            foreach ($picked as $i => $unit) {
                $party = $quote->roomParty[$i];
                $booking->units()->create([
                    'unit_id' => $unit->id,
                    'accommodation_id' => $accommodation->id,
                    'check_in' => $stay->checkIn,
                    'check_out' => $stay->checkOut,
                    'adults' => $party['adults'],
                    'children' => $party['children'],
                    'nightly_rates' => $quote->nights,
                    'subtotal' => round($party['subtotal'] + ($i === 0 ? $quote->petFee : 0), 2),
                ]);
            }

            foreach ($quote->extras as $extra) {
                $booking->extras()->create([
                    'experience_id' => $extra['experience_id'],
                    'name' => $extra['name'],
                    'quantity' => $extra['quantity'],
                    'unit_price' => $extra['unit_price'],
                    'total' => $extra['total'],
                ]);
            }

            $booking->log('created', ($picked->count() > 1 ? 'Units ' : 'Unit ').$picked->pluck('code')->implode(', ').' held', ['quote' => $quote->toArray()]);

            return $booking->fresh(['units.unit', 'units.accommodation', 'guest', 'extras']);
        });
    }

    /**
     * Apply a successful payment. Idempotent per payment: a payment already
     * marked paid is ignored.
     */
    public function markPaymentPaid(Payment $payment, array $data = []): Booking
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

            if ($payment->status === PaymentStatus::Paid) {
                return $booking;
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'method' => $data['method'] ?? $payment->method,
                'provider_reference' => $data['provider_reference'] ?? $payment->provider_reference,
                'payload' => array_merge($payment->payload ?? [], ['callback' => $data['payload'] ?? null]),
            ]);

            $this->refreshPaymentTotals($booking);
            $booking->log('payment_received', money($payment->amount)." via {$payment->provider->getLabel()}", ['payment_id' => $payment->id]);

            // An expired hold that gets paid late is re-confirmed if the unit is still free.
            if (in_array($booking->status, [BookingStatus::Pending, BookingStatus::Expired], true)
                && (float) $booking->amount_paid + 0.01 >= (float) $booking->amount_due_now) {
                $this->confirm($booking, fromLatePayment: $booking->status === BookingStatus::Expired || $booking->isHoldExpired());
            }

            return $booking->fresh();
        });
    }

    /** Staff recording cash / bank transfer / InstaPay at the desk or from a receipt. */
    public function recordManualPayment(Booking $booking, float $amount, PaymentProvider $provider, ?string $notes = null): Payment
    {
        $payment = $booking->payments()->create([
            'provider' => $provider,
            'amount' => $amount,
            'currency' => $booking->currency,
            'status' => PaymentStatus::Initiated,
            'notes' => $notes,
            'recorded_by' => auth()->id(),
        ]);
        $this->markPaymentPaid($payment, ['method' => $provider->value]);

        return $payment->fresh();
    }

    public function confirm(Booking $booking, bool $fromLatePayment = false): Booking
    {
        if ($booking->status === BookingStatus::Confirmed) {
            return $booking;
        }

        if ($fromLatePayment) {
            $taken = $booking->units()->with('unit')->get()
                ->contains(fn ($bu) => ! $this->availability->isUnitAvailable($bu->unit, $bu->check_in, $bu->check_out, $booking->id));
            if ($taken) {
                $booking->log('needs_attention', 'Paid after hold expired but unit is no longer free — reassign or refund.');
                Log::warning('Late payment on unavailable unit', ['booking' => $booking->reference]);
                $this->notifyAdmin($booking, 'attention');

                return $booking;
            }
        }

        $booking->update([
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
            'expires_at' => null,
        ]);
        $booking->units()->update(['is_active' => true]);

        if ($booking->promotion_id) {
            Promotion::whereKey($booking->promotion_id)->increment('used_count');
        }

        $booking->log('confirmed');
        $this->sendGuestMail($booking, new BookingConfirmedMail($booking));
        $this->notifyAdmin($booking);

        return $booking;
    }

    /** Guest chose bank transfer / InstaPay: keep a longer hold and email instructions. */
    public function awaitOfflinePayment(Booking $booking): void
    {
        $hours = (int) setting('offline_hold_hours', 24);
        $booking->update(['expires_at' => now()->addHours($hours)]);
        $booking->log('awaiting_offline_payment', "Hold extended {$hours}h");
        $this->sendGuestMail($booking, new BookingReceivedMail($booking));
        $this->notifyAdmin($booking, 'offline');
    }

    /** Refund due if cancelled now, per the booking policy settings. */
    public function cancellationQuote(Booking $booking): array
    {
        $daysBefore = (int) now()->startOfDay()->diffInDays($booking->check_in, false);
        $freeDays = (int) setting('free_cancellation_days', 7);
        $lateDays = (int) setting('late_cancellation_days', 2);
        $latePercent = (int) setting('late_cancellation_refund_percent', 50);
        $paid = (float) $booking->amount_paid;

        [$percent, $tier] = match (true) {
            $daysBefore >= $freeDays => [100, 'free'],
            $daysBefore >= $lateDays => [$latePercent, 'late'],
            default => [0, 'none'],
        };

        return [
            'tier' => $tier,
            'percent' => $percent,
            'days_before' => $daysBefore,
            'refund' => round($paid * $percent / 100, 2),
            'paid' => $paid,
            'guest_can_cancel' => in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true) && $daysBefore >= 0,
        ];
    }

    public function cancel(Booking $booking, ?string $reason = null, bool $byGuest = false, ?float $refundOverride = null): Booking
    {
        if (! $booking->status->isActive()) {
            throw new BookingException('booking.errors.not_cancellable');
        }

        $quote = $this->cancellationQuote($booking);
        $refund = $refundOverride ?? $quote['refund'];

        DB::transaction(function () use ($booking, $reason, $refund, $byGuest) {
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'refund_amount' => $refund,
                'expires_at' => null,
            ]);
            $booking->units()->update(['is_active' => false]);
            $booking->log('cancelled', ($byGuest ? 'Cancelled by guest' : 'Cancelled by staff').($reason ? ": {$reason}" : ''), ['refund' => $refund]);
        });

        // Refunds are executed by staff in the EasyKash dashboard / at the desk, then recorded in admin.
        $this->sendGuestMail($booking, new BookingCancelledMail($booking));
        $this->notifyAdmin($booking, 'cancelled');

        return $booking->fresh();
    }

    public function checkIn(Booking $booking): void
    {
        $booking->update(['status' => BookingStatus::CheckedIn]);
        $booking->log('checked_in');
    }

    public function checkOut(Booking $booking): void
    {
        $booking->update(['status' => BookingStatus::CheckedOut]);
        $booking->log('checked_out');
    }

    public function markNoShow(Booking $booking): void
    {
        $booking->update(['status' => BookingStatus::NoShow]);
        $booking->units()->update(['is_active' => false]);
        $booking->log('no_show');
    }

    /** Move one of a booking's units (the first if not given) to another free unit for the same dates (admin). */
    public function reassignUnit(Booking $booking, Unit $unit, ?int $bookingUnitId = null): void
    {
        DB::transaction(function () use ($booking, $unit, $bookingUnitId) {
            Unit::whereKey($unit->id)->lockForUpdate()->first();
            $bu = $bookingUnitId ? $booking->units()->findOrFail($bookingUnitId) : $booking->units()->firstOrFail();
            if (! $this->availability->isUnitAvailable($unit, $bu->check_in, $bu->check_out, $booking->id)) {
                throw new BookingException('booking.errors.sold_out');
            }
            $old = $bu->unit->code;
            $bu->update(['unit_id' => $unit->id, 'accommodation_id' => $unit->accommodation_id]);
            $booking->log('unit_changed', "{$old} → {$unit->code}");
        });
    }

    /** Release holds whose time ran out. Run every minute by the scheduler. */
    public function expireHolds(): int
    {
        $count = 0;
        Booking::where('status', BookingStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', PaymentStatus::Pending)->where('created_at', '>', now()->subHours(config('services.easykash.cash_expiry_hours', 12))))
            ->chunkById(100, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $booking->update(['status' => BookingStatus::Expired]);
                    $booking->units()->update(['is_active' => false]);
                    $booking->payments()->whereIn('status', [PaymentStatus::Initiated, PaymentStatus::Pending])->update(['status' => PaymentStatus::Expired]);
                    $booking->log('expired', 'Hold released');
                    $count++;
                }
            });

        return $count;
    }

    public function refreshPaymentTotals(Booking $booking): void
    {
        $paid = (float) $booking->payments()->where('status', PaymentStatus::Paid)->where('type', 'charge')->sum('amount');
        $refunded = (float) $booking->payments()->where('status', PaymentStatus::Refunded)->sum('amount');
        $net = round($paid - $refunded, 2);

        $status = match (true) {
            $refunded > 0 && $net <= 0 => BookingPaymentStatus::Refunded,
            $net <= 0 => BookingPaymentStatus::Unpaid,
            $net + 0.01 >= (float) $booking->total => BookingPaymentStatus::Paid,
            default => BookingPaymentStatus::Partial,
        };

        $booking->update(['amount_paid' => $net, 'payment_status' => $status]);
    }

    private function sendGuestMail(Booking $booking, $mailable): void
    {
        try {
            Mail::to($booking->guest->email)->locale($booking->locale)->queue($mailable);
        } catch (\Throwable $e) {
            Log::error('Guest mail failed', ['booking' => $booking->reference, 'error' => $e->getMessage()]);
        }
    }

    private function notifyAdmin(Booking $booking, string $context = 'new'): void
    {
        $to = setting('notification_email', config('heavengate.admin_email'));
        if (! $to) {
            return;
        }
        try {
            Mail::to($to)->locale('en')->queue(new NewBookingAdminMail($booking, $context));
        } catch (\Throwable $e) {
            Log::error('Admin mail failed', ['booking' => $booking->reference, 'error' => $e->getMessage()]);
        }
    }
}
