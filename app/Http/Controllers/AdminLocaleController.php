<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetAdminLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, SetAdminLocale::LOCALES), 404);

        $request->session()->put('admin_locale', $locale);
        $request->user()?->update(['locale' => $locale]);

        return redirect()->back(fallback: url('admin'));
    }
}
