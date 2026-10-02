<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class Photo extends Model
{
    use HasTranslations;

    public array $translatable = ['caption', 'alt'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): string
    {
        return media_url($this->path) ?? asset('images/scenes/sunset-gulf.svg');
    }
}
