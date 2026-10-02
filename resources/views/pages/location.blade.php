@extends('layouts.site')
@section('title', __('site.location.title'))
@php
    $intro = $blocks->get('location.intro');
    $lat = config('heavengate.coordinates.lat');
    $lng = config('heavengate.coordinates.lng');
    $maps = "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
    $dir = "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
    $drives = [
        ['from_taba', '≈ 1 h'], ['from_taba_airport', '≈ 1 h 15'], ['from_dahab', '≈ 1 h 15'], ['from_sharm', '≈ 2 h 15'], ['from_cairo', '≈ 6–7 h'],
    ];
@endphp
@section('description', \Illuminate\Support\Str::limit((string) $intro?->body, 155))

@section('content')
@include('partials.page-hero', ['eyebrow' => $intro?->eyebrow ?? __('site.location.title'), 'title' => $intro?->title ?? __('site.location.title')])

<section class="pb-28">
    <div class="container-hg grid gap-12 lg:grid-cols-[1.4fr_1fr]">
        <div class="arch-soft relative min-h-[460px] overflow-hidden bg-sand-200 reveal">
            <iframe title="Heaven Gate Camp map" class="absolute inset-0 size-full grayscale-[35%] sepia-[15%]" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps?q={{ $lat }},{{ $lng }}&z=12&output=embed&hl={{ app()->getLocale() }}"></iframe>
        </div>
        <div>
            <p class="lede reveal">{{ $intro?->body }}</p>
            <div class="mt-8 flex flex-wrap gap-3 reveal">
                <a href="{{ $dir }}" target="_blank" rel="noopener" class="btn btn-primary"><x-icon name="pin" class="size-4"/> {{ __('site.location.directions') }}</a>
                <a href="{{ $maps }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ __('site.location.open_maps') }}</a>
            </div>
            <div class="panel mt-10 p-6 reveal">
                <p class="eyebrow">{{ __('site.location.distances') }}</p>
                <dl class="mt-5 divide-y divide-sand-200">
                    @foreach ($drives as [$key, $time])
                        <div class="flex items-center justify-between py-3"><dt class="text-ink-600">{{ __('site.location.'.$key) }}</dt><dd class="font-semibold" dir="ltr">{{ $time }}</dd></div>
                    @endforeach
                </dl>
                <p class="mt-5 flex items-center gap-2 text-sm text-ink-400"><x-icon name="pin" class="size-4"/> {{ __('site.location.coordinates') }} <span dir="ltr">{{ $lat }}, {{ $lng }}</span></p>
            </div>
        </div>
    </div>
</section>
@endsection
