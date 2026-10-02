@extends('layouts.site')
@section('title', __('site.gallery.title'))

@section('content')
@include('partials.page-hero', ['eyebrow' => '@heavengatecamp', 'title' => __('site.gallery.title'), 'intro' => __('site.gallery.intro')])

<section class="pb-28" x-data="{ filter: 'all', open: null, items: @js($photos->map(fn ($p) => ['src' => $p->url(), 'caption' => $p->caption, 'cat' => $p->category])->values()) }"
         @keydown.escape.window="open = null" @keydown.arrow-right.window="open !== null && (open = (open + 1) % items.length)" @keydown.arrow-left.window="open !== null && (open = (open - 1 + items.length) % items.length)">
    <div class="container-hg">
        <div class="flex flex-wrap gap-2" role="tablist">
            <button type="button" class="chip !px-4 !py-2 !text-sm" :class="filter === 'all' && '!bg-night-900 !text-sand-50 !border-night-900'" @click="filter = 'all'">{{ __('site.gallery.all') }}</button>
            @foreach ($categories as $c)
                <button type="button" class="chip !px-4 !py-2 !text-sm" :class="filter === '{{ $c }}' && '!bg-night-900 !text-sand-50 !border-night-900'" @click="filter = '{{ $c }}'">{{ __('site.gallery.categories.'.$c) }}</button>
            @endforeach
        </div>
        <div class="mt-10 columns-1 gap-4 sm:columns-2 lg:columns-3 [&>*]:mb-4">
            @foreach ($photos as $i => $photo)
                <button type="button" x-show="filter === 'all' || filter === '{{ $photo->category }}'" x-transition.opacity
                        @click="open = {{ $i }}" @class(['group relative block w-full overflow-hidden bg-sand-200 break-inside-avoid', 'arch' => $i % 5 === 0, 'rounded-[var(--radius-md)]' => $i % 5 !== 0])>
                    <img src="{{ $photo->url() }}" alt="{{ $photo->alt ?? $photo->caption }}" loading="lazy" class="w-full object-cover transition duration-700 group-hover:scale-105 {{ $i % 3 === 0 ? 'aspect-[3/4]' : 'aspect-[4/3]' }}">
                    @if ($photo->caption)<span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-night-900/70 p-4 pt-10 text-start font-display text-lg italic text-sand-50 opacity-0 transition group-hover:opacity-100">{{ $photo->caption }}</span>@endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Lightbox --}}
    <div x-show="open !== null" x-cloak x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center bg-night-950/95 p-4" role="dialog" aria-modal="true" @click.self="open = null">
        <button type="button" class="absolute end-5 top-5 grid size-11 place-items-center rounded-full text-sand-50 hover:bg-white/10" @click="open = null" aria-label="{{ __('site.gallery.close') }}"><x-icon name="x" class="size-6"/></button>
        <button type="button" class="absolute start-4 grid size-12 place-items-center rounded-full text-sand-50 hover:bg-white/10" @click="open = (open - 1 + items.length) % items.length" aria-label="{{ __('site.gallery.prev') }}"><x-icon name="chevron" class="size-6 rotate-180"/></button>
        <figure class="max-h-full max-w-5xl" x-show="open !== null">
            <img :src="open !== null ? items[open].src : ''" :alt="open !== null ? items[open].caption : ''" class="max-h-[80vh] w-auto rounded-[var(--radius-md)]">
            <figcaption class="mt-4 text-center font-display text-xl italic text-sand-200" x-text="open !== null ? items[open].caption : ''"></figcaption>
        </figure>
        <button type="button" class="absolute end-4 grid size-12 place-items-center rounded-full text-sand-50 hover:bg-white/10" @click="open = (open + 1) % items.length" aria-label="{{ __('site.gallery.next') }}"><x-icon name="chevron" class="size-6"/></button>
    </div>
</section>
@endsection
