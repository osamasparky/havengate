{{-- Read-only summary of a stored booking. Expects $booking with units, extras loaded. --}}
@php $bu = $booking->units->first(); @endphp
<div class="panel overflow-hidden">
    @if ($bu)
        <div class="relative h-40 bg-sand-200"><img src="{{ $bu->accommodation->coverUrl() }}" alt="" class="size-full object-cover"><div class="absolute inset-0 bg-gradient-to-t from-night-900/60 to-transparent"></div><p class="absolute bottom-4 start-5 font-display text-2xl text-sand-50">{{ $bu->accommodation->name }}@if ($booking->roomCount() > 1)<span class="ms-2 align-middle font-sans text-sm font-semibold">× {{ trans_choice('booking.rooms_count', $booking->roomCount(), ['count' => $booking->roomCount()]) }}</span>@endif</p></div>
    @endif
    <div class="space-y-5 p-6 text-sm">
        <div class="flex items-center justify-between"><span class="eyebrow">{{ __('booking.reference') }}</span><span class="font-mono text-base font-semibold tracking-wider" dir="ltr">{{ $booking->reference }}</span></div>
        <dl class="grid grid-cols-2 gap-4">
            <div><dt class="text-ink-400">{{ __('booking.check_in') }}</dt><dd class="mt-0.5 font-semibold">{{ $booking->check_in->translatedFormat('D j M Y') }}</dd><dd class="text-xs text-ink-400">{{ setting('check_in_time') }}</dd></div>
            <div><dt class="text-ink-400">{{ __('booking.check_out') }}</dt><dd class="mt-0.5 font-semibold">{{ $booking->check_out->translatedFormat('D j M Y') }}</dd><dd class="text-xs text-ink-400">{{ setting('check_out_time') }}</dd></div>
            <div><dt class="text-ink-400">{{ __('site.search.guests') }}</dt><dd class="mt-0.5 font-semibold">{{ trans_choice('site.guests', $booking->guestCount(), ['count' => $booking->guestCount()]) }}</dd></div>
            <div><dt class="text-ink-400">{{ __('booking.nightly') }}</dt><dd class="mt-0.5 font-semibold">{{ trans_choice('site.nights', $booking->nights, ['count' => $booking->nights]) }}</dd></div>
        </dl>
        <div class="border-t border-sand-200 pt-4">
            <div class="flex justify-between py-1"><span class="text-ink-600">{{ __('booking.room') }}{{ $booking->roomCount() > 1 ? ' × '.$booking->roomCount() : '' }}</span><span>{{ money($booking->accommodation_total) }}</span></div>
            @foreach ($booking->extras as $x)
                <div class="flex justify-between py-1"><span class="text-ink-600">{{ $x->name }} × {{ $x->quantity }}</span><span>{{ money($x->total) }}</span></div>
            @endforeach
            @if ($booking->discount_total > 0)<div class="flex justify-between py-1 text-sage-600"><span>{{ __('booking.discount') }} @if($booking->promo_code)({{ $booking->promo_code }})@endif</span><span>− {{ money($booking->discount_total) }}</span></div>@endif
            @if ($booking->tax_total > 0)<div class="flex justify-between py-1"><span class="text-ink-600">{{ __('booking.service_charge') }} / {{ __('booking.vat') }}</span><span>{{ money($booking->tax_total) }}</span></div>@endif
            <div class="mt-3 flex items-end justify-between border-t border-sand-200 pt-4"><span class="font-semibold">{{ __('booking.total') }}</span><span class="font-display text-3xl">{{ money($booking->total) }}</span></div>
            @if ((float) $booking->amount_paid > 0)
                <div class="mt-2 flex justify-between text-sage-600"><span>{{ __('booking.manage.paid') }}</span><span>{{ money($booking->amount_paid) }}</span></div>
                @if ($booking->balanceDue() > 0)<div class="flex justify-between font-semibold"><span>{{ __('booking.manage.balance') }}</span><span>{{ money($booking->balanceDue()) }}</span></div>@endif
            @elseif ((float) $booking->amount_due_now < (float) $booking->total)
                <div class="mt-2 flex justify-between font-semibold"><span>{{ __('booking.due_now') }}</span><span>{{ money($booking->amount_due_now) }}</span></div>
            @endif
        </div>
    </div>
</div>
