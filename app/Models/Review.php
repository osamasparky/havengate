<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = ['id'];

    /** Per-request cache of rating aggregates, see ratingsBy(). */
    private static array $ratings = [];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified' => 'boolean',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::$ratings = []);
        static::deleted(fn () => static::$ratings = []);
    }

    /** Booking statuses that count as a "confirmed reservation" for reviewing. */
    public static function eligibleStatuses(): array
    {
        return [BookingStatus::Confirmed, BookingStatus::CheckedIn, BookingStatus::CheckedOut];
    }

    /** One review per reservation for the stay, and one per experience. */
    public static function alreadyReviewed(Booking $booking, ?int $experienceId = null): bool
    {
        return static::where('booking_id', $booking->id)
            ->when($experienceId, fn ($q) => $q->where('experience_id', $experienceId), fn ($q) => $q->whereNull('experience_id'))
            ->exists();
    }

    public static function bookingCanReview(Booking $booking, ?int $experienceId = null): bool
    {
        return in_array($booking->status, static::eligibleStatuses(), true)
            && ! static::alreadyReviewed($booking, $experienceId);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_approved', true)->latest();
    }

    /** What the review is about, for the card chip: the experience, else the stay. */
    public function subjectName(): ?string
    {
        return $this->experience?->name ?? $this->accommodation?->name;
    }

    /** "Sara M." — first name + last initial for public display. */
    public function displayName(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name)) ?: [];
        $first = array_shift($parts) ?? '';
        $last = $parts ? mb_substr(end($parts), 0, 1).'.' : '';

        return trim("{$first} {$last}");
    }

    /** ['average' => 4.7, 'count' => 23] over approved reviews, optionally scoped. */
    public static function summary(?\Closure $scope = null): array
    {
        $row = static::where('is_approved', true)->when($scope, $scope)
            ->selectRaw('AVG(rating) as avg, COUNT(*) as cnt')->first();

        return ['average' => round((float) $row->avg, 1), 'count' => (int) $row->cnt];
    }

    /**
     * Approved-review rating per accommodation_id / experience_id, one query per
     * column per request, so cards in a list don't each hit the database.
     *
     * @return array<int, array{average: float, count: int}>
     */
    public static function ratingsBy(string $column): array
    {
        abort_unless(in_array($column, ['accommodation_id', 'experience_id'], true), 500);

        return static::$ratings[$column] ??= static::where('is_approved', true)->whereNotNull($column)
            // Stay ratings count stay reviews only, not reviews of experiences booked with the stay.
            ->when($column === 'accommodation_id', fn ($q) => $q->whereNull('experience_id'))
            ->groupBy($column)
            ->selectRaw("{$column} as subject, AVG(rating) as avg, COUNT(*) as cnt")
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->subject => ['average' => round((float) $r->avg, 1), 'count' => (int) $r->cnt]])
            ->all();
    }
}
