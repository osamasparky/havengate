<?php

namespace App\Models;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'payment_status' => BookingPaymentStatus::class,
            'check_in' => 'date',
            'check_out' => 'date',
            'accommodation_total' => 'decimal:2',
            'extras_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_due_now' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'with_pet' => 'boolean',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $b) {
            $b->reference ??= static::generateReference();
            $b->manage_token ??= Str::random(48);
        });
    }

    /** Short, unambiguous reference: HG- + 6 chars without 0/O/1/I. */
    public static function generateReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $ref = 'HG-'.collect(range(1, 6))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
        } while (static::withTrashed()->where('reference', $ref)->exists());

        return $ref;
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(BookingUnit::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(BookingExtra::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(BookingEvent::class)->latest('created_at');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->whereDate('check_in', '>=', today())
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Pending])
            ->orderBy('check_in');
    }

    public function scopeArrivingOn(Builder $q, $date): Builder
    {
        return $q->whereDate('check_in', $date)->whereIn('status', [BookingStatus::Confirmed, BookingStatus::CheckedIn]);
    }

    public function scopeDepartingOn(Builder $q, $date): Builder
    {
        return $q->whereDate('check_out', $date)->whereIn('status', [BookingStatus::CheckedIn, BookingStatus::Confirmed]);
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    public function isHoldExpired(): bool
    {
        return $this->status === BookingStatus::Pending && $this->expires_at && $this->expires_at->isPast();
    }

    public function guestCount(): int
    {
        return $this->adults + $this->children;
    }

    /** Number of units (rooms) this booking takes. */
    public function roomCount(): int
    {
        return max(1, $this->units->count());
    }

    /** "C-03, C-04" — unit codes for staff views. */
    public function unitCodes(): string
    {
        return $this->units->map(fn ($bu) => $bu->unit?->code)->filter()->implode(', ');
    }

    public function manageUrl(): string
    {
        return route('booking.manage.show', ['locale' => $this->locale, 'booking' => $this->reference, 'token' => $this->manage_token]);
    }

    public function log(string $type, ?string $message = null, array $data = []): void
    {
        $this->events()->create([
            'type' => $type,
            'message' => $message,
            'data' => $data ?: null,
            'user_id' => auth()->id(),
        ]);
    }
}
