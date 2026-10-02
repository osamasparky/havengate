<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\HasTranslations;

class ContentBlock extends Model
{
    use HasTranslations;

    public array $translatable = ['eyebrow', 'title', 'body', 'cta_label'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('content_blocks'));
        static::deleted(fn () => Cache::forget('content_blocks'));
    }

    /** All active blocks keyed by `key`, cached. */
    public static function map(): \Illuminate\Support\Collection
    {
        return Cache::rememberForever('content_blocks', fn () => static::where('is_active', true)->get()->keyBy('key'));
    }

    public static function block(string $key): ?self
    {
        return static::map()->get($key);
    }
}
