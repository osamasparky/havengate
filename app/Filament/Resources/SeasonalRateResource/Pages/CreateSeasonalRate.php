<?php

namespace App\Filament\Resources\SeasonalRateResource\Pages;

use App\Filament\Resources\SeasonalRateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSeasonalRate extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = SeasonalRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
