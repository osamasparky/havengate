<?php

use App\Http\Controllers\AdminLocaleController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\ManageBookingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StayController;
use App\Livewire\BookingWizard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Root: send visitors to their language (session → browser → default).
*/
Route::get('/', function (Request $request) {
    $supported = array_keys(config('heavengate.locales'));
    $locale = $request->session()->get('locale')
        ?? $request->getPreferredLanguage($supported)
        ?? config('heavengate.default_locale');

    return redirect("/{$locale}");
})->name('root');

// Server-to-server, signed by HMAC (CSRF-exempt in bootstrap/app.php).
Route::post('payments/easykash/callback', [PaymentController::class, 'easykashCallback'])
    ->middleware('throttle:120,1')
    ->name('payments.easykash.callback');

// Admin panel language switch (English / Arabic).
Route::get('admin/locale/{locale}', AdminLocaleController::class)->name('admin.locale');

Route::get('sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', array_keys(config('heavengate.locales')))])
    ->middleware('locale')
    ->group(function () {
        Route::get('/', [SiteController::class, 'home'])->name('home');
        Route::get('camp', [SiteController::class, 'camp'])->name('camp');
        Route::get('gallery', [SiteController::class, 'gallery'])->name('gallery');
        Route::get('location', [SiteController::class, 'location'])->name('location');

        Route::get('stay', [StayController::class, 'index'])->name('stays.index');
        Route::get('stay/{accommodation:slug}', [StayController::class, 'show'])->name('stays.show');

        Route::get('experiences', [ExperienceController::class, 'index'])->name('experiences.index');
        Route::get('experiences/{experience:slug}', [ExperienceController::class, 'show'])->name('experiences.show');

        Route::get('contact', [ContactController::class, 'show'])->name('contact');
        Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,10')->name('contact.store');

        // Booking flow
        Route::get('book', BookingWizard::class)->name('book');
        Route::get('booking/{booking:reference}/checkout', [BookingController::class, 'checkout'])->name('booking.checkout');
        Route::post('booking/{booking:reference}/pay/online', [BookingController::class, 'payOnline'])->middleware('throttle:10,1')->name('booking.pay.online');
        Route::post('booking/{booking:reference}/pay/offline', [BookingController::class, 'payOffline'])->middleware('throttle:10,1')->name('booking.pay.offline');
        Route::get('booking/{booking:reference}/status', [BookingController::class, 'status'])->name('booking.status');
        Route::get('payments/easykash/return/{payment:reference}', [PaymentController::class, 'easykashReturn'])->name('payments.easykash.return');

        // Manage booking (reference + email, or the private link from the email)
        Route::get('manage', [ManageBookingController::class, 'form'])->name('booking.manage');
        Route::post('manage', [ManageBookingController::class, 'lookup'])->middleware('throttle:8,1')->name('booking.manage.lookup');
        Route::get('manage/{booking:reference}', [ManageBookingController::class, 'show'])->name('booking.manage.show');
        Route::post('manage/{booking:reference}/cancel', [ManageBookingController::class, 'cancel'])->middleware('throttle:5,1')->name('booking.manage.cancel');
        Route::get('manage/{booking:reference}/receipt', [ManageBookingController::class, 'receipt'])->name('booking.manage.receipt');

        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews');
        Route::post('reviews', [ReviewController::class, 'store'])->middleware('throttle:5,10')->name('reviews.store');

        Route::get('p/{page:slug}', [PageController::class, 'show'])->name('page');
    });
