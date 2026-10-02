<?php

namespace App\Filament\Resources\AccommodationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Physical rooms of a stay type — what actually gets assigned to bookings. */
class UnitsRelationManager extends RelationManager
{
    use \App\Filament\Concerns\TranslatesRelationLabels;

    protected static ?string $modelLabel = 'unit';

    protected static string $relationship = 'units';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Units (rooms)');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(20)->placeholder(__('C-09')),
            Forms\Components\TextInput::make('name')->maxLength(60)->placeholder(__('Optional nickname')),
            Forms\Components\TextInput::make('zone')->maxLength(60)->placeholder(__('Front row')),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Textarea::make('notes')->rows(2)->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->default(true)->helperText(__('Inactive units are never offered to guests.')),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('code')->weight('bold')->searchable(),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('zone'),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
