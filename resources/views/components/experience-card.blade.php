@props(['experience', 'dark' => false])
<article class="group reveal">
    <a href="{{ lroute('experiences.show', ['experience' => $experience->slug]) }}" class="block">
        <div class="arch-soft relative aspect-[5/4] {{ $dark ? 'bg-night-800' : 'bg-sand-200' }}">
            <img src="{{ $experience->imageUrl() }}" alt="{{ $experience->name }}" loading="lazy"
                 class="absolute inset-0 size-full object-cover transition duration-[1200ms] group-hover:scale-[1.04]">
        </div>
        <div class="mt-5">
            <div class="flex flex-wrap items-center gap-2 text-sm {{ $dark ? 'text-sand-200/70' : 'text-ink-400' }}">
                @if ($experience->durationLabel())
                    <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="size-4"/> {{ $experience->durationLabel() }}</span>
                    <span aria-hidden="true">·</span>
                @endif
                <span>{{ (float) $experience->price > 0 ? money($experience->price).' '.__('site.'.$experience->pricing_unit) : __('site.free') }}</span>
                @if ($experience->rating()['count'] > 0 && setting('reviews_enabled', true))
                    <span aria-hidden="true">·</span>
                    <x-rating-badge :rating="$experience->rating()" :dark="$dark" class="{{ $dark ? 'text-sand-50' : 'text-ink-900' }}"/>
                @endif
            </div>
            <h3 class="t-h3 mt-2">{{ $experience->name }}</h3>
            <p class="mt-2 line-clamp-2 {{ $dark ? 'text-sand-200/75' : 'text-ink-600' }}">{{ $experience->summary }}</p>
        </div>
    </a>
</article>
