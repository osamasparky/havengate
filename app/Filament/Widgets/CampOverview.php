<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingUnit;
use App\Models\Payment;
use App\Models\Unit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $today = today();
        $units = Unit::where('is_active', true)->count();
        $occupied = BookingUnit::where('is_active', true)
            ->whereDate('check_in', '<=', $today)->whereDate('check_out', '>', $today)
            ->whereHas('booking', fn ($q) => $q->whereIn('status', [BookingStatus::Confirmed, BookingStatus::CheckedIn]))
            ->count();
        $arrivals = Booking::arrivingOn($today)->count();
        $departures = Booking::departingOn($today)->count();
        $revenue = (float) Payment::where('status', PaymentStatus::Paid)->where('paid_at', '>=', now()->startOfMonth())->sum('amount');
        $lastMonth = (float) Payment::where('status', PaymentStatus::Paid)->whereBetween('paid_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('amount');
        $pending = Booking::where('status', BookingStatus::Pending)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count();

        $trend = collect(range(13, 0))->map(fn ($d) => Booking::whereDate('created_at', today()->subDays($d))->whereNotIn('status', [BookingStatus::Expired])->count())->all();

        return [
            Stat::make(__('Occupancy tonight'), $units ? round($occupied / $units * 100).'%' : '—')
                ->description(__(':occupied of :units units', ['occupied' => $occupied, 'units' => $units]))->descriptionIcon('heroicon-m-moon')->color('primary'),
            Stat::make(__('Arriving today'), $arrivals)->description(__(':count leaving today', ['count' => $departures]))->descriptionIcon('heroicon-m-arrow-right-end-on-rectangle')
                ->url(\App\Filament\Resources\BookingResource::getUrl('index', ['activeTab' => 'arrivals'])),
            Stat::make(__('Revenue this month'), number_format($revenue).' '.config('heavengate.currency'))
                ->description($lastMonth ? __(':change% vs last month', ['change' => ($revenue >= $lastMonth ? '+' : '').round(($revenue - $lastMonth) / $lastMonth * 100)]) : __('Collected payments'))
                ->descriptionIcon($revenue >= $lastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenue >= $lastMonth ? 'success' : 'warning'),
            Stat::make(__('New bookings (14 days)'), array_sum($trend))->chart($trend)->color('info')
                ->description($pending ? __(':count awaiting payment', ['count' => $pending]) : __('All paid'))->descriptionIcon('heroicon-m-clock'),
        ];
    }
}
