<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label(__('New booking'))];
    }

    public function getTabs(): array
    {
        return [
            'upcoming' => Tab::make(__('Upcoming'))->modifyQueryUsing(fn (Builder $query) => $query
                ->whereDate('check_out', '>=', today())
                ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::CheckedIn, BookingStatus::Pending])),
            'arrivals' => Tab::make(__('Arriving today'))->modifyQueryUsing(fn (Builder $query) => $query->arrivingOn(today())),
            'departures' => Tab::make(__('Leaving today'))->modifyQueryUsing(fn (Builder $query) => $query->departingOn(today())),
            'in_house' => Tab::make(__('In house'))->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::CheckedIn)),
            'pending' => Tab::make(__('Awaiting payment'))->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::Pending)),
            'all' => Tab::make(__('All')),
        ];
    }
}
