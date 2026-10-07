<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function checkout(Request $request, string $locale, Booking $booking, PaymentManager $payments)
    {
        $this->authorizeToken($request, $booking);

        if ($booking->status !== BookingStatus::Pending) {
            return redirect()->route('booking.manage.show', ['booking' => $booking->reference, 'token' => $booking->manage_token]);
        }
        if ($booking->isHoldExpired()) {
            return redirect()->route('book')->with('error', __('booking.errors.hold_expired'));
        }

        $booking->load('guest', 'units.unit', 'units.accommodation', 'extras');

        return view('pages.booking.checkout', [
            'booking' => $booking,
            'onlineEnabled' => $payments->onlineEnabled(),
            'offlineEnabled' => $payments->offlineEnabled(),
            'atPropertyEnabled' => $payments->atPropertyEnabled(),
        ]);
    }

    public function payOnline(Request $request, string $locale, Booking $booking, PaymentManager $payments)
    {
        $this->authorizeToken($request, $booking);
        $request->validate(['accept_terms' => 'accepted']);

        try {
            return redirect()->away($payments->startOnline($booking));
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Online payment start failed', ['booking' => $booking->reference, 'e' => $e->getMessage()]);

            return back()->with('error', __('booking.errors.gateway'));
        }
    }

    public function payOffline(Request $request, string $locale, Booking $booking, BookingService $bookings, PaymentManager $payments)
    {
        $this->authorizeToken($request, $booking);
        $request->validate(['accept_terms' => 'accepted']);
        abort_unless($payments->offlineEnabled() && $booking->status === BookingStatus::Pending && ! $booking->isHoldExpired(), 422);

        $bookings->awaitOfflinePayment($booking);

        return redirect()->route('booking.manage.show', ['booking' => $booking->reference, 'token' => $booking->manage_token])
            ->with('status', __('booking.offline.received'));
    }

    public function payAtProperty(Request $request, string $locale, Booking $booking, BookingService $bookings, PaymentManager $payments)
    {
        $this->authorizeToken($request, $booking);
        $request->validate(['accept_terms' => 'accepted']);
        abort_unless($payments->atPropertyEnabled() && $booking->status === BookingStatus::Pending && ! $booking->isHoldExpired(), 422);

        $bookings->reserveAtProperty($booking);

        return redirect()->route('booking.manage.show', ['booking' => $booking->reference, 'token' => $booking->manage_token])
            ->with('status', __($booking->status === BookingStatus::Confirmed ? 'booking.at_property.confirmed' : 'booking.at_property.requested'));
    }

    /** Polled by the return page while waiting for the gateway callback. */
    public function status(Request $request, string $locale, Booking $booking): JsonResponse
    {
        $this->authorizeToken($request, $booking);

        return response()->json([
            'status' => $booking->status->value,
            'payment_status' => $booking->payment_status->value,
            'redirect' => $booking->status === BookingStatus::Confirmed
                ? route('booking.manage.show', ['booking' => $booking->reference, 'token' => $booking->manage_token])
                : null,
        ]);
    }

    private function authorizeToken(Request $request, Booking $booking): void
    {
        abort_unless(hash_equals($booking->manage_token, (string) $request->query('token', $request->input('token'))), 403);
    }
}
