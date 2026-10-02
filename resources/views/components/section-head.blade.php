@props(['eyebrow' => null, 'title', 'body' => null, 'align' => 'start', 'as' => 'h2'])
<div {{ $attributes->merge(['class' => 'max-w-3xl '.($align === 'center' ? 'mx-auto text-center' : '')]) }}>
    @if ($eyebrow)
        <p class="eyebrow reveal">{{ $eyebrow }}</p>
    @endif
    <{{ $as }} class="t-h2 mt-4 reveal">{!! hg_emphasis($title) !!}</{{ $as }}>
    @if ($body)
        <p class="lede mt-5 reveal {{ $align === 'center' ? 'mx-auto' : '' }}">{{ $body }}</p>
    @endif
    {{ $slot }}
</div>
