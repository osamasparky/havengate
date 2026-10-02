{{-- Two-month availability calendar. Requires an enclosing x-data="rangePicker(...)". --}}
<div class="select-none">
    <div class="mb-4 flex items-center justify-between">
        <button type="button" @click="shift(-1)" class="grid size-9 place-items-center rounded-full hover:bg-sand-100" aria-label="{{ __('site.gallery.prev') }}"><x-icon name="chevron" class="size-4 rotate-180"/></button>
        <p class="text-sm text-ink-600">
            <span x-show="!checkIn">{{ __('booking.select_dates') }}</span>
            <span x-show="checkIn && !checkOut" x-cloak>{{ __('booking.select_checkout') }}</span>
            <span x-show="checkIn && checkOut" x-cloak><span x-text="fmt(checkIn)"></span> → <span x-text="fmt(checkOut)"></span> · <span x-text="nights()"></span> {{ __('booking.nights_short') }}</span>
        </p>
        <button type="button" @click="shift(1)" class="grid size-9 place-items-center rounded-full hover:bg-sand-100" aria-label="{{ __('site.gallery.next') }}"><x-icon name="chevron" class="size-4"/></button>
    </div>
    <div class="grid gap-8 md:grid-cols-2">
        <template x-for="(m, mi) in months()" :key="mi">
            <div :class="mi === 1 && 'hidden md:block'">
                <p class="mb-3 text-center font-display text-xl" x-text="monthLabel(m)"></p>
                <div class="grid grid-cols-7 text-center text-[0.7rem] font-semibold text-ink-400">
                    <template x-for="w in weekdays()"><span class="py-1" x-text="w"></span></template>
                </div>
                <div class="grid grid-cols-7 gap-y-1">
                    <template x-for="(d, di) in cells(m)" :key="di">
                        <div class="aspect-square">
                            <template x-if="d">
                                <button type="button" @click="pick(d)" @mouseenter="hover = iso(d)"
                                        :disabled="isPast(d)"
                                        :aria-pressed="isStart(d) || isEnd(d)"
                                        class="relative flex size-full flex-col items-center justify-center text-sm transition"
                                        :class="{
                                            'text-ink-400/50 cursor-not-allowed': isPast(d),
                                            'text-ink-400 line-through decoration-ink-400/60': !isPast(d) && isFull(d) && !isEnd(d),
                                            'bg-copper-500 text-night-900 font-semibold rounded-full z-10': isStart(d) || isEnd(d),
                                            'bg-sand-200/70': inRange(d),
                                            'hover:ring-1 hover:ring-copper-500 rounded-full': !isPast(d) && !isStart(d) && !isEnd(d)
                                        }">
                                    <span x-text="d.getDate()"></span>
                                    <span class="text-[0.6rem] leading-none" :class="(isStart(d)||isEnd(d)) ? 'text-night-900/70' : 'text-copper-600'" x-text="priceLabel(d)"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
    <div class="mt-4 flex items-center justify-between border-t border-sand-200 pt-4 text-xs text-ink-400">
        <span class="inline-flex items-center gap-2"><span class="line-through">12</span> {{ __('booking.sold_out') }} · <span class="text-copper-600">3.8k</span> {{ __('booking.price_legend', ['cur' => __('site.currency.'.config('heavengate.currency'))]) }}</span>
        <button type="button" class="link text-xs" @click="clear()" x-show="checkIn">{{ __('booking.clear') }}</button>
    </div>
</div>
