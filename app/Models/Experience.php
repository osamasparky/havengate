<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Translatable\HasTranslations;

class Experience extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'summary', 'description', 'schedule'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_addon' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->orderBy('sort_order');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }

    public function isPerPerson(): bool
    {
        return $this->pricing_unit === 'per_person';
    }

    public function imageUrl(): string
    {
        return media_url($this->image) ?? asset('images/scenes/sunset-gulf.svg');
    }

    public function durationLabel(): ?string
    {
        if (! $this->duration_minutes) {
            return null;
        }
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;

        return trim(($h ? __('site.hours', ['n' => $h]) : '').' '.($m ? __('site.minutes', ['n' => $m]) : ''));
    }
}
