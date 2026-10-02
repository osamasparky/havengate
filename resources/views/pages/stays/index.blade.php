@extends('layouts.site')
@section('title', __('site.stays.title'))
@section('description', __('site.stays.intro'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('site.place'), 'title' => __('site.stays.title'), 'intro' => __('site.stays.intro')])

<section class="pb-28">
    <div class="container-hg space-y-24 md:space-y-32">
        @foreach ($stays as $i => $stay)
            <article class="grid items-center gap-10 lg:grid-cols-2 lg:gap-20">
                <a href="{{ lroute('stays.show', ['accommodation' => $stay->slug]) }}" @class(['group arch-outline reveal', 'lg:order-2' => $i % 2])>
                    <div class="arch relative aspect-[4/5] bg-sand-200 md:aspect-[5/5]">
                        <img src="{{ $stay->coverUrl() }}" alt="{{ $stay->name }}" loading="lazy" class="absolute inset-0 size-full object-cover transition duration-[1200ms] group-hover:scale-[1.04]">
                    </div>
                </a>
                <div>
                    <p class="eyebrow reveal">0{{ $i + 1 }}</p>
                    <h2 class="t-h2 mt-4 reveal">{{ $stay->name }}</h2>
                    <p class="mt-3 font-display text-xl italic text-copper-600 reveal">{{ $stay->tagline }}</p>
                    <p class="lede mt-6 reveal">{{ \Illuminate\Support\Str::limit(strtok((string) $stay->description, "\n"), 260) }}</p>
                    <dl class="mt-8 grid grid-cols-3 gap-4 border-y border-sand-200 py-6 text-sm reveal">
                        <div><dt class="text-ink-400">{{ __('site.stays.capacity') }}</dt><dd class="mt-1 font-semibold">{{ __('site.up_to_guests', ['n' => $stay->max_guests]) }}</dd></div>
                        <div><dt class="text-ink-400">{{ __('site.stays.beds') }}</dt><dd class="mt-1 font-semibold">{{ $stay->bed_configuration }}</dd></div>
                        @if ($stay->size_sqm)<div><dt class="text-ink-400">{{ __('site.stays.size') }}</dt><dd class="mt-1 font-semibold">{{ __('site.sqm', ['n' => $stay->size_sqm]) }}</dd></div>@endif
                    </dl>
                    <div class="mt-8 flex flex-wrap items-center justify-between gap-6 reveal">
                        <p><span class="text-sm text-ink-400">{{ __('site.from') }}</span> <span class="font-display text-3xl">{{ money($pricing->fromPrice($stay)) }}</span> <span class="text-sm text-ink-400">{{ __('site.per_night') }}</span></p>
                        <div class="flex gap-3">
                            <a href="{{ lroute('stays.show', ['accommodation' => $stay->slug]) }}" class="btn btn-secondary">{{ __('site.details') }}</a>
                            <a href="{{ lroute('book', ['stay' => $stay->slug]) }}" class="btn btn-primary">{{ __('site.check_availability') }}</a>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endsection
