@extends('layouts.site')
@section('title', __('booking.manage.title').' · '.$booking->reference)
@php
    $statusColor = match ($booking->status->value) {
        'confirmed', 'checked_in' => 'border-sage-600/30 bg-sage-600/10 text-sage-600',
        'pending' => 'border-ember-500/30 bg-ember-500/10 text-ember-500',
        'cancelled', 'expired', 'no_show' => 'border-rock-600/30 bg-rock-600/10 text-rock-600',
        default => 'border-sand-300 bg-sand-100 text-ink-600',
    };
    $whatsapp = preg_replace('/\D/', '', (string) setting('contact_whatsapp'));
    $lat = config('heavengate.coordinates.lat'); $lng = config('heavengate.coordinates.lng');
@endphp

@section('content')
<section class="py-14 md:py-20">
    <div class="container-hg grid gap-10 lg:grid-cols-[1fr_380px] lg:gap-14">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-semibold {{ $statusColor }}">{{ $booking->status->getLabel() }} · {{ $booking->payment_status->getLabel() }}</span>
            <h1 class="t-h1 mt-6">{{ __('booking.manage.hello', ['name' => $booking->guest->first_name]) }}</h1>
            @if ($booking->status->value === 'confirmed')
                <p class="lede mt-4">{{ __('mail.confirmed.body') }}</p>
            @endif

            @if ($booking->status->value === 'pending')
                <div class="panel mt-10 p-7">
                    <h2 class="t-h3">{{ __('booking.offline.instructions') }}</h2>
                    <p class="mt-3 text-ink-600">{{ setting('offline_payment_instructions') }}</p>
                    <p class="mt-4 font-semibold">{{ __('booking.due_now') }}: {{ money((float) $booking->amount_due_now - (float) $booking->amount_paid) }} · {{ __('booking.reference') }} <span class="font-mono" dir="ltr">{{ $booking->reference }}</span></p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        @if ($whatsapp)<a href="https://wa.me/{{ $whatsapp }}?text={{ urlencode($booking->reference) }}" target="_blank" rel="noopener" class="btn btn-dark btn-sm"><x-icon name="whatsapp" class="size-4"/> {{ __('site.contact.whatsapp') }}</a>@endif
                        @if ($booking->expires_at && $booking->expires_at->isFuture())
                            <a href="{{ lroute('booking.checkout', ['booking' => $booking->reference, 'token' => $booking->manage_token]) }}" class="btn btn-secondary btn-sm">{{ __('booking.checkout.online') }}</a>
                        @endif
                    </div>
                </div>
            @endif

            <div class="mt-10 grid gap-4 sm:grid-cols-2">
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $lat }},{{ $lng }}" target="_blank" rel="noopener" class="panel flex items-center gap-4 p-5 hover:border-copper-500"><x-icon name="pin" class="size-6 text-copper-500"/> {{ __('booking.manage.directions') }}</a>
                <a href="{{ lroute('booking.manage.receipt', ['booking' => $booking->reference, 'token' => $booking->manage_token]) }}" class="panel flex items-center gap-4 p-5 hover:border-copper-500"><x-icon name="download" class="size-6 text-copper-500"/> {{ __('booking.manage.receipt') }}</a>
                @if (setting('reviews_enabled', true) && \App\Models\Review::bookingCanReview($booking))
                    <a href="{{ lroute('reviews', ['ref' => $booking->reference, 'token' => $booking->manage_token]) }}#write" class="panel flex items-center gap-4 p-5 hover:border-copper-500 sm:col-span-2">
                        <x-stars rating="5" class="size-5"/> {{ __('site.reviews.write_stay') }}
                    </a>
                @endif
            </div>

            @if ($booking->payments->isNotEmpty())
                <h2 class="t-h3 mt-14">{{ __('booking.manage.payments') }}</h2>
                <ul class="mt-4 divide-y divide-sand-200 border-y border-sand-200 text-sm">
                    @foreach ($booking->payments as $p)
                        <li class="flex items-center justify-between py-3"><span>{{ $p->created_at->translatedFormat('j M Y H:i') }} · {{ $p->provider->getLabel() }}@if($p->method) · {{ $p->method }}@endif</span><span class="flex items-center gap-3"><span class="chip">{{ $p->status->getLabel() }}</span> {{ money($p->amount) }}</span></li>
                    @endforeach
                </ul>
            @endif

            <p class="mt-14 text-ink-600">{{ __('booking.manage.contact_change') }} <a href="{{ lroute('contact') }}" class="link">{{ __('site.nav.contact') }}</a></p>

            @if ($cancellation['guest_can_cancel'])
                <details class="panel mt-10 p-7" @error('confirm') open @enderror>
                    <summary class="cursor-pointer font-display text-2xl">{{ __('booking.manage.cancel_title') }}</summary>
                    <p class="mt-4 text-ink-600">
                        @if ($cancellation['tier'] === 'free') {{ __('booking.manage.cancel_free', ['amount' => money($cancellation['refund'])]) }}
                        @elseif ($cancellation['tier'] === 'late') {{ __('booking.manage.cancel_partial', ['percent' => $cancellation['percent'], 'amount' => money($cancellation['refund'])]) }}
                        @else {{ __('booking.manage.cancel_none') }} @endif
                    </p>
                    <form method="POST" action="{{ lroute('booking.manage.cancel', ['booking' => $booking->reference]) }}" class="mt-6 space-y-4">
                        @csrf
                        <input type="hidden" name="token" value="{{ $booking->manage_token }}">
                        <div><label for="reason" class="field-label">{{ __('booking.manage.cancel_reason') }}</label><textarea id="reason" name="reason" rows="2" class="field"></textarea></div>
                        <label class="flex items-start gap-3"><input type="checkbox" name="confirm" value="1" class="checkbox mt-0.5" required> <span class="text-sm">{{ __('booking.manage.cancel_confirm') }}</span></label>
                        @error('confirm')<p class="field-error">{{ $message }}</p>@enderror
                        <button class="btn btn-secondary !border-rock-600/40 !text-rock-600">{{ __('booking.manage.cancel_btn') }}</button>
                    </form>
                </details>
            @endif
        </div>
        <aside><div class="sticky top-28">@include('partials.booking-summary')</div></aside>
    </div>
</section>
@endsection
