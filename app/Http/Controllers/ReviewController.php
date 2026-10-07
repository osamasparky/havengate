<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Experience;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(setting('reviews_enabled', true), 404);

        // ?stay=slug / ?experience=slug narrows the list to one subject ("Read all" on detail pages).
        $experience = $request->filled('experience') ? Experience::active()->where('slug', $request->query('experience'))->first() : null;
        $stay = ! $experience && $request->filled('stay') ? Accommodation::active()->where('slug', $request->query('stay'))->first() : null;
        $scope = fn ($q) => $q
            ->when($experience, fn ($q) => $q->where('experience_id', $experience->id))
            ->when($stay, fn ($q) => $q->where('accommodation_id', $stay->id)->whereNull('experience_id'));

        return view('pages.reviews', [
            'reviews' => Review::published()->with('accommodation', 'experience')->tap($scope)->paginate(12)->withQueryString(),
            'summary' => Review::summary($scope),
            'subject' => $experience ?? $stay,
            'form' => static::formContext($request, $stay, $experience),
        ]);
    }

    /**
     * State for the review form partial. Arriving from the manage-booking page,
     * ?ref=&token= pre-fills the guest and the reservation.
     */
    public static function formContext(Request $request, ?Accommodation $stay = null, ?Experience $experience = null): array
    {
        $requireBooking = (bool) setting('reviews_require_booking', true);
        $booking = static::bookingFromToken($request->query('ref'), $request->query('token'));

        $blocked = null;
        if ($booking) {
            if (Review::alreadyReviewed($booking, $experience?->id)) {
                $blocked = __('site.reviews.already');
            } elseif ($requireBooking && ! in_array($booking->status, Review::eligibleStatuses(), true)) {
                $blocked = __('site.reviews.not_confirmed');
            } elseif ($stay && ! static::bookingIncludesStay($booking, $stay)) {
                $booking = null; // a different stay: let them type a matching reference instead
            }
        }

        return [
            'enabled' => (bool) setting('reviews_enabled', true),
            'requireBooking' => $requireBooking,
            'booking' => $booking,
            'token' => $booking ? $request->query('token') : null,
            'blocked' => $blocked,
            'stay' => $stay,
            'experience' => $experience,
        ];
    }

    public function store(Request $request)
    {
        abort_unless(setting('reviews_enabled', true), 404);

        // Honeypot: bots fill every field.
        if ($request->filled('website')) {
            return $this->back(__('site.reviews.thanks_pending'));
        }

        $requireBooking = (bool) setting('reviews_require_booking', true);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email:rfc|max:180',
            'country' => 'nullable|string|max:80',
            'rating' => 'required|integer|between:1,5',
            'title' => 'nullable|string|max:160',
            'comment' => 'required|string|min:10|max:3000',
            'reference' => ($requireBooking ? 'required' : 'nullable').'|string|max:16',
            'token' => 'nullable|string|max:64',
            'accommodation_id' => 'nullable|integer|exists:accommodations,id',
            'experience_id' => 'nullable|integer|exists:experiences,id',
        ]);

        $experience = filled($data['experience_id'] ?? null) ? Experience::active()->findOrFail($data['experience_id']) : null;
        $stay = ! $experience && filled($data['accommodation_id'] ?? null) ? Accommodation::active()->findOrFail($data['accommodation_id']) : null;

        $booking = null;
        if (filled($data['reference'] ?? null)) {
            $reference = strtoupper(trim($data['reference']));
            $booking = static::bookingFromToken($reference, $data['token'] ?? null)
                ?? Booking::where('reference', $reference)
                    ->whereHas('guest', fn ($q) => $q->where('email', strtolower(trim($data['email']))))
                    ->first();

            if (! $booking) {
                return back()->withInput()->withErrors(['reference' => __('site.reviews.not_found')]);
            }
            if (Review::alreadyReviewed($booking, $experience?->id)) {
                return back()->withInput()->withErrors(['reference' => __('site.reviews.already')]);
            }
            if ($stay && ! static::bookingIncludesStay($booking, $stay)) {
                return back()->withInput()->withErrors(['reference' => __('site.reviews.other_stay', ['stay' => $stay->name])]);
            }
            if (! in_array($booking->status, Review::eligibleStatuses(), true)) {
                // Only fatal when the setting demands a confirmed stay; otherwise post as unverified.
                if ($requireBooking) {
                    return back()->withInput()->withErrors(['reference' => __('site.reviews.not_confirmed')]);
                }
                $booking = null;
            }
        }

        $autoApprove = (bool) setting('reviews_auto_approve', false);

        $review = Review::create([
            'booking_id' => $booking?->id,
            'guest_id' => $booking?->guest_id,
            // Experience reviews stand alone so they don't move the stay's rating.
            'accommodation_id' => $experience ? null : ($stay?->id ?? $booking?->units()->value('accommodation_id')),
            'experience_id' => $experience?->id,
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'country' => $data['country'] ?? null,
            'rating' => (int) $data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'],
            'locale' => app()->getLocale(),
            'is_verified' => (bool) $booking,
            'is_approved' => $autoApprove,
        ]);

        $about = $review->subjectName();
        $booking?->log('review', "Guest left a {$review->rating}★ review".($about ? " of {$about}" : ''));

        if ($to = setting('notification_email', config('heavengate.admin_email'))) {
            try {
                Mail::raw("{$review->rating}/5 from {$review->name} <{$review->email}>".($booking ? " · {$booking->reference}" : '')
                    .($about ? "\nAbout: {$about}" : '')
                    ."\n\n{$review->title}\n{$review->comment}\n\n".url('admin/reviews'),
                    fn ($m) => $m->to($to)->replyTo($review->email, $review->name)->subject("New {$review->rating}★ review: {$review->name}"));
            } catch (\Throwable $e) {
                Log::error('Review mail failed', ['e' => $e->getMessage()]);
            }
        }

        return $this->back(__($autoApprove ? 'site.reviews.thanks' : 'site.reviews.thanks_pending'));
    }

    /** Back to the page the form was on (reviews page, stay or experience), at the reviews section. */
    private function back(string $status)
    {
        $previous = strtok(url()->previous(), '#');

        return redirect()->to($previous.'#reviews')->with('status', $status);
    }

    private static function bookingIncludesStay(Booking $booking, Accommodation $stay): bool
    {
        return $booking->units()->where('accommodation_id', $stay->id)->exists();
    }

    private static function bookingFromToken(?string $reference, ?string $token): ?Booking
    {
        if (! $reference || ! $token) {
            return null;
        }
        $booking = Booking::with('guest')->where('reference', strtoupper(trim($reference)))->first();

        return $booking && hash_equals($booking->manage_token, $token) ? $booking : null;
    }
}
