@extends('layouts.site')
@section('title', $stay->meta_title ?: $stay->name)
@section('description', $stay->meta_description ?: $stay->tagline)

@php
    $gallery = collect([$stay->coverUrl()])->merge($stay->photos->map->url())->unique()->values();
@endphp

@section('content')
<section class="pt-10 md:pt-16">
    <div class="container-hg">
        <nav class="text-sm text-ink-400" aria-label="Breadcrumb"><a href="{{ lroute('stays.index') }}" class="hover:text-copper-600">{{ __('site.stays.title') }}</a> <span class="mx-2">/</span> <span class="text-ink-600">{{ $stay->name }}</span></nav>
        <div class="mt-6 grid items-end gap-6 lg:grid-cols-[1.5fr_1fr]">
            <div>
                <h1 class="t-h1 reveal">{{ $stay->name }}</h1>
                <p class="mt-4 font-display text-2xl italic text-copper-600 reveal">{{ $stay->tagline }}</p>
            </div>
            <ul class="flex flex-wrap gap-2 lg:justify-end reveal">
                <li class="chip"><x-icon name="users" class="size-3.5"/> {{ __('site.up_to_guests', ['n' => $stay->max_guests]) }}</li>
                <li class="chip"><x-icon name="bed" class="size-3.5"/> {{ $stay->bed_configuration }}</li>
                @if ($stay->size_sqm)<li class="chip"><x-icon name="ruler" class="size-3.5"/> {{ __('site.sqm', ['n' => $stay->size_sqm]) }}</li>@endif
            </ul>
        </div>

        {{-- Gallery: arch lead + supporting frames --}}
        <div class="mt-10 grid gap-4 md:grid-cols-[1.2fr_1fr]" x-data="{ i: 0, imgs: @js($gallery) }">
            <div class="arch relative aspect-[4/5] bg-sand-200 md:aspect-auto md:h-[640px]">
                <template x-for="(src, k) in imgs" :key="k">
                    <img :src="src" alt="{{ $stay->name }}" x-show="i === k" x-transition.opacity.duration.500ms class="absolute inset-0 size-full object-cover">
                </template>
            </div>
            <div class="grid grid-cols-2 gap-4 md:grid-rows-2">
                @foreach ($gallery->slice(1)->take(4)->values() as $k => $src)
                    <button type="button" @click="i = {{ $k + 1 }}" class="relative overflow-hidden rounded-[var(--radius-md)] bg-sand-200" :class="i === {{ $k + 1 }} && 'ring-2 ring-copper-500'">
                        <img src="{{ $src }}" alt="" loading="lazy" class="size-full object-cover">
                    </button>
                @endforeach
                @if ($gallery->count() < 2)
                    <div class="col-span-2 row-span-2 rounded-[var(--radius-md)] bg-night-900 p-8 text-sand-50 on-night">
                        <x-logo mark class="h-16"/>
                        <p class="mt-6 font-display text-2xl">{{ $stay->tagline }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="py-20 md:py-28">
    <div class="container-hg grid gap-16 lg:grid-cols-[1.4fr_1fr]">
        <div>
            <div class="prose-hg text-lg">
                @foreach (preg_split('/\R{2,}/', (string) $stay->description) as $para)
                    <p class="reveal">{{ $para }}</p>
                @endforeach
            </div>

            @if ($stay->highlightList())
                <h2 class="t-h3 mt-14 reveal">{{ __('site.stays.highlights') }}</h2>
                <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($stay->highlightList() as $h)
                        <li class="flex items-start gap-3 reveal"><span class="mt-1 text-copper-500">✦</span> {{ $h }}</li>
                    @endforeach
                </ul>
            @endif

            <h2 class="t-h3 mt-14 reveal">{{ __('site.stays.facilities') }}</h2>
            <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                @foreach ($stay->features ?? [] as $f)
                    <li class="flex items-center gap-3 reveal"><x-icon :name="$f" class="size-5 text-copper-500"/> {{ __('site.features.'.$f) }}</li>
                @endforeach
                @foreach ($stay->facilities as $f)
                    <li class="flex items-center gap-3 reveal"><x-icon :name="$f->icon" class="size-5 text-copper-500"/> {{ $f->name }}</li>
                @endforeach
            </ul>

            <div class="mt-16 reveal">
                <h2 class="t-h3">{{ __('site.stays.availability') }}</h2>
                <p class="mt-2 text-ink-600">{{ __('site.stays.availability_hint') }}</p>
                <div class="panel mt-6 p-5 md:p-7"
                     x-data="rangePicker({ endpoint: '{{ route('api.availability.accommodation', $stay->slug) }}', inline: true, locale: '{{ app()->getLocale() }}' })"
                     @range-selected="window.location = '{{ lroute('book', ['stay' => $stay->slug]) }}&checkin=' + $event.detail.checkIn + '&checkout=' + $event.detail.checkOut">
                    @include('partials.calendar')
                </div>
            </div>
        </div>

        {{-- Sticky booking card --}}
        <aside>
            <div class="panel sticky top-28 p-7">
                <p class="text-sm text-ink-400">{{ __('site.from') }}</p>
                <p><span class="font-display text-4xl">{{ money($fromPrice) }}</span> <span class="text-ink-400">{{ __('site.per_night') }}</span></p>
                <div class="divider-star my-6 text-xs">✦</div>
                <ul class="space-y-3 text-sm text-ink-600">
                    <li class="flex items-center gap-3"><x-icon name="clock" class="size-4 text-copper-500"/> {{ __('site.stays.policies', ['in' => setting('check_in_time', '14:00'), 'out' => setting('check_out_time', '11:00')]) }}</li>
                    @if ($stay->min_nights > 1)<li class="flex items-center gap-3"><x-icon name="calendar" class="size-4 text-copper-500"/> {{ __('site.stays.min_nights', ['n' => $stay->min_nights]) }}</li>@endif
                    <li class="flex items-center gap-3"><x-icon name="shield" class="size-4 text-copper-500"/> {{ __('site.stays.free_cancel', ['n' => setting('free_cancellation_days', 7)]) }}</li>
                </ul>
                <a href="{{ lroute('book', ['stay' => $stay->slug]) }}" class="btn btn-primary mt-8 w-full">{{ __('site.book_this') }}</a>
                <p class="mt-4 text-center text-xs text-ink-400"><x-icon name="lock" class="inline size-3.5"/> {{ __('site.footer.secure') }}</p>
            </div>
        </aside>
    </div>
</section>

@if ($others->isNotEmpty())
<section class="bg-sand-100 py-24">
    <div class="container-hg">
        <x-section-head :title="__('site.stays.others')"/>
        <div class="mt-12 grid gap-10 md:grid-cols-2 lg:w-2/3">
            @foreach ($others as $o)<x-stay-card :stay="$o"/>@endforeach
        </div>
    </div>
</section>
@endif
@endsection
