<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Models\BookingUnit;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class OccupancyChart extends ChartWidget
{
    public function getHeading(): ?string
    {
        return __('Occupancy — next 30 nights');
    }

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $from = CarbonImmutable::today();
        $to = $from->addDays(30);
        $total = max(1, Unit::where('is_active', true)->count());
        $rows = BookingUnit::where('is_active', true)
            ->whereDate('check_in', '<', $to->toDateString())->whereDate('check_out', '>', $from->toDateString())
            ->whereHas('booking', fn ($q) => $q->whereIn('status', [BookingStatus::Confirmed, BookingStatus::CheckedIn, BookingStatus::Pending]))
            ->get(['check_in', 'check_out']);

        $labels = $values = [];
        for ($d = $from; $d->lt($to); $d = $d->addDay()) {
            $labels[] = $d->format('D j');
            $taken = $rows->filter(fn ($r) => $r->check_in->lte($d) && $r->check_out->gt($d))->count();
            $values[] = round($taken / $total * 100);
        }

        return [
            'datasets' => [[
                'label' => __('Occupancy %'),
                'data' => $values,
                'borderColor' => '#b8875a',
                'backgroundColor' => 'rgba(184,135,90,0.15)',
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['min' => 0, 'max' => 100]], 'plugins' => ['legend' => ['display' => false]]];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
