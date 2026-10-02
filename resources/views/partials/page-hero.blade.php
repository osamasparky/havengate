{{-- Inner-page hero: eyebrow + title + intro on sand, with a quiet arch line. --}}
<section class="relative overflow-hidden pt-16 pb-16 md:pt-24 md:pb-20">
    <svg class="pointer-events-none absolute -top-24 end-[-6rem] h-[520px] w-auto text-copper-500/15" viewBox="0 0 120 140" fill="none" aria-hidden="true">
        <path d="M22 140V62a38 38 0 0 1 76 0v78" stroke="currentColor" stroke-width="0.6"/>
        <path d="M30 140V64a30 30 0 0 1 60 0v76" stroke="currentColor" stroke-width="0.4"/>
    </svg>
    <div class="container-hg relative">
        @isset($eyebrow)<p class="eyebrow reveal">{{ $eyebrow }}</p>@endisset
        <h1 class="t-h1 mt-4 max-w-4xl reveal">{!! hg_emphasis($title) !!}</h1>
        @isset($intro)<p class="lede mt-6 reveal">{{ $intro }}</p>@endisset
    </div>
</section>
