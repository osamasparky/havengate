@props(['mark' => false, 'class' => ''])
{{-- Heaven Gate mark: the gate (arch), the star, the mountains, the sea. Redrawn from the Instagram logo. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 '.$class]) }}>
    <svg viewBox="0 0 120 140" class="h-full w-auto shrink-0" aria-hidden="true" fill="none">
        <defs>
            <linearGradient id="hg-copper" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#E9CBA3"/><stop offset=".5" stop-color="#C08F5E"/><stop offset="1" stop-color="#8F6038"/>
            </linearGradient>
        </defs>
        <g stroke="url(#hg-copper)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 128V62a38 38 0 0 1 76 0v66"/>
            <path d="M30 128V64a30 30 0 0 1 60 0v64" stroke-width="1.6" opacity=".7"/>
            <path d="M12 128h96"/>
            <path d="M18 128v6M102 128v6" stroke-width="2.4"/>
            <path d="M32 100l17-19 9 9 14-17 16 27"/>
            <path d="M40 108h40M46 114h28M52 120h16" stroke-width="2.2"/>
            <path d="M58 102l4 4-4 4 4 4-4 4" stroke-width="1.8"/>
        </g>
        <path d="M60 30l3 9 9 3-9 3-3 9-3-9-9-3 9-3z" fill="url(#hg-copper)"/>
    </svg>
    @unless ($mark)
        <span class="flex flex-col leading-none">
            <span class="font-display text-[1.45em] tracking-wide">Heaven Gate</span>
            <span class="mt-1 text-[0.55em] font-semibold uppercase tracking-[0.32em] opacity-70">Camp · Nuweiba</span>
        </span>
    @endunless
</span>
