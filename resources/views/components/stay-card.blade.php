@props(['stay', 'index' => 0])
<article class="group reveal">
    <a href="{{ lroute('stays.show', ['accommodation' => $stay->slug]) }}" class="block">
        <div class="arch arch-outline relative aspect-[4/5] bg-sand-200">
            <img src="{{ $stay->coverUrl() }}" alt="{{ $stay->name }}" loading="lazy"
                 class="arch absolute inset-0 size-full object-cover transition duration-[1200ms] ease-[var(--ease-soft)] group-hover:scale-[1.04]">
            <span class="absolute bottom-4 start-4 chip !border-transparent !bg-sand-50/90 backdrop-blur">
                <x-icon name="users" class="size-3.5"/> {{ __('site.up_to_guests', ['n' => $stay->max_guests]) }}
            </span>
        </div>
        <div class="mt-6 flex items-start justify-between gap-6">
            <div>
                <h3 class="t-h3">{{ $stay->name }}</h3>
                <x-rating-badge :rating="$stay->rating()" class="mt-1.5"/>
                <p class="mt-1.5 text-ink-600">{{ $stay->tagline }}</p>
            </div>
            <p class="shrink-0 text-end">
                <span class="block text-xs text-ink-400">{{ __('site.from') }}</span>
                <span class="font-display text-2xl">{{ money($stay->fromPrice()) }}</span>
                <span class="block text-xs text-ink-400">{{ __('site.per_night') }}</span>
            </p>
        </div>
        <ul class="mt-4 flex flex-wrap gap-2">
            @foreach (array_slice($stay->features ?? [], 0, 3) as $f)
                <li class="chip"><x-icon :name="$f" class="size-3.5"/> {{ __('site.features.'.$f) }}</li>
            @endforeach
        </ul>
    </a>
</article>
