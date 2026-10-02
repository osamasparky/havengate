<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Accommodation;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Support\StayRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AvailabilityController extends Controller
{
    public function camp(Request $request, AvailabilityService $availability, PricingService $pricing): JsonResponse
    {
        [$from, $to] = $this->range($request);
        $key = "cal:camp:{$from->toDateString()}:{$to->toDateString()}";
        $data = Cache::remember($key, 60, fn () => $availability->campCalendar($from, $to, $pricing));

        return response()->json(['currency' => config('heavengate.currency'), 'days' => $data]);
    }

    public function accommodation(Request $request, Accommodation $accommodation, AvailabilityService $availability, PricingService $pricing): JsonResponse
    {
        abort_unless($accommodation->is_active, 404);
        [$from, $to] = $this->range($request);

        $cal = $availability->calendar($accommodation, $from, $to);
        $rates = collect($pricing->nightlyRates($accommodation, new StayRequest($from, $to, 1)))->pluck('rate', 'date');
        $days = [];
        foreach ($cal as $date => $free) {
            $days[$date] = ['free' => $free, 'from' => $free > 0 ? (float) $rates[$date] : null];
        }

        return response()->json([
            'currency' => config('heavengate.currency'),
            'min_nights' => $accommodation->min_nights,
            'days' => $days,
        ]);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} max 3 months per request */
    private function range(Request $request): array
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);
        $from = CarbonImmutable::parse($request->input('from', today()))->startOfDay();
        $from = $from->lt(today()) ? CarbonImmutable::today() : $from;
        $to = CarbonImmutable::parse($request->input('to', $from->addMonths(2)))->startOfDay();
        if ($to->lte($from) || $from->diffInDays($to) > 93) {
            $to = $from->addDays(62);
        }

        return [$from, $to];
    }
}
