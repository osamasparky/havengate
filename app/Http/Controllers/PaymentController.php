<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Guest lands here after EasyKash. This page never confirms anything by
     * itself; it waits (polling) for the verified server callback.
     */
    public function easykashReturn(Request $request, string $locale, Payment $payment)
    {
        $booking = $payment->booking()->with('guest')->firstOrFail();

        return view('pages.booking.return', [
            'booking' => $booking,
            'payment' => $payment,
            'gatewayStatus' => strtoupper((string) $request->query('status')),
            'voucher' => $request->query('voucher'),
        ]);
    }

    public function easykashCallback(Request $request, PaymentManager $payments)
    {
        $code = $payments->handleEasyKashCallback($request->all());

        return response()->json(['ok' => $code === 200], $code);
    }
}
