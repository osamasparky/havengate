@extends('layouts.site')
@section('title', __('booking.checkout.title'))

@section('content')
<section class="py-14 md:py-20">
    <div class="container-hg grid gap-10 lg:grid-cols-[1fr_380px] lg:gap-14">
        <div>
            <p class="eyebrow">{{ __('booking.steps.4') }}</p>
            <h1 class="t-h1 mt-4">{{ __('booking.checkout.title') }}</h1>

            {{-- Hold countdown --}}
            <div class="panel mt-8 flex items-center gap-4 p-5"
                 x-data="{ left: {{ (int) max(0, now()->diffInSeconds($booking->expires_at, false)) }}, get mm() { return String(Math.floor(this.left / 60)).padStart(2, '0') }, get ss() { return String(this.left % 60).padStart(2, '0') } }"
                 x-init="const t = setInterval(() => { left = Math.max(0, left - 1); if (!left) { clearInterval(t); window.location = '{{ lroute('book') }}' } }, 1000)">
                <x-icon name="clock" class="size-6 text-copper-500"/>
                <p class="text-ink-600">{{ __('booking.checkout.held_for') }} <span class="font-mono text-lg font-semibold text-ink-900" dir="ltr" x-text="mm + ':' + ss"></span></p>
            </div>

            <form method="POST" class="mt-10 space-y-5" x-data="{ method: '{{ $onlineEnabled ? 'online' : 'offline' }}', accepted: false }"
                  :action="method === 'online' ? '{{ lroute('booking.pay.online', ['booking' => $booking->reference]) }}' : '{{ lroute('booking.pay.offline', ['booking' => $booking->reference]) }}'">
                @csrf
                <input type="hidden" name="token" value="{{ $booking->manage_token }}">

                @if ($onlineEnabled)
                    <label class="panel flex cursor-pointer gap-5 p-6 transition" :class="method === 'online' && '!border-copper-500 ring-1 ring-copper-500'">
                        <input type="radio" name="method" value="online" x-model="method" class="mt-1 accent-copper-500">
                        <span class="flex-1">
                            <span class="flex flex-wrap items-center justify-between gap-3"><span class="font-display text-2xl">{{ __('booking.checkout.online') }}</span><span class="flex gap-1.5 text-[0.65rem] font-bold text-ink-400"><span class="chip">VISA</span><span class="chip">Mastercard</span><span class="chip">Meeza</span><span class="chip">Wallets</span><span class="chip">Fawry</span></span></span>
                            <span class="mt-2 block text-ink-600">{{ __('booking.checkout.online_desc') }}</span>
                        </span>
                    </label>
                @endif
                @if ($offlineEnabled)
                    <label class="panel flex cursor-pointer gap-5 p-6 transition" :class="method === 'offline' && '!border-copper-500 ring-1 ring-copper-500'">
                        <input type="radio" name="method" value="offline" x-model="method" class="mt-1 accent-copper-500">
                        <span class="flex-1">
                            <span class="font-display text-2xl">{{ __('booking.checkout.offline') }}</span>
                            <span class="mt-2 block text-ink-600">{{ __('booking.checkout.offline_desc') }}</span>
                        </span>
                    </label>
                @endif

                <label class="flex items-start gap-3 pt-4">
                    <input type="checkbox" name="accept_terms" value="1" x-model="accepted" class="checkbox mt-0.5" required>
                    <span class="text-sm text-ink-600">{!! __('booking.accept', [
                        'cancellation' => '<a href="'.lroute('page', ['page' => 'cancellation-policy']).'" target="_blank" class="link">'.e(__('booking.cancellation_policy')).'</a>',
                        'rules' => '<a href="'.lroute('page', ['page' => 'house-rules']).'" target="_blank" class="link">'.e(__('booking.house_rules')).'</a>',
                    ]) !!}</span>
                </label>
                @error('accept_terms')<p class="field-error">{{ $message }}</p>@enderror

                <button type="submit" class="btn btn-primary w-full md:w-auto" :disabled="!accepted">
                    <x-icon name="lock" class="size-4"/>
                    <span x-show="method === 'online'">{{ __('booking.checkout.online_btn', ['amount' => money((float) $booking->amount_due_now - (float) $booking->amount_paid)]) }}</span>
                    <span x-show="method === 'offline'" x-cloak>{{ __('booking.checkout.offline_btn') }}</span>
                </button>
                <p class="flex items-center gap-2 text-xs text-ink-400"><x-icon name="shield" class="size-4"/> {{ __('site.footer.secure') }}</p>
            </form>
        </div>
        <aside><div class="sticky top-28">@include('partials.booking-summary')</div></aside>
    </div>
</section>
@endsection
