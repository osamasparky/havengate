@php
    $stay = $this->selectedStay;
    $quote = $step >= 3 ? $this->quote : null;
    $nights = $checkIn && $checkOut ? \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut)) : 0;
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->translatedFormat('D j M Y') : '—';
    $countries = ['EG' => 'Egypt / مصر', 'IL' => 'Israel / ישראל', 'SA' => 'Saudi Arabia', 'JO' => 'Jordan', 'AE' => 'UAE', 'DE' => 'Germany', 'IT' => 'Italy', 'GB' => 'United Kingdom', 'FR' => 'France', 'RU' => 'Russia', 'US' => 'United States', 'NL' => 'Netherlands', 'PL' => 'Poland', 'UA' => 'Ukraine', 'XX' => '—'];
@endphp

<div class="pb-28">
    {{-- Progress --}}
    <div class="border-b border-sand-200 bg-sand-50">
        <div class="container-hg flex flex-wrap items-center justify-between gap-6 py-8">
            <h1 class="t-h2">{{ __('booking.title') }}</h1>
            <ol class="flex items-center gap-2 text-sm md:gap-4">
                @foreach (__('booking.steps') as $n => $label)
                    <li class="flex items-center gap-2 md:gap-4">
                        <button type="button" @if ($n < $step && $n < 4) wire:click="goTo({{ $n }})" @endif
                                @class(['flex items-center gap-2', 'cursor-default' => $n >= $step])>
                            <span @class([
                                'grid size-8 place-items-center rounded-full border text-xs font-semibold transition',
                                'border-copper-500 bg-copper-500 text-night-900' => $n === $step,
                                'border-copper-500 text-copper-600' => $n < $step,
                                'border-sand-300 text-ink-400' => $n > $step,
                            ])>@if ($n < $step)<x-icon name="check" class="size-4"/>@else{{ $n }}@endif</span>
                            <span @class(['hidden md:inline', 'font-semibold text-ink-900' => $n === $step, 'text-ink-400' => $n !== $step])>{{ $label }}</span>
                        </button>
                        @if (! $loop->last)<span class="h-px w-4 bg-sand-300 md:w-8"></span>@endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <div class="container-hg mt-12 grid gap-10 lg:grid-cols-[1fr_380px] lg:gap-14">
        <div class="min-w-0">
            @error('submit')<div class="panel mb-6 flex items-start gap-3 border-rock-600/30 bg-rock-600/5 p-4 text-rock-600" role="alert"><x-icon name="info" class="mt-0.5 size-5 shrink-0"/> {{ $message }}</div>@enderror
            @error('stay')<div class="panel mb-6 flex items-start gap-3 border-rock-600/30 bg-rock-600/5 p-4 text-rock-600" role="alert"><x-icon name="info" class="mt-0.5 size-5 shrink-0"/> {{ $message }}</div>@enderror

            {{-- STEP 1 · dates & guests ------------------------------------------------ --}}
            @if ($step === 1)
                <section class="space-y-8">
                    <div class="panel p-5 md:p-7" wire:ignore
                         x-data="rangePicker({ endpoint: '{{ $staySlug ? route('api.availability.accommodation', $staySlug) : route('api.availability.camp') }}', inline: true, checkIn: @js($checkIn), checkOut: @js($checkOut), locale: '{{ app()->getLocale() }}' })"
                         x-on:range-selected="$wire.checkIn = $event.detail.checkIn; $wire.checkOut = $event.detail.checkOut">
                        @include('partials.calendar')
                    </div>
                    @error('checkIn')<p class="field-error">{{ $message }}</p>@enderror
                    @error('checkOut')<p class="field-error">{{ $message }}</p>@enderror

                    <div class="panel grid gap-6 p-6 md:grid-cols-3 md:p-7">
                        @foreach (['adults' => [1, 12], 'children' => [0, 10]] as $field => [$min, $max])
                            <div>
                                <p class="field-label">{{ __('booking.'.$field) }}</p>
                                <div class="flex h-[52px] items-center justify-between rounded-[var(--radius-sm)] border border-sand-200 bg-white px-2">
                                    <button type="button" wire:click="$set('{{ $field }}', {{ max($min, $$field - 1) }})" class="grid size-9 place-items-center rounded-full hover:bg-sand-100 disabled:opacity-30" @disabled($$field <= $min) aria-label="−"><x-icon name="minus" class="size-4"/></button>
                                    <span class="font-semibold">{{ $$field }}</span>
                                    <button type="button" wire:click="$set('{{ $field }}', {{ min($max, $$field + 1) }})" class="grid size-9 place-items-center rounded-full hover:bg-sand-100 disabled:opacity-30" @disabled($$field >= $max) aria-label="+"><x-icon name="plus" class="size-4"/></button>
                                </div>
                                @if ($field === 'children')<p class="mt-1.5 text-xs text-ink-400">{{ __('site.search.children_hint', ['age' => setting('children_max_age', 11)]) }}</p>@endif
                            </div>
                        @endforeach
                        @if (setting('pets_allowed', true))
                            <label class="flex items-center gap-3 self-center md:pt-6">
                                <input type="checkbox" wire:model.live="withPet" class="checkbox">
                                <span class="inline-flex items-center gap-2"><x-icon name="paw" class="size-4 text-copper-500"/> {{ __('booking.with_pet') }}</span>
                            </label>
                        @endif
                    </div>

                    <button type="button" wire:click="searchStays" wire:loading.attr="disabled" class="btn btn-primary w-full md:w-auto">
                        <span wire:loading.remove wire:target="searchStays">{{ __('booking.search') }}</span>
                        <span wire:loading wire:target="searchStays">✦</span>
                        <x-icon name="arrow" class="size-4"/>
                    </button>
                </section>
            @endif

            {{-- STEP 2 · choose stay ------------------------------------------------------ --}}
            @if ($step === 2)
                <section class="space-y-6">
                    @php $results = $this->results; @endphp
                    @forelse ($results as $r)
                        @php $a = $r['accommodation']; $q = $r['quote']; @endphp
                        <article @class(['panel grid overflow-hidden md:grid-cols-[240px_1fr]', 'opacity-60' => ! $r['bookable']]) wire:key="res-{{ $a->id }}">
                            <div class="relative aspect-[16/10] bg-sand-200 md:aspect-auto">
                                <img src="{{ $a->coverUrl() }}" alt="{{ $a->name }}" class="absolute inset-0 size-full object-cover">
                            </div>
                            <div class="flex flex-col gap-5 p-6 md:p-7">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h2 class="t-h3">{{ $a->name }}</h2>
                                        <p class="mt-1 text-ink-600">{{ $a->tagline }}</p>
                                    </div>
                                    @if ($r['bookable'])
                                        <span @class(['chip', '!border-ember-500/40 !bg-ember-500/10 !text-ember-500' => $r['available'] - $r['rooms_needed'] <= 1])>{{ trans_choice('booking.available_count', $r['available'], ['count' => $r['available']]) }}</span>
                                    @endif
                                </div>
                                @if ($r['bookable'] && $r['rooms_needed'] > 1)
                                    <div class="flex items-start gap-3 rounded-[var(--radius-md)] border border-copper-300/50 bg-copper-500/5 p-4 text-sm">
                                        <x-icon name="users" class="mt-0.5 size-5 shrink-0 text-copper-600"/>
                                        <div>
                                            <p class="font-semibold text-ink-900">{{ __('booking.rooms_for_group', ['rooms' => trans_choice('booking.rooms_count', $r['rooms_needed'], ['count' => $r['rooms_needed']]), 'guests' => trans_choice('site.guests', $adults + $children, ['count' => $adults + $children])]) }}</p>
                                            <p class="mt-0.5 text-ink-600">{{ collect($q->roomParty)->map(fn ($p, $i) => __('booking.room_n', ['n' => $i + 1]).': '.party_label($p['adults'], $p['children']))->implode(' — ') }}</p>
                                        </div>
                                    </div>
                                @endif
                                <ul class="flex flex-wrap gap-2">
                                    <li class="chip"><x-icon name="users" class="size-3.5"/> {{ __('site.up_to_guests', ['n' => $a->max_guests]) }} {{ __('booking.per_room') }}</li>
                                    @foreach (array_slice($a->features ?? [], 0, 3) as $f)
                                        <li class="chip"><x-icon :name="$f" class="size-3.5"/> {{ __('site.features.'.$f) }}</li>
                                    @endforeach
                                </ul>
                                <div class="mt-auto flex flex-wrap items-end justify-between gap-4 border-t border-sand-200 pt-5">
                                    @if ($r['bookable'])
                                        <div>
                                            <p class="text-sm text-ink-400">{{ money($q->averageNightly()) }} {{ $q->rooms > 1 ? __('booking.avg_room_night') : __('booking.avg_night') }}</p>
                                            <p class="font-display text-3xl">{{ money($q->total) }}</p>
                                            <p class="text-xs text-ink-400">{{ __('booking.total_for', ['nights' => trans_choice('site.nights', $nights, ['count' => $nights])]) }}</p>
                                        </div>
                                        <button type="button" wire:click="chooseStay('{{ $a->slug }}')" class="btn btn-primary">{{ __('booking.choose') }} <x-icon name="arrow" class="size-4"/></button>
                                    @else
                                        <p class="text-sm font-semibold text-ink-600">
                                            @if (! $r['fits']) {{ __('booking.too_many_guests', ['n' => $adults + $children]) }}
                                            @elseif (! $r['min_nights_ok']) {{ __('booking.min_nights_required', ['n' => $a->min_nights]) }}
                                            @elseif ($r['available'] > 0) {{ __('booking.not_enough_rooms', ['needed' => trans_choice('booking.rooms_count', $r['rooms_needed'], ['count' => $r['rooms_needed']]), 'count' => $r['available']]) }}
                                            @else {{ __('booking.unavailable') }} @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="panel p-10 text-center"><p class="lede mx-auto">{{ __('booking.no_results') }}</p></div>
                    @endforelse
                    @if (collect($results)->where('bookable', true)->isEmpty() && count($results))
                        <div class="panel flex flex-wrap items-center justify-between gap-4 p-6">
                            <p class="text-ink-600">{{ __('booking.no_results') }}</p>
                            <a href="{{ lroute('contact') }}" class="btn btn-secondary btn-sm">{{ __('site.nav.contact') }}</a>
                        </div>
                    @endif
                </section>
            @endif

            {{-- STEP 3 · extras & details --------------------------------------------------- --}}
            @if ($step === 3 && $stay)
                <form wire:submit="submit" class="space-y-12">
                    @if ($this->addons->isNotEmpty())
                        <section>
                            <h2 class="t-h3">{{ __('booking.extras') }}</h2>
                            <p class="mt-1 text-ink-600">{{ __('booking.extras_hint') }}</p>
                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                @foreach ($this->addons as $exp)
                                    @php $on = isset($extras[$exp->id]); @endphp
                                    <div wire:key="ex-{{ $exp->id }}" @class(['panel flex gap-4 p-4 transition', '!border-copper-500 ring-1 ring-copper-500' => $on])>
                                        <img src="{{ $exp->imageUrl() }}" alt="" class="arch size-20 shrink-0 object-cover">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold">{{ $exp->name }}</p>
                                            <p class="text-sm text-ink-400">{{ money($exp->price) }} {{ __('site.'.$exp->pricing_unit) }}</p>
                                            <div class="mt-3 flex items-center gap-3">
                                                <button type="button" wire:click="toggleExtra({{ $exp->id }})" @class(['btn btn-sm !h-9 !px-4', 'btn-dark' => $on, 'btn-secondary' => ! $on])>
                                                    @if ($on)<x-icon name="check" class="size-4"/> {{ __('booking.added') }}@else<x-icon name="plus" class="size-4"/> {{ __('booking.add') }}@endif
                                                </button>
                                                @if ($on && $exp->isPerPerson())
                                                    <div class="flex items-center gap-2 text-sm">
                                                        <button type="button" wire:click="setExtraQty({{ $exp->id }}, {{ $extras[$exp->id] - 1 }})" class="grid size-8 place-items-center rounded-full border border-sand-200" aria-label="−"><x-icon name="minus" class="size-3.5"/></button>
                                                        <span class="w-4 text-center font-semibold">{{ $extras[$exp->id] }}</span>
                                                        <button type="button" wire:click="setExtraQty({{ $exp->id }}, {{ $extras[$exp->id] + 1 }})" class="grid size-8 place-items-center rounded-full border border-sand-200" aria-label="+"><x-icon name="plus" class="size-3.5"/></button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section>
                        <h2 class="t-h3">{{ __('booking.your_details') }}</h2>
                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            @foreach (['first_name' => ['text', 'given-name'], 'last_name' => ['text', 'family-name'], 'email' => ['email', 'email'], 'phone' => ['tel', 'tel']] as $f => [$type, $ac])
                                <div>
                                    <label for="g-{{ $f }}" class="field-label">{{ __('booking.'.$f) }}</label>
                                    <input id="g-{{ $f }}" type="{{ $type }}" wire:model.blur="guest.{{ $f }}" autocomplete="{{ $ac }}" class="field" @if (in_array($f, ['email', 'phone'])) dir="ltr" @endif required>
                                    @error('guest.'.$f)<p class="field-error">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                            <div>
                                <label for="g-country" class="field-label">{{ __('booking.country') }}</label>
                                <select id="g-country" wire:model="guest.country" class="field">
                                    <option value="">—</option>
                                    @foreach ($countries as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label for="g-arrival" class="field-label">{{ __('booking.arrival_time') }}</label>
                                <select id="g-arrival" wire:model="arrivalTime" class="field">
                                    <option value="">—</option>
                                    @foreach (['12:00–14:00', '14:00–16:00', '16:00–18:00', '18:00–20:00', '20:00–22:00', '22:00+'] as $slot)<option value="{{ $slot }}">{{ $slot }}</option>@endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label for="g-req" class="field-label">{{ __('booking.special_requests') }}</label>
                                <textarea id="g-req" wire:model.blur="specialRequests" rows="3" class="field" placeholder="{{ __('booking.special_requests_hint') }}"></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <label class="flex items-start gap-3">
                            <input type="checkbox" wire:model="guest.marketing_opt_in" class="checkbox mt-0.5">
                            <span class="text-sm text-ink-600">{{ __('booking.marketing') }}</span>
                        </label>
                        <label class="flex items-start gap-3">
                            <input type="checkbox" wire:model="acceptPolicies" class="checkbox mt-0.5">
                            <span class="text-sm text-ink-600">{!! __('booking.accept', [
                                'cancellation' => '<a href="'.lroute('page', ['page' => 'cancellation-policy']).'" target="_blank" class="link">'.e(__('booking.cancellation_policy')).'</a>',
                                'rules' => '<a href="'.lroute('page', ['page' => 'house-rules']).'" target="_blank" class="link">'.e(__('booking.house_rules')).'</a>',
                            ]) !!}</span>
                        </label>
                        @error('acceptPolicies')<p class="field-error">{{ $message }}</p>@enderror
                    </section>

                    <div class="flex flex-wrap items-center gap-4">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submit">
                            <span wire:loading.remove wire:target="submit">{{ __('booking.reserve') }}</span>
                            <span wire:loading wire:target="submit">{{ __('booking.reserving') }}</span>
                            <x-icon name="lock" class="size-4"/>
                        </button>
                        <p class="text-sm text-ink-400">{{ __('booking.secure_note', ['min' => config('heavengate.hold_minutes')]) }}</p>
                    </div>
                </form>
            @endif
        </div>

        {{-- Summary --------------------------------------------------------------------- --}}
        <aside>
            <div class="panel sticky top-28 overflow-hidden">
                @if ($stay)
                    <div class="relative h-40 bg-sand-200"><img src="{{ $stay->coverUrl() }}" alt="" class="size-full object-cover"><div class="absolute inset-0 bg-gradient-to-t from-night-900/60 to-transparent"></div><p class="absolute bottom-4 start-5 font-display text-2xl text-sand-50">{{ $stay->name }}</p></div>
                @endif
                <div class="space-y-5 p-6">
                    <p class="eyebrow">{{ __('booking.summary') }}</p>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-ink-400">{{ __('booking.check_in') }}</dt><dd class="mt-0.5 font-semibold">{{ $fmt($checkIn) }}</dd></div>
                        <div><dt class="text-ink-400">{{ __('booking.check_out') }}</dt><dd class="mt-0.5 font-semibold">{{ $fmt($checkOut) }}</dd></div>
                        <div><dt class="text-ink-400">{{ __('site.search.guests') }}</dt><dd class="mt-0.5 font-semibold">{{ trans_choice('site.guests', $adults + $children, ['count' => $adults + $children]) }}</dd></div>
                        <div><dt class="text-ink-400">{{ __('booking.nightly') }}</dt><dd class="mt-0.5 font-semibold">{{ $nights ? trans_choice('site.nights', $nights, ['count' => $nights]) : '—' }}</dd></div>
                    </dl>
                    @if ($step >= 2)
                        <button type="button" wire:click="goTo(1)" class="link text-sm">{{ __('booking.change') }}</button>
                    @endif

                    @if ($step === 3 && ($range = $this->roomRange))
                        <div class="border-t border-sand-200 pt-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold">{{ __('booking.rooms') }}</p>
                                    <p class="text-xs text-ink-400">{{ $range['min'] > 1 ? __('booking.rooms_min_hint', ['n' => $range['min']]) : __('booking.rooms_hint') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="setRooms({{ $rooms - 1 }})" @disabled($rooms <= $range['min']) class="grid size-9 place-items-center rounded-full border border-sand-200 hover:border-copper-500 disabled:opacity-30" aria-label="−"><x-icon name="minus" class="size-4"/></button>
                                    <span class="w-6 text-center font-semibold">{{ $rooms }}</span>
                                    <button type="button" wire:click="setRooms({{ $rooms + 1 }})" @disabled($rooms >= $range['max']) class="grid size-9 place-items-center rounded-full border border-sand-200 hover:border-copper-500 disabled:opacity-30" aria-label="+"><x-icon name="plus" class="size-4"/></button>
                                </div>
                            </div>
                            @if ($quote && $quote->rooms > 1)
                                <ul class="mt-3 space-y-1 text-xs text-ink-600">
                                    @foreach ($quote->roomParty as $i => $p)
                                        <li class="flex justify-between"><span>{{ __('booking.room_n', ['n' => $i + 1]) }}</span><span>{{ party_label($p['adults'], $p['children']) }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    @if ($quote)
                        <div class="border-t border-sand-200 pt-5 text-sm" x-data="{ nightsOpen: false }">
                            <div class="flex justify-between py-1.5">
                                <button type="button" class="inline-flex items-center gap-1 text-ink-600" @click="nightsOpen = !nightsOpen">{{ $quote->rooms > 1 ? __('booking.room').' × '.$quote->rooms : __('booking.room') }} <x-icon name="chevron-down" class="size-3.5 transition" ::class="nightsOpen && 'rotate-180'"/></button>
                                <span>{{ money($quote->roomSubtotal) }}</span>
                            </div>
                            <ul x-show="nightsOpen" x-collapse class="mb-2 space-y-1 border-s border-sand-200 ps-3 text-xs text-ink-400">
                                @foreach ($quote->nights as $n)
                                    <li class="flex justify-between"><span>{{ \Carbon\Carbon::parse($n['date'])->translatedFormat('D j M') }}@if ($n['label']) · {{ $n['label'] }}@endif</span><span>{{ money($n['rate']) }}</span></li>
                                @endforeach
                            </ul>
                            @if ($quote->extraGuestFees > 0)<div class="flex justify-between py-1.5 text-ink-600"><span>{{ __('booking.extra_guests') }}</span><span>{{ money($quote->extraGuestFees) }}</span></div>@endif
                            @if ($quote->petFee > 0)<div class="flex justify-between py-1.5 text-ink-600"><span>{{ __('booking.pet_fee') }}</span><span>{{ money($quote->petFee) }}</span></div>@endif
                            @foreach ($quote->extras as $x)
                                <div class="flex justify-between py-1.5 text-ink-600"><span>{{ $x['name'] }} × {{ $x['quantity'] }}</span><span>{{ money($x['total']) }}</span></div>
                            @endforeach
                            @if ($quote->discount > 0)<div class="flex justify-between py-1.5 text-sage-600"><span>{{ __('booking.discount') }} @if ($quote->promoCode)({{ $quote->promoCode }})@endif</span><span>− {{ money($quote->discount) }}</span></div>@endif
                            @if ($quote->serviceCharge > 0)<div class="flex justify-between py-1.5 text-ink-600"><span>{{ __('booking.service_charge') }}</span><span>{{ money($quote->serviceCharge) }}</span></div>@endif
                            @if ($quote->vat > 0)<div class="flex justify-between py-1.5 text-ink-600"><span>{{ __('booking.vat') }}</span><span>{{ money($quote->vat) }}</span></div>@endif
                            <div class="mt-3 flex items-end justify-between border-t border-sand-200 pt-4">
                                <span class="font-semibold">{{ __('booking.total') }}</span>
                                <span class="font-display text-3xl">{{ money($quote->total) }}</span>
                            </div>
                            @if ($quote->depositPercent < 100)
                                <p class="mt-2 text-xs text-ink-400">{{ __('booking.deposit_note', ['percent' => $quote->depositPercent]) }} {{ __('booking.due_now') }}: <strong>{{ money($quote->dueNow) }}</strong></p>
                            @endif
                        </div>

                        {{-- Promo --}}
                        <div class="border-t border-sand-200 pt-5">
                            @if ($promoCode)
                                <div class="flex items-center justify-between text-sm"><span class="inline-flex items-center gap-2 text-sage-600"><x-icon name="tag" class="size-4"/> {{ __('booking.promo_applied', ['code' => $promoCode]) }}</span><button type="button" wire:click="removePromo" class="link text-xs">{{ __('booking.remove') }}</button></div>
                            @else
                                <label for="promo" class="field-label">{{ __('booking.promo_code') }}</label>
                                <div class="flex gap-2">
                                    <input id="promo" wire:model="promoInput" wire:keydown.enter.prevent="applyPromo" class="field !h-11 uppercase" dir="ltr">
                                    <button type="button" wire:click="applyPromo" class="btn btn-secondary btn-sm !h-11">{{ __('booking.apply') }}</button>
                                </div>
                                @error('promoInput')<p class="field-error">{{ $message }}</p>@enderror
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>
