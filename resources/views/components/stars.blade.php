@props(['rating' => 0, 'class' => 'size-4'])
{{-- Read-only star rating; supports halves for averages (4.5 → 4½). --}}
@php $r = max(0, min(5, round((float) $rating * 2) / 2)); @endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 text-copper-500']) }} role="img" aria-label="{{ __('site.reviews.stars_label', ['n' => rtrim(rtrim(number_format($r, 1), '0'), '.')]) }}">
    @for ($i = 1; $i <= 5; $i++)
        @php $fill = $r >= $i ? 'full' : ($r >= $i - 0.5 ? 'half' : 'none'); @endphp
        <svg viewBox="0 0 24 24" class="{{ $class }} rtl:-scale-x-100" aria-hidden="true">
            @if ($fill === 'half')
                <defs><linearGradient id="hg-half-{{ $i }}"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="currentColor" stop-opacity=".22"/></linearGradient></defs>
            @endif
            <path d="M12 2.8l2.83 5.73 6.32.92-4.57 4.46 1.08 6.3L12 17.24l-5.66 2.97 1.08-6.3L2.85 9.45l6.32-.92z"
                  fill="{{ $fill === 'full' ? 'currentColor' : ($fill === 'half' ? 'url(#hg-half-'.$i.')' : 'currentColor') }}"
                  @if ($fill === 'none') fill-opacity=".22" @endif/>
        </svg>
    @endfor
</span>
