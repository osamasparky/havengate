<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Services\PricingService;
use Illuminate\Http\Request;

class StayController extends Controller
{
    public function index(PricingService $pricing)
    {
        $stays = Accommodation::active()->with('facilities')->get();

        return view('pages.stays.index', ['stays' => $stays, 'pricing' => $pricing]);
    }

    public function show(Request $request, string $locale, Accommodation $accommodation, PricingService $pricing)
    {
        abort_unless($accommodation->is_active, 404);
        $accommodation->load('facilities', 'photos');

        return view('pages.stays.show', [
            'stay' => $accommodation,
            'fromPrice' => $pricing->fromPrice($accommodation),
            'others' => Accommodation::active()->whereKeyNot($accommodation->id)->take(2)->get(),
            'reviews' => $accommodation->reviews()->take(6)->get(),
            'rating' => $accommodation->rating(),
            'form' => ReviewController::formContext($request, stay: $accommodation),
        ]);
    }
}
