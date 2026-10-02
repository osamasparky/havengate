<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingArrivals extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): ?string
    {
        return __('Next arrivals');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Booking::query()->with(['guest', 'units.unit', 'units.accommodation'])
                ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Pending])
                ->whereBetween('check_in', [today(), today()->addDays(7)])
                ->orderBy('check_in'))
            ->paginated([10])
            ->columns([
                Tables\Columns\TextColumn::make('check_in')->date('D j M'),
                Tables\Columns\TextColumn::make('guest.full_name')->label(__('Guest'))->description(fn (Booking $b) => $b->arrival_time ? 'ETA '.$b->arrival_time : null),
                Tables\Columns\TextColumn::make('unit_codes')->label(__('Unit'))->state(fn ($record) => $record->units->map(fn ($bu) => $bu->unit?->code)->filter()->all())->badge(),
                Tables\Columns\TextColumn::make('nights'),
                Tables\Columns\TextColumn::make('adults')->label(__('Guests'))->formatStateUsing(fn (Booking $b) => $b->adults.($b->children ? '+'.$b->children : '').($b->with_pet ? ' 🐾' : '')),
                Tables\Columns\TextColumn::make('balance')->state(fn (Booking $b) => $b->balanceDue())->money(config('heavengate.currency'))->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('special_requests')->limit(40)->placeholder(__('—'))->color('gray'),
            ])
            ->recordUrl(fn (Booking $b) => BookingResource::getUrl('view', ['record' => $b]));
    }
}
