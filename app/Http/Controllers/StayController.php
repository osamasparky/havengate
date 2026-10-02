<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Services\PricingService;

class StayController extends Controller
{
    public function index(PricingService $pricing)
    {
        $stays = Accommodation::active()->with('facilities')->get();

        return view('pages.stays.index', ['stays' => $stays, 'pricing' => $pricing]);
    }

    public function show(string $locale, Accommodation $accommodation, PricingService $pricing)
    {
        abort_unless($accommodation->is_active, 404);
        $accommodation->load('facilities', 'photos');

        return view('pages.stays.show', [
            'stay' => $accommodation,
            'fromPrice' => $pricing->fromPrice($accommodation),
            'others' => Accommodation::active()->whereKeyNot($accommodation->id)->take(2)->get(),
        ]);
    }
}
