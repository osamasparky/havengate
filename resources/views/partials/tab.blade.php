{{-- One bottom-tab link. Expects $tab = [route, icon, label, active]. --}}
<a href="{{ lroute($tab['route']) }}" @if ($tab['active']) aria-current="page" @endif
   @class(['relative flex size-full flex-col items-center justify-center gap-1 text-[0.68rem] font-medium transition active:scale-90',
           'text-copper-300' => $tab['active'], 'hover:text-sand-50' => ! $tab['active']])>
    @if ($tab['active'])<span class="absolute top-0 h-0.5 w-8 rounded-full bg-copper-300"></span>@endif
    <x-icon :name="$tab['icon']" class="size-[1.35rem]"/>
    {{ $tab['label'] }}
</a>
