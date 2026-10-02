@extends('layouts.site')
@section('header_theme', 'night')
@section('description', strip_tags(optional($blocks->get('home.hero'))->body))

@php
    $hero = $blocks->get('home.hero');
    $story = $blocks->get('home.story');
    $night = $blocks->get('home.night');
    $wellness = $blocks->get('home.wellness');
    $ctaUrl = fn ($u) => $u && str_starts_with($u, '/') ? url(app()->getLocale().$u) : ($u ?: lroute('book'));
@endphp

@section('content')
{{-- 1 · HERO — night video, the gate opens ---------------------------------- --}}
@php
    // Admin upload (Page sections → home.hero → video) wins; otherwise the bundled night loop.
    $heroVideo = media_url($hero?->video) ?? (is_file(public_path('videos/hero.mp4')) ? asset('videos/hero.mp4') : null);
    $heroPoster = $hero?->video && $hero?->image ? media_url($hero->image) : (is_file(public_path('videos/hero-poster.jpg')) ? asset('videos/hero-poster.jpg') : (media_url($hero?->image) ?? asset('images/scenes/milky-way.svg')));
@endphp
<section class="night on-night grain relative isolate z-10 flex min-h-[100svh] flex-col overflow-x-clip pt-28 pb-8 lg:pt-36" data-hero>
    {{-- Background: poster paints instantly, the video fades in once it actually plays. --}}
    <div class="absolute inset-0 -z-20 overflow-hidden" aria-hidden="true">
        <img src="{{ $heroPoster }}" alt="" class="absolute inset-0 size-full object-cover" fetchpriority="high" data-hero-media>
        @if ($heroVideo)
            <video data-hero-video data-hero-media class="absolute inset-0 size-full object-cover opacity-0 transition-opacity duration-[1400ms]"
                   muted loop playsinline preload="none" poster="{{ $heroPoster }}" disablepictureinpicture>
                <source data-src="{{ $heroVideo }}" type="{{ str_ends_with(strtolower((string) parse_url($heroVideo, PHP_URL_PATH)), '.webm') ? 'video/webm' : 'video/mp4' }}">
            </video>
        @endif
    </div>
    {{-- Legibility: darker at the top (header), the reading side and the bottom (search bar). --}}
    <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-night-950/75 via-night-950/10 to-night-950/95"></div>
    <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-r from-night-950/85 via-night-950/35 to-transparent rtl:bg-gradient-to-l"></div>
    <span data-twinkle class="absolute top-[22%] start-[6%] text-copper-300/70" aria-hidden="true">✦</span>
    <span data-twinkle class="absolute top-[58%] start-[44%] text-xs text-copper-300/60" aria-hidden="true">✦</span>

    <div class="container-hg grid flex-1 items-center gap-12 lg:grid-cols-[1.15fr_1fr] lg:gap-16">
        <div class="min-w-0 max-w-2xl py-6">
            @if ($promotion)
                <a href="{{ lroute('book') }}" data-hero-line class="mb-8 inline-flex max-w-full items-center gap-3 rounded-full border border-copper-300/30 bg-night-900/60 py-1.5 ps-1.5 pe-4 text-sm text-sand-200 backdrop-blur-md transition hover:border-copper-300/60">
                    <span class="shrink-0 rounded-full bg-copper-500 px-2.5 py-0.5 text-xs font-semibold text-night-900">{{ $promotion->name }}</span>
                    <span class="truncate">{{ $promotion->description }}</span>
                    @if ($promotion->code)<span class="shrink-0 font-semibold text-copper-300">{{ __('site.home.promo_code', ['code' => $promotion->code]) }}</span>@endif
                </a>
            @endif
            <p class="eyebrow" data-hero-line>{{ $hero?->eyebrow ?? __('site.place') }}</p>
            <h1 class="t-display mt-6 text-sand-50 [text-shadow:0_2px_30px_rgb(7_13_24/.55)]" data-hero-line>{!! hg_emphasis($hero?->title) !!}</h1>
            <p class="lede mt-7 !text-sand-100/85" data-hero-line>{{ $hero?->body }}</p>
            <div class="mt-10 flex flex-wrap items-center gap-4" data-hero-line>
                <a href="{{ lroute('book') }}" class="btn btn-primary">{{ $hero?->cta_label ?? __('site.nav.book') }} <x-icon name="arrow" class="size-4 rtl:-scale-x-100"/></a>
                <a href="{{ lroute('stays.index') }}" class="btn btn-ghost backdrop-blur-sm">{{ __('site.home.hero_secondary') }}</a>
            </div>
            <ul class="mt-10 flex flex-wrap gap-x-6 gap-y-3 text-sm text-sand-200/80" data-hero-line>
                @foreach (['waves' => 'hero_fact_beach', 'moon' => 'hero_fact_sky', 'coffee' => 'hero_fact_hospitality'] as $icon => $key)
                    <li class="inline-flex items-center gap-2"><x-icon :name="$icon" class="size-4 text-copper-300"/> {{ __('site.home.'.$key) }}</li>
                @endforeach
            </ul>
        </div>

        {{-- The gate: an open arch you look through to the night beyond. --}}
        <div class="relative mx-auto hidden w-full max-w-[420px] lg:block" aria-hidden="true">
            <div data-gate class="arch relative aspect-[4/5] overflow-hidden border border-copper-300/45 bg-white/[0.03] shadow-[inset_0_0_80px_rgb(184_135_90/.12)] backdrop-blur-[1.5px]">
                <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-night-950/50 to-transparent"></div>
            </div>
            <p class="mt-5 text-end font-display text-xl italic text-copper-300/85">{{ __('site.home.hero_whisper') }}</p>
        </div>
    </div>

    <div class="container-hg relative z-20 mt-8">
        <x-search-bar dark class="mx-auto max-w-4xl shadow-[0_30px_80px_-20px_rgb(0_0_0/.6)]"/>
        <div class="mt-6 flex items-center justify-between gap-4 text-xs text-sand-200/60">
            <a href="#story" class="group inline-flex items-center gap-2 transition hover:text-sand-50">
                <span class="grid size-8 place-items-center rounded-full border border-sand-200/25 transition group-hover:border-copper-300"><x-icon name="chevron-down" class="size-4 motion-safe:animate-bounce"/></span>
                {{ __('site.home.scroll') }}
            </a>
            @if ($heroVideo)
                <button type="button" data-hero-video-toggle aria-pressed="false" hidden
                        data-label-pause="{{ __('site.home.video_pause') }}" data-label-play="{{ __('site.home.video_play') }}"
                        class="inline-flex items-center gap-2 rounded-full border border-sand-200/25 px-3 py-1.5 transition hover:border-copper-300 hover:text-sand-50">
                    <span data-icon-pause><x-icon name="pause" class="size-3.5"/></span>
                    <span data-icon-play hidden><x-icon name="play" class="size-3.5"/></span>
                    <span data-label>{{ __('site.home.video_pause') }}</span>
                </button>
            @endif
        </div>
    </div>
</section>

{{-- 2 · STORY ------------------------------------------------------------------ --}}
<section id="story" class="scroll-mt-20 py-24 md:py-36">
    <div class="container-hg grid items-center gap-16 lg:grid-cols-2">
        <div class="relative order-2 grid grid-cols-2 items-end gap-5 lg:order-1">
            <div class="arch reveal aspect-[3/4] bg-sand-200"><img src="{{ asset('images/scenes/sunset-gulf.svg') }}" alt="" loading="lazy" class="size-full object-cover"></div>
            <div class="arch reveal mb-16 aspect-[3/5] bg-sand-200"><img src="{{ asset('images/scenes/reed-hut.svg') }}" alt="" loading="lazy" class="size-full object-cover"></div>
            <span class="absolute -top-6 start-1/2 -translate-x-1/2 text-2xl text-copper-500" data-twinkle>✦</span>
        </div>
        <div class="order-1 lg:order-2">
            <x-section-head :eyebrow="$story?->eyebrow" :title="$story?->title ?? ''" :body="$story?->body"/>
            <dl class="mt-12 grid grid-cols-3 gap-6 border-t border-sand-200 pt-8 reveal">
                <div><x-icon name="waves" class="size-6 text-copper-500"/><dt class="mt-3 text-sm text-ink-600">{{ __('site.features.beachfront') }}</dt></div>
                <div><x-icon name="plane" class="size-6 text-copper-500"/><dt class="mt-3 text-sm text-ink-600">{{ __('site.location.from_taba') }} · {{ __('site.location.approx', ['t' => '1 h']) }}</dt></div>
                <div><x-icon name="paw" class="size-6 text-copper-500"/><dt class="mt-3 text-sm text-ink-600">{{ __('site.features.pets') }}</dt></div>
            </dl>
        </div>
    </div>
</section>

{{-- 3 · STAYS ------------------------------------------------------------------ --}}
<section class="bg-sand-100 py-24 md:py-32">
    <div class="container-hg">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <x-section-head :eyebrow="__('site.home.stays_eyebrow')" :title="__('site.home.stays_title')" :body="__('site.home.stays_body')"/>
            <a href="{{ lroute('stays.index') }}" class="link reveal">{{ __('site.view_all') }} <x-icon name="arrow" class="size-4"/></a>
        </div>
        <div class="mt-16 grid gap-x-8 gap-y-16 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($stays as $i => $stay)
                <div @class(['lg:mt-16' => $i === 1])><x-stay-card :stay="$stay" :index="$i"/></div>
            @endforeach
        </div>
    </div>
</section>

{{-- 4 · NIGHT ----------------------------------------------------------------- --}}
<section class="night on-night relative isolate overflow-hidden">
    <img src="{{ media_url($night?->image) ?? asset('images/scenes/moon-path.svg') }}" alt="" loading="lazy" class="absolute inset-0 -z-10 size-full object-cover opacity-90">
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-night-900/30 via-night-900/20 to-night-900/90"></div>
    <div class="container-hg flex min-h-[80svh] flex-col justify-end py-24">
        <div class="max-w-2xl">
            <p class="eyebrow reveal">{{ $night?->eyebrow }}</p>
            <h2 class="t-h1 mt-5 reveal">{!! hg_emphasis($night?->title) !!}</h2>
            <p class="lede mt-6 reveal">{{ $night?->body }}</p>
            @if ($night?->cta_label)
                <a href="{{ $ctaUrl($night->cta_url) }}" class="btn btn-ghost mt-10 reveal">{{ $night->cta_label }} <x-icon name="arrow" class="size-4"/></a>
            @endif
        </div>
    </div>
</section>

{{-- 5 · EXPERIENCES ------------------------------------------------------------ --}}
<section class="py-24 md:py-32">
    <div class="container-hg">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <x-section-head :eyebrow="__('site.home.experiences_eyebrow')" :title="__('site.home.experiences_title')"/>
            <a href="{{ lroute('experiences.index') }}" class="link reveal">{{ __('site.view_all') }} <x-icon name="arrow" class="size-4"/></a>
        </div>
        <div class="mt-14 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($experiences as $exp)
                <x-experience-card :experience="$exp"/>
            @endforeach
        </div>
    </div>
</section>

{{-- 6 · WELLNESS --------------------------------------------------------------- --}}
@if ($wellness)
<section class="pb-24 md:pb-32">
    <div class="container-hg">
        <div class="grid items-center overflow-hidden rounded-[var(--radius-lg)] bg-night-900 text-sand-50 on-night lg:grid-cols-2">
            <div class="relative aspect-[4/3] lg:aspect-auto lg:h-full">
                <img src="{{ media_url($wellness->image) ?? asset('images/scenes/arch-window.svg') }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
            </div>
            <div class="p-10 md:p-16">
                <p class="eyebrow reveal">{{ $wellness->eyebrow }}</p>
                <h2 class="t-h2 mt-5 reveal">{!! hg_emphasis($wellness->title) !!}</h2>
                <p class="lede mt-5 reveal">{{ $wellness->body }}</p>
                @if ($wellness->cta_label)
                    <a href="{{ $ctaUrl($wellness->cta_url) }}" class="btn btn-primary mt-10 reveal">{{ $wellness->cta_label }}</a>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

{{-- 7 · FACILITIES -------------------------------------------------------------- --}}
<section class="border-y border-sand-200 bg-sand-100 py-24 md:py-28">
    <div class="container-hg">
        <x-section-head :eyebrow="__('site.home.facilities_eyebrow')" :title="__('site.home.facilities_title')" align="center"/>
        <ul class="mt-16 grid grid-cols-2 gap-px overflow-hidden rounded-[var(--radius-md)] border border-sand-200 bg-sand-200 md:grid-cols-4">
            @foreach ($facilities as $f)
                <li class="reveal bg-sand-50 p-7">
                    <x-icon :name="$f->icon" class="size-7 text-copper-500"/>
                    <p class="mt-5 font-display text-xl">{{ $f->name }}</p>
                    <p class="mt-1.5 text-sm text-ink-600">{{ $f->description }}</p>
                </li>
            @endforeach
        </ul>
        <p class="mt-10 text-center reveal"><a href="{{ lroute('camp') }}" class="link">{{ __('site.nav.camp') }} <x-icon name="arrow" class="size-4"/></a></p>
    </div>
</section>

{{-- 8 · GALLERY ------------------------------------------------------------------ --}}
@if ($photos->isNotEmpty())
<section class="py-24 md:py-32">
    <div class="container-hg">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <x-section-head :eyebrow="__('site.home.gallery_eyebrow')" :title="__('site.home.gallery_title')"/>
            <a href="{{ setting('instagram') }}" target="_blank" rel="noopener" class="btn btn-secondary reveal"><x-icon name="instagram" class="size-4"/> {{ __('site.home.gallery_cta') }}</a>
        </div>
        <div class="mt-14 grid auto-rows-[180px] grid-cols-2 gap-4 md:auto-rows-[220px] md:grid-cols-4">
            @foreach ($photos as $i => $photo)
                <figure @class([
                    'reveal group relative overflow-hidden bg-sand-200',
                    'arch row-span-2' => $i === 0,
                    'rounded-[var(--radius-md)]' => $i !== 0,
                    'md:col-span-2' => $i === 3,
                    'row-span-2' => $i === 4,
                ])>
                    <img src="{{ $photo->url() }}" alt="{{ $photo->alt ?? $photo->caption }}" loading="lazy" class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-105">
                    @if ($photo->caption)
                        <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-night-900/70 to-transparent p-4 pt-10 font-display text-lg italic text-sand-50 opacity-0 transition group-hover:opacity-100">{{ $photo->caption }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 9 · FAQ ------------------------------------------------------------------------ --}}
@if ($faqs->isNotEmpty())
<section class="pb-24 md:pb-32">
    <div class="container-hg grid gap-12 lg:grid-cols-[1fr_1.6fr]">
        <x-section-head :title="__('site.home.faq_title')"/>
        <div class="divide-y divide-sand-200 border-y border-sand-200" x-data="{ open: 0 }">
            @foreach ($faqs as $i => $faq)
                <div class="reveal">
                    <button type="button" class="flex w-full items-center justify-between gap-6 py-6 text-start" @click="open = open === {{ $i }} ? null : {{ $i }}" :aria-expanded="open === {{ $i }}">
                        <span class="font-display text-2xl">{{ $faq->question }}</span>
                        <x-icon name="plus" class="size-5 shrink-0 text-copper-500 transition" ::class="open === {{ $i }} && 'rotate-45'"/>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse x-cloak class="pb-6 pe-10 text-ink-600">{{ $faq->answer }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 10 · CTA ----------------------------------------------------------------------- --}}
<section class="night on-night relative overflow-hidden py-28 md:py-36">
    <div class="container-hg text-center">
        <x-logo mark class="mx-auto h-24 reveal"/>
        <h2 class="t-h1 mx-auto mt-8 max-w-3xl reveal">{{ __('site.home.cta_title') }}</h2>
        <p class="lede mx-auto mt-5 reveal">{{ __('site.home.cta_body') }}</p>
        <x-search-bar dark class="mx-auto mt-12 max-w-4xl text-start reveal"/>
    </div>
</section>
@endsection
