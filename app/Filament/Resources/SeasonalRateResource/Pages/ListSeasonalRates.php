<?php

namespace App\Filament\Resources\SeasonalRateResource\Pages;

use App\Filament\Resources\SeasonalRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeasonalRates extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = SeasonalRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
