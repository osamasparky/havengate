<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Services\BookingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ManageBookingController extends Controller
{
    public function form()
    {
        return view('pages.booking.manage-form');
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'reference' => 'required|string|max:16',
            'email' => 'required|email',
        ]);

        $booking = Booking::where('reference', strtoupper(trim($data['reference'])))
            ->whereHas('guest', fn ($q) => $q->where('email', strtolower(trim($data['email']))))
            ->first();

        if (! $booking) {
            return back()->withInput()->withErrors(['reference' => __('booking.manage.not_found')]);
        }

        return redirect()->route('booking.manage.show', ['booking' => $booking->reference, 'token' => $booking->manage_token]);
    }

    public function show(Request $request, string $locale, Booking $booking, BookingService $bookings)
    {
        $this->authorizeToken($request, $booking);
        $booking->load('guest', 'units.unit', 'units.accommodation', 'extras', 'payments');

        return view('pages.booking.manage', [
            'booking' => $booking,
            'cancellation' => $bookings->cancellationQuote($booking),
        ]);
    }

    public function cancel(Request $request, string $locale, Booking $booking, BookingService $bookings)
    {
        $this->authorizeToken($request, $booking);
        $data = $request->validate(['reason' => 'nullable|string|max:500', 'confirm' => 'accepted']);

        abort_unless($bookings->cancellationQuote($booking)['guest_can_cancel'], 403);

        try {
            $bookings->cancel($booking, $data['reason'] ?? null, byGuest: true);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('booking.manage.show', ['booking' => $booking->reference, 'token' => $booking->manage_token])
            ->with('status', __('booking.manage.cancelled'));
    }

    public function receipt(Request $request, string $locale, Booking $booking)
    {
        $this->authorizeToken($request, $booking);
        $booking->load('guest', 'units.unit', 'units.accommodation', 'extras', 'payments');

        // DomPDF can't shape Arabic script; receipts are issued in English.
        // Swap to mPDF (carlos-meneses/laravel-mpdf) if Arabic/Hebrew receipts are required.
        app()->setLocale('en');

        return Pdf::loadView('pdf.receipt', ['booking' => $booking])
            ->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans'])
            ->download("heaven-gate-{$booking->reference}.pdf");
    }

    private function authorizeToken(Request $request, Booking $booking): void
    {
        abort_unless(hash_equals($booking->manage_token, (string) $request->query('token', $request->input('token'))), 403);
    }
}
