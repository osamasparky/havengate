{{-- Review form. Expects $form from ReviewController::formContext(). --}}
@php
    $booking = $form['booking'];
    $requireBooking = $form['requireBooking'];
    $subject = $form['experience'] ?? $form['stay'];
@endphp
<div class="panel @container p-7 md:p-8" @if ($errors->any()) x-data x-init="$el.scrollIntoView({ block: 'start' })" @endif>
    <h2 class="t-h3">{{ $subject ? __('site.reviews.write_about', ['name' => $subject->name]) : __('site.reviews.write') }}</h2>

    @if ($form['blocked'])
        <p class="mt-4 text-ink-600">{{ $form['blocked'] }}</p>
    @else
        <p class="mt-2 text-sm text-ink-600">
            @if ($booking) {{ __('site.reviews.for_booking', ['ref' => $booking->reference]) }}
            @elseif ($requireBooking) {{ __('site.reviews.require_note') }}
            @else {{ __('site.reviews.open_note') }} @endif
        </p>

        <form method="POST" action="{{ lroute('reviews.store') }}" class="mt-6 grid gap-5"
              x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }">
            @csrf
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            @if ($form['experience'])<input type="hidden" name="experience_id" value="{{ $form['experience']->id }}">@endif
            @if ($form['stay'])<input type="hidden" name="accommodation_id" value="{{ $form['stay']->id }}">@endif

            <fieldset>
                <legend class="field-label">{{ __('site.reviews.rating') }}</legend>
                <div class="flex gap-1" @mouseleave="hover = 0">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer" @mouseenter="hover = {{ $i }}">
                            <input type="radio" name="rating" value="{{ $i }}" class="peer sr-only" x-model.number="rating" required @checked(old('rating') == $i)>
                            <svg viewBox="0 0 24 24" class="size-9 text-copper-500 transition peer-focus-visible:scale-110" aria-hidden="true">
                                <path d="M12 2.8l2.83 5.73 6.32.92-4.57 4.46 1.08 6.3L12 17.24l-5.66 2.97 1.08-6.3L2.85 9.45l6.32-.92z"
                                      fill="currentColor" :fill-opacity="(hover || rating) >= {{ $i }} ? 1 : .2"/>
                            </svg>
                            <span class="sr-only">{{ __('site.reviews.stars_label', ['n' => $i]) }}</span>
                        </label>
                    @endfor
                </div>
                @error('rating')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>

            @if ($booking)
                <input type="hidden" name="reference" value="{{ $booking->reference }}">
                <input type="hidden" name="token" value="{{ $form['token'] }}">
            @endif

            <div class="grid gap-5 @md:grid-cols-2">
                <div>
                    <label for="r-name" class="field-label">{{ __('site.contact.name') }}</label>
                    <input id="r-name" name="name" value="{{ old('name', $booking?->guest?->full_name) }}" class="field" required maxlength="120">
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="r-email" class="field-label">{{ __('site.contact.email') }}</label>
                    <input id="r-email" name="email" type="email" value="{{ old('email', $booking?->guest?->email) }}" class="field" required>
                    <p class="mt-1 text-xs text-ink-600">{{ __('site.reviews.email_private') }}</p>
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            @unless ($booking)
                <div>
                    <label for="r-reference" class="field-label">{{ __('booking.reference') }}@unless ($requireBooking) <span class="font-normal text-ink-600">({{ __('site.reviews.optional') }})</span>@endunless</label>
                    <input id="r-reference" name="reference" value="{{ old('reference') }}" class="field font-mono uppercase" dir="ltr" placeholder="HG-XXXXXX" maxlength="16" @required($requireBooking)>
                    @error('reference')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            @else
                @error('reference')<p class="field-error">{{ $message }}</p>@enderror
            @endunless

            <div class="grid gap-5 @md:grid-cols-2">
                <div>
                    <label for="r-country" class="field-label">{{ __('site.reviews.country') }} <span class="font-normal text-ink-600">({{ __('site.reviews.optional') }})</span></label>
                    <input id="r-country" name="country" value="{{ old('country') }}" class="field" maxlength="80">
                </div>
                <div>
                    <label for="r-title" class="field-label">{{ __('site.reviews.headline') }} <span class="font-normal text-ink-600">({{ __('site.reviews.optional') }})</span></label>
                    <input id="r-title" name="title" value="{{ old('title') }}" class="field" maxlength="160">
                </div>
            </div>
            <div>
                <label for="r-comment" class="field-label">{{ __('site.reviews.comment') }}</label>
                <textarea id="r-comment" name="comment" rows="5" class="field" required minlength="10" maxlength="3000">{{ old('comment') }}</textarea>
                @error('comment')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div><button class="btn btn-primary">{{ __('site.reviews.submit') }} <x-icon name="arrow" class="size-4 rtl:-scale-x-100"/></button></div>
        </form>
    @endif
</div>
