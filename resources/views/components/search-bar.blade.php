@props(['dark' => false, 'checkIn' => null, 'checkOut' => null, 'adults' => 2, 'children' => 0])
{{-- Hero availability search: dates (live calendar) + guests → /book --}}
<form method="GET" action="{{ lroute('book') }}"
      x-data="{ adults: {{ (int) $adults }}, children: {{ (int) $children }}, guestsOpen: false, checkIn: @js($checkIn), checkOut: @js($checkOut) }"
      @range-selected.window="checkIn = $event.detail.checkIn; checkOut = $event.detail.checkOut"
      {{ $attributes->merge(['class' => 'relative grid gap-2 rounded-[var(--radius-lg)] p-2 md:grid-cols-[1.4fr_1fr_auto] '.($dark ? 'bg-night-900/70 ring-1 ring-copper-300/20 backdrop-blur-md' : 'bg-white shadow-[var(--shadow-lift)]')]) }}>
    <input type="hidden" name="checkin" :value="checkIn">
    <input type="hidden" name="checkout" :value="checkOut">
    <input type="hidden" name="adults" :value="adults">
    <input type="hidden" name="children" :value="children">

    {{-- Dates --}}
    <div x-data="rangePicker({ endpoint: '{{ route('api.availability.camp') }}', checkIn: @js($checkIn), checkOut: @js($checkOut), locale: '{{ app()->getLocale() }}' })" class="relative" @keydown.escape.window="open = false">
        <button type="button" @click="open = !open" class="flex h-16 w-full items-center gap-3 rounded-[var(--radius-md)] px-4 text-start transition {{ $dark ? 'hover:bg-white/5' : 'hover:bg-sand-100' }}">
            <x-icon name="calendar" class="size-5 shrink-0 {{ $dark ? 'text-copper-300' : 'text-copper-600' }}"/>
            <span class="min-w-0">
                <span class="block text-xs font-semibold {{ $dark ? 'text-sand-200/70' : 'text-ink-400' }}">{{ __('site.search.dates') }}</span>
                <span class="block truncate font-semibold" x-text="checkIn ? (fmt(checkIn) + (checkOut ? ' → ' + fmt(checkOut) : ' → …')) : @js(__('site.search.add_dates'))"></span>
            </span>
        </button>
        <div x-show="open" x-cloak x-transition.origin.top @click.outside="open = false"
             class="absolute start-0 top-[calc(100%+10px)] z-40 w-[min(92vw,680px)] rounded-[var(--radius-lg)] bg-white p-5 text-ink-900 shadow-[var(--shadow-lift)]">
            @include('partials.calendar')
        </div>
    </div>

    {{-- Guests --}}
    <div class="relative" @click.outside="guestsOpen = false">
        <button type="button" @click="guestsOpen = !guestsOpen" class="flex h-16 w-full items-center gap-3 rounded-[var(--radius-md)] px-4 text-start transition {{ $dark ? 'hover:bg-white/5' : 'hover:bg-sand-100' }}">
            <x-icon name="users" class="size-5 shrink-0 {{ $dark ? 'text-copper-300' : 'text-copper-600' }}"/>
            <span>
                <span class="block text-xs font-semibold {{ $dark ? 'text-sand-200/70' : 'text-ink-400' }}">{{ __('site.search.guests') }}</span>
                <span class="block font-semibold"><span x-text="adults"></span> {{ __('site.search.adults') }}<template x-if="children > 0"><span> · <span x-text="children"></span> {{ __('site.search.children') }}</span></template></span>
            </span>
        </button>
        <div x-show="guestsOpen" x-cloak x-transition.origin.top class="absolute start-0 top-[calc(100%+10px)] z-40 w-72 rounded-[var(--radius-lg)] bg-white p-5 text-ink-900 shadow-[var(--shadow-lift)]">
            @foreach (['adults' => [1, 12], 'children' => [0, 10]] as $field => [$min, $max])
                <div class="flex items-center justify-between py-2">
                    <div>
                        <p class="font-semibold">{{ __('site.search.'.$field) }}</p>
                        @if ($field === 'children')<p class="text-xs text-ink-400">{{ __('site.search.children_hint', ['age' => setting('children_max_age', 11)]) }}</p>@endif
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" class="grid size-9 place-items-center rounded-full border border-sand-200 hover:border-copper-500 disabled:opacity-30" :disabled="{{ $field }} <= {{ $min }}" @click="{{ $field }}--" aria-label="−"><x-icon name="minus" class="size-4"/></button>
                        <span class="w-5 text-center font-semibold" x-text="{{ $field }}"></span>
                        <button type="button" class="grid size-9 place-items-center rounded-full border border-sand-200 hover:border-copper-500 disabled:opacity-30" :disabled="{{ $field }} >= {{ $max }}" @click="{{ $field }}++" aria-label="+"><x-icon name="plus" class="size-4"/></button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <button type="submit" class="btn btn-primary h-16 px-8 md:h-auto">
        {{ __('site.search.submit') }} <x-icon name="arrow" class="size-4"/>
    </button>
</form>
