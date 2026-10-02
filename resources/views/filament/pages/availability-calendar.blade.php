<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between">
        <div style="display:flex;gap:.5rem;align-items:center">
            <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-left" wire:click="shift(-{{ $days }})">{{ __('Prev') }}</x-filament::button>
            <x-filament::button color="gray" size="sm" wire:click="goToday">{{ __('Today') }}</x-filament::button>
            <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-right" icon-position="after" wire:click="shift({{ $days }})">{{ __('Next') }}</x-filament::button>
            <x-filament::input.wrapper style="margin-inline-start:.5rem">
                <x-filament::input type="date" wire:model.live="start"/>
            </x-filament::input.wrapper>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="accommodationId">
                    <option value="">{{ __('All stays') }}</option>
                    @foreach ($accommodations as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="days">
                    @foreach ([14, 21, 31] as $n)<option value="{{ $n }}">{{ __(':n nights', ['n' => $n]) }}</option>@endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </div>

    <div style="display:flex;gap:1rem;font-size:.75rem;align-items:center;flex-wrap:wrap">
        <span><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:3px;background:#4e7d5b;vertical-align:middle"></span> {{ __('Confirmed') }}</span>
        <span><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:3px;background:#3d7891;vertical-align:middle"></span> {{ __('In house') }}</span>
        <span><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:3px;background:#e0813a;vertical-align:middle"></span> {{ __('Awaiting payment') }}</span>
        <span><span class="blocked" style="display:inline-block;width:.8rem;height:.8rem;border-radius:3px;vertical-align:middle;background:repeating-linear-gradient(45deg, rgb(180 72 60 / .35) 0 3px, transparent 3px 6px)"></span> {{ __('Blocked') }}</span>
    </div>

    <x-filament::section>
        <div style="overflow:auto;max-height:70vh">
            <table class="hg-cal">
                <thead>
                    <tr>
                        <th class="unit">{{ __('Unit') }}</th>
                        @foreach ($dates as $d)
                            <th @class(['weekend' => in_array($d->dayOfWeek, $weekendNights), 'today' => $d->isToday()])>
                                <div style="opacity:.6;font-weight:500">{{ $d->translatedFormat('D') }}</div>{{ $d->format('j') }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($unitGroups as $accId => $units)
                        <tr><td class="unit" colspan="{{ $dates->count() + 1 }}" style="background:rgb(184 135 90 / .08);font-family:'Cormorant Garamond',serif;font-size:1rem">{{ $accommodations[$accId] ?? '' }}</td></tr>
                        @foreach ($units as $unit)
                            <tr>
                                <td class="unit">{{ $unit->code }}</td>
                                @php $skip = 0; @endphp
                                @foreach ($dates as $i => $d)
                                    @php
                                        if ($skip > 0) { $skip--; continue; }
                                        $cell = $grid[$unit->id][$d->toDateString()] ?? ['type' => 'free'];
                                        $span = 1;
                                        if ($cell['type'] === 'booking') {
                                            for ($j = $i + 1; $j < $dates->count(); $j++) {
                                                $next = $grid[$unit->id][$dates[$j]->toDateString()] ?? null;
                                                if (($next['booking_id'] ?? null) !== $cell['booking_id']) break;
                                                $span++;
                                            }
                                            $skip = $span - 1;
                                        }
                                    @endphp
                                    @if ($cell['type'] === 'booking')
                                        <td colspan="{{ $span }}" class="bk bk-{{ $cell['status'] }}" title="{{ $cell['reference'] }} · {{ $cell['guest'] }}"
                                            onclick="window.location='{{ \App\Filament\Resources\BookingResource::getUrl('view', ['record' => $cell['booking_id']]) }}'"
                                            style="border-radius:6px;padding-inline:.4rem;text-align:start">{{ $cell['guest'] }}</td>
                                    @elseif ($cell['type'] === 'blocked')
                                        <td class="blocked" title="{{ $cell['reason'] ?? __('Blocked') }}"></td>
                                    @else
                                        <td @class(['weekend' => in_array($d->dayOfWeek, $weekendNights), 'today' => $d->isToday()])></td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
