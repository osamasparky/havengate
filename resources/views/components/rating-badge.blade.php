@props(['rating', 'dark' => false])
{{-- Compact "★ 4.8 (12)" for cards. $rating = ['average' => float, 'count' => int]. Hidden until a subject has approved reviews. --}}
@if ($rating['count'] > 0 && setting('reviews_enabled', true))
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-sm']) }}
          aria-label="{{ __('site.reviews.stars_label', ['n' => number_format($rating['average'], 1)]) }} · {{ trans_choice('site.reviews.count', $rating['count'], ['count' => $rating['count']]) }}">
        <svg viewBox="0 0 24 24" class="size-4 text-copper-500" aria-hidden="true"><path fill="currentColor" d="M12 2.8l2.83 5.73 6.32.92-4.57 4.46 1.08 6.3L12 17.24l-5.66 2.97 1.08-6.3L2.85 9.45l6.32-.92z"/></svg>
        <span class="font-semibold" aria-hidden="true">{{ number_format($rating['average'], 1) }}</span>
        <span class="{{ $dark ? 'text-sand-200/60' : 'text-ink-400' }}" aria-hidden="true">({{ $rating['count'] }})</span>
    </span>
@endif
