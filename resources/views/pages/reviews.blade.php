@extends('layouts.site')
@section('title', $subject ? __('site.reviews.title').' · '.$subject->name : __('site.reviews.title'))
@section('description', __('site.reviews.intro'))

@push('head')
    @if (! $subject && $summary['count'] > 0)
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Campground',
                'name' => 'Heaven Gate Camp',
                'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => $summary['average'], 'reviewCount' => $summary['count'], 'bestRating' => 5, 'worstRating' => 1],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
@endpush

@section('content')
@include('partials.page-hero', ['eyebrow' => $subject?->name ?? __('site.place'), 'title' => __('site.reviews.title'), 'intro' => __('site.reviews.intro')])

<section id="reviews" class="scroll-mt-24 pb-28">
    <div class="container-hg grid gap-14 lg:grid-cols-[1.5fr_1fr]">
        {{-- Published reviews --}}
        <div>
            @if ($subject)
                <p class="mb-6 flex flex-wrap items-center gap-3 text-sm">
                    <span class="chip">{{ $subject->name }}</span>
                    <a href="{{ lroute('reviews') }}" class="link">{{ __('site.reviews.all_reviews') }}</a>
                </p>
            @endif
            @if ($summary['count'] > 0)
                <div class="panel flex flex-wrap items-center gap-x-6 gap-y-3 p-6 reveal">
                    <span class="font-display text-5xl leading-none">{{ number_format($summary['average'], 1) }}</span>
                    <div>
                        <x-stars :rating="$summary['average']" class="size-5"/>
                        <p class="mt-1 text-sm text-ink-600">{{ trans_choice('site.reviews.count', $summary['count'], ['count' => $summary['count']]) }}</p>
                    </div>
                </div>
                <div class="mt-8 grid gap-5 md:grid-cols-2">
                    @foreach ($reviews as $review)
                        @include('partials.review-card', ['review' => $review, 'hideSubject' => (bool) $subject])
                    @endforeach
                </div>
                <div class="mt-10">{{ $reviews->links() }}</div>
            @else
                <div class="panel p-8 text-ink-600 reveal">{{ __('site.reviews.empty') }}</div>
            @endif
        </div>

        {{-- Write a review --}}
        <aside id="write" class="scroll-mt-24">
            <div class="lg:sticky lg:top-28">@include('partials.review-form', ['form' => $form])</div>
        </aside>
    </div>
</section>
@endsection
