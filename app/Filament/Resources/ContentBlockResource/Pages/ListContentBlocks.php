<?php

namespace App\Filament\Resources\ContentBlockResource\Pages;

use App\Filament\Resources\ContentBlockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContentBlocks extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = ContentBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }
}
