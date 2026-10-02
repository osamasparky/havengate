@extends('layouts.site')
@section('title', __('booking.manage.title'))

@section('content')
<section class="py-20 md:py-28">
    <div class="container-hg grid items-center gap-16 lg:grid-cols-2">
        <div>
            <p class="eyebrow">{{ __('site.nav.manage') }}</p>
            <h1 class="t-h1 mt-4">{{ __('booking.manage.title') }}</h1>
            <p class="lede mt-5">{{ __('booking.manage.intro') }}</p>
            <form method="POST" action="{{ lroute('booking.manage.lookup') }}" class="mt-10 max-w-md space-y-5">
                @csrf
                <div>
                    <label for="m-ref" class="field-label">{{ __('booking.reference') }}</label>
                    <input id="m-ref" name="reference" value="{{ old('reference') }}" placeholder="HG-XXXXXX" class="field font-mono uppercase tracking-wider" dir="ltr" required autocomplete="off">
                    @error('reference')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="m-email" class="field-label">{{ __('booking.email') }}</label>
                    <input id="m-email" name="email" type="email" value="{{ old('email') }}" class="field" dir="ltr" required autocomplete="email">
                </div>
                <button class="btn btn-primary">{{ __('booking.manage.find') }} <x-icon name="arrow" class="size-4"/></button>
            </form>
        </div>
        <div class="arch-outline mx-auto hidden w-full max-w-md lg:block">
            <div class="arch aspect-[4/5] bg-sand-200"><img src="{{ asset('images/scenes/arch-window.svg') }}" alt="" class="size-full object-cover"></div>
        </div>
    </div>
</section>
@endsection
