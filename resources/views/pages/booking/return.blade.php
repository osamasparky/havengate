@extends('layouts.site')
@section('title', __('booking.return.processing'))
@php
    $failed = in_array($gatewayStatus, ['FAILED', 'CANCELED', 'CANCELLED', 'EXPIRED', 'REFUSED']);
    $pendingCash = in_array($gatewayStatus, ['PENDING', 'NEW']) && $voucher;
@endphp

@section('content')
<section class="py-20 md:py-28">
    <div class="container-hg max-w-2xl text-center"
         x-data="{ tries: 0, slow: false }"
         x-init="@if (! $failed) const poll = async () => { tries++; try { const r = await fetch('{{ lroute('booking.status', ['booking' => $booking->reference, 'token' => $booking->manage_token]) }}', { headers: { Accept: 'application/json' } }); const j = await r.json(); if (j.redirect) { window.location = j.redirect; return; } } catch (e) {} if (tries > 20) slow = true; setTimeout(poll, tries < 10 ? 2000 : 5000); }; poll(); @endif">
        <x-logo mark class="mx-auto h-24"/>

        @if ($failed)
            <h1 class="t-h2 mt-10">{{ __('booking.return.failed') }}</h1>
            <p class="lede mx-auto mt-4">{{ __('booking.return.failed_body') }}</p>
            <a href="{{ lroute('booking.checkout', ['booking' => $booking->reference, 'token' => $booking->manage_token]) }}" class="btn btn-primary mt-10">{{ __('booking.return.try_again') }}</a>
        @elseif ($pendingCash)
            <h1 class="t-h2 mt-10">{{ __('booking.return.pending_cash') }}</h1>
            <p class="lede mx-auto mt-4">{{ __('booking.return.pending_cash_body') }}</p>
            <p class="panel mx-auto mt-8 inline-block px-10 py-6 font-mono text-4xl font-semibold tracking-[0.2em]" dir="ltr">{{ $voucher }}</p>
            <p class="mt-4 text-ink-600">{{ money($payment->amount) }}</p>
        @else
            <h1 class="t-h2 mt-10">{{ __('booking.return.processing') }}</h1>
            <p class="lede mx-auto mt-4">{{ __('booking.return.processing_body') }}</p>
            <div class="mx-auto mt-10 flex justify-center gap-3 text-2xl text-copper-500" aria-hidden="true"><span class="animate-pulse">✦</span><span class="animate-pulse [animation-delay:200ms]">✦</span><span class="animate-pulse [animation-delay:400ms]">✦</span></div>
            <p x-show="slow" x-cloak class="mt-8 text-ink-600">{{ __('booking.return.slow') }}</p>
        @endif
        <p class="mt-12 text-sm text-ink-400">{{ __('booking.reference') }}: <span class="font-mono font-semibold" dir="ltr">{{ $booking->reference }}</span></p>
    </div>
</section>
@endsection
