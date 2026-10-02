<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admin panel language: the staff member's saved choice, else the session, else English. */
class SetAdminLocale
{
    public const LOCALES = ['en' => 'English', 'ar' => 'العربية'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? $request->session()->get('admin_locale');
        if (! array_key_exists((string) $locale, self::LOCALES)) {
            $locale = 'en';
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        // Content editing (EN/AR/HE) opens in the admin's own language first.
        $plugin = Filament::getCurrentPanel()?->hasPlugin('spatie-laravel-translatable')
            ? Filament::getCurrentPanel()->getPlugin('spatie-laravel-translatable')
            : null;
        if ($plugin) {
            $locales = array_keys(config('heavengate.locales'));
            $plugin->defaultLocales(array_values(array_unique([$locale, ...$locales])));
        }

        return $next($request);
    }
}
