@extends('layouts.site')
@section('title', __('site.camp.title'))
@php $story = $blocks->get('home.story'); @endphp
@section('description', \Illuminate\Support\Str::limit((string) $story?->body, 155))

@section('content')
@include('partials.page-hero', ['eyebrow' => $story?->eyebrow, 'title' => $story?->title ?? __('site.camp.title'), 'intro' => $story?->body])

<section class="pb-8">
    <div class="container-hg grid gap-4 md:grid-cols-3">
        @foreach (['chalets-dusk', 'arch-window', 'sunset-gulf'] as $i => $img)
            <div @class(['reveal overflow-hidden bg-sand-200', 'arch aspect-[3/4]' => $i === 1, 'rounded-[var(--radius-md)] aspect-[3/4] md:mt-24' => $i !== 1])>
                <img src="{{ asset('images/scenes/'.$img.'.svg') }}" alt="" loading="lazy" class="size-full object-cover">
            </div>
        @endforeach
    </div>
</section>

<section class="py-24 md:py-32">
    <div class="container-hg">
        <x-section-head :eyebrow="__('site.home.facilities_eyebrow')" :title="__('site.camp.facilities')"/>
        <div class="mt-14 space-y-14">
            @foreach ($facilities as $category => $items)
                <div class="grid gap-8 border-t border-sand-200 pt-10 lg:grid-cols-[1fr_3fr]">
                    <h3 class="t-h3 reveal">{{ __('site.camp.categories.'.$category) }}</h3>
                    <ul class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($items as $f)
                            <li class="reveal">
                                <x-icon :name="$f->icon" class="size-7 text-copper-500"/>
                                <p class="mt-4 font-display text-xl">{{ $f->name }}</p>
                                <p class="mt-1.5 text-sm text-ink-600">{{ $f->description }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

@if ($faqs->isNotEmpty())
<section class="bg-sand-100 py-24 md:py-32">
    <div class="container-hg">
        <x-section-head :title="__('site.camp.faq')"/>
        <div class="mt-12 grid gap-12 lg:grid-cols-2">
            @foreach ($faqs as $topic => $items)
                <div>
                    <p class="eyebrow">{{ __('site.camp.topics.'.$topic) }}</p>
                    <dl class="mt-6 space-y-6">
                        @foreach ($items as $faq)
                            <div class="reveal"><dt class="font-display text-xl">{{ $faq->question }}</dt><dd class="mt-2 text-ink-600">{{ $faq->answer }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
