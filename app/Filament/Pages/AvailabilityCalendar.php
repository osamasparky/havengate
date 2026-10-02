<?php

namespace App\Filament\Pages;

use App\Filament\Resources\BlockedDateResource;
use App\Filament\Resources\BookingResource;
use App\Models\Accommodation;
use App\Models\Unit;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;

/** Unit × night grid: who is where, what's free, what's blocked. */
class AvailabilityCalendar extends Page
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationGroup = 'Reservations';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.availability-calendar';

    public string $start;

    public int $days = 21;

    public ?int $accommodationId = null;

    public function mount(): void
    {
        $this->start = today()->toDateString();
    }

    public function shift(int $days): void
    {
        $this->start = CarbonImmutable::parse($this->start)->addDays($days)->toDateString();
    }

    public function goToday(): void
    {
        $this->start = today()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new')->label(__('New booking'))->url(BookingResource::getUrl('create')),
            Action::make('block')->label(__('Block dates'))->color('gray')->url(BlockedDateResource::getUrl('create')),
        ];
    }

    protected function getViewData(): array
    {
        $from = CarbonImmutable::parse($this->start);
        $dates = collect(range(0, $this->days - 1))->map(fn ($i) => $from->addDays($i));
        $grid = app(AvailabilityService::class)->grid($from, $this->days, $this->accommodationId);
        $units = Unit::with('accommodation')->where('is_active', true)
            ->when($this->accommodationId, fn ($q) => $q->where('accommodation_id', $this->accommodationId))
            ->orderBy('accommodation_id')->orderBy('sort_order')->get()->groupBy('accommodation_id');

        return [
            'dates' => $dates,
            'grid' => $grid,
            'unitGroups' => $units,
            'accommodations' => Accommodation::all()->mapWithKeys(fn ($a) => [$a->id => $a->name]),
            'weekendNights' => array_map('intval', (array) setting('weekend_nights', [4, 5])),
        ];
    }
}
