<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value settings. Use the `setting('key', $default)` helper.
 * Translatable values are stored as {"en": "...", "ar": "...", "he": "..."}.
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function all_cached(): array
    {
        return Cache::rememberForever('settings', fn () => static::pluck('value', 'key')->all());
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings');
    }

    public static function putMany(array $values): void
    {
        foreach ($values as $k => $v) {
            static::updateOrCreate(['key' => $k], ['value' => $v]);
        }
        Cache::forget('settings');
    }
}
