{{-- Reviews block on stay & experience pages. Expects $reviews, $rating (['average','count']), $form. --}}
@if ($form['enabled'])
<section id="reviews" class="scroll-mt-24 border-t border-sand-200 py-20 md:py-28">
    <div class="container-hg">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <x-section-head :eyebrow="__('site.reviews.eyebrow')" :title="__('site.reviews.section_title')">
                @if ($rating['count'] > 0)
                    <p class="mt-5 flex flex-wrap items-center gap-3 reveal">
                        <x-stars :rating="$rating['average']" class="size-5"/>
                        <span class="font-semibold">{{ number_format($rating['average'], 1) }}</span>
                        <span class="text-ink-600">· {{ trans_choice('site.reviews.count', $rating['count'], ['count' => $rating['count']]) }}</span>
                    </p>
                @endif
            </x-section-head>
            @if ($rating['count'] > $reviews->count())
                <a href="{{ lroute('reviews', $form['experience'] ? ['experience' => $form['experience']->slug] : ['stay' => $form['stay']?->slug]) }}" class="btn btn-secondary reveal">{{ __('site.reviews.read_all') }}</a>
            @endif
        </div>

        <div class="mt-12 grid gap-10 lg:grid-cols-[1.4fr_1fr] lg:gap-14">
            <div>
                @if ($reviews->isNotEmpty())
                    <div class="grid gap-5 md:grid-cols-2">
                        @foreach ($reviews as $review)
                            @include('partials.review-card', ['review' => $review, 'hideSubject' => true])
                        @endforeach
                    </div>
                @else
                    <div class="panel p-8 text-ink-600 reveal">{{ __('site.reviews.empty_subject') }}</div>
                @endif
            </div>
            <div class="lg:sticky lg:top-28 lg:self-start">@include('partials.review-form', ['form' => $form])</div>
        </div>
    </div>
</section>
@endif
