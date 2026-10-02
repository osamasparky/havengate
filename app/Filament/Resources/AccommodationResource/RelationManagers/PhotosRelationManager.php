<?php

namespace App\Filament\Resources\AccommodationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    use \App\Filament\Concerns\TranslatesRelationLabels;

    protected static ?string $modelLabel = 'photo';

    use Translatable;

    protected static string $relationship = 'photos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')->label(__('Photo'))->image()->imageEditor()->directory('stays')->required()->maxSize(6144)->columnSpanFull(),
            Forms\Components\TextInput::make('caption'),
            Forms\Components\TextInput::make('alt')->label(__('Alt text'))->helperText(__('Describe the photo for screen readers.')),
            Forms\Components\Hidden::make('category')->default('stay'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('path')->label('')->height(64)->state(fn ($record) => $record->url()),
                Tables\Columns\TextColumn::make('caption'),
            ])
            ->headerActions([Tables\Actions\LocaleSwitcher::make(), Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
