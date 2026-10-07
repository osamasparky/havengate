{{-- One published review. Expects $review; $hideSubject drops the stay/experience chip on its own page. --}}
<article class="panel flex h-full flex-col p-6 md:p-7 reveal">
    <div class="flex items-center justify-between gap-3">
        <x-stars :rating="$review->rating"/>
        <time class="text-xs text-ink-600" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->translatedFormat('M Y') }}</time>
    </div>
    @if ($review->title)
        <h3 class="mt-4 font-display text-2xl leading-tight">{{ $review->title }}</h3>
    @endif
    <p class="mt-3 whitespace-pre-line text-ink-600" dir="auto">{{ $review->comment }}</p>
    @if ($review->reply)
        <div class="mt-5 border-s-2 border-copper-500/50 ps-4 text-sm">
            <p class="font-semibold text-copper-600">{{ __('site.reviews.reply_from') }}</p>
            <p class="mt-1 whitespace-pre-line text-ink-600" dir="auto">{{ $review->reply }}</p>
        </div>
    @endif
    <footer class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-6 text-sm">
        <span class="font-semibold">{{ $review->displayName() }}</span>
        @if ($review->country)<span class="text-ink-600">· {{ $review->country }}</span>@endif
        @if ($review->is_verified)
            <span class="inline-flex items-center gap-1 text-xs font-semibold text-sage-600"><x-icon name="check" class="size-3.5"/> {{ __('site.reviews.verified') }}</span>
        @endif
        @if (empty($hideSubject) && $review->subjectName())
            <span class="chip ms-auto">{{ $review->subjectName() }}</span>
        @endif
    </footer>
</article>
