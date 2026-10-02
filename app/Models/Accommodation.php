<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Accommodation extends Model
{
    use HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'tagline', 'description', 'highlights', 'bed_configuration', 'meta_title', 'meta_description'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'base_price' => 'decimal:2',
            'weekend_price' => 'decimal:2',
            'extra_adult_fee' => 'decimal:2',
            'extra_child_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sort_order');
    }

    public function activeUnits(): HasMany
    {
        return $this->units()->where('is_active', true);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class)->orderBy('sort_order');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->orderBy('sort_order');
    }

    public function seasonalRates(): HasMany
    {
        return $this->hasMany(SeasonalRate::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }

    /** Upper bound on units one booking may take, whatever the group size. */
    public const MAX_ROOMS_PER_BOOKING = 10;

    /** Whether $rooms units of this type can sleep the group (every unit needs an adult). */
    public function canSleep(int $adults, int $children, int $rooms = 1): bool
    {
        return $rooms >= 1
            && $rooms <= min($adults, self::MAX_ROOMS_PER_BOOKING)
            && $adults <= $rooms * $this->max_adults
            && $children <= $rooms * $this->max_children
            && $adults + $children <= $rooms * $this->max_guests;
    }

    /** Fewest units of this type that sleep the group, or null if it can't be done. */
    public function roomsNeeded(int $adults, int $children): ?int
    {
        for ($rooms = 1; $rooms <= min($adults, self::MAX_ROOMS_PER_BOOKING); $rooms++) {
            if ($this->canSleep($adults, $children, $rooms)) {
                return $rooms;
            }
        }

        return null;
    }

    /**
     * Spread the group over $rooms units: adults evenly, then each child into
     * the unit with the most space left. Always fits when canSleep() is true.
     *
     * @return array<int, array{adults:int, children:int}>
     */
    public function splitParty(int $adults, int $children, int $rooms): array
    {
        $rooms = max(1, $rooms);
        $split = [];
        for ($i = 0; $i < $rooms; $i++) {
            $split[$i] = ['adults' => intdiv($adults, $rooms) + ($i < $adults % $rooms ? 1 : 0), 'children' => 0];
        }

        $space = fn (array $r) => min($this->max_children - $r['children'], $this->max_guests - $r['adults'] - $r['children']);
        for ($c = 0; $c < $children; $c++) {
            $best = 0;
            foreach ($split as $i => $r) {
                if ($space($r) > $space($split[$best])) {
                    $best = $i;
                }
            }
            $split[$best]['children']++;
        }

        return $split;
    }

    /** Highlights stored one-per-line → array for the current locale. */
    public function highlightList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->highlights))));
    }

    public function coverUrl(): string
    {
        return media_url($this->cover_image) ?? asset('images/scenes/chalets-dusk.svg');
    }

    /** Cheapest price a guest could see (base or lowest active fixed season). */
    public function fromPrice(): float
    {
        return (float) $this->base_price;
    }
}
