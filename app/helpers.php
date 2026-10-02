<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /**
     * Read a setting. Translatable values ({"en":..,"ar":..}) resolve to the
     * current locale with English fallback.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        try {
            $value = Setting::all_cached()[$key] ?? $default;
        } catch (\Throwable) {
            return $default; // before migrations
        }

        if (is_array($value) && array_key_exists('en', $value) && count(array_diff(array_keys($value), array_keys(config('heavengate.locales')))) === 0) {
            return $value[app()->getLocale()] ?? $value['en'] ?? $default;
        }

        return $value;
    }
}

if (! function_exists('media_url')) {
    /** Public URL for an uploaded file or a bundled asset path ("images/..."). */
    function media_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }
        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('money')) {
    /** "EGP 3,200" — Western digits in every locale (clear for mixed audiences). */
    function money(float|int|string|null $amount, ?string $currency = null): string
    {
        $currency ??= config('heavengate.currency');
        $amount = (float) $amount;
        $formatted = number_format($amount, fmod($amount, 1.0) == 0.0 ? 0 : 2);
        $label = __('site.currency.'.$currency);

        return app()->getLocale() === 'en' ? "{$label} {$formatted}" : "{$formatted} {$label}";
    }
}

if (! function_exists('locale_dir')) {
    function locale_dir(?string $locale = null): string
    {
        return config('heavengate.locales.'.($locale ?? app()->getLocale()).'.dir', 'ltr');
    }
}

if (! function_exists('lroute')) {
    /** route() that injects the current locale prefix. */
    function lroute(string $name, array $params = [], bool $absolute = true): string
    {
        return route($name, ['locale' => app()->getLocale()] + $params, $absolute);
    }
}

if (! function_exists('switch_locale_url')) {
    /** Same page in another locale. */
    function switch_locale_url(string $locale): string
    {
        $route = request()->route();
        if (! $route || ! $route->getName() || ! array_key_exists('locale', $route->parameters())) {
            return url($locale);
        }

        $params = array_merge($route->parameters(), ['locale' => $locale]);
        $params = array_map(fn ($p) => $p instanceof \Illuminate\Database\Eloquent\Model ? $p->getRouteKey() : $p, $params);

        return route($route->getName(), $params + request()->query());
    }
}

if (! function_exists('hg_emphasis')) {
    /** Escape text and turn *word* into <em>word</em> (editorial italic emphasis from the CMS). */
    function hg_emphasis(?string $text): string
    {
        return preg_replace('/\*(.+?)\*/u', '<em>$1</em>', e((string) $text));
    }
}

if (! function_exists('party_label')) {
    /** "2 adults · 1 child" in the current locale. */
    function party_label(int $adults, int $children = 0): string
    {
        $label = trans_choice('booking.adults_count', $adults, ['count' => $adults]);

        return $children ? $label.' · '.trans_choice('booking.children_count', $children, ['count' => $children]) : $label;
    }
}

if (! function_exists('__label')) {
    /** __() for UI labels that may collide with a lang file name ("booking" → lang/booking.php array). */
    function __label(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return $key;
        }
        $line = __($key);

        return is_string($line) ? $line : $key;
    }
}
