<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...BookingResource::workflowActions(),
            Actions\Action::make('guestLink')->label(__('Guest link'))->icon('heroicon-o-link')->color('gray')
                ->url(fn () => $this->record->manageUrl(), true),
            Actions\Action::make('pdf')->label(__('Reservation PDF'))->icon('heroicon-o-document-arrow-down')->color('gray')
                ->url(fn () => route('booking.manage.receipt', ['locale' => $this->record->locale ?: 'en', 'booking' => $this->record->reference, 'token' => $this->record->manage_token])),
            Actions\EditAction::make()->label(__('Notes')),
        ];
    }

    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return parent::resolveRecord($key)->load('guest', 'units.unit', 'units.accommodation', 'extras');
    }
}
