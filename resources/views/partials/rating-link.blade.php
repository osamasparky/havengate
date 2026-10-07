{{-- "★★★★½ 4.6 · 12 reviews" under a detail-page title, jumping to #reviews. Expects $rating, $form. --}}
@if ($form['enabled'] && $rating['count'] > 0)
    <a href="#reviews" class="mt-4 inline-flex flex-wrap items-center gap-2 text-sm hover:text-copper-600 reveal">
        <x-stars :rating="$rating['average']"/>
        <span class="font-semibold">{{ number_format($rating['average'], 1) }}</span>
        <span class="text-ink-600 underline decoration-sand-300 underline-offset-4">{{ trans_choice('site.reviews.count', $rating['count'], ['count' => $rating['count']]) }}</span>
    </a>
@endif
