<?php

namespace App\Models\Concerns;

use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Accommodation & Experience: approved guest reviews and their star rating. */
trait HasReviews
{
    abstract protected function reviewKey(): string; // accommodation_id | experience_id

    /** Approved reviews about this record, newest first. */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, $this->reviewKey())
            ->where('is_approved', true)
            ->when($this->reviewKey() === 'accommodation_id', fn ($q) => $q->whereNull('experience_id'))
            ->latest();
    }

    /** @return array{average: float, count: int} */
    public function rating(): array
    {
        return Review::ratingsBy($this->reviewKey())[$this->getKey()] ?? ['average' => 0.0, 'count' => 0];
    }
}
