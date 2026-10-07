@extends('layouts.site')
@section('title', $experience->name)
@section('description', $experience->summary)

@section('content')
<section class="pt-10 md:pt-16">
    <div class="container-hg">
        <nav class="text-sm text-ink-400" aria-label="Breadcrumb"><a href="{{ lroute('experiences.index') }}" class="hover:text-copper-600">{{ __('site.experiences.title') }}</a> <span class="mx-2">/</span> <span class="text-ink-600">{{ $experience->name }}</span></nav>
        <div class="mt-8 grid items-center gap-12 lg:grid-cols-2">
            <div>
                <h1 class="t-h1 reveal">{{ $experience->name }}</h1>
                @include('partials.rating-link')
                <p class="lede mt-6 reveal">{{ $experience->summary }}</p>
                <dl class="mt-10 grid grid-cols-3 gap-6 border-y border-sand-200 py-6 reveal">
                    @if ($experience->durationLabel())
                        <div><dt class="text-sm text-ink-400">{{ __('site.experiences.duration') }}</dt><dd class="mt-1 font-semibold">{{ $experience->durationLabel() }}</dd></div>
                    @endif
                    @if ($experience->schedule)
                        <div><dt class="text-sm text-ink-400">{{ __('site.experiences.when') }}</dt><dd class="mt-1 font-semibold">{{ $experience->schedule }}</dd></div>
                    @endif
                    <div><dt class="text-sm text-ink-400">{{ __('site.experiences.price') }}</dt><dd class="mt-1 font-semibold">{{ (float) $experience->price > 0 ? money($experience->price).' '.__('site.'.$experience->pricing_unit) : __('site.free') }}</dd></div>
                </dl>
                @if ($experience->is_addon)
                    <p class="mt-6 flex items-center gap-2 text-sm text-ink-600 reveal"><x-icon name="info" class="size-4 text-copper-500"/> {{ __('site.experiences.add_note') }}</p>
                @endif
                <a href="{{ lroute('book') }}" class="btn btn-primary mt-8 reveal">{{ __('site.nav.book') }} <x-icon name="arrow" class="size-4"/></a>
            </div>
            <div class="arch-outline reveal">
                <div class="arch aspect-[4/5] bg-sand-200"><img src="{{ $experience->imageUrl() }}" alt="{{ $experience->name }}" class="size-full object-cover"></div>
            </div>
        </div>
    </div>
</section>

@if ($experience->description && $experience->description !== $experience->summary)
<section class="py-20"><div class="container-hg prose-hg text-lg">{!! nl2br(e($experience->description)) !!}</div></section>
@endif

@include('partials.reviews-section')

@if ($others->isNotEmpty())
<section class="bg-sand-100 py-24">
    <div class="container-hg">
        <x-section-head :title="__('site.experiences.others')"/>
        <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($others as $o)<x-experience-card :experience="$o"/>@endforeach
        </div>
    </div>
</section>
@endif
@endsection
