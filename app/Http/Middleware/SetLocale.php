<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = array_keys(config('heavengate.locales'));
        $locale = $request->route('locale');

        if (! in_array($locale, $locales, true)) {
            abort(404);
        }

        app()->setLocale($locale);
        Carbon::setLocale(config("heavengate.locales.$locale.carbon", $locale));
        URL::defaults(['locale' => $locale]);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
