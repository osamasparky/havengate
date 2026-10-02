<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacilityResource\Pages;
use App\Models\Facility;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FacilityResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    use Translatable;

    protected static ?string $model = Facility::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 3;

    public const ICONS = ['waves', 'utensils', 'coffee', 'wifi', 'flame', 'sun', 'dice', 'car', 'plane', 'paw', 'bell', 'mask', 'mountain', 'moon', 'star', 'users', 'shield', 'home', 'shower', 'towel'];

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set, ?Facility $record) => $record ? null : $set('slug', Str::slug($state))),
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
            Forms\Components\Select::make('icon')->options(array_combine(self::ICONS, self::ICONS))->required(),
            Forms\Components\Select::make('category')->options(['camp' => __('Around the camp'), 'dining' => __('Food & drink'), 'beach' => __('Beach & sea'), 'service' => __('Services')])->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold')->description(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('category')->badge()->formatStateUsing(fn ($state) => __(ucfirst(str_replace('_', ' ', (string) $state)))),
                Tables\Columns\TextColumn::make('icon')->color('gray'),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->filters([Tables\Filters\SelectFilter::make('category')->options(['camp' => __('Camp'), 'dining' => __('Dining'), 'beach' => __('Beach'), 'service' => __('Service')])])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacilities::route('/'),
            'create' => Pages\CreateFacility::route('/create'),
            'edit' => Pages\EditFacility::route('/{record}/edit'),
        ];
    }
}
