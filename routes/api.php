<?php

use App\Http\Controllers\Api\AvailabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    // Night-by-night availability + nightly price for the date picker.
    Route::get('availability', [AvailabilityController::class, 'camp'])->name('api.availability.camp');
    Route::get('availability/{accommodation:slug}', [AvailabilityController::class, 'accommodation'])->name('api.availability.accommodation');
});
