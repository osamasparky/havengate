<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getRedirectUrl(): string
    {
        return BookingResource::getUrl('view', ['record' => $this->record]);
    }

    protected function afterSave(): void
    {
        $this->record->log('updated', 'Notes / arrival details edited');
    }
}
